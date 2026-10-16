import React, { useState, useEffect } from 'react'
import { FiTrendingUp, FiAward, FiCheckCircle, FiClock, FiTarget, FiAlertCircle } from 'react-icons/fi'
import { getStudentStats } from '../../services/dashboardService'

function StatCard({ title, value, subtitle, icon: Icon }) {
    return (
        <div className="t-card" style={{
            background: '#fff', borderRadius: 16, padding: 24, border: '1px solid var(--t-border)',
            boxShadow: 'var(--t-shadow-sm)', position: 'relative', overflow: 'hidden'
        }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
                <div style={{ width: 56, height: 56, borderRadius: 14, background: 'var(--t-primary-soft)', color: 'var(--t-primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 24, zIndex: 1, position: 'relative' }}>
                    <Icon />
                </div>
                <div style={{ zIndex: 1, position: 'relative' }}>
                    <div style={{ fontSize: 28, fontWeight: 800, color: 'var(--t-text)', lineHeight: 1 }}>{value}</div>
                    <div style={{ fontSize: 14, fontWeight: 600, color: 'var(--t-text-muted)', marginTop: 4 }}>{title}</div>
                </div>
            </div>
            <div style={{ marginTop: 16, paddingTop: 16, borderTop: '1px solid var(--t-border)', fontSize: 13, color: 'var(--t-text-muted)', fontWeight: 500, zIndex: 1, position: 'relative' }}>
                {subtitle}
            </div>
        </div>
    )
}

export default function StudentPerformance() {
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
        load()
    }, [])

    if (loading) return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 300 }}>
            <div style={{ width: 40, height: 40, border: '4px solid var(--t-border)', borderTopColor: 'var(--t-primary)', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
        </div>
    )

    const completionRate = Math.min(100, Math.round(((stats?.completed_assignments || 0) / (stats?.assignments_total || 1)) * 100))
    const attendanceDisplay = stats?.attendance_rate_has_data ? `${stats.attendance_rate}%` : 'No classes yet'
    const attendanceSubtitle = stats?.attendance_rate_has_data
        ? `${stats?.attended_classes || 0}/${stats?.held_classes || 0} classes attended (last 30 days)`
        : 'No held classes in the last 30 days'

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24
            }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: 'var(--t-text)', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>My Performance</h1>
                    <p style={{ color: 'var(--t-text-muted)', margin: 0, fontSize: 14 }}>Track your learning progress and achievements</p>
                </div>
            </div>

            {/* Overview Stats */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(240px, 100%), 1fr))', gap: 20, marginBottom: 24 }}>
                <StatCard title="Average Grade" value={stats?.average_grade || 'N/A'} subtitle="Overall performance" icon={FiAward} />
                <StatCard title="Attendance" value={attendanceDisplay} subtitle={attendanceSubtitle} icon={FiCheckCircle} />
                <StatCard title="Assignments" value={`${stats?.completed_assignments || 0}/${stats?.assignments_total || 0}`} subtitle={`${completionRate}% completion rate`} icon={FiTarget} />
                <StatCard title="Study Hours" value={`${stats?.study_hours || 0}h`} subtitle="Class time joined" icon={FiClock} />
            </div>

            {/* Detailed Progress */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(300px, 100%), 1fr))', gap: 20 }}>
                
                {/* Subject Performance */}
                <div className="t-card" style={{ background: '#fff', borderRadius: 16, padding: 24, border: '1px solid var(--t-border)', boxShadow: 'var(--t-shadow-sm)' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
                        <div>
                            <h3 style={{ margin: '0 0 4px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)', display: 'flex', alignItems: 'center', gap: 8 }}>
                                <FiTrendingUp style={{ color: 'var(--t-primary)' }} /> Subject Mastery
                            </h3>
                            <p style={{ margin: 0, color: 'var(--t-text-muted)', fontSize: 14 }}>Your grades per subject area</p>
                        </div>
                    </div>
                    
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
                        {Array.isArray(stats?.subject_mastery) && stats.subject_mastery.length > 0 ? stats.subject_mastery.map((item, i) => (
                            <div key={i}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 14, fontWeight: 700, color: 'var(--t-text)', marginBottom: 8 }}>
                                    <span>{item.label}</span>
                                    <span style={{ color: 'var(--t-primary)' }}>{item.val}%</span>
                                </div>
                                <div style={{ width: '100%', height: 10, background: 'var(--t-bg)', borderRadius: 6, overflow: 'hidden' }}>
                                    <div style={{ width: `${item.val}%`, height: '100%', background: 'var(--t-primary)', borderRadius: 6 }} />
                                </div>
                            </div>
                        )) : (
                            <div style={{ color: 'var(--t-text-muted)', fontSize: 14, textAlign: 'center', padding: '20px 0' }}>No subjects graded yet.</div>
                        )}
                    </div>
                </div>

                {/* Recent Achievements */}
                <div className="t-card" style={{ background: '#fff', borderRadius: 16, padding: 24, border: '1px solid var(--t-border)', boxShadow: 'var(--t-shadow-sm)' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
                        <div>
                            <h3 style={{ margin: '0 0 4px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)', display: 'flex', alignItems: 'center', gap: 8 }}>
                                <FiAward style={{ color: 'var(--t-primary)' }} /> Recent Achievements
                            </h3>
                            <p style={{ margin: 0, color: 'var(--t-text-muted)', fontSize: 14 }}>Milestones you've reached</p>
                        </div>
                    </div>
                    
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                        {Array.isArray(stats?.recent_achievements) && stats.recent_achievements.length > 0 ? stats.recent_achievements.map((item, i) => {
                            let ItemIcon = FiAward
                            if (item.icon === 'FiCheckCircle') ItemIcon = FiCheckCircle
                            if (item.icon === 'FiTrendingUp') ItemIcon = FiTrendingUp

                            return (
                                <div key={i} style={{ display: 'flex', gap: 16, paddingBottom: 16, borderBottom: i < stats.recent_achievements.length - 1 ? '1px solid var(--t-border)' : 'none' }}>
                                    <div style={{ width: 48, height: 48, borderRadius: 12, background: 'var(--t-primary-soft)', color: 'var(--t-primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20, flexShrink: 0 }}>
                                        <ItemIcon />
                                    </div>
                                    <div>
                                        <div style={{ fontSize: 15, fontWeight: 700, color: 'var(--t-text)', marginBottom: 2 }}>{item.title}</div>
                                        <div style={{ fontSize: 13, color: 'var(--t-text-muted)', marginBottom: 4 }}>{item.desc}</div>
                                        <div style={{ fontSize: 11, fontWeight: 600, color: 'var(--t-text-muted)' }}>{item.date}</div>
                                    </div>
                                </div>
                            )
                        }) : (
                            <div style={{ color: 'var(--t-text-muted)', fontSize: 14, textAlign: 'center', padding: '20px 0' }}>Work hard to earn achievements!</div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    )
}
