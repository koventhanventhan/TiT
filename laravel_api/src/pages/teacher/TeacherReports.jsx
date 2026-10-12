import React, { useState, useEffect } from 'react'
import { FiUsers, FiCheckCircle, FiFileText, FiCalendar, FiAward, FiPrinter } from 'react-icons/fi'
import { getTeacherDashboardStats, getTeacherAssignments } from '../../services/dashboardService'

function ReportCard({ title, value, subtitle, icon: Icon }) {
    return (
        <div className="t-card" style={{
            background: 'var(--t-surface)', borderRadius: 16, padding: 24, border: '1px solid var(--t-border)',
            boxShadow: '0 1px 3px rgba(0,0,0,0.02)', position: 'relative', overflow: 'hidden'
        }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
                <div style={{
                    width: 56, height: 56, borderRadius: 14,
                    background: 'var(--t-primary-soft)', color: 'var(--t-primary)',
                    display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 24, zIndex: 1, position: 'relative'
                }}>
                    <Icon />
                </div>
                <div style={{ zIndex: 1, position: 'relative' }}>
                    <div style={{ fontSize: 32, fontWeight: 800, color: 'var(--t-text)', lineHeight: 1 }}>{value}</div>
                    <div style={{ fontSize: 14, fontWeight: 600, color: 'var(--t-text-3)', marginTop: 4 }}>{title}</div>
                </div>
            </div>
            {subtitle && (
                <div style={{ marginTop: 16, paddingTop: 16, borderTop: '1px solid var(--t-border)', fontSize: 13, color: 'var(--t-text-3)', fontWeight: 500, zIndex: 1, position: 'relative' }}>
                    {subtitle}
                </div>
            )}
        </div>
    )
}

export default function TeacherReports() {
    const [stats, setStats] = useState(null)
    const [assignments, setAssignments] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                const [statsData, assignData] = await Promise.all([
                    getTeacherDashboardStats().catch(() => null),
                    getTeacherAssignments().catch(() => null)
                ])
                setStats(statsData || {})
                setAssignments(Array.isArray(assignData) ? assignData : (assignData?.data || []))
            } catch (e) {
                console.error('Error loading reports:', e)
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

    // Compute per assignment sum: total submissions / sum of total_students
    const totalMaxExpected = assignments.reduce((acc, a) => acc + (a.total_students || 0), 0)
    const totalActualSubmissions = assignments.reduce((acc, a) => acc + (a.submissions_count || 0), 0)
    const submissionRate = totalMaxExpected > 0 ? Math.min(100, Math.round((totalActualSubmissions / totalMaxExpected) * 100)) : null

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div className="teacher-page-header">
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: 'var(--t-text)', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Reports & Analytics</h1>
                    <p style={{ color: 'var(--t-text-3)', margin: 0, fontSize: 14 }}>Track student engagement and assignment completion</p>
                </div>
                <button
                    onClick={() => window.print()}
                    className="t-btn"
                    style={{
                        display: 'flex', alignItems: 'center', gap: 8, padding: '10px 20px', minHeight: 44,
                        background: 'var(--t-surface)', color: 'var(--t-primary)', border: '1px solid var(--t-border)',
                        borderRadius: 12, fontWeight: 600, fontSize: 14, cursor: 'pointer'
                    }}
                >
                    <FiPrinter /> Print Report
                </button>
            </div>

            {/* Overview Stats */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(240px, 100%), 1fr))', gap: 20, marginBottom: 24 }}>
                <ReportCard
                    title="Total Students"
                    value={stats?.total_students || 0}
                    subtitle="Approved students in institute"
                    icon={FiUsers}
                />
                <ReportCard
                    title="Assignments"
                    value={stats?.total_assignments || 0}
                    subtitle={`${assignments.length} total tasks`}
                    icon={FiFileText}
                />
                <ReportCard
                    title="Submissions"
                    value={stats?.total_submissions || 0}
                    subtitle={`${stats?.new_submissions || 0} waiting to be graded`}
                    icon={FiCheckCircle}
                />
                <ReportCard
                    title="Classes This Week"
                    value={stats?.week_classes || 0}
                    subtitle={`${stats?.today_classes || 0} classes today`}
                    icon={FiCalendar}
                />
            </div>

            {/* Performance Card */}
            {submissionRate !== null && (
                <div style={{
                    background: 'var(--t-surface)', borderRadius: 16, padding: 24,
                    border: '1px solid var(--t-border)', boxShadow: '0 1px 3px rgba(0,0,0,0.02)'
                }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20, flexWrap: 'wrap', gap: 12 }}>
                        <div>
                            <h3 style={{ margin: '0 0 4px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)', display: 'flex', alignItems: 'center', gap: 8 }}>
                                <FiAward style={{ color: 'var(--t-primary)' }} /> Assignment Completion Rate
                            </h3>
                            <p style={{ margin: 0, color: 'var(--t-text-3)', fontSize: 14 }}>
                                Ratio of received submissions ({totalActualSubmissions}) across all eligible students ({totalMaxExpected})
                            </p>
                        </div>
                        <div style={{ fontSize: 28, fontWeight: 800, color: 'var(--t-primary)' }}>{submissionRate}%</div>
                    </div>

                    <div style={{ width: '100%', height: 10, background: 'var(--t-bg)', borderRadius: 6, overflow: 'hidden' }}>
                        <div style={{ width: `${submissionRate}%`, height: '100%', background: 'var(--t-primary)', borderRadius: 6, transition: 'width 0.5s ease' }} />
                    </div>
                </div>
            )}
        </div>
    )
}
