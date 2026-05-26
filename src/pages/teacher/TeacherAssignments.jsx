import React, { useState, useEffect } from 'react'
import { FiPlus, FiFileText, FiCalendar, FiCheckCircle, FiUsers, FiClock, FiSearch, FiFilter } from 'react-icons/fi'
import { getTeacherAssignments } from '../../services/dashboardService'

export default function TeacherAssignments() {
    const [assignments, setAssignments] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                // Mocking data if API fails
                const data = await getTeacherAssignments().catch(() => null)
                setAssignments(data || [
                    { id: 1, title: 'Calculus Quiz 1', subject: 'Mathematics', grade: 'A/L 2026', description: 'Complete all 10 questions on integration and differentiation.', due_date: '2026-05-25', submissions_count: 32, total_students: 45, status: 'active' },
                    { id: 2, title: 'Thermodynamics Essay', subject: 'Physics', grade: 'A/L 2026', description: 'Write a 1000-word essay on the laws of thermodynamics with real-world examples.', due_date: '2026-05-28', submissions_count: 12, total_students: 45, status: 'active' },
                    { id: 3, title: 'Organic Chemistry Lab Report', subject: 'Chemistry', grade: 'A/L 2025', description: 'Submit the lab report for the esterification experiment.', due_date: '2026-05-20', submissions_count: 50, total_students: 50, status: 'closed' }
                ])
            } catch (e) {
                console.error(e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24,
                '@media (minWidth: 640px)': { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }
            }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: '#1e293b', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Assignments</h1>
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>Manage your homework and class assignments</p>
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
                    <FiPlus style={{ fontSize: 18 }} /> Create Assignment
                </button>
            </div>

            {/* Filters Bar */}
            <div style={{
                display: 'flex', alignItems: 'center', gap: 12, marginBottom: 24,
                background: '#fff', padding: 16, borderRadius: 16,
                border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.02)',
                flexWrap: 'wrap'
            }}>
                <div style={{ position: 'relative', flex: '1 1 250px' }}>
                    <FiSearch style={{ position: 'absolute', left: 14, top: 12, color: '#94a3b8' }} />
                    <input type="text" placeholder="Search assignments..." style={{
                        width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10,
                        border: '1px solid #e2e8f0', background: '#f8fafc', fontSize: 14,
                        outline: 'none', transition: 'border-color 0.2s'
                    }} onFocus={e => e.target.style.borderColor = '#0ea5e9'} onBlur={e => e.target.style.borderColor = '#e2e8f0'} />
                </div>
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                    <button style={{ padding: '8px 16px', borderRadius: 8, border: '1px solid #0ea5e9', background: '#f0f9ff', color: '#0ea5e9', fontSize: 13, fontWeight: 600, cursor: 'pointer' }}>All Active</button>
                    <button style={{ padding: '8px 16px', borderRadius: 8, border: '1px solid #e2e8f0', background: '#fff', color: '#64748b', fontSize: 13, fontWeight: 600, cursor: 'pointer' }}>Needs Grading</button>
                    <button style={{ padding: '8px 16px', borderRadius: 8, border: '1px solid #e2e8f0', background: '#fff', color: '#64748b', fontSize: 13, fontWeight: 600, cursor: 'pointer' }}>Closed</button>
                </div>
            </div>

            {/* Content Area */}
            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#0ea5e9', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : (
                <div style={{
                    display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))', gap: 20
                }}>
                    {assignments.map(assign => {
                        const progress = assign.total_students > 0 ? (assign.submissions_count / assign.total_students) * 100 : 0;
                        const isClosed = assign.status === 'closed';

                        return (
                            <div key={assign.id} style={{
                                background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0',
                                padding: 20, display: 'flex', flexDirection: 'column',
                                boxShadow: '0 2px 8px rgba(0,0,0,0.04)', transition: 'transform 0.2s, box-shadow 0.2s',
                                cursor: 'pointer', opacity: isClosed ? 0.7 : 1
                            }}
                            onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-4px)'; e.currentTarget.style.boxShadow = '0 12px 24px rgba(0,0,0,0.08)' }}
                            onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,0.04)' }}
                            >
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 12 }}>
                                    <div style={{
                                        background: isClosed ? '#f1f5f9' : '#f0fdfa', color: isClosed ? '#64748b' : '#0d9488',
                                        padding: '4px 10px', borderRadius: 6, fontSize: 11, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.5px'
                                    }}>
                                        {assign.subject}
                                    </div>
                                    <div style={{
                                        background: isClosed ? '#fee2e2' : '#fef3c7', color: isClosed ? '#ef4444' : '#d97706',
                                        padding: '4px 8px', borderRadius: 6, fontSize: 11, fontWeight: 700, display: 'flex', alignItems: 'center', gap: 4
                                    }}>
                                        <FiClock /> {isClosed ? 'Closed' : 'Due ' + new Date(assign.due_date).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
                                    </div>
                                </div>

                                <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#1e293b', lineHeight: 1.3 }}>{assign.title}</h3>
                                <p style={{ margin: '0 0 16px 0', color: '#64748b', fontSize: 13, lineHeight: 1.5, display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                                    {assign.description}
                                </p>

                                <div style={{ marginTop: 'auto' }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 12, color: '#475569', fontWeight: 600, marginBottom: 8 }}>
                                        <span>Submissions</span>
                                        <span>{assign.submissions_count} / {assign.total_students}</span>
                                    </div>
                                    <div style={{ width: '100%', height: 6, background: '#f1f5f9', borderRadius: 4, overflow: 'hidden', marginBottom: 16 }}>
                                        <div style={{ width: `${progress}%`, height: '100%', background: isClosed ? '#94a3b8' : 'linear-gradient(90deg, #0ea5e9, #6366f1)', borderRadius: 4 }} />
                                    </div>

                                    <div style={{ display: 'flex', gap: 10 }}>
                                        <button style={{
                                            flex: 1, padding: '10px', background: '#f8fafc', border: '1px solid #e2e8f0',
                                            borderRadius: 10, color: '#475569', fontWeight: 600, fontSize: 13, cursor: 'pointer', transition: 'background 0.2s'
                                        }} onMouseEnter={e => e.currentTarget.style.background = '#f1f5f9'} onMouseLeave={e => e.currentTarget.style.background = '#f8fafc'}>
                                            Edit
                                        </button>
                                        <button style={{
                                            flex: 2, padding: '10px', background: '#0ea5e9', border: 'none',
                                            borderRadius: 10, color: '#fff', fontWeight: 600, fontSize: 13, cursor: 'pointer', transition: 'background 0.2s'
                                        }} onMouseEnter={e => e.currentTarget.style.background = '#0284c7'} onMouseLeave={e => e.currentTarget.style.background = '#0ea5e9'}>
                                            Grade Papers
                                        </button>
                                    </div>
                                </div>
                            </div>
                        )
                    })}
                    
                    {assignments.length === 0 && (
                        <div style={{
                            gridColumn: '1 / -1', background: '#fff', borderRadius: 16, border: '1px dashed #cbd5e1',
                            padding: '48px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center'
                        }}>
                            <div style={{ width: 64, height: 64, borderRadius: 16, background: '#f1f5f9', color: '#94a3b8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>
                                <FiFileText />
                            </div>
                            <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#334155' }}>No assignments yet</h3>
                            <p style={{ margin: '0 0 20px 0', color: '#64748b', fontSize: 14 }}>Create your first assignment to get started.</p>
                            <button style={{
                                padding: '10px 20px', background: '#0ea5e9', color: '#fff', border: 'none',
                                borderRadius: 10, fontWeight: 600, fontSize: 14, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8
                            }}>
                                <FiPlus /> Create Assignment
                            </button>
                        </div>
                    )}
                </div>
            )}
        </div>
    )
}
