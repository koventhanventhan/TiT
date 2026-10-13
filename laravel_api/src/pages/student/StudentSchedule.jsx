import React, { useState, useEffect } from 'react'
import { FiCalendar, FiClock, FiVideo, FiMapPin, FiExternalLink } from 'react-icons/fi'
import { getStudentUpcomingSchedules } from '../../services/dashboardService'
import useJoinClass from '../../hooks/useJoinClass'

export default function StudentSchedule() {
    const [schedules, setSchedules] = useState([])
    const [loading, setLoading] = useState(true)
    const { joinClass, isJoining } = useJoinClass()

    useEffect(() => {
        async function load() {
            try {
                let data = await getStudentUpcomingSchedules().catch(() => null)
                if (!data || data.length === 0) {
                    data = []
                }
                const arr = Array.isArray(data) ? data : data.data || []
                arr.sort((a, b) => new Date(a.scheduled_at || a.start_time) - new Date(b.scheduled_at || b.start_time))
                setSchedules(arr)
            } catch (e) {
                console.error('Error loading schedule:', e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    const grouped = schedules.reduce((acc, cls) => {
        const date = new Date(cls.scheduled_at || cls.start_time).toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        })
        if (!acc[date]) acc[date] = []
        acc[date].push(cls)
        return acc
    }, {})

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24,
                '@media (minWidth: 640px)': { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }
            }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: '#1e293b', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>My Schedule</h1>
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>View your upcoming physical and online classes</p>
                </div>
            </div>

            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#6366f1', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : Object.keys(grouped).length === 0 ? (
                <div style={{
                    background: '#fff', borderRadius: 16, border: '1px dashed #cbd5e1',
                    padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center'
                }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: '#f1f5f9', color: '#94a3b8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>
                        <FiCalendar />
                    </div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#334155' }}>No upcoming classes</h3>
                    <p style={{ margin: '0 0 20px 0', color: '#64748b', fontSize: 14 }}>You don't have any classes scheduled in the near future.</p>
                </div>
            ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 32 }}>
                    {Object.entries(grouped).map(([date, items]) => (
                        <div key={date}>
                            <h3 style={{
                                fontSize: 16, color: '#1e293b', fontWeight: 800, marginBottom: 16,
                                display: 'flex', alignItems: 'center', gap: 10
                            }}>
                                <span style={{ width: 32, height: 32, borderRadius: 8, background: '#e0e7ff', color: '#6366f1', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 16 }}>
                                    <FiCalendar />
                                </span>
                                {date}
                            </h3>
                            
                            <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                                {items.map(cls => {
                                    const time = new Date(cls.scheduled_at || cls.start_time)
                                    const now = new Date()
                                    const end = time.getTime() + (cls.duration || 60) * 60000
                                    const isEnded = end <= now.getTime()
                                    const isLive = time.getTime() <= now.getTime() && !isEnded
                                    const canJoin = !isEnded && now.getTime() >= (time.getTime() - 10 * 60 * 1000)
                                    const isOnline = cls.type === 'online' || Boolean(cls.zoom_link || cls.join_url)
                                    const hasLink = Boolean(cls.zoom_link || cls.join_url)
                                    const busy = isJoining(cls.id)

                                    return (
                                        <div key={cls.id} style={{
                                            display: 'flex', alignItems: 'stretch',
                                            background: '#fff', borderRadius: 16,
                                            border: isLive ? '1px solid #c7d2fe' : '1px solid #e2e8f0',
                                            boxShadow: isLive ? '0 4px 12px rgba(99,102,241,0.1)' : '0 1px 3px rgba(0,0,0,0.02)',
                                            overflow: 'hidden', transition: 'all 0.2s', flexWrap: 'wrap',
                                            opacity: isEnded ? 0.65 : 1
                                        }}
                                        onMouseEnter={e => { if (!isEnded) { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.boxShadow = '0 8px 16px rgba(0,0,0,0.04)' } }}
                                        onMouseLeave={e => { if (!isEnded) { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = isLive ? '0 4px 12px rgba(99,102,241,0.1)' : '0 1px 3px rgba(0,0,0,0.02)' } }}
                                        >
                                            {/* Time Block */}
                                            <div style={{
                                                width: 100, padding: 20,
                                                background: isLive ? 'linear-gradient(135deg, #6366f1, #8b5cf6)' : '#f8fafc',
                                                borderRight: '1px solid #e2e8f0',
                                                display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
                                                color: isLive ? '#fff' : '#1e293b'
                                            }}>
                                                <span style={{ fontSize: 20, fontWeight: 800, lineHeight: 1 }}>{time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }).split(' ')[0]}</span>
                                                <span style={{ fontSize: 13, fontWeight: 700, opacity: 0.8 }}>{time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }).split(' ')[1]}</span>
                                                {isLive && <span style={{ padding: '2px 8px', borderRadius: 12, background: 'rgba(255,255,255,0.2)', fontSize: 10, fontWeight: 700, textTransform: 'uppercase', marginTop: 8 }}>Live Now</span>}
                                            </div>

                                            {/* Info Block */}
                                            <div style={{ flex: 1, padding: 20, display: 'flex', flexDirection: 'column', justifyContent: 'center', minWidth: 200 }}>
                                                <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
                                                    <span style={{ padding: '2px 8px', borderRadius: 6, background: '#f1f5f9', color: '#64748b', fontSize: 11, fontWeight: 700, textTransform: 'uppercase' }}>{cls.subject || 'Subject'}</span>
                                                    <span style={{ padding: '2px 8px', borderRadius: 6, background: isOnline ? '#eff6ff' : '#ecfdf5', color: isOnline ? '#3b82f6' : '#10b981', fontSize: 11, fontWeight: 700, textTransform: 'uppercase', display: 'flex', alignItems: 'center', gap: 4 }}>
                                                        {isOnline ? <FiVideo /> : <FiMapPin />} {isOnline ? 'Online' : 'Physical'}
                                                    </span>
                                                </div>
                                                <h4 style={{ margin: '0 0 4px 0', fontSize: 18, color: '#1e293b', fontWeight: 700 }}>
                                                    {cls.title || 'Class Session'}
                                                </h4>
                                                <div style={{ color: '#64748b', fontSize: 13, fontWeight: 500, marginBottom: 8 }}>{cls.teacher}</div>
                                                
                                                <div style={{ display: 'flex', gap: 16, color: '#64748b', fontSize: 13, fontWeight: 500, flexWrap: 'wrap' }}>
                                                    <span style={{ display: 'flex', alignItems: 'center', gap: 6 }}><FiClock /> {cls.duration || 60} min duration</span>
                                                    {!isOnline && cls.location && <span style={{ display: 'flex', alignItems: 'center', gap: 6 }}><FiMapPin /> {cls.location}</span>}
                                                </div>
                                            </div>

                                            {/* Action Block */}
                                            {isOnline && (
                                                <div style={{ padding: '16px 20px', display: 'flex', alignItems: 'center', justifyContent: 'flex-end', borderLeft: '1px solid #f1f5f9' }}>
                                                    {isEnded ? (
                                                        <span style={{ padding: '6px 14px', borderRadius: 8, background: '#f1f5f9', color: '#94a3b8', fontSize: 12, fontWeight: 700 }}>
                                                            Ended
                                                        </span>
                                                    ) : canJoin ? (
                                                        <button
                                                            onClick={() => joinClass(cls)}
                                                            disabled={busy || !hasLink}
                                                            style={{
                                                                padding: '10px 18px', borderRadius: 10,
                                                                background: isLive ? 'linear-gradient(135deg, #6366f1, #8b5cf6)' : '#f8fafc',
                                                                color: isLive ? '#fff' : '#475569',
                                                                border: isLive ? 'none' : '1px solid #e2e8f0',
                                                                fontWeight: 700, fontSize: 13,
                                                                cursor: (busy || !hasLink) ? 'not-allowed' : 'pointer',
                                                                display: 'flex', alignItems: 'center', gap: 6,
                                                                boxShadow: isLive ? '0 4px 12px rgba(99,102,241,0.25)' : 'none',
                                                                opacity: busy ? 0.7 : 1
                                                            }}
                                                        >
                                                            <FiExternalLink /> {busy ? 'Joining...' : (isLive ? 'Join Live Class' : 'Join Class')}
                                                        </button>
                                                    ) : (
                                                        <span style={{ padding: '6px 12px', borderRadius: 8, background: '#f8fafc', border: '1px solid #e2e8f0', color: '#64748b', fontSize: 12, fontWeight: 600 }}>
                                                            Starts at {time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                                                        </span>
                                                    )}
                                                </div>
                                            )}
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

