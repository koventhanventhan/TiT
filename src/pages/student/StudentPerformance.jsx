import React, { useState, useEffect } from 'react'
import { FiCheckCircle, FiTrendingUp, FiAward, FiBarChart2, FiFileText, FiVideo } from 'react-icons/fi'
import { getStudentStats } from '../../services/dashboardService'
import './StudentSections.css'

export default function StudentPerformance() {
    const [stats, setStats] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                const data = await getStudentStats()
                setStats(data)
            } catch (e) {
                console.error('Error loading performance data:', e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    if (loading) return <div className="student-section"><div className="loading-shimmer">Loading performance...</div></div>

    return (
        <div className="student-section">
            <div className="section-top">
                <h2>My Performance</h2>
            </div>

            <div className="perf-stats-grid">
                <div className="perf-stat-card">
                    <FiCheckCircle style={{ fontSize: '1.5rem', color: '#10b981', marginBottom: 8 }} />
                    <span className="stat-value green">{stats?.attendance_rate || 0}%</span>
                    <span className="stat-label">Attendance Rate</span>
                </div>
                <div className="perf-stat-card">
                    <FiFileText style={{ fontSize: '1.5rem', color: '#2563eb', marginBottom: 8 }} />
                    <span className="stat-value purple">{stats?.completed_assignments || 0}</span>
                    <span className="stat-label">Completed Tasks</span>
                </div>
                <div className="perf-stat-card">
                    <FiVideo style={{ fontSize: '1.5rem', color: '#3b82f6', marginBottom: 8 }} />
                    <span className="stat-value blue">{stats?.total_classes_attended || stats?.today_classes || 0}</span>
                    <span className="stat-label">Classes Attended</span>
                </div>
                <div className="perf-stat-card">
                    <FiTrendingUp style={{ fontSize: '1.5rem', color: '#f59e0b', marginBottom: 8 }} />
                    <span className="stat-value orange">{stats?.pending_assignments || 0}</span>
                    <span className="stat-label">Pending Tasks</span>
                </div>
            </div>

            <div className="cards-grid">
                <div className="section-card">
                    <h3 style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                        <FiBarChart2 style={{ color: '#2563eb' }} /> Attendance Summary
                    </h3>
                    <p>Your attendance this month: <strong>{stats?.attendance_rate || 0}%</strong></p>
                    <div style={{
                        height: 12,
                        background: '#f1f5f9',
                        borderRadius: 6,
                        marginTop: 12,
                        overflow: 'hidden'
                    }}>
                        <div style={{
                            height: '100%',
                            width: `${stats?.attendance_rate || 0}%`,
                            background: 'linear-gradient(90deg, #10b981, #059669)',
                            borderRadius: 6,
                            transition: 'width 0.5s'
                        }} />
                    </div>
                </div>

                <div className="section-card">
                    <h3 style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                        <FiAward style={{ color: '#f59e0b' }} /> Assignment Progress
                    </h3>
                    <p>
                        Completed: <strong>{stats?.completed_assignments || 0}</strong> |
                        Pending: <strong>{stats?.pending_assignments || 0}</strong>
                    </p>
                    <div style={{
                        height: 12,
                        background: '#f1f5f9',
                        borderRadius: 6,
                        marginTop: 12,
                        overflow: 'hidden'
                    }}>
                        {(() => {
                            const total = (stats?.completed_assignments || 0) + (stats?.pending_assignments || 0)
                            const pct = total > 0 ? ((stats?.completed_assignments || 0) / total) * 100 : 0
                            return (
                                <div style={{
                                    height: '100%',
                                    width: `${pct}%`,
                                    background: 'linear-gradient(90deg, #2563eb, #3b82f6)',
                                    borderRadius: 6,
                                    transition: 'width 0.5s'
                                }} />
                            )
                        })()}
                    </div>
                </div>
            </div>
        </div>
    )
}
