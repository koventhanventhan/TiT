import React, { useState, useEffect } from 'react'
import { FiPlus, FiFileText, FiCalendar, FiCheckCircle, FiUsers, FiClock, FiSearch, FiFilter, FiX } from 'react-icons/fi'
import { getTeacherAssignments, createTeacherAssignment } from '../../services/dashboardService'

export default function TeacherAssignments() {
    const [assignments, setAssignments] = useState([])
    const [loading, setLoading] = useState(true)
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
    const [createLoading, setCreateLoading] = useState(false)
    const [newAssignment, setNewAssignment] = useState({ title: '', subject: '', description: '', due_date: '', grade: '' })
    const [filter, setFilter] = useState('active')
    const [searchQuery, setSearchQuery] = useState('')

    const filteredAssignments = assignments.filter(assign => {
        if (searchQuery && !assign.title.toLowerCase().includes(searchQuery.toLowerCase())) return false;
        // Assume assignment is closed if due_date is past
        const isClosed = assign.due_date ? new Date(assign.due_date) < new Date() : false;
        if (filter === 'active') return !isClosed;
        if (filter === 'closed') return isClosed;
        if (filter === 'needs_grading') return assign.ungraded_count > 0;
        return true;
    });

    const loadAssignments = async () => {
        setLoading(true)
        try {
            // Mocking data if API fails
            const data = await getTeacherAssignments().catch(() => null)
            setAssignments(data?.data || data || [])
        } catch (e) {
            console.error(e)
        } finally {
            setLoading(false)
        }
    }

    useEffect(() => {
        loadAssignments()
    }, [])

    const handleCreateSubmit = async (e) => {
        e.preventDefault()
        setCreateLoading(true)
        try {
            const formData = new FormData()
            formData.append('title', newAssignment.title)
            formData.append('subject', newAssignment.subject)
            formData.append('description', newAssignment.description)
            formData.append('due_date', newAssignment.due_date)
            if (newAssignment.grade) {
                formData.append('grade', newAssignment.grade)
            }

            await createTeacherAssignment(formData)
            alert('Assignment created successfully!')
            setIsCreateModalOpen(false)
            setNewAssignment({ title: '', subject: '', description: '', due_date: '', grade: '' })
            loadAssignments()
        } catch (error) {
            alert(error.message || 'Error creating assignment')
        } finally {
            setCreateLoading(false)
        }
    }

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
                onClick={() => setIsCreateModalOpen(true)}
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
                    <input type="text" placeholder="Search assignments..." value={searchQuery} onChange={e => setSearchQuery(e.target.value)} style={{
                        width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10,
                        border: '1px solid #e2e8f0', background: '#f8fafc', fontSize: 14,
                        outline: 'none', transition: 'border-color 0.2s'
                    }} onFocus={e => e.target.style.borderColor = '#0ea5e9'} onBlur={e => e.target.style.borderColor = '#e2e8f0'} />
                </div>
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                    <button onClick={() => setFilter('active')} style={{ padding: '8px 16px', borderRadius: 8, border: filter === 'active' ? '1px solid #0ea5e9' : '1px solid #e2e8f0', background: filter === 'active' ? '#f0f9ff' : '#fff', color: filter === 'active' ? '#0ea5e9' : '#64748b', fontSize: 13, fontWeight: 600, cursor: 'pointer' }}>All Active</button>
                    <button onClick={() => setFilter('needs_grading')} style={{ padding: '8px 16px', borderRadius: 8, border: filter === 'needs_grading' ? '1px solid #0ea5e9' : '1px solid #e2e8f0', background: filter === 'needs_grading' ? '#f0f9ff' : '#fff', color: filter === 'needs_grading' ? '#0ea5e9' : '#64748b', fontSize: 13, fontWeight: 600, cursor: 'pointer' }}>Needs Grading</button>
                    <button onClick={() => setFilter('closed')} style={{ padding: '8px 16px', borderRadius: 8, border: filter === 'closed' ? '1px solid #0ea5e9' : '1px solid #e2e8f0', background: filter === 'closed' ? '#f0f9ff' : '#fff', color: filter === 'closed' ? '#0ea5e9' : '#64748b', fontSize: 13, fontWeight: 600, cursor: 'pointer' }}>Closed</button>
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
                    {filteredAssignments.map(assign => {
                        const progress = assign.total_students ? (assign.submissions_count / assign.total_students) * 100 : 0;
                        const isClosed = assign.due_date ? new Date(assign.due_date) < new Date() : false;

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
                    
                    {filteredAssignments.length === 0 && (
                        <div style={{
                            gridColumn: '1 / -1', background: '#fff', borderRadius: 16, border: '1px dashed #cbd5e1',
                            padding: '48px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center'
                        }}>
                            <div style={{ width: 64, height: 64, borderRadius: 16, background: '#f1f5f9', color: '#94a3b8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>
                                <FiFileText />
                            </div>
                            <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#334155' }}>No assignments yet</h3>
                            <p style={{ margin: '0 0 20px 0', color: '#64748b', fontSize: 14 }}>Create your first assignment to get started.</p>
                            <button onClick={() => setIsCreateModalOpen(true)} style={{
                                padding: '10px 20px', background: '#0ea5e9', color: '#fff', border: 'none',
                                borderRadius: 10, fontWeight: 600, fontSize: 14, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8
                            }}>
                                <FiPlus /> Create Assignment
                            </button>
                        </div>
                    )}
                </div>
            )}

            {/* Create Assignment Modal */}
            {isCreateModalOpen && (
                <div style={{
                    position: 'fixed', inset: 0, background: 'rgba(15, 23, 42, 0.6)', 
                    backdropFilter: 'blur(4px)', zIndex: 1000, display: 'flex', 
                    alignItems: 'center', justifyContent: 'center', padding: 20
                }}>
                    <div style={{
                        background: '#fff', width: '100%', maxWidth: 500, 
                        borderRadius: 24, padding: 32, boxShadow: '0 25px 50px -12px rgba(0,0,0,0.25)',
                        animation: 'page-enter-active 0.3s ease-out'
                    }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
                            <h2 style={{ fontSize: 20, fontWeight: 800, color: '#1e293b', margin: 0 }}>Create New Assignment</h2>
                            <button onClick={() => setIsCreateModalOpen(false)} style={{
                                width: 32, height: 32, borderRadius: '50%', background: '#f1f5f9', border: 'none',
                                display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#64748b', cursor: 'pointer', transition: 'all 0.2s'
                            }} onMouseEnter={e => e.currentTarget.style.background='#e2e8f0'} onMouseLeave={e => e.currentTarget.style.background='#f1f5f9'}>
                                <FiX />
                            </button>
                        </div>
                        <form onSubmit={handleCreateSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                            <div>
                                <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 6 }}>Assignment Title</label>
                                <input type="text" required value={newAssignment.title} onChange={e => setNewAssignment({...newAssignment, title: e.target.value})} style={{ width: '100%', padding: '10px 14px', borderRadius: 10, border: '1px solid #e2e8f0', outline: 'none', fontSize: 14 }} placeholder="e.g. Calculus Quiz 1" />
                            </div>
                            <div>
                                <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 6 }}>Subject</label>
                                <input type="text" required value={newAssignment.subject} onChange={e => setNewAssignment({...newAssignment, subject: e.target.value})} style={{ width: '100%', padding: '10px 14px', borderRadius: 10, border: '1px solid #e2e8f0', outline: 'none', fontSize: 14 }} placeholder="e.g. Mathematics" />
                            </div>
                            <div>
                                <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 6 }}>Target Grade</label>
                                <select value={newAssignment.grade} onChange={e => setNewAssignment({...newAssignment, grade: e.target.value})} style={{ width: '100%', padding: '10px 14px', borderRadius: 10, border: '1px solid #e2e8f0', outline: 'none', fontSize: 14, background: '#fff' }}>
                                    <option value="">All Grades</option>
                                    {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13].map(grade => (
                                        <option key={grade} value={grade}>Grade {grade}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 6 }}>Due Date</label>
                                <input type="date" required value={newAssignment.due_date} onChange={e => setNewAssignment({...newAssignment, due_date: e.target.value})} style={{ width: '100%', padding: '10px 14px', borderRadius: 10, border: '1px solid #e2e8f0', outline: 'none', fontSize: 14 }} />
                            </div>
                            <div>
                                <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 6 }}>Description / Instructions</label>
                                <textarea required value={newAssignment.description} onChange={e => setNewAssignment({...newAssignment, description: e.target.value})} rows="4" style={{ width: '100%', padding: '10px 14px', borderRadius: 10, border: '1px solid #e2e8f0', outline: 'none', fontSize: 14, resize: 'none' }} placeholder="Write assignment instructions here..."></textarea>
                            </div>
                            <div style={{ marginTop: 8, display: 'flex', gap: 12 }}>
                                <button type="button" onClick={() => setIsCreateModalOpen(false)} style={{ flex: 1, padding: '12px', background: '#f1f5f9', border: 'none', borderRadius: 12, color: '#475569', fontWeight: 600, fontSize: 14, cursor: 'pointer' }}>Cancel</button>
                                <button type="submit" disabled={createLoading} style={{ flex: 2, padding: '12px', background: '#0ea5e9', border: 'none', borderRadius: 12, color: '#fff', fontWeight: 600, fontSize: 14, cursor: createLoading ? 'not-allowed' : 'pointer', opacity: createLoading ? 0.7 : 1 }}>
                                    {createLoading ? 'Publishing...' : 'Publish Assignment'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    )
}
