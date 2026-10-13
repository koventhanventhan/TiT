import React, { useState, useEffect, useRef } from 'react'
import { Link } from 'react-router-dom'
import {
    FiVideo, FiCalendar, FiCheckCircle, FiFileText,
    FiArrowRight, FiBook, FiPlay, FiAlertCircle, FiClock,
    FiTrendingUp
} from 'react-icons/fi'
import { getStudentStats, getStudentUpcomingSchedules } from '../../services/dashboardService'
import StudentPaymentModule from '../../components/student/StudentPaymentModule'
import SubjectUpdateModal from '../../components/student/SubjectUpdateModal'
import useJoinClass from '../../hooks/useJoinClass'
import useMediaQuery from '../../hooks/useMediaQuery'

// Reusable Stat Card
function StatCard({ icon: Icon, label, value }) {
    return (
        <div style={{
            background: '#fff', borderRadius: 16, padding: '22px 20px',
            border: '1px solid var(--t-border)',
            boxShadow: '0 1px 3px rgba(0,0,0,0.04)',
            display: 'flex', alignItems: 'center', gap: 16,
            transition: 'all 0.3s ease', cursor: 'default',
            position: 'relative', overflow: 'hidden'
        }}>
            <div style={{
                width: 52, height: 52, borderRadius: 14,
                background: 'var(--t-primary-soft)', display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: 22, color: 'var(--t-primary)', flexShrink: 0
            }}>
                <Icon />
            </div>
            <div>
                <div style={{ fontSize: 13, fontWeight: 500, color: 'var(--t-text-3)', marginBottom: 2 }}>{label}</div>
                <div style={{ fontSize: 28, fontWeight: 800, color: 'var(--t-text)', letterSpacing: '-1px', lineHeight: 1 }}>{value}</div>
            </div>
        </div>
    )
}

export default function StudentOverview() {
    const [stats, setStats] = useState(null)
    const [todaySchedules, setTodaySchedules] = useState([])
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState(null)
    const [showSubjectModal, setShowSubjectModal] = useState(false)
    const [paymentRefreshKey, setPaymentRefreshKey] = useState(0)
    const paymentRef = useRef(null)
    const isDesktop = useMediaQuery('(min-width: 1024px)')
    const { joinClass, isJoining } = useJoinClass()

    const hour = new Date().getHours()
    const greeting = hour < 12 ? 'Good Morning' : (hour < 18 ? 'Good Afternoon' : 'Good Evening')

    const loadStats = async () => {
        setLoading(true)
        setError(null)
        try {
            const [data, schedules] = await Promise.all([
                getStudentStats(),
                getStudentUpcomingSchedules().catch(() => [])
            ])
            setStats(data)
            
            const scheduleList = Array.isArray(schedules) ? schedules : (schedules?.data || [])
            const todayStr = new Date().toDateString()
            const todays = scheduleList.filter(s => new Date(s.scheduled_at).toDateString() === todayStr)
            setTodaySchedules(todays)
        } catch (err) {
            console.error('Failed to load student dashboard:', err)
            setError(err.message || 'Failed to load dashboard data')
        } finally {
            setLoading(false)
        }
    }

    useEffect(() => {
        loadStats()
    }, [])

    if (loading) return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 300 }}>
            <div style={{
                width: 40, height: 40, border: '4px solid var(--t-border)', borderTopColor: 'var(--t-primary)',
                borderRadius: '50%', animation: 'spin 0.8s linear infinite'
            }} />
            <style>{`@keyframes spin { to { transform: rotate(360deg) } }`}</style>
        </div>
    )

    if (error) {
        return (
            <div style={{ padding: '40px 16px', display: 'flex', justifyContent: 'center' }}>
                <div style={{
                    maxWidth: 480, width: '100%', background: '#fff', borderRadius: 16,
                    border: '1px solid var(--t-danger-bg)', padding: '32px 24px', textAlign: 'center',
                    boxShadow: '0 4px 12px rgba(0,0,0,0.06)'
                }}>
                    <div style={{
                        width: 56, height: 56, borderRadius: '50%', background: 'var(--t-danger-bg)',
                        color: 'var(--t-danger)', display: 'flex', alignItems: 'center', justifyContent: 'center',
                        fontSize: 24, margin: '0 auto 16px'
                    }}>
                        <FiAlertCircle />
                    </div>
                    <h2 style={{ fontSize: 18, fontWeight: 700, color: 'var(--t-danger)', margin: '0 0 8px' }}>
                        Failed to load dashboard
                    </h2>
                    <p style={{ color: 'var(--t-text-3)', fontSize: 14, margin: '0 0 20px' }}>
                        {error}
                    </p>
                    <button
                        onClick={loadStats}
                        style={{
                            padding: '10px 24px', borderRadius: 10, background: 'var(--t-primary)',
                            color: '#fff', border: 'none', fontWeight: 600, fontSize: 14,
                            cursor: 'pointer'
                        }}
                    >
                        Retry
                    </button>
                </div>
            </div>
        )
    }

    return (
        <div>
            {/* Welcome Banner - Solid var(--t-sidebar) */}
            <div style={{
                background: 'var(--t-sidebar)',
                borderRadius: 20, padding: '32px 28px', marginBottom: 24,
                position: 'relative', overflow: 'hidden', color: '#fff'
            }}>
                <div style={{ position: 'relative', zIndex: 1 }}>
                    <div style={{ fontSize: 14, fontWeight: 500, color: 'var(--t-primary-border)', marginBottom: 6, display: 'flex', alignItems: 'center', gap: 6 }}>
                        <FiTrendingUp /> {greeting}
                    </div>
                    <h1 style={{
                        fontSize: 28, fontWeight: 800, margin: 0, letterSpacing: '-0.5px',
                        lineHeight: 1.2, marginBottom: 6
                    }}>
                        Welcome Back, {stats?.user?.full_name || stats?.user?.name || stats?.user?.first_name || stats?.user_name || 'Student'}! 👋
                    </h1>
                    <p style={{ fontSize: 15, color: 'var(--t-sidebar-text)', margin: 0, maxWidth: 500 }}>
                        Here's what's happening with your learning today. Stay consistent and keep growing!
                    </p>
                </div>
            </div>

            {/* Subject Review Banner */}
            {stats?.user?.needs_subject_review && (
                <div style={{
                    background: 'var(--t-danger-bg)',
                    borderRadius: 16, padding: '20px 24px', marginBottom: 24,
                    display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    border: '1px solid var(--t-danger)'
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
                        <div style={{
                            width: 44, height: 44, borderRadius: '50%', background: 'var(--t-danger)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#fff'
                        }}>
                            <FiAlertCircle size={24} />
                        </div>
                        <div>
                            <h3 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: 'var(--t-danger)' }}>Action Required: Update Subjects</h3>
                            <p style={{ margin: '4px 0 0', fontSize: 14, color: 'var(--t-danger)' }}>
                                You've been promoted to <strong>{stats?.user?.current_grade}</strong>. Some subjects may have changed. Please select your subjects for the new academic year.
                            </p>
                        </div>
                    </div>
                    <button onClick={() => setShowSubjectModal(true)} style={{
                        padding: '10px 20px', borderRadius: 8, border: 'none', background: 'var(--t-danger)',
                        color: '#fff', fontSize: 14, fontWeight: 600, cursor: 'pointer', whiteSpace: 'nowrap'
                    }}>
                        Review Subjects
                    </button>
                </div>
            )}

            <SubjectUpdateModal 
                isOpen={showSubjectModal} 
                onClose={() => setShowSubjectModal(false)}
                currentGrade={stats?.user?.current_grade}
                medium={stats?.user?.medium}
                initialSubjects={stats?.user?.selected_subjects}
                onUpdateSuccess={(newSubjects) => {
                    setStats(prev => ({
                        ...prev,
                        user: {
                            ...prev.user,
                            needs_subject_review: false,
                            selected_subjects: newSubjects
                        }
                    }))
                    setShowSubjectModal(false)
                    // Re-render payment module to fetch the new amount
                    setPaymentRefreshKey(k => k + 1)
                    // Auto-scroll to payment section after a brief delay
                    setTimeout(() => {
                        paymentRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' })
                    }, 300)
                }}
            />

            {/* Payment Module */}
            <div ref={paymentRef}>
                <StudentPaymentModule key={paymentRefreshKey} />
            </div>

            {/* Stats Grid - All use primary-soft chip + primary icon */}
            <div style={{
                display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(220px, 100%), 1fr))',
                gap: 16, marginBottom: 24
            }}>
                <StatCard icon={FiVideo} label="Today's Classes" value={stats?.today_classes || 0} />
                <StatCard icon={FiFileText} label="Pending Tasks" value={stats?.pending_assignments || 0} />
                <StatCard icon={FiCheckCircle} label="Attendance" value={`${stats?.attendance_rate || 0}%`} />
                <StatCard icon={FiCalendar} label="Next 7 Days" value={stats?.upcoming_classes || 0} />
            </div>

            {/* Main Content Grid */}
            <div style={{ display: 'grid', gridTemplateColumns: isDesktop ? '2fr 1fr' : '1fr', gap: 24 }}>
                {/* Today's Learning Path */}
                <div style={{
                    background: '#fff', borderRadius: 16, border: '1px solid var(--t-border)',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.04)', overflow: 'hidden'
                }}>
                    <div style={{
                        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                        padding: '18px 22px', borderBottom: '1px solid var(--t-border)'
                    }}>
                        <h3 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: 'var(--t-text)' }}>Today's Learning Path</h3>
                        <Link to="/student/schedule" style={{
                            color: 'var(--t-primary)', fontSize: 13, fontWeight: 600,
                            textDecoration: 'none', display: 'flex', alignItems: 'center', gap: 4
                        }}>
                            View All <FiArrowRight />
                        </Link>
                    </div>

                    {todaySchedules.length > 0 ? todaySchedules.map((schedule, i) => {
                        const dateObj = new Date(schedule.scheduled_at)
                        const hour = dateObj.getHours()
                        let hourStr = hour % 12 || 12
                        hourStr = hourStr < 10 ? '0' + hourStr : hourStr
                        const ampm = hour >= 12 ? 'PM' : 'AM'
                        
                        const durationMs = (schedule.duration || 60) * 60 * 1000
                        const isLive = new Date() >= dateObj && new Date() <= new Date(dateObj.getTime() + durationMs)
                        const busy = isJoining(schedule.id)
                        
                        return (
                            <div key={i} style={{
                                display: 'flex', alignItems: 'center', gap: 16,
                                padding: '16px 22px', borderBottom: i < todaySchedules.length - 1 ? '1px solid var(--t-border)' : 'none',
                                background: isLive ? 'var(--t-primary-soft)' : 'transparent',
                                flexWrap: 'wrap'
                            }}>
                                <div style={{
                                    width: 56, height: 56, borderRadius: 14,
                                    background: isLive ? 'var(--t-primary)' : 'var(--t-bg)',
                                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                                    flexDirection: 'column', color: isLive ? '#fff' : 'var(--t-text-3)', flexShrink: 0
                                }}>
                                    <span style={{ fontSize: 16, fontWeight: 800, lineHeight: 1 }}>{hourStr}</span>
                                    <span style={{ fontSize: 9, fontWeight: 600, opacity: isLive ? 0.8 : 0.7 }}>{ampm}</span>
                                </div>
                                <div style={{ flex: 1, minWidth: 180 }}>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 4, flexWrap: 'wrap' }}>
                                        {isLive && (
                                            <span style={{
                                                padding: '2px 8px', borderRadius: 6, fontSize: 10, fontWeight: 700,
                                                background: 'var(--t-success-bg)', color: 'var(--t-success)', textTransform: 'uppercase', letterSpacing: '0.5px'
                                            }}>● Live Now</span>
                                        )}
                                        <span style={{ fontSize: 15, fontWeight: 700, color: 'var(--t-text)' }}>{schedule.subject || schedule.title}</span>
                                    </div>
                                    <span style={{ fontSize: 13, color: 'var(--t-text-3)', fontWeight: 500 }}>{schedule.title} {schedule.grade ? `• ${schedule.grade}` : ''}</span>
                                </div>
                                {isLive ? (
                                    <button style={{
                                        padding: '10px 20px', borderRadius: 12, border: 'none',
                                        background: 'var(--t-primary)',
                                        color: '#fff', fontSize: 13, fontWeight: 700, cursor: busy ? 'not-allowed' : 'pointer',
                                        display: 'flex', alignItems: 'center', gap: 6,
                                        boxShadow: '0 4px 12px rgba(79,11,217,0.3)',
                                        transition: 'all 0.2s ease', opacity: busy ? 0.7 : 1
                                    }}
                                    disabled={busy}
                                    onClick={() => joinClass(schedule)}>
                                        <FiPlay style={{ fill: 'currentColor' }} /> {busy ? 'Joining...' : 'Join Class'}
                                    </button>
                                ) : (
                                    <button style={{
                                        padding: '10px 20px', borderRadius: 12, border: '1px solid var(--t-border)',
                                        background: 'var(--t-bg)', color: 'var(--t-muted)', fontSize: 13, fontWeight: 600,
                                        cursor: 'default', display: 'flex', alignItems: 'center', gap: 6
                                    }}>
                                        <FiClock /> Upcoming
                                    </button>
                                )}
                            </div>
                        )
                    }) : (
                        <div style={{ padding: '40px 20px', textAlign: 'center', color: 'var(--t-muted)', fontSize: 14, fontWeight: 500 }}>
                            <div style={{ fontSize: 32, marginBottom: 8 }}>☕</div>
                            No classes scheduled for today.
                        </div>
                    )}
                </div>

                {/* Notice Board */}
                <div style={{
                    background: '#fff', borderRadius: 16, border: '1px solid var(--t-border)',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.04)', overflow: 'hidden',
                    display: 'flex', flexDirection: 'column'
                }}>
                    <div style={{
                        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                        padding: '18px 22px', borderBottom: '1px solid var(--t-border)'
                    }}>
                        <h3 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: 'var(--t-text)' }}>📢 Notice Board</h3>
                    </div>
                    <div style={{ padding: '12px 22px', flex: 1 }}>
                        {Array.isArray(stats?.recent_announcements) && stats.recent_announcements.length > 0 ? stats.recent_announcements.map((notice, i) => (
                            <div key={i} style={{ display: 'flex', gap: 12, padding: '12px 0', borderBottom: i < stats.recent_announcements.length - 1 ? '1px solid var(--t-bg)' : 'none' }}>
                                <div style={{
                                    width: 40, height: 40, borderRadius: 12, 
                                    background: notice.type === 'important' ? 'var(--t-danger-bg)' : 'var(--t-primary-soft)',
                                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                                    color: notice.type === 'important' ? 'var(--t-danger)' : 'var(--t-primary)', fontSize: 18, flexShrink: 0
                                }}>{notice.type === 'important' ? <FiAlertCircle /> : <FiBook />}</div>
                                <div>
                                    <div style={{ fontSize: 11, fontWeight: 600, color: 'var(--t-muted)', marginBottom: 3 }}>{notice.date}</div>
                                    <div style={{ fontSize: 14, fontWeight: 700, color: 'var(--t-text)', marginBottom: 3 }}>{notice.title}</div>
                                    <div style={{ fontSize: 13, color: 'var(--t-text-2)', lineHeight: 1.5 }}>{notice.message}</div>
                                </div>
                            </div>
                        )) : (
                            <div style={{ padding: '30px 20px', textAlign: 'center', color: 'var(--t-muted)', fontSize: 14, fontWeight: 500 }}>
                                No new notices
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Quick Access Cards - Unified Style */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(250px, 100%), 1fr))', gap: 16, marginTop: 24 }}>
                <Link to="/student/materials" style={{
                    textDecoration: 'none', padding: '22px 24px', borderRadius: 16,
                    background: '#fff', border: '1px solid var(--t-border)',
                    color: 'var(--t-text)', display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.04)',
                    transition: 'all 0.3s ease'
                }}>
                    <div>
                        <div style={{ fontSize: 17, fontWeight: 700, marginBottom: 4, color: 'var(--t-text)' }}>📚 Study Materials</div>
                        <div style={{ fontSize: 13, color: 'var(--t-text-3)', fontWeight: 500 }}>Download latest notes & PDFs</div>
                    </div>
                    <div style={{
                        width: 44, height: 44, borderRadius: 12, background: 'var(--t-primary-soft)',
                        color: 'var(--t-primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20
                    }}><FiArrowRight /></div>
                </Link>

                <Link to="/student/assignments" style={{
                    textDecoration: 'none', padding: '22px 24px', borderRadius: 16,
                    background: '#fff', border: '1px solid var(--t-border)',
                    color: 'var(--t-text)', display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.04)',
                    transition: 'all 0.3s ease'
                }}>
                    <div>
                        <div style={{ fontSize: 17, fontWeight: 700, marginBottom: 4, color: 'var(--t-text)' }}>📝 Assignments</div>
                        <div style={{ fontSize: 13, color: 'var(--t-text-3)', fontWeight: 500 }}>{stats?.pending_assignments || 0} tasks pending</div>
                    </div>
                    <div style={{
                        width: 44, height: 44, borderRadius: 12, background: 'var(--t-primary-soft)',
                        color: 'var(--t-primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20
                    }}><FiArrowRight /></div>
                </Link>
            </div>
        </div>
    )
}
