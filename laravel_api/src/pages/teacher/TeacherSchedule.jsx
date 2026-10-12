import React, { useState, useEffect } from 'react'
import { FiCalendar, FiClock, FiVideo } from 'react-icons/fi'
import { getTeacherZoomClasses, teacherAttend } from '../../services/dashboardService'
import { useToast } from '../../components/shared/ToastContext'

export default function TeacherSchedule() {
    const toast = useToast()
    const [schedules, setSchedules] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                const res = await getTeacherZoomClasses().catch(() => null)
                const data = res?.data || (Array.isArray(res) ? res : [])
                data.sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at))
                setSchedules(data)
            } catch (e) {
                console.error('Error loading schedule:', e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    // Group by date
    const grouped = schedules.reduce((acc, cls) => {
        const date = new Date(cls.scheduled_at).toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        })
        if (!acc[date]) acc[date] = []
        acc[date].push(cls)
        return acc
    }, {})

    const handleJoin = async (cls) => {
        const link = cls.start_url || cls.zoom_link || cls.join_url
        if (!link) {
            toast.error('No Zoom link available')
            return
        }
        const w = window.open('', '_blank')
        try {
            await teacherAttend(cls.id)
            if (w) {
                w.location.href = link
            }
        } catch (e) {
            console.error('Error recording attendance:', e)
            if (w) {
                w.location.href = link
            }
        }
    }

    const now = new Date()

    return (
        <div style={{ paddingBottom: 40 }}>
            <style>{`
                .ts-card { display: flex; align-items: stretch; }
                .ts-time { width: 100px; padding: 20px; flex-shrink: 0; }
                .ts-info { flex: 1; min-width: 0; padding: 20px; }
                .ts-action { padding: 20px; flex-shrink: 0; border-left: 1px dashed var(--t-border); }
                .ts-btn { white-space: nowrap; }
                @media (max-width: 640px) {
                    .ts-card { flex-wrap: wrap; }
                    .ts-time { width: 84px; padding: 14px 8px; }
                    .ts-info { padding: 14px 16px; flex-basis: calc(100% - 84px); }
                    .ts-info h4 { font-size: 16px !important; }
                    .ts-action { width: 100%; flex-basis: 100%; border-left: none !important; border-top: 1px dashed var(--t-border); padding: 12px 16px; }
                    .ts-btn { width: 100%; justify-content: center; }
                }
            `}</style>

            {/* Header Section */}
            <div className="teacher-page-header">
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: 'var(--t-text)', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>My Schedule</h1>
                    <p style={{ color: 'var(--t-text-3)', margin: 0, fontSize: 14 }}>View your scheduled online classes</p>
                </div>
            </div>

            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid var(--t-border)', borderTopColor: 'var(--t-primary)', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : Object.keys(grouped).length === 0 ? (
                <div style={{
                    background: 'var(--t-surface)', borderRadius: 16, border: '1px dashed var(--t-border)',
                    padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center'
                }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: 'var(--t-primary-soft)', color: 'var(--t-primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>
                        <FiCalendar />
                    </div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>No classes scheduled</h3>
                    <p style={{ margin: 0, color: 'var(--t-text-3)', fontSize: 14 }}>You have a free schedule. Classes assigned by administration will appear here.</p>
                </div>
            ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 32 }}>
                    {Object.entries(grouped).map(([date, items]) => (
                        <div key={date}>
                            <h3 style={{
                                fontSize: 16, color: 'var(--t-text)', fontWeight: 800, marginBottom: 16,
                                display: 'flex', alignItems: 'center', gap: 10
                            }}>
                                <span style={{ width: 32, height: 32, borderRadius: 8, background: 'var(--t-primary-soft)', color: 'var(--t-primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 16 }}>
                                    <FiCalendar />
                                </span>
                                {date}
                            </h3>
                            
                            <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                                {items.map(cls => {
                                    const time = new Date(cls.scheduled_at)
                                    const durationMin = Number(cls.duration) || 60
                                    const endTime = new Date(time.getTime() + durationMin * 60000)
                                    const isEnded = endTime <= now
                                    const isLive = time <= now && !isEnded
                                    const isAttended = cls.attendance_status === 'present'
                                    const timeStr = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })

                                    return (
                                        <div key={cls.id} className="ts-card" style={{
                                            background: 'var(--t-surface)', borderRadius: 16,
                                            border: isLive ? '1px solid var(--t-success)' : '1px solid var(--t-border)',
                                            boxShadow: isLive ? '0 4px 12px rgba(21,128,61,0.1)' : '0 1px 3px rgba(0,0,0,0.02)',
                                            overflow: 'hidden', transition: 'all 0.2s',
                                            opacity: isEnded ? 0.75 : 1
                                        }}
                                        onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.boxShadow = '0 8px 16px rgba(0,0,0,0.04)' }}
                                        onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = isLive ? '0 4px 12px rgba(21,128,61,0.1)' : '0 1px 3px rgba(0,0,0,0.02)' }}
                                        >
                                            {/* Time Block */}
                                            <div className="ts-time" style={{
                                                background: isLive ? 'var(--t-success-bg)' : 'var(--t-bg)',
                                                borderRight: '1px solid var(--t-border)',
                                                display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
                                                color: isLive ? 'var(--t-success)' : 'var(--t-text)'
                                            }}>
                                                <span style={{ fontSize: 18, fontWeight: 800, lineHeight: 1.1 }}>{timeStr}</span>
                                                {isLive && (
                                                    <span style={{ padding: '2px 8px', borderRadius: 12, background: 'var(--t-success)', color: '#fff', fontSize: 10, fontWeight: 700, textTransform: 'uppercase', marginTop: 8 }}>
                                                        Live
                                                    </span>
                                                )}
                                                {isEnded && (
                                                    <span style={{ padding: '2px 8px', borderRadius: 12, background: 'var(--t-border)', color: 'var(--t-text-3)', fontSize: 10, fontWeight: 700, textTransform: 'uppercase', marginTop: 8 }}>
                                                        Ended
                                                    </span>
                                                )}
                                            </div>

                                            {/* Info Block */}
                                            <div className="ts-info" style={{ display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
                                                <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 4 }}>
                                                    <span style={{ padding: '2px 8px', borderRadius: 6, background: 'var(--t-primary-soft)', color: 'var(--t-primary)', fontSize: 11, fontWeight: 700, textTransform: 'uppercase' }}>
                                                        {cls.subject || 'Subject'}
                                                    </span>
                                                    {cls.grade && (
                                                        <span style={{ padding: '2px 8px', borderRadius: 6, background: 'var(--t-bg)', color: 'var(--t-text-2)', fontSize: 11, fontWeight: 600 }}>
                                                            Grade {cls.grade}
                                                        </span>
                                                    )}
                                                    {isEnded && (
                                                        <span style={{
                                                            padding: '2px 8px', borderRadius: 6,
                                                            background: isAttended ? 'var(--t-success-bg)' : 'var(--t-danger-bg)',
                                                            color: isAttended ? 'var(--t-success)' : 'var(--t-danger)',
                                                            fontSize: 11, fontWeight: 700
                                                        }}>
                                                            {isAttended ? 'Attended' : 'Missed'}
                                                        </span>
                                                    )}
                                                </div>
                                                <h4 style={{ margin: '0 0 8px 0', fontSize: 18, color: 'var(--t-text)', fontWeight: 700 }}>
                                                    {cls.title || 'Class Session'}
                                                </h4>
                                                <div style={{ display: 'flex', gap: 16, color: 'var(--t-text-3)', fontSize: 13, fontWeight: 500 }}>
                                                    <span style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                                                        <FiClock /> {durationMin} min
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Action Block */}
                                            <div className="ts-action" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                                {isEnded ? (
                                                    <span style={{ fontSize: 13, fontWeight: 600, color: 'var(--t-text-3)' }}>Class Ended</span>
                                                ) : (
                                                    <button
                                                        className="ts-btn"
                                                        onClick={() => handleJoin(cls)}
                                                        style={{
                                                            padding: '12px 20px', borderRadius: 12, border: 'none',
                                                            background: isLive ? 'var(--t-success)' : 'var(--t-primary)',
                                                            color: '#fff', fontSize: 13, fontWeight: 700, cursor: 'pointer',
                                                            display: 'flex', alignItems: 'center', gap: 8,
                                                            boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
                                                            transition: 'all 0.2s'
                                                        }}
                                                        onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.background = isLive ? 'var(--t-success)' : 'var(--t-primary-hover)' }}
                                                        onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.background = isLive ? 'var(--t-success)' : 'var(--t-primary)' }}
                                                    >
                                                        <FiVideo /> {isLive ? 'Join Now' : `Starts at ${timeStr}`}
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    )
                                })}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    )
}
