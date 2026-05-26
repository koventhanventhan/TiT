import React, { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import {
    FiVideo, FiCalendar, FiCheckCircle, FiFileText,
    FiArrowRight, FiBook, FiPlay, FiAlertCircle, FiClock,
    FiTrendingUp
} from 'react-icons/fi'
import { getStudentStats } from '../../services/dashboardService'
import StudentPaymentModule from '../../components/student/StudentPaymentModule'

// Reusable Stat Card
function StatCard({ icon: Icon, label, value, color, bgColor, iconBg }) {
    return (
        <div style={{
            background: '#fff', borderRadius: 16, padding: '22px 20px',
            border: '1px solid #f1f5f9',
            boxShadow: '0 1px 3px rgba(0,0,0,0.04)',
            display: 'flex', alignItems: 'center', gap: 16,
            transition: 'all 0.3s ease', cursor: 'default',
            position: 'relative', overflow: 'hidden'
        }}
        onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.boxShadow = '0 8px 24px rgba(0,0,0,0.08)' }}
        onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 1px 3px rgba(0,0,0,0.04)' }}
        >
            <div style={{
                width: 52, height: 52, borderRadius: 14,
                background: iconBg, display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: 22, color: color, flexShrink: 0
            }}>
                <Icon />
            </div>
            <div>
                <div style={{ fontSize: 13, fontWeight: 500, color: '#94a3b8', marginBottom: 2 }}>{label}</div>
                <div style={{ fontSize: 28, fontWeight: 800, color: '#1e293b', letterSpacing: '-1px', lineHeight: 1 }}>{value}</div>
            </div>
            {/* Decorative circle */}
            <div style={{
                position: 'absolute', right: -20, top: -20, width: 80, height: 80,
                borderRadius: '50%', background: bgColor, opacity: 0.5
            }} />
        </div>
    )
}

export default function StudentOverview() {
    const [stats, setStats] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function loadStats() {
            try {
                const data = await getStudentStats().catch(() => null)
                setStats(data || {
                    user_name: 'Student',
                    today_classes: 2,
                    pending_assignments: 3,
                    attendance_rate: 85,
                    upcoming_classes: 5
                })
            } finally {
                setLoading(false)
            }
        }
        loadStats()
    }, [])

    if (loading) return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 300 }}>
            <div style={{
                width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#6366f1',
                borderRadius: '50%', animation: 'spin 0.8s linear infinite'
            }} />
            <style>{`@keyframes spin { to { transform: rotate(360deg) } }`}</style>
        </div>
    )

    return (
        <div>
            {/* Welcome Banner */}
            <div style={{
                background: 'linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%)',
                borderRadius: 20, padding: '32px 28px', marginBottom: 24,
                position: 'relative', overflow: 'hidden', color: '#fff'
            }}>
                {/* Decorative shapes */}
                <div style={{
                    position: 'absolute', right: 40, top: -30, width: 160, height: 160,
                    borderRadius: '50%', background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.08)'
                }} />
                <div style={{
                    position: 'absolute', right: 120, bottom: -40, width: 100, height: 100,
                    borderRadius: '50%', background: 'rgba(255,255,255,0.03)', border: '1px solid rgba(255,255,255,0.06)'
                }} />

                <div style={{ position: 'relative', zIndex: 1 }}>
                    <div style={{ fontSize: 14, fontWeight: 500, color: '#a5b4fc', marginBottom: 6, display: 'flex', alignItems: 'center', gap: 6 }}>
                        <FiTrendingUp /> Good Morning
                    </div>
                    <h1 style={{
                        fontSize: 28, fontWeight: 800, margin: 0, letterSpacing: '-0.5px',
                        lineHeight: 1.2, marginBottom: 6
                    }}>
                        Welcome Back, {stats?.user_name || 'Student'}! 👋
                    </h1>
                    <p style={{ fontSize: 15, color: '#c7d2fe', margin: 0, maxWidth: 500 }}>
                        Here's what's happening with your learning today. Stay consistent and keep growing!
                    </p>
                </div>
            </div>

            {/* Payment Module */}
            <StudentPaymentModule />

            {/* Stats Grid */}
            <div style={{
                display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
                gap: 16, marginBottom: 24
            }}>
                <StatCard icon={FiVideo} label="Today's Classes" value={stats?.today_classes || 0} color="#6366f1" iconBg="#eef2ff" bgColor="#eef2ff" />
                <StatCard icon={FiFileText} label="Pending Tasks" value={stats?.pending_assignments || 0} color="#f97316" iconBg="#fff7ed" bgColor="#fff7ed" />
                <StatCard icon={FiCheckCircle} label="Attendance" value={`${stats?.attendance_rate || 0}%`} color="#10b981" iconBg="#ecfdf5" bgColor="#ecfdf5" />
                <StatCard icon={FiCalendar} label="Next 7 Days" value={stats?.upcoming_classes || 0} color="#8b5cf6" iconBg="#f5f3ff" bgColor="#f5f3ff" />
            </div>

            {/* Main Content Grid */}
            <div style={{ display: 'grid', gridTemplateColumns: window.innerWidth >= 1024 ? '2fr 1fr' : '1fr', gap: 24 }}>
                {/* Today's Learning Path */}
                <div style={{
                    background: '#fff', borderRadius: 16, border: '1px solid #f1f5f9',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.04)', overflow: 'hidden'
                }}>
                    <div style={{
                        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                        padding: '18px 22px', borderBottom: '1px solid #f1f5f9'
                    }}>
                        <h3 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: '#1e293b' }}>Today's Learning Path</h3>
                        <Link to="/student/schedule" style={{
                            color: '#6366f1', fontSize: 13, fontWeight: 600,
                            textDecoration: 'none', display: 'flex', alignItems: 'center', gap: 4
                        }}>
                            View All <FiArrowRight />
                        </Link>
                    </div>

                    {/* Active Class */}
                    <div style={{
                        display: 'flex', alignItems: 'center', gap: 16,
                        padding: '16px 22px', borderBottom: '1px solid #f1f5f9',
                        background: 'linear-gradient(90deg, rgba(99,102,241,0.04) 0%, transparent 100%)',
                        flexWrap: 'wrap'
                    }}>
                        <div style={{
                            width: 56, height: 56, borderRadius: 14,
                            background: 'linear-gradient(135deg, #6366f1, #8b5cf6)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            flexDirection: 'column', color: '#fff', flexShrink: 0
                        }}>
                            <span style={{ fontSize: 16, fontWeight: 800, lineHeight: 1 }}>09</span>
                            <span style={{ fontSize: 9, fontWeight: 600, opacity: 0.8 }}>AM</span>
                        </div>
                        <div style={{ flex: 1, minWidth: 180 }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 4, flexWrap: 'wrap' }}>
                                <span style={{
                                    padding: '2px 8px', borderRadius: 6, fontSize: 10, fontWeight: 700,
                                    background: '#dcfce7', color: '#16a34a', textTransform: 'uppercase', letterSpacing: '0.5px'
                                }}>● Live Now</span>
                                <span style={{ fontSize: 15, fontWeight: 700, color: '#1e293b' }}>Mathematics - Calculus Intro</span>
                            </div>
                            <span style={{ fontSize: 13, color: '#94a3b8', fontWeight: 500 }}>Prof. Kumara • A/L 2026</span>
                        </div>
                        <button style={{
                            padding: '10px 20px', borderRadius: 12, border: 'none',
                            background: 'linear-gradient(135deg, #6366f1, #8b5cf6)',
                            color: '#fff', fontSize: 13, fontWeight: 700, cursor: 'pointer',
                            display: 'flex', alignItems: 'center', gap: 6,
                            boxShadow: '0 4px 12px rgba(99,102,241,0.35)',
                            transition: 'all 0.2s ease'
                        }}
                        onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-1px)'; e.currentTarget.style.boxShadow = '0 6px 20px rgba(99,102,241,0.45)' }}
                        onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 4px 12px rgba(99,102,241,0.35)' }}
                        >
                            <FiPlay style={{ fill: 'currentColor' }} /> Join Class
                        </button>
                    </div>

                    {/* Upcoming Class */}
                    <div style={{
                        display: 'flex', alignItems: 'center', gap: 16,
                        padding: '16px 22px', flexWrap: 'wrap'
                    }}>
                        <div style={{
                            width: 56, height: 56, borderRadius: 14,
                            background: '#f1f5f9',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            flexDirection: 'column', color: '#64748b', flexShrink: 0
                        }}>
                            <span style={{ fontSize: 16, fontWeight: 800, lineHeight: 1 }}>11</span>
                            <span style={{ fontSize: 9, fontWeight: 600, opacity: 0.7 }}>AM</span>
                        </div>
                        <div style={{ flex: 1, minWidth: 180 }}>
                            <div style={{ fontSize: 15, fontWeight: 700, color: '#1e293b', marginBottom: 4 }}>Physics - Thermodynamics</div>
                            <span style={{ fontSize: 13, color: '#94a3b8', fontWeight: 500 }}>Prof. Silva • A/L 2026</span>
                        </div>
                        <button style={{
                            padding: '10px 20px', borderRadius: 12, border: '1px solid #e2e8f0',
                            background: '#f8fafc', color: '#94a3b8', fontSize: 13, fontWeight: 600,
                            cursor: 'default', display: 'flex', alignItems: 'center', gap: 6
                        }}>
                            <FiClock /> Upcoming
                        </button>
                    </div>
                </div>

                {/* Notice Board */}
                <div style={{
                    background: '#fff', borderRadius: 16, border: '1px solid #f1f5f9',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.04)', overflow: 'hidden',
                    display: 'flex', flexDirection: 'column'
                }}>
                    <div style={{
                        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                        padding: '18px 22px', borderBottom: '1px solid #f1f5f9'
                    }}>
                        <h3 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: '#1e293b' }}>📢 Notice Board</h3>
                    </div>
                    <div style={{ padding: '12px 22px', flex: 1 }}>
                        {/* Notice 1 */}
                        <div style={{ display: 'flex', gap: 12, padding: '12px 0', borderBottom: '1px solid #f8fafc' }}>
                            <div style={{
                                width: 40, height: 40, borderRadius: 12, background: '#fef2f2',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                color: '#ef4444', fontSize: 18, flexShrink: 0
                            }}><FiAlertCircle /></div>
                            <div>
                                <div style={{ fontSize: 11, fontWeight: 600, color: '#94a3b8', marginBottom: 3 }}>Today, 08:30 AM</div>
                                <div style={{ fontSize: 14, fontWeight: 700, color: '#1e293b', marginBottom: 3 }}>Physics Class Rescheduled</div>
                                <div style={{ fontSize: 13, color: '#64748b', lineHeight: 1.5 }}>The evening physics class has been moved to tomorrow 8 AM.</div>
                            </div>
                        </div>
                        {/* Notice 2 */}
                        <div style={{ display: 'flex', gap: 12, padding: '12px 0' }}>
                            <div style={{
                                width: 40, height: 40, borderRadius: 12, background: '#eef2ff',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                color: '#6366f1', fontSize: 18, flexShrink: 0
                            }}><FiBook /></div>
                            <div>
                                <div style={{ fontSize: 11, fontWeight: 600, color: '#94a3b8', marginBottom: 3 }}>Yesterday</div>
                                <div style={{ fontSize: 14, fontWeight: 700, color: '#1e293b', marginBottom: 3 }}>New Notes Uploaded</div>
                                <div style={{ fontSize: 13, color: '#64748b', lineHeight: 1.5 }}>Chemistry organic chapters 1-3 PDF notes are available.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Quick Access Cards */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: 16, marginTop: 24 }}>
                <Link to="/student/materials" style={{
                    textDecoration: 'none', padding: '22px 24px', borderRadius: 16,
                    background: 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)',
                    color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    boxShadow: '0 8px 24px rgba(79,70,229,0.25)',
                    transition: 'all 0.3s ease', position: 'relative', overflow: 'hidden'
                }}
                onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-3px)'; e.currentTarget.style.boxShadow = '0 12px 32px rgba(79,70,229,0.35)' }}
                onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 8px 24px rgba(79,70,229,0.25)' }}
                >
                    <div style={{ position: 'relative', zIndex: 1 }}>
                        <div style={{ fontSize: 17, fontWeight: 700, marginBottom: 4 }}>📚 Study Materials</div>
                        <div style={{ fontSize: 13, opacity: 0.85, fontWeight: 500 }}>Download latest notes & PDFs</div>
                    </div>
                    <div style={{
                        width: 48, height: 48, borderRadius: '50%', background: 'rgba(255,255,255,0.15)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 22,
                        backdropFilter: 'blur(4px)', position: 'relative', zIndex: 1
                    }}><FiArrowRight /></div>
                    <div style={{
                        position: 'absolute', right: -30, bottom: -30, width: 120, height: 120,
                        borderRadius: '50%', background: 'rgba(255,255,255,0.08)'
                    }} />
                </Link>

                <Link to="/student/assignments" style={{
                    textDecoration: 'none', padding: '22px 24px', borderRadius: 16,
                    background: 'linear-gradient(135deg, #0891b2 0%, #0ea5e9 100%)',
                    color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    boxShadow: '0 8px 24px rgba(8,145,178,0.25)',
                    transition: 'all 0.3s ease', position: 'relative', overflow: 'hidden'
                }}
                onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-3px)'; e.currentTarget.style.boxShadow = '0 12px 32px rgba(8,145,178,0.35)' }}
                onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 8px 24px rgba(8,145,178,0.25)' }}
                >
                    <div style={{ position: 'relative', zIndex: 1 }}>
                        <div style={{ fontSize: 17, fontWeight: 700, marginBottom: 4 }}>📝 Assignments</div>
                        <div style={{ fontSize: 13, opacity: 0.85, fontWeight: 500 }}>{stats?.pending_assignments || 0} tasks pending</div>
                    </div>
                    <div style={{
                        width: 48, height: 48, borderRadius: '50%', background: 'rgba(255,255,255,0.15)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 22,
                        backdropFilter: 'blur(4px)', position: 'relative', zIndex: 1
                    }}><FiArrowRight /></div>
                    <div style={{
                        position: 'absolute', right: -30, bottom: -30, width: 120, height: 120,
                        borderRadius: '50%', background: 'rgba(255,255,255,0.08)'
                    }} />
                </Link>
            </div>
        </div>
    )
}
