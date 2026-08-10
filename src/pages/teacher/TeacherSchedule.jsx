import React, { useState, useEffect } from 'react'
import { FiCalendar, FiClock, FiUsers, FiVideo, FiPlus, FiMoreHorizontal } from 'react-icons/fi'
import { getTeacherUpcomingSchedules, teacherAttend } from '../../services/dashboardService'

export default function TeacherSchedule() {
    const [schedules, setSchedules] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                // Mock data fallback if API fails
                let data = await getTeacherUpcomingSchedules().catch(() => null)
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

    // Group by date
    const grouped = schedules.reduce((acc, cls) => {
        const date = new Date(cls.scheduled_at || cls.start_time).toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        })
        if (!acc[date]) acc[date] = []
        acc[date].push(cls)
        return acc
    }, {})

    const handleJoin = async (cls) => {
        try {
            await teacherAttend(cls.id)
            const link = cls.start_url || cls.zoom_link;
            if (link) {
                window.open(link, '_blank')
            }
        } catch (e) {
            console.error('Error joining class:', e)
            const link = cls.start_url || cls.zoom_link;
            if (link) {
                window.open(link, '_blank')
            }
        }
    }

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: '#1e293b', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>My Schedule</h1>
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>View and manage your upcoming classes</p>
                </div>
                <button style={{
                    display: 'flex', alignItems: 'center', gap: 8, padding: '10px 20px',
                    background: 'linear-gradient(135deg, #0ea5e9, #06b6d4)', color: '#fff',
                    border: 'none', borderRadius: 12, fontWeight: 600, fontSize: 14,
                    cursor: 'pointer', boxShadow: '0 4px 12px rgba(14,165,233,0.3)',
                    transition: 'all 0.2s', width: 'fit-content'
                }}
                onMouseEnter={e => e.currentTarget.style.transform = 'translateY(-2px)'}
                onMouseLeave={e => e.currentTarget.style.transform = 'translateY(0)'}
                >
                    <FiPlus style={{ fontSize: 18 }} /> Schedule Class
                </button>
            </div>

            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#0ea5e9', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : Object.keys(grouped).length === 0 ? (
                <div style={{
                    background: '#fff', borderRadius: 16, border: '1px dashed #cbd5e1',
                    padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center'
                }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: '#f1f5f9', color: '#94a3b8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>
                        <FiCalendar />
                    </div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#334155' }}>No classes scheduled</h3>
                    <p style={{ margin: '0 0 20px 0', color: '#64748b', fontSize: 14 }}>You have a free schedule. Take a break!</p>
                </div>
            ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 32 }}>
                    {Object.entries(grouped).map(([date, items]) => (
                        <div key={date}>
                            <h3 style={{
                                fontSize: 16, color: '#1e293b', fontWeight: 800, marginBottom: 16,
                                display: 'flex', alignItems: 'center', gap: 10
                            }}>
                                <span style={{ width: 32, height: 32, borderRadius: 8, background: '#e0f2fe', color: '#0ea5e9', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 16 }}>
                                    <FiCalendar />
                                </span>
                                {date}
                            </h3>
                            
                            <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                                {items.map(cls => {
                                    const time = new Date(cls.scheduled_at || cls.start_time)
                                    const now = new Date()
                                    const isLive = time.getTime() <= now.getTime() && time.getTime() + (cls.duration || 60)*60000 > now.getTime()

                                    return (
                                        <div key={cls.id} className="flex flex-col sm:flex-row items-stretch bg-white rounded-2xl overflow-hidden transition-all duration-200" style={{
                                            border: isLive ? '1px solid #bae6fd' : '1px solid #e2e8f0',
                                            boxShadow: isLive ? '0 4px 12px rgba(14,165,233,0.1)' : '0 1px 3px rgba(0,0,0,0.02)',
                                        }}
                                        onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.boxShadow = '0 8px 16px rgba(0,0,0,0.04)' }}
                                        onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = isLive ? '0 4px 12px rgba(14,165,233,0.1)' : '0 1px 3px rgba(0,0,0,0.02)' }}
                                        >
                                            {/* Time Block */}
                                            <div className="w-full sm:w-[100px] p-4 sm:p-5 flex flex-row sm:flex-col items-center justify-between sm:justify-center border-b sm:border-b-0 sm:border-r border-slate-200" style={{
                                                background: isLive ? 'linear-gradient(135deg, #0ea5e9, #06b6d4)' : '#f8fafc',
                                                color: isLive ? '#fff' : '#1e293b'
                                            }}>
                                                <div className="flex items-center sm:flex-col gap-2 sm:gap-0">
                                                    <span style={{ fontSize: 20, fontWeight: 800, lineHeight: 1 }}>{time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }).split(' ')[0]}</span>
                                                    <span style={{ fontSize: 13, fontWeight: 700, opacity: 0.8 }}>{time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }).split(' ')[1]}</span>
                                                </div>
                                                {isLive && <span style={{ padding: '2px 8px', borderRadius: 12, background: 'rgba(255,255,255,0.2)', fontSize: 10, fontWeight: 700, textTransform: 'uppercase', marginTop: 0 }} className="sm:mt-2">Live</span>}
                                            </div>

                                            {/* Info Block */}
                                            <div className="flex-1 p-4 sm:p-5 flex flex-col justify-center">
                                                <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 4 }}>
                                                    <span style={{ padding: '2px 8px', borderRadius: 6, background: '#f1f5f9', color: '#64748b', fontSize: 11, fontWeight: 700, textTransform: 'uppercase' }}>{cls.subject || 'Subject'}</span>
                                                </div>
                                                <h4 style={{ margin: '0 0 8px 0', fontSize: 18, color: '#1e293b', fontWeight: 700 }}>
                                                    {cls.title || 'Class Session'}
                                                </h4>
                                                <div style={{ display: 'flex', gap: 16, color: '#64748b', fontSize: 13, fontWeight: 500 }}>
                                                    <span style={{ display: 'flex', alignItems: 'center', gap: 6 }}><FiUsers /> {cls.student_count || 0} students</span>
                                                    <span style={{ display: 'flex', alignItems: 'center', gap: 6 }}><FiClock /> {cls.duration || 60} min</span>
                                                </div>
                                            </div>

                                            {/* Action Block */}
                                            <div className="p-4 sm:p-5 flex items-center justify-center border-t sm:border-t-0 sm:border-l border-dashed border-slate-200">
                                                {cls.zoom_link ? (
                                                    <button
                                                        onClick={() => handleJoin(cls)}
                                                        style={{
                                                            padding: '12px 24px', borderRadius: 12, border: 'none',
                                                            background: isLive ? '#10b981' : '#0ea5e9',
                                                            color: '#fff', fontSize: 14, fontWeight: 700, cursor: 'pointer',
                                                            display: 'flex', alignItems: 'center', gap: 8,
                                                            boxShadow: isLive ? '0 4px 12px rgba(16,185,129,0.3)' : '0 4px 12px rgba(14,165,233,0.3)',
                                                            transition: 'all 0.2s'
                                                        }}
                                                        onMouseEnter={e => e.currentTarget.style.transform = 'translateY(-2px)'}
                                                        onMouseLeave={e => e.currentTarget.style.transform = 'translateY(0)'}
                                                    >
                                                        <FiVideo /> {isLive ? 'Join Now' : 'Start'}
                                                    </button>
                                                ) : (
                                                    <button style={{
                                                        padding: 12, borderRadius: 12, border: '1px solid #e2e8f0',
                                                        background: '#fff', color: '#94a3b8', fontSize: 20, cursor: 'pointer'
                                                    }}>
                                                        <FiMoreHorizontal />
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
