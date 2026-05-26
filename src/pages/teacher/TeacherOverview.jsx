import React, { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import {
    FiVideo, FiUsers, FiFileText, FiCalendar,
    FiArrowRight, FiCheck, FiClock, FiMoreVertical,
    FiTrendingUp, FiPlay, FiPlus
} from 'react-icons/fi'
import { getTeacherDashboardStats } from '../../services/dashboardService'

function StatCard({ icon: Icon, label, value, color, iconBg }) {
    return (
        <div style={{
            background: '#fff', borderRadius: 16, padding: '22px 20px',
            border: '1px solid #f1f5f9', boxShadow: '0 1px 3px rgba(0,0,0,0.04)',
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
            }}><Icon /></div>
            <div>
                <div style={{ fontSize: 13, fontWeight: 500, color: '#94a3b8', marginBottom: 2 }}>{label}</div>
                <div style={{ fontSize: 28, fontWeight: 800, color: '#1e293b', letterSpacing: '-1px', lineHeight: 1 }}>{value}</div>
            </div>
            <div style={{
                position: 'absolute', right: -20, top: -20, width: 80, height: 80,
                borderRadius: '50%', background: iconBg, opacity: 0.4
            }} />
        </div>
    )
}

export default function TeacherOverview() {
    const [stats, setStats] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function loadStats() {
            try {
                const data = await getTeacherDashboardStats().catch(() => null)
                setStats(data || {
                    user_name: 'Teacher',
                    today_classes: 3,
                    total_students: 145,
                    new_submissions: 12,
                    upcoming_classes: 8
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
                width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#0ea5e9',
                borderRadius: '50%', animation: 'spin 0.8s linear infinite'
            }} />
            <style>{`@keyframes spin { to { transform: rotate(360deg) } }`}</style>
        </div>
    )

    return (
        <div>
            {/* Welcome Banner */}
            <div style={{
                background: 'linear-gradient(135deg, #0c4a6e 0%, #0369a1 40%, #0284c7 100%)',
                borderRadius: 20, padding: '32px 28px', marginBottom: 24,
                position: 'relative', overflow: 'hidden', color: '#fff'
            }}>
                <div style={{
                    position: 'absolute', right: 40, top: -30, width: 160, height: 160,
                    borderRadius: '50%', background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.08)'
                }} />
                <div style={{
                    position: 'absolute', right: 120, bottom: -40, width: 100, height: 100,
                    borderRadius: '50%', background: 'rgba(255,255,255,0.03)'
                }} />

                <div style={{ position: 'relative', zIndex: 1, display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 16 }}>
                    <div>
                        <div style={{ fontSize: 14, fontWeight: 500, color: '#7dd3fc', marginBottom: 6, display: 'flex', alignItems: 'center', gap: 6 }}>
                            <FiTrendingUp /> Good Morning
                        </div>
                        <h1 style={{ fontSize: 28, fontWeight: 800, margin: 0, letterSpacing: '-0.5px', lineHeight: 1.2, marginBottom: 6 }}>
                            Welcome Back, Prof. {stats?.user_name}! 👋
                        </h1>
                        <p style={{ fontSize: 15, color: '#bae6fd', margin: 0, maxWidth: 500 }}>
                            You have {stats?.today_classes || 0} classes today. Let's make it a great day!
                        </p>
                    </div>
                    <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
                        <button style={{
                            padding: '10px 20px', borderRadius: 12, border: '1px solid rgba(255,255,255,0.2)',
                            background: 'rgba(255,255,255,0.1)', color: '#fff', fontSize: 13,
                            fontWeight: 600, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6,
                            backdropFilter: 'blur(4px)', transition: 'all 0.2s'
                        }}
                        onMouseEnter={e => e.currentTarget.style.background = 'rgba(255,255,255,0.2)'}
                        onMouseLeave={e => e.currentTarget.style.background = 'rgba(255,255,255,0.1)'}
                        >
                            <FiCalendar /> Schedule
                        </button>
                        <button style={{
                            padding: '10px 20px', borderRadius: 12, border: 'none',
                            background: '#fff', color: '#0369a1', fontSize: 13,
                            fontWeight: 700, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6,
                            boxShadow: '0 4px 12px rgba(0,0,0,0.15)', transition: 'all 0.2s'
                        }}
                        onMouseEnter={e => e.currentTarget.style.transform = 'translateY(-1px)'}
                        onMouseLeave={e => e.currentTarget.style.transform = 'translateY(0)'}
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
                <StatCard icon={FiVideo} label="Today's Classes" value={stats?.today_classes || 0} color="#0ea5e9" iconBg="#e0f2fe" />
                <StatCard icon={FiUsers} label="Total Students" value={stats?.total_students || 0} color="#8b5cf6" iconBg="#f5f3ff" />
                <StatCard icon={FiFileText} label="New Submissions" value={stats?.new_submissions || 0} color="#f43f5e" iconBg="#fff1f2" />
                <StatCard icon={FiCalendar} label="This Week" value={stats?.upcoming_classes || 0} color="#f59e0b" iconBg="#fffbeb" />
            </div>

            {/* Main Content Grid */}
            <div style={{ display: 'grid', gridTemplateColumns: window.innerWidth >= 1024 ? '2fr 1fr' : '1fr', gap: 24 }}>
                {/* Schedule */}
                <div style={{
                    background: '#fff', borderRadius: 16, border: '1px solid #f1f5f9',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.04)', overflow: 'hidden'
                }}>
                    <div style={{
                        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                        padding: '18px 22px', borderBottom: '1px solid #f1f5f9'
                    }}>
                        <h3 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: '#1e293b' }}>Your Schedule Today</h3>
                        <Link to="/teacher/schedule" style={{
                            color: '#0ea5e9', fontSize: 13, fontWeight: 600,
                            textDecoration: 'none', display: 'flex', alignItems: 'center', gap: 4
                        }}>View All <FiArrowRight /></Link>
                    </div>

                    {/* Class 1 */}
                    <div style={{
                        display: 'flex', alignItems: 'center', gap: 16, padding: '16px 22px',
                        borderBottom: '1px solid #f8fafc',
                        background: 'linear-gradient(90deg, rgba(14,165,233,0.04) 0%, transparent 100%)',
                        flexWrap: 'wrap'
                    }}>
                        <div style={{
                            width: 56, height: 56, borderRadius: 14,
                            background: 'linear-gradient(135deg, #0ea5e9, #06b6d4)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            flexDirection: 'column', color: '#fff', flexShrink: 0
                        }}>
                            <span style={{ fontSize: 16, fontWeight: 800, lineHeight: 1 }}>09</span>
                            <span style={{ fontSize: 9, fontWeight: 600, opacity: 0.8 }}>AM</span>
                        </div>
                        <div style={{ flex: 1, minWidth: 180 }}>
                            <div style={{ fontSize: 15, fontWeight: 700, color: '#1e293b', marginBottom: 4 }}>Advanced Calculus - Grade 12</div>
                            <div style={{ fontSize: 13, color: '#94a3b8', fontWeight: 500, display: 'flex', alignItems: 'center', gap: 6 }}>
                                <FiUsers style={{ color: '#cbd5e1' }} /> 45 Students expected
                            </div>
                        </div>
                        <button style={{
                            padding: '10px 20px', borderRadius: 12, border: 'none',
                            background: 'linear-gradient(135deg, #0ea5e9, #06b6d4)',
                            color: '#fff', fontSize: 13, fontWeight: 700, cursor: 'pointer',
                            display: 'flex', alignItems: 'center', gap: 6,
                            boxShadow: '0 4px 12px rgba(14,165,233,0.35)', transition: 'all 0.2s'
                        }}
                        onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-1px)'; e.currentTarget.style.boxShadow = '0 6px 20px rgba(14,165,233,0.45)' }}
                        onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 4px 12px rgba(14,165,233,0.35)' }}
                        >
                            <FiPlay style={{ fill: 'currentColor' }} /> Host
                        </button>
                    </div>

                    {/* Class 2 */}
                    <div style={{
                        display: 'flex', alignItems: 'center', gap: 16,
                        padding: '16px 22px', flexWrap: 'wrap'
                    }}>
                        <div style={{
                            width: 56, height: 56, borderRadius: 14, background: '#f1f5f9',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            flexDirection: 'column', color: '#64748b', flexShrink: 0
                        }}>
                            <span style={{ fontSize: 16, fontWeight: 800, lineHeight: 1 }}>14</span>
                            <span style={{ fontSize: 9, fontWeight: 600, opacity: 0.7 }}>PM</span>
                        </div>
                        <div style={{ flex: 1, minWidth: 180 }}>
                            <div style={{ fontSize: 15, fontWeight: 700, color: '#1e293b', marginBottom: 4 }}>Linear Algebra Revision</div>
                            <div style={{ fontSize: 13, color: '#94a3b8', fontWeight: 500, display: 'flex', alignItems: 'center', gap: 6 }}>
                                <FiUsers style={{ color: '#cbd5e1' }} /> 30 Students expected
                            </div>
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

                {/* Recent Submissions */}
                <div style={{
                    background: '#fff', borderRadius: 16, border: '1px solid #f1f5f9',
                    boxShadow: '0 1px 3px rgba(0,0,0,0.04)', overflow: 'hidden',
                    display: 'flex', flexDirection: 'column'
                }}>
                    <div style={{
                        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                        padding: '18px 22px', borderBottom: '1px solid #f1f5f9'
                    }}>
                        <h3 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: '#1e293b' }}>📋 Recent Submissions</h3>
                    </div>

                    {/* Submission 1 */}
                    <div style={{
                        display: 'flex', alignItems: 'flex-start', gap: 12, padding: '14px 22px',
                        borderBottom: '1px solid #f8fafc', cursor: 'pointer', transition: 'background 0.2s'
                    }}
                    onMouseEnter={e => e.currentTarget.style.background = '#fafafa'}
                    onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                    >
                        <div style={{
                            width: 40, height: 40, borderRadius: '50%',
                            background: 'linear-gradient(135deg, #a855f7, #ec4899)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            color: '#fff', fontWeight: 700, fontSize: 14, flexShrink: 0
                        }}>K</div>
                        <div style={{ flex: 1, minWidth: 0 }}>
                            <div style={{ fontSize: 14, fontWeight: 600, color: '#1e293b', marginBottom: 2 }}>Kamal Perera</div>
                            <div style={{ fontSize: 12, color: '#94a3b8', marginBottom: 6 }}>Calculus Quiz 1</div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                <span style={{
                                    fontSize: 10, fontWeight: 700, color: '#f59e0b', background: '#fffbeb',
                                    padding: '2px 8px', borderRadius: 6, textTransform: 'uppercase'
                                }}>Needs Grading</span>
                                <span style={{ fontSize: 11, color: '#cbd5e1', display: 'flex', alignItems: 'center', gap: 3 }}>
                                    <FiClock /> 2m ago
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Submission 2 */}
                    <div style={{
                        display: 'flex', alignItems: 'flex-start', gap: 12, padding: '14px 22px',
                        borderBottom: '1px solid #f8fafc', cursor: 'pointer', transition: 'background 0.2s'
                    }}
                    onMouseEnter={e => e.currentTarget.style.background = '#fafafa'}
                    onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                    >
                        <div style={{
                            width: 40, height: 40, borderRadius: '50%',
                            background: 'linear-gradient(135deg, #0ea5e9, #6366f1)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            color: '#fff', fontWeight: 700, fontSize: 14, flexShrink: 0
                        }}>S</div>
                        <div style={{ flex: 1, minWidth: 0 }}>
                            <div style={{ fontSize: 14, fontWeight: 600, color: '#1e293b', marginBottom: 2 }}>Saman Fernando</div>
                            <div style={{ fontSize: 12, color: '#94a3b8', marginBottom: 6 }}>Calculus Quiz 1</div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                <span style={{
                                    fontSize: 10, fontWeight: 700, color: '#10b981', background: '#ecfdf5',
                                    padding: '2px 8px', borderRadius: 6, textTransform: 'uppercase'
                                }}>Graded ✓</span>
                                <span style={{ fontSize: 11, color: '#cbd5e1', display: 'flex', alignItems: 'center', gap: 3 }}>
                                    <FiCheck /> 1h ago
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div style={{ padding: '14px 22px', textAlign: 'center', borderTop: '1px solid #f1f5f9' }}>
                        <Link to="/teacher/assignments" style={{
                            color: '#0ea5e9', fontSize: 13, fontWeight: 600, textDecoration: 'none'
                        }}>View all submissions →</Link>
                    </div>
                </div>
            </div>

            {/* Quick Actions */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 16, marginTop: 24 }}>
                <Link to="/teacher/assignments" style={{
                    textDecoration: 'none', padding: '22px 24px', borderRadius: 16,
                    background: 'linear-gradient(135deg, #0c4a6e 0%, #0284c7 100%)',
                    color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    boxShadow: '0 8px 24px rgba(2,132,199,0.25)', transition: 'all 0.3s', overflow: 'hidden', position: 'relative'
                }}
                onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-3px)'; e.currentTarget.style.boxShadow = '0 12px 32px rgba(2,132,199,0.35)' }}
                onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 8px 24px rgba(2,132,199,0.25)' }}
                >
                    <div style={{ position: 'relative', zIndex: 1 }}>
                        <div style={{ fontSize: 17, fontWeight: 700, marginBottom: 4 }}>📝 Create Assignment</div>
                        <div style={{ fontSize: 13, opacity: 0.85, fontWeight: 500 }}>Post new tasks for students</div>
                    </div>
                    <div style={{
                        width: 48, height: 48, borderRadius: '50%', background: 'rgba(255,255,255,0.15)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 22,
                        position: 'relative', zIndex: 1
                    }}><FiPlus /></div>
                    <div style={{ position: 'absolute', right: -30, bottom: -30, width: 120, height: 120, borderRadius: '50%', background: 'rgba(255,255,255,0.08)' }} />
                </Link>

                <Link to="/teacher/materials" style={{
                    textDecoration: 'none', padding: '22px 24px', borderRadius: 16,
                    background: 'linear-gradient(135deg, #7c3aed 0%, #a855f7 100%)',
                    color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    boxShadow: '0 8px 24px rgba(124,58,237,0.25)', transition: 'all 0.3s', overflow: 'hidden', position: 'relative'
                }}
                onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-3px)'; e.currentTarget.style.boxShadow = '0 12px 32px rgba(124,58,237,0.35)' }}
                onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 8px 24px rgba(124,58,237,0.25)' }}
                >
                    <div style={{ position: 'relative', zIndex: 1 }}>
                        <div style={{ fontSize: 17, fontWeight: 700, marginBottom: 4 }}>📚 Upload Materials</div>
                        <div style={{ fontSize: 13, opacity: 0.85, fontWeight: 500 }}>Share notes & resources</div>
                    </div>
                    <div style={{
                        width: 48, height: 48, borderRadius: '50%', background: 'rgba(255,255,255,0.15)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 22,
                        position: 'relative', zIndex: 1
                    }}><FiArrowRight /></div>
                    <div style={{ position: 'absolute', right: -30, bottom: -30, width: 120, height: 120, borderRadius: '50%', background: 'rgba(255,255,255,0.08)' }} />
                </Link>

                <Link to="/teacher/reports" style={{
                    textDecoration: 'none', padding: '22px 24px', borderRadius: 16,
                    background: 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
                    color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    boxShadow: '0 8px 24px rgba(5,150,105,0.25)', transition: 'all 0.3s', overflow: 'hidden', position: 'relative'
                }}
                onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-3px)'; e.currentTarget.style.boxShadow = '0 12px 32px rgba(5,150,105,0.35)' }}
                onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 8px 24px rgba(5,150,105,0.25)' }}
                >
                    <div style={{ position: 'relative', zIndex: 1 }}>
                        <div style={{ fontSize: 17, fontWeight: 700, marginBottom: 4 }}>📊 View Reports</div>
                        <div style={{ fontSize: 13, opacity: 0.85, fontWeight: 500 }}>Attendance & progress</div>
                    </div>
                    <div style={{
                        width: 48, height: 48, borderRadius: '50%', background: 'rgba(255,255,255,0.15)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 22,
                        position: 'relative', zIndex: 1
                    }}><FiArrowRight /></div>
                    <div style={{ position: 'absolute', right: -30, bottom: -30, width: 120, height: 120, borderRadius: '50%', background: 'rgba(255,255,255,0.08)' }} />
                </Link>
            </div>
        </div>
    )
}
