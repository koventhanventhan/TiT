import React, { useState, useEffect } from 'react'
import { FiBarChart2, FiUsers, FiCheckCircle, FiTrendingUp, FiFileText } from 'react-icons/fi'
import { getTeacherDashboardStats } from '../../services/dashboardService'
import './TeacherSections.css'

export default function TeacherReports() {
    const [stats, setStats] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                const data = await getTeacherDashboardStats()
                setStats(data)
            } catch (e) {
                console.error('Error loading reports:', e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    if (loading) return <div className="teacher-section"><div className="loading-shimmer">Loading reports...</div></div>

    return (
        <div className="teacher-section">
            <div className="section-top">
                <h2>Reports & Analytics</h2>
            </div>

            <div style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(12.5rem, 1fr))',
                gap: 20,
                marginBottom: 28
            }}>
                <div style={{
                    background: '#fff', borderRadius: 14, padding: 24,
                    border: '1.0px solid #e2e8f0', textAlign: 'center'
                }}>
                    <FiUsers style={{ fontSize: '1.5rem', color: '#2563eb', marginBottom: 8 }} />
                    <div style={{ fontSize: '2rem', fontWeight: 800, color: '#2563eb' }}>
                        {stats?.total_students || 0}
                    </div>
                    <div style={{ color: '#64748b', fontSize: '0.85rem' }}>Total Students</div>
                </div>
                <div style={{
                    background: '#fff', borderRadius: 14, padding: 24,
                    border: '1.0px solid #e2e8f0', textAlign: 'center'
                }}>
                    <FiFileText style={{ fontSize: '1.5rem', color: '#10b981', marginBottom: 8 }} />
                    <div style={{ fontSize: '2rem', fontWeight: 800, color: '#10b981' }}>
                        {stats?.total_assignments || 0}
                    </div>
                    <div style={{ color: '#64748b', fontSize: '0.85rem' }}>Assignments Created</div>
                </div>
                <div style={{
                    background: '#fff', borderRadius: 14, padding: 24,
                    border: '1.0px solid #e2e8f0', textAlign: 'center'
                }}>
                    <FiCheckCircle style={{ fontSize: '1.5rem', color: '#3b82f6', marginBottom: 8 }} />
                    <div style={{ fontSize: '2rem', fontWeight: 800, color: '#3b82f6' }}>
                        {stats?.total_submissions || 0}
                    </div>
                    <div style={{ color: '#64748b', fontSize: '0.85rem' }}>Submissions Received</div>
                </div>
                <div style={{
                    background: '#fff', borderRadius: 14, padding: 24,
                    border: '1.0px solid #e2e8f0', textAlign: 'center'
                }}>
                    <FiTrendingUp style={{ fontSize: '1.5rem', color: '#f59e0b', marginBottom: 8 }} />
                    <div style={{ fontSize: '2rem', fontWeight: 800, color: '#f59e0b' }}>
                        {stats?.upcoming_classes || 0}
                    </div>
                    <div style={{ color: '#64748b', fontSize: '0.85rem' }}>Upcoming Classes</div>
                </div>
            </div>

            <div style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(20rem, 1fr))',
                gap: 20
            }}>
                <div style={{
                    background: '#fff', borderRadius: 14, padding: 24,
                    border: '1.0px solid #e2e8f0'
                }}>
                    <h3 style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 16, color: '#1e293b' }}>
                        <FiBarChart2 style={{ color: '#2563eb' }} /> Class Performance
                    </h3>
                    <p style={{ color: '#64748b', fontSize: '0.9rem', marginBottom: 12 }}>
                        Average student attendance rate
                    </p>
                    <div style={{
                        height: 12, background: '#f1f5f9', borderRadius: 6, overflow: 'hidden'
                    }}>
                        <div style={{
                            height: '100%',
                            width: `${stats?.avg_attendance || 75}%`,
                            background: 'linear-gradient(90deg, #10b981, #059669)',
                            borderRadius: 6,
                            transition: 'width 0.5s'
                        }} />
                    </div>
                    <p style={{ color: '#10b981', fontWeight: 700, marginTop: 8, fontSize: '0.9rem' }}>
                        {stats?.avg_attendance || 75}%
                    </p>
                </div>

                <div style={{
                    background: '#fff', borderRadius: 14, padding: 24,
                    border: '1.0px solid #e2e8f0'
                }}>
                    <h3 style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 16, color: '#1e293b' }}>
                        <FiFileText style={{ color: '#f59e0b' }} /> Assignment Completion
                    </h3>
                    <p style={{ color: '#64748b', fontSize: '0.9rem', marginBottom: 12 }}>
                        Submission rate across all assignments
                    </p>
                    <div style={{
                        height: 12, background: '#f1f5f9', borderRadius: 6, overflow: 'hidden'
                    }}>
                        {(() => {
                            const total = stats?.total_assignments || 1
                            const submitted = stats?.total_submissions || 0
                            const pct = Math.min(100, Math.round((submitted / total) * 100))
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
