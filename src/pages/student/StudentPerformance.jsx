import React, { useState, useEffect } from 'react'
import { FiTrendingUp, FiAward, FiCheckCircle, FiClock, FiTarget } from 'react-icons/fi'
import { getStudentStats } from '../../services/dashboardService'
import { SelectedChildContext } from '../../context/SelectedChildContext'

function StatCard({ title, value, subtitle, icon: Icon, color, bg }) {
    return (
        <div style={{
            background: '#fff', borderRadius: 16, padding: 24, border: '1px solid #e2e8f0',
            boxShadow: '0 1px 3px rgba(0,0,0,0.02)', position: 'relative', overflow: 'hidden',
            transition: 'all 0.3s'
        }}
        onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.boxShadow = '0 8px 16px rgba(0,0,0,0.04)' }}
        onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 1px 3px rgba(0,0,0,0.02)' }}
        >
            <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
                <div style={{ width: 56, height: 56, borderRadius: 14, background: bg, color: color, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 24, zIndex: 1, position: 'relative' }}>
                    <Icon />
                </div>
                <div style={{ zIndex: 1, position: 'relative' }}>
                    <div style={{ fontSize: 32, fontWeight: 800, color: '#1e293b', lineHeight: 1 }}>{value}</div>
                    <div style={{ fontSize: 14, fontWeight: 600, color: '#64748b', marginTop: 4 }}>{title}</div>
                </div>
            </div>
            <div style={{ marginTop: 16, paddingTop: 16, borderTop: '1px solid #f1f5f9', fontSize: 13, color: '#94a3b8', fontWeight: 500, zIndex: 1, position: 'relative' }}>
                {subtitle}
            </div>
        </div>
    )
}

export default function StudentPerformance() {
    const { selectedChild } = React.useContext(SelectedChildContext)
    const [stats, setStats] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                let data = await getStudentStats().catch(() => ({}))
                setStats(data || {})
            } catch (e) {
                console.error(e)
            } finally {
                setLoading(false)
            }
        }
        if (selectedChild) {
            setLoading(true)
            load()
        }
    }, [selectedChild])

    if (loading || !selectedChild) return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 300 }}>
            <div style={{ width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#6366f1', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
        </div>
    )

    const completionRate = Math.min(100, Math.round(((stats?.completed_assignments || 0) / (stats?.assignments_total || 1)) * 100));

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24,
                '@media (minWidth: 640px)': { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }
            }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: '#1e293b', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>My Performance</h1>
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>Track your learning progress and achievements</p>
                </div>
            </div>

            {/* Overview Stats */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: 20, marginBottom: 24 }}>
                <StatCard title="Average Grade" value={stats?.average_grade || 'N/A'} subtitle="Overall performance" icon={FiAward} color="#8b5cf6" bg="#f5f3ff" />
                <StatCard title="Attendance" value={`${stats?.attendance_rate || 0}%`} subtitle="Class participation" icon={FiCheckCircle} color="#10b981" bg="#ecfdf5" />
                <StatCard title="Assignments" value={`${stats?.completed_assignments || 0}/${stats?.assignments_total || 0}`} subtitle={`${completionRate}% completion rate`} icon={FiTarget} color="#0ea5e9" bg="#e0f2fe" />
                <StatCard title="Study Hours" value={`${stats?.study_hours || 0}h`} subtitle="Time spent in classes" icon={FiClock} color="#f59e0b" bg="#fffbeb" />
            </div>

            {/* Detailed Progress */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: 20 }}>
                
                {/* Subject Performance */}
                <div style={{ background: '#fff', borderRadius: 16, padding: 24, border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.02)' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
                        <div>
                            <h3 style={{ margin: '0 0 4px 0', fontSize: 18, fontWeight: 700, color: '#1e293b', display: 'flex', alignItems: 'center', gap: 8 }}><FiTrendingUp style={{ color: '#6366f1' }} /> Subject Mastery</h3>
                            <p style={{ margin: 0, color: '#64748b', fontSize: 14 }}>Your grades per subject area</p>
                        </div>
                    </div>
                    
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
                        {stats?.subject_mastery?.length > 0 ? stats.subject_mastery.map((item, i) => (
                            <div key={i}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 14, fontWeight: 700, color: '#334155', marginBottom: 8 }}>
                                    <span>{item.label}</span>
                                    <span style={{ color: item.color }}>{item.val}%</span>
                                </div>
                                <div style={{ width: '100%', height: 10, background: '#f1f5f9', borderRadius: 6, overflow: 'hidden' }}>
                                    <div style={{ width: `${item.val}%`, height: '100%', background: item.color, borderRadius: 6 }} />
                                </div>
                            </div>
                        )) : (
                            <div style={{ color: '#94a3b8', fontSize: 14, textAlign: 'center', padding: '20px 0' }}>No subjects graded yet.</div>
                        )}
                    </div>
                </div>

                {/* Recent Achievements */}
                <div style={{ background: '#fff', borderRadius: 16, padding: 24, border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.02)' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
                        <div>
                            <h3 style={{ margin: '0 0 4px 0', fontSize: 18, fontWeight: 700, color: '#1e293b', display: 'flex', alignItems: 'center', gap: 8 }}><FiAward style={{ color: '#f59e0b' }} /> Recent Achievements</h3>
                            <p style={{ margin: 0, color: '#64748b', fontSize: 14 }}>Milestones you've reached</p>
                        </div>
                    </div>
                    
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                        {stats?.recent_achievements?.length > 0 ? stats.recent_achievements.map((item, i) => {
                            let ItemIcon = FiAward;
                            if (item.icon === 'FiCheckCircle') ItemIcon = FiCheckCircle;
                            if (item.icon === 'FiTrendingUp') ItemIcon = FiTrendingUp;

                            return (
                            <div key={i} style={{ display: 'flex', gap: 16, paddingBottom: 16, borderBottom: i < stats.recent_achievements.length - 1 ? '1px solid #f1f5f9' : 'none' }}>
                                <div style={{ width: 48, height: 48, borderRadius: 12, background: item.bg, color: item.color, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20, flexShrink: 0 }}>
                                    <ItemIcon />
                                </div>
                                <div>
                                    <div style={{ fontSize: 15, fontWeight: 700, color: '#1e293b', marginBottom: 2 }}>{item.title}</div>
                                    <div style={{ fontSize: 13, color: '#64748b', marginBottom: 4 }}>{item.desc}</div>
                                    <div style={{ fontSize: 11, fontWeight: 600, color: '#94a3b8' }}>{item.date}</div>
                                </div>
                            </div>
                        )}) : (
                            <div style={{ color: '#94a3b8', fontSize: 14, textAlign: 'center', padding: '20px 0' }}>Work hard to earn achievements!</div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    )
}
