import React, { useState, useEffect } from 'react'
import { FiFileText, FiClock, FiCheckCircle, FiUpload, FiAlertCircle } from 'react-icons/fi'
import { getStudentAssignments } from '../../services/dashboardService'

export default function StudentAssignments() {
    const [assignments, setAssignments] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                let data = await getStudentAssignments().catch(() => null)
                if (!data || data.length === 0) {
                    data = [
                        { id: 1, title: 'Calculus Quiz 1', subject: 'Mathematics', teacher: 'Prof. Kumara', description: 'Complete all 10 questions on integration.', due_date: '2026-05-25', status: 'pending' },
                        { id: 2, title: 'Thermodynamics Essay', subject: 'Physics', teacher: 'Prof. Silva', description: 'Write a 1000-word essay on the laws of thermodynamics.', due_date: '2026-05-28', status: 'pending' },
                        { id: 3, title: 'Organic Chemistry Lab', subject: 'Chemistry', teacher: 'Dr. Perera', description: 'Submit the lab report for the esterification experiment.', due_date: '2026-05-20', status: 'submitted' },
                        { id: 4, title: 'Vectors Worksheet', subject: 'Mathematics', teacher: 'Prof. Kumara', description: 'Solve problems 1-20 from chapter 4.', due_date: '2026-05-18', status: 'graded', grade: '95/100' }
                    ]
                }
                setAssignments(data)
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
            {/* Header */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24, '@media (minWidth: 640px)': { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' } }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: '#1e293b', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Assignments</h1>
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>Track and submit your class homework</p>
                </div>
                <div style={{ display: 'flex', gap: 8 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '6px 12px', background: '#fff7ed', color: '#f97316', borderRadius: 20, fontSize: 12, fontWeight: 700 }}>
                        <div style={{ width: 6, height: 6, borderRadius: '50%', background: '#f97316' }} /> {assignments.filter(a => a.status === 'pending').length} Pending
                    </div>
                </div>
            </div>

            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#0ea5e9', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : assignments.length === 0 ? (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px dashed #cbd5e1', padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center' }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: '#f1f5f9', color: '#94a3b8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}><FiFileText /></div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#334155' }}>No assignments</h3>
                    <p style={{ margin: 0, color: '#64748b', fontSize: 14 }}>You have no assignments at the moment. Great job!</p>
                </div>
            ) : (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))', gap: 20 }}>
                    {assignments.map(assign => {
                        const isPending = assign.status === 'pending' || !assign.status;
                        const isSubmitted = assign.status === 'submitted';
                        const isGraded = assign.status === 'graded';
                        
                        let statusColor = '#f59e0b'; let statusBg = '#fffbeb'; let statusText = 'Pending';
                        if (isSubmitted) { statusColor = '#3b82f6'; statusBg = '#eff6ff'; statusText = 'Submitted'; }
                        if (isGraded) { statusColor = '#10b981'; statusBg = '#ecfdf5'; statusText = 'Graded'; }

                        const dueDate = new Date(assign.due_date);
                        const isOverdue = isPending && dueDate < new Date();

                        return (
                            <div key={assign.id} style={{
                                background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0',
                                padding: 20, display: 'flex', flexDirection: 'column',
                                boxShadow: '0 2px 8px rgba(0,0,0,0.04)', transition: 'transform 0.2s, box-shadow 0.2s'
                            }}
                            onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-4px)'; e.currentTarget.style.boxShadow = '0 12px 24px rgba(0,0,0,0.08)' }}
                            onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,0.04)' }}
                            >
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 12 }}>
                                    <div style={{
                                        background: '#f8fafc', color: '#475569',
                                        padding: '4px 10px', borderRadius: 6, fontSize: 11, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.5px'
                                    }}>
                                        {assign.subject}
                                    </div>
                                    <div style={{
                                        background: statusBg, color: statusColor,
                                        padding: '4px 8px', borderRadius: 6, fontSize: 11, fontWeight: 700, textTransform: 'uppercase'
                                    }}>
                                        {statusText}
                                    </div>
                                </div>

                                <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#1e293b', lineHeight: 1.3 }}>{assign.title}</h3>
                                <div style={{ color: '#64748b', fontSize: 13, fontWeight: 500, marginBottom: 16 }}>By {assign.teacher}</div>
                                
                                <p style={{ margin: '0 0 16px 0', color: '#475569', fontSize: 13, lineHeight: 1.5, display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                                    {assign.description}
                                </p>

                                <div style={{ marginTop: 'auto', paddingTop: 16, borderTop: '1px solid #f1f5f9' }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 6, color: isOverdue ? '#ef4444' : '#64748b', fontSize: 13, fontWeight: 600 }}>
                                            {isOverdue ? <FiAlertCircle /> : <FiClock />} 
                                            {isOverdue ? 'Overdue' : 'Due'} {dueDate.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
                                        </div>
                                        {isGraded && (
                                            <div style={{ fontSize: 14, fontWeight: 800, color: '#10b981' }}>{assign.grade}</div>
                                        )}
                                    </div>

                                    {isPending && (
                                        <button style={{
                                            width: '100%', padding: '10px', background: 'linear-gradient(135deg, #0ea5e9, #38bdf8)',
                                            border: 'none', borderRadius: 10, color: '#fff', fontWeight: 600, fontSize: 14, cursor: 'pointer',
                                            display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8, boxShadow: '0 4px 12px rgba(14,165,233,0.3)', transition: 'all 0.2s'
                                        }} onMouseEnter={e => e.currentTarget.style.transform = 'translateY(-1px)'} onMouseLeave={e => e.currentTarget.style.transform = 'translateY(0)'}>
                                            <FiUpload /> Submit Work
                                        </button>
                                    )}
                                    {(isSubmitted || isGraded) && (
                                        <button style={{
                                            width: '100%', padding: '10px', background: '#f8fafc', border: '1px solid #e2e8f0',
                                            borderRadius: 10, color: '#475569', fontWeight: 600, fontSize: 14, cursor: 'pointer',
                                            display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8, transition: 'background 0.2s'
                                        }} onMouseEnter={e => e.currentTarget.style.background = '#f1f5f9'} onMouseLeave={e => e.currentTarget.style.background = '#f8fafc'}>
                                            <FiCheckCircle style={{ color: '#10b981' }} /> View Submission
                                        </button>
                                    )}
                                </div>
                            </div>
                        )
                    })}
                </div>
            )}
        </div>
    )
}
