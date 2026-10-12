import React, { useState, useEffect } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import {
    FiVideo, FiUsers, FiFileText, FiCalendar,
    FiArrowRight, FiCheck, FiClock,
    FiTrendingUp, FiPlus
} from 'react-icons/fi'
import { getTeacherDashboardStats, getTeacherUpcomingSchedules, teacherAttend } from '../../services/dashboardService'
import { useToast } from '../../components/shared/ToastContext'

function StatCard({ icon: Icon, label, value }) {
    return (
        <div style={{
            background: 'var(--t-surface)', borderRadius: 16, padding: '22px 20px',
            border: '1px solid var(--t-border)', boxShadow: '0 1px 3px rgba(0,0,0,0.04)',
            display: 'flex', alignItems: 'center', gap: 16,
            transition: 'all 0.3s ease', cursor: 'default',
            position: 'relative', overflow: 'hidden'
        }}
        onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.boxShadow = '0 8px 24px rgba(0,0,0,0.08)' }}
        onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 1px 3px rgba(0,0,0,0.04)' }}
        >
            <div style={{
                width: 52, height: 52, borderRadius: 14,
                background: 'var(--t-primary-soft)', display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: 22, color: 'var(--t-primary)', flexShrink: 0
            }}><Icon /></div>
            <div>
                <div style={{ fontSize: 13, fontWeight: 500, color: 'var(--t-text-3)', marginBottom: 2 }}>{label}</div>
                <div style={{ fontSize: 28, fontWeight: 800, color: 'var(--t-text)', letterSpacing: '-1px', lineHeight: 1 }}>{value}</div>
            </div>
        </div>
    )
}

export default function TeacherOverview() {
    const toast = useToast()
    const navigate = useNavigate()
    const [stats, setStats] = useState(null)
    const [todaySchedules, setTodaySchedules] = useState([])
    const [loading, setLoading] = useState(true)

    const hour = new Date().getHours()
    const greeting = hour < 12 ? 'Good Morning' : (hour < 18 ? 'Good Afternoon' : 'Good Evening')

    useEffect(() => {
        async function load() {
            try {
                const [statsData, schedulesData] = await Promise.all([
                    getTeacherDashboardStats().catch(() => null),
                    getTeacherUpcomingSchedules().catch(() => null)
                ])
                setStats(statsData || {})
                const schedArr = Array.isArray(schedulesData) ? schedulesData : (schedulesData?.data || [])
                schedArr.sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at))
                setTodaySchedules(schedArr)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    const handleJoinClass = async (cls) => {
        const link = cls.start_url || cls.zoom_link || cls.join_url
        if (!link) {
            toast.error('No Zoom link available')
            return
        }
        const w = window.open('', '_blank')
        try {
            await teacherAttend(cls.id).catch(err => {
                console.error('Attendance recording error:', err)
            })
            if (w) {
                w.location.href = link
            }
        } catch (e) {
            if (w) {
                w.location.href = link
            }
        }
    }

    // Find the next/live class of today
    const now = new Date()
    const nextClass = todaySchedules.find(cls => {
        const time = new Date(cls.scheduled_at)
        const durationMin = Number(cls.duration) || 60
        const endTime = new Date(time.getTime() + durationMin * 60000)
        return endTime > now
    }) || todaySchedules[0]

    const teacherName = stats?.user?.full_name || stats?.user?.name || stats?.user?.first_name || stats?.user_name || 'Teacher'

    if (loading) return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 300 }}>
            <div style={{
                width: 40, height: 40, border: '4px solid var(--t-border)', borderTopColor: 'var(--t-primary)',
                borderRadius: '50%', animation: 'spin 0.8s linear infinite'
            }} />
            <style>{`@keyframes spin { to { transform: rotate(360deg) } }`}</style>
        </div>
    )

    return (
        <div>
            {/* Welcome Banner */}
            <div style={{
                background: 'var(--t-sidebar)',
                borderRadius: 20, padding: '32px 28px', marginBottom: 24,
                position: 'relative', overflow: 'hidden', color: '#fff'
            }}>
                <div style={{ position: 'relative', zIndex: 1, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 16 }}>
                    <div>
                        <div style={{ fontSize: 14, fontWeight: 500, color: 'var(--t-sidebar-text)', marginBottom: 6, display: 'flex', alignItems: 'center', gap: 6 }}>
                            <FiTrendingUp /> {greeting}
                        </div>
                        <h1 style={{ fontSize: 28, fontWeight: 800, margin: 0, letterSpacing: '-0.5px', lineHeight: 1.2, marginBottom: 6 }}>
                            Welcome Back, {teacherName}! 👋
                        </h1>
                        <p style={{ fontSize: 15, color: 'var(--t-sidebar-text)', margin: 0, maxWidth: 500 }}>
                            You have {stats?.today_classes || 0} classes today. Let's make it a great day!
                        </p>
                    </div>
                    <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
                        <button style={{
                            padding: '10px 20px', borderRadius: 12, border: '1px solid rgba(255,255,255,0.2)',
                            background: 'rgba(255,255,255,0.1)', color: '#fff', fontSize: 13,
                            fontWeight: 600, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6,
                            transition: 'all 0.2s'
                        }}
                        onClick={() => navigate('/teacher/schedule')}
                        onMouseEnter={e => e.currentTarget.style.background = 'rgba(255,255,255,0.2)'}
                        onMouseLeave={e => e.currentTarget.style.background = 'rgba(255,255,255,0.1)'}
                        >
                            <FiCalendar /> Schedule
                        </button>
                        <button style={{
                            padding: '10px 20px', borderRadius: 12, border: 'none',
                            background: nextClass ? 'var(--t-primary)' : 'rgba(255,255,255,0.2)',
                            color: '#fff', fontSize: 13,
                            fontWeight: 700, cursor: nextClass ? 'pointer' : 'not-allowed',
                            display: 'flex', alignItems: 'center', gap: 6,
                            transition: 'all 0.2s',
                            opacity: nextClass ? 1 : 0.6
                        }}
                        disabled={!nextClass}
                        title={!nextClass ? 'No class right now' : `Start: ${nextClass.title}`}
                        onClick={() => nextClass && handleJoinClass(nextClass)}
                        onMouseEnter={e => { if (nextClass) e.currentTarget.style.background = 'var(--t-primary-hover)' }}
                        onMouseLeave={e => { if (nextClass) e.currentTarget.style.background = 'var(--t-primary)' }}
                        >
                            <FiVideo /> Start Class
                        </button>
                    </div>
                </div>
            </div>

            {/* Stats Grid */}
            <div style={{
                display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
                gap: 16, marginBottom: 24
            }}>
                <StatCard icon={FiVideo} label="Today's Classes" value={stats?.today_classes || 0} />
                <StatCard icon={FiUsers} label="Total Students" value={stats?.total_students || 0} />
                <StatCard icon={FiFileText} label="New Submissions" value={stats?.new_submissions || 0} />
                <StatCard icon={FiCalendar} label="This Week" value={stats?.week_classes || 0} />
            </div>

            {/* Main Content Grid */}
            <div className="teacher-overview-grid">
                {/* Schedule */}
                <div style={{
                    background: 'var(--t-surface)', borderRadius: 16, border: '1px solid var(--t-border)',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.04)', overflow: 'hidden'
                }}>
                    <div style={{
                        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                        padding: '18px 22px', borderBottom: '1px solid var(--t-border)'
                    }}>
                        <h3 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: 'var(--t-text)' }}>Your Schedule Today</h3>
                        <Link to="/teacher/schedule" style={{
                            color: 'var(--t-primary)', fontSize: 13, fontWeight: 600,
                            textDecoration: 'none', display: 'flex', alignItems: 'center', gap: 4
                        }}>View All <FiArrowRight /></Link>
                    </div>

                    {todaySchedules.length === 0 ? (
                        <div style={{ padding: '32px 24px', textAlign: 'center', color: 'var(--t-text-3)', fontSize: 13 }}>
                            No upcoming schedules found for today.
                        </div>
                    ) : (
                        <div style={{ display: 'flex', flexDirection: 'column' }}>
                            {todaySchedules.map((cls, idx) => {
                                const time = new Date(cls.scheduled_at)
                                const durationMin = Number(cls.duration) || 60
                                const isEnded = new Date(time.getTime() + durationMin * 60000) <= now
                                const isLive = time <= now && !isEnded

                                return (
                                    <div key={cls.id} style={{
                                        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                                        padding: '16px 22px', borderBottom: idx === todaySchedules.length - 1 ? 'none' : '1px solid var(--t-border)',
                                        gap: 16, flexWrap: 'wrap'
                                    }}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
                                            <div style={{
                                                padding: '8px 12px', borderRadius: 10,
                                                background: isLive ? 'var(--t-success-bg)' : 'var(--t-primary-soft)',
                                                color: isLive ? 'var(--t-success)' : 'var(--t-primary)',
                                                fontWeight: 700, fontSize: 13, textAlign: 'center', minWidth: 65
                                            }}>
                                                {time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                            </div>
                                            <div>
                                                <div style={{ fontSize: 14, fontWeight: 700, color: 'var(--t-text)', marginBottom: 2 }}>{cls.title}</div>
                                                <div style={{ fontSize: 12, color: 'var(--t-text-3)' }}>
                                                    {cls.subject || 'Subject'} • {cls.grade ? `Grade ${cls.grade}` : 'All Grades'}
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            {isEnded ? (
                                                <span style={{ fontSize: 12, fontWeight: 600, color: 'var(--t-text-3)' }}>Ended</span>
                                            ) : (
                                                <button
                                                    onClick={() => handleJoinClass(cls)}
                                                    style={{
                                                        padding: '8px 16px', borderRadius: 8, border: 'none',
                                                        background: isLive ? 'var(--t-success)' : 'var(--t-primary)',
                                                        color: '#fff', fontSize: 13, fontWeight: 600, cursor: 'pointer',
                                                        display: 'flex', alignItems: 'center', gap: 6, transition: 'all 0.2s'
                                                    }}
                                                >
                                                    <FiVideo /> {isLive ? 'Join Now' : 'Start'}
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                )
                            })}
                        </div>
                    )}
                </div>

                {/* Recent Submissions */}
                <div style={{
                    background: 'var(--t-surface)', borderRadius: 16, border: '1px solid var(--t-border)',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.04)', overflow: 'hidden',
                    display: 'flex', flexDirection: 'column'
                }}>
                    <div style={{
                        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                        padding: '18px 22px', borderBottom: '1px solid var(--t-border)'
                    }}>
                        <h3 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: 'var(--t-text)' }}>📋 Recent Submissions</h3>
                    </div>

                    {(!stats?.recent_submissions || stats.recent_submissions.length === 0) ? (
                        <div style={{ padding: '32px 24px', textAlign: 'center', color: 'var(--t-text-3)', fontSize: 13, flex: 1 }}>
                            No recent submissions pending review.
                        </div>
                    ) : (
                        <div style={{ display: 'flex', flexDirection: 'column', flex: 1 }}>
                            {stats.recent_submissions.map((sub, idx) => (
                                <div key={sub.id} style={{
                                    padding: '14px 20px', borderBottom: idx === stats.recent_submissions.length - 1 ? 'none' : '1px solid var(--t-border)',
                                    display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12
                                }}>
                                    <div>
                                        <div style={{ fontSize: 14, fontWeight: 600, color: 'var(--t-text)' }}>{sub.student_name}</div>
                                        <div style={{ fontSize: 12, color: 'var(--t-text-3)' }}>{sub.assignment_title}</div>
                                    </div>
                                    <div style={{ fontSize: 11, color: 'var(--t-text-3)', textAlign: 'right' }}>
                                        {sub.submitted_at ? new Date(sub.submitted_at).toLocaleDateString() : 'Pending'}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Footer */}
                    <div style={{ padding: '14px 22px', textAlign: 'center', borderTop: '1px solid var(--t-border)' }}>
                        <Link to="/teacher/assignments" style={{
                            color: 'var(--t-primary)', fontSize: 13, fontWeight: 600, textDecoration: 'none'
                        }}>View all submissions →</Link>
                    </div>
                </div>
            </div>

            {/* Quick Actions */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 16, marginTop: 24 }}>
                <Link to="/teacher/assignments" style={{
                    textDecoration: 'none', padding: '22px 24px', borderRadius: 16,
                    background: 'var(--t-surface)', border: '1px solid var(--t-border)',
                    color: 'var(--t-text)', display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.04)', transition: 'all 0.3s'
                }}
                onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-3px)'; e.currentTarget.style.boxShadow = '0 8px 24px rgba(0,0,0,0.08)' }}
                onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 1px 3px rgba(0,0,0,0.04)' }}
                >
                    <div>
                        <div style={{ fontSize: 16, fontWeight: 700, marginBottom: 4, color: 'var(--t-text)' }}>Create Assignment</div>
                        <div style={{ fontSize: 13, color: 'var(--t-text-3)', fontWeight: 500 }}>Post new tasks for students</div>
                    </div>
                    <div style={{
                        width: 44, height: 44, borderRadius: 12, background: 'var(--t-primary-soft)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20,
                        color: 'var(--t-primary)'
                    }}><FiPlus /></div>
                </Link>

                <Link to="/teacher/materials" style={{
                    textDecoration: 'none', padding: '22px 24px', borderRadius: 16,
                    background: 'var(--t-surface)', border: '1px solid var(--t-border)',
                    color: 'var(--t-text)', display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.04)', transition: 'all 0.3s'
                }}
                onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-3px)'; e.currentTarget.style.boxShadow = '0 8px 24px rgba(0,0,0,0.08)' }}
                onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 1px 3px rgba(0,0,0,0.04)' }}
                >
                    <div>
                        <div style={{ fontSize: 16, fontWeight: 700, marginBottom: 4, color: 'var(--t-text)' }}>Upload Materials</div>
                        <div style={{ fontSize: 13, color: 'var(--t-text-3)', fontWeight: 500 }}>Share notes & resources</div>
                    </div>
                    <div style={{
                        width: 44, height: 44, borderRadius: 12, background: 'var(--t-primary-soft)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20,
                        color: 'var(--t-primary)'
                    }}><FiArrowRight /></div>
                </Link>

                <Link to="/teacher/reports" style={{
                    textDecoration: 'none', padding: '22px 24px', borderRadius: 16,
                    background: 'var(--t-surface)', border: '1px solid var(--t-border)',
                    color: 'var(--t-text)', display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.04)', transition: 'all 0.3s'
                }}
                onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-3px)'; e.currentTarget.style.boxShadow = '0 8px 24px rgba(0,0,0,0.08)' }}
                onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 1px 3px rgba(0,0,0,0.04)' }}
                >
                    <div>
                        <div style={{ fontSize: 16, fontWeight: 700, marginBottom: 4, color: 'var(--t-text)' }}>View Reports</div>
                        <div style={{ fontSize: 13, color: 'var(--t-text-3)', fontWeight: 500 }}>Attendance & progress</div>
                    </div>
                    <div style={{
                        width: 44, height: 44, borderRadius: 12, background: 'var(--t-primary-soft)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20,
                        color: 'var(--t-primary)'
                    }}><FiArrowRight /></div>
                </Link>
            </div>
        </div>
    )
}
