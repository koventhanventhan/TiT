import React, { useState, useEffect } from 'react'
import {
    FiPlus, FiFileText, FiCalendar, FiClock, FiSearch,
    FiX, FiDownload, FiCheck, FiAlertCircle, FiRefreshCw, FiExternalLink
} from 'react-icons/fi'
import {
    getTeacherAssignments, createTeacherAssignment,
    getAssignmentSubmissions, gradeSubmission
} from '../../services/dashboardService'
import { useToast } from '../../components/shared/ToastContext'

export default function TeacherAssignments() {
    const toast = useToast()

    const [assignments, setAssignments] = useState([])
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState(null)
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
    const [createLoading, setCreateLoading] = useState(false)
    const [newAssignment, setNewAssignment] = useState({ title: '', subject: '', description: '', due_date: '', grade: '' })
    const [assignmentFile, setAssignmentFile] = useState(null)
    const [filter, setFilter] = useState('active')
    const [searchQuery, setSearchQuery] = useState('')

    // Grade Papers Drawer/Modal state
    const [gradingModalOpen, setGradingModalOpen] = useState(false)
    const [selectedAssignment, setSelectedAssignment] = useState(null)
    const [submissions, setSubmissions] = useState([])
    const [loadingSubmissions, setLoadingSubmissions] = useState(false)
    const [gradeDrafts, setGradeDrafts] = useState({})
    const [savingSubId, setSavingSubId] = useState(null)

    const loadAssignments = async () => {
        setLoading(true)
        setError(null)
        try {
            const data = await getTeacherAssignments()
            setAssignments(data?.data || data || [])
        } catch (e) {
            console.error('Error loading assignments:', e)
            setError(e.message || 'Failed to load assignments')
        } finally {
            setLoading(false)
        }
    }

    useEffect(() => {
        loadAssignments()
    }, [])

    const filteredAssignments = assignments.filter(assign => {
        if (searchQuery && !assign.title.toLowerCase().includes(searchQuery.toLowerCase())) return false

        const dueDateObj = assign.due_date ? new Date(assign.due_date) : null
        const isValidDate = dueDateObj && !isNaN(dueDateObj.getTime())
        const isClosed = isValidDate ? dueDateObj < new Date() : false

        if (filter === 'active') return !isClosed
        if (filter === 'closed') return isClosed
        if (filter === 'needs_grading') return (assign.ungraded_count || 0) > 0
        return true
    })

    const handleCreateSubmit = async (e) => {
        e.preventDefault()
        if (createLoading) return
        setCreateLoading(true)
        try {
            const formData = new FormData()
            formData.append('title', newAssignment.title)
            formData.append('subject', newAssignment.subject)
            formData.append('description', newAssignment.description)
            if (newAssignment.due_date) {
                formData.append('due_date', newAssignment.due_date)
            }
            if (newAssignment.grade) {
                formData.append('grade', newAssignment.grade)
            }
            if (assignmentFile) {
                formData.append('file', assignmentFile)
            }

            await createTeacherAssignment(formData)
            toast.success('Assignment created successfully!')
            setIsCreateModalOpen(false)
            setNewAssignment({ title: '', subject: '', description: '', due_date: '', grade: '' })
            setAssignmentFile(null)
            loadAssignments()
        } catch (error) {
            toast.error(error.message || 'Error creating assignment')
        } finally {
            setCreateLoading(false)
        }
    }

    // Open Grade Papers drawer
    const handleOpenGrading = async (assign) => {
        setSelectedAssignment(assign)
        setGradingModalOpen(true)
        setLoadingSubmissions(true)
        try {
            const data = await getAssignmentSubmissions(assign.id)
            const list = Array.isArray(data) ? data : (data?.data || [])
            setSubmissions(list)

            // Populate initial drafts
            const drafts = {}
            list.forEach(sub => {
                drafts[sub.id] = {
                    marks: sub.marks !== null && sub.marks !== undefined ? sub.marks : '',
                    feedback: sub.feedback || ''
                }
            })
            setGradeDrafts(drafts)
        } catch (err) {
            console.error('Error loading submissions:', err)
            toast.error('Failed to load submissions')
        } finally {
            setLoadingSubmissions(false)
        }
    }

    // Save individual submission grade
    const handleSaveGrade = async (subId) => {
        if (savingSubId) return
        const draft = gradeDrafts[subId] || {}
        if (draft.marks === '' || isNaN(draft.marks) || Number(draft.marks) < 0 || Number(draft.marks) > 100) {
            toast.error('Please enter a valid mark between 0 and 100')
            return
        }

        setSavingSubId(subId)
        try {
            const updated = await gradeSubmission(subId, {
                marks: Number(draft.marks),
                feedback: draft.feedback || ''
            })

            toast.success('Grade saved successfully')

            // Update submissions list
            setSubmissions(prev => prev.map(s => s.id === subId ? { ...s, marks: updated.marks, feedback: updated.feedback, status: 'graded' } : s))

            // Update assignment ungraded_count
            setAssignments(prev => prev.map(a => {
                if (a.id === selectedAssignment?.id) {
                    const newCount = Math.max(0, (a.ungraded_count || 1) - 1)
                    return { ...a, ungraded_count: newCount }
                }
                return a
            }))
        } catch (err) {
            toast.error(err.message || 'Failed to save grade')
        } finally {
            setSavingSubId(null)
        }
    }

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div className="teacher-page-header">
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: 'var(--t-text)', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Assignments</h1>
                    <p style={{ color: 'var(--t-text-3)', margin: 0, fontSize: 14 }}>Manage homework, view submissions, and grade student papers</p>
                </div>
                <button
                    className="t-btn"
                    style={{
                        display: 'flex', alignItems: 'center', gap: 8, padding: '10px 20px',
                        background: 'var(--t-primary)', color: '#fff',
                        border: 'none', borderRadius: 12, fontWeight: 600, fontSize: 14,
                        cursor: 'pointer', width: 'fit-content'
                    }}
                    onClick={() => setIsCreateModalOpen(true)}
                >
                    <FiPlus style={{ fontSize: 18 }} /> Create Assignment
                </button>
            </div>

            {/* Filters Bar */}
            <div style={{
                display: 'flex', alignItems: 'center', gap: 12, marginBottom: 24,
                background: 'var(--t-surface)', padding: 16, borderRadius: 16,
                border: '1px solid var(--t-border)', boxShadow: '0 1px 3px rgba(0,0,0,0.02)',
                flexWrap: 'wrap'
            }}>
                <div style={{ position: 'relative', flex: '1 1 250px' }}>
                    <FiSearch style={{ position: 'absolute', left: 14, top: 12, color: 'var(--t-text-3)' }} />
                    <input
                        type="text"
                        placeholder="Search assignments by title..."
                        value={searchQuery}
                        onChange={e => setSearchQuery(e.target.value)}
                        style={{
                            width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10,
                            border: '1px solid var(--t-border)', background: 'var(--t-bg)', fontSize: 14,
                            outline: 'none', transition: 'border-color 0.2s', boxSizing: 'border-box',
                            color: 'var(--t-text)'
                        }}
                    />
                </div>
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                    <button
                        className="t-btn"
                        onClick={() => setFilter('active')}
                        style={{
                            padding: '8px 16px', borderRadius: 8,
                            border: filter === 'active' ? '1px solid var(--t-primary)' : '1px solid var(--t-border)',
                            background: filter === 'active' ? 'var(--t-primary-soft)' : 'var(--t-surface)',
                            color: filter === 'active' ? 'var(--t-primary)' : 'var(--t-text-2)',
                            fontSize: 13, fontWeight: 600, cursor: 'pointer'
                        }}
                    >
                        All Active
                    </button>
                    <button
                        className="t-btn"
                        onClick={() => setFilter('needs_grading')}
                        style={{
                            padding: '8px 16px', borderRadius: 8,
                            border: filter === 'needs_grading' ? '1px solid var(--t-primary)' : '1px solid var(--t-border)',
                            background: filter === 'needs_grading' ? 'var(--t-primary-soft)' : 'var(--t-surface)',
                            color: filter === 'needs_grading' ? 'var(--t-primary)' : 'var(--t-text-2)',
                            fontSize: 13, fontWeight: 600, cursor: 'pointer'
                        }}
                    >
                        Needs Grading
                    </button>
                    <button
                        className="t-btn"
                        onClick={() => setFilter('closed')}
                        style={{
                            padding: '8px 16px', borderRadius: 8,
                            border: filter === 'closed' ? '1px solid var(--t-primary)' : '1px solid var(--t-border)',
                            background: filter === 'closed' ? 'var(--t-primary-soft)' : 'var(--t-surface)',
                            color: filter === 'closed' ? 'var(--t-primary)' : 'var(--t-text-2)',
                            fontSize: 13, fontWeight: 600, cursor: 'pointer'
                        }}
                    >
                        Closed
                    </button>
                </div>
            </div>

            {/* Content Area */}
            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid var(--t-border)', borderTopColor: 'var(--t-primary)', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : error ? (
                <div style={{
                    background: 'var(--t-danger-bg)', borderRadius: 16, border: '1px solid var(--t-danger)',
                    padding: '32px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center'
                }}>
                    <FiAlertCircle style={{ fontSize: 32, color: 'var(--t-danger)', marginBottom: 12 }} />
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-danger)' }}>Failed to load assignments</h3>
                    <p style={{ margin: '0 0 16px 0', color: 'var(--t-text-2)', fontSize: 14 }}>{error}</p>
                    <button
                        className="t-btn"
                        onClick={loadAssignments}
                        style={{
                            padding: '8px 16px', background: 'var(--t-primary)', color: '#fff', border: 'none',
                            borderRadius: 8, fontWeight: 600, fontSize: 13, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6
                        }}
                    >
                        <FiRefreshCw /> Retry
                    </button>
                </div>
            ) : (
                <div style={{
                    display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(320px, 100%), 1fr))', gap: 20
                }}>
                    {filteredAssignments.map(assign => {
                        const totalStudents = assign.total_students || 0
                        const progress = totalStudents > 0 ? Math.min(100, Math.round(((assign.submissions_count || 0) / totalStudents) * 100)) : 0
                        const dueDateObj = assign.due_date ? new Date(assign.due_date) : null
                        const hasValidDate = dueDateObj && !isNaN(dueDateObj.getTime())
                        const isClosed = hasValidDate ? dueDateObj < new Date() : false

                        return (
                            <div key={assign.id} className="t-card" style={{
                                background: 'var(--t-surface)', borderRadius: 16, border: '1px solid var(--t-border)',
                                padding: 20, display: 'flex', flexDirection: 'column',
                                boxShadow: '0 2px 8px rgba(0,0,0,0.04)',
                                opacity: isClosed ? 0.75 : 1
                            }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 12 }}>
                                    <div style={{
                                        background: 'var(--t-primary-soft)', color: 'var(--t-primary)',
                                        padding: '4px 10px', borderRadius: 6, fontSize: 11, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.5px'
                                    }}>
                                        {assign.subject || 'Subject'}
                                        {assign.grade ? ` • Grade ${assign.grade}` : ''}
                                    </div>
                                    <div style={{
                                        background: isClosed ? 'var(--t-danger-bg)' : 'var(--t-warning-bg)',
                                        color: isClosed ? 'var(--t-danger)' : 'var(--t-warning)',
                                        padding: '4px 8px', borderRadius: 6, fontSize: 11, fontWeight: 700, display: 'flex', alignItems: 'center', gap: 4
                                    }}>
                                        <FiClock /> {isClosed ? 'Closed' : (hasValidDate ? 'Due ' + dueDateObj.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) : 'No Due Date')}
                                    </div>
                                </div>

                                <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)', lineHeight: 1.3 }}>{assign.title}</h3>
                                <p style={{ margin: '0 0 16px 0', color: 'var(--t-text-3)', fontSize: 13, lineHeight: 1.5, display: '-webkit-box', WebKitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                                    {assign.description || 'No description provided.'}
                                </p>

                                <div style={{ marginTop: 'auto' }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 12, color: 'var(--t-text-2)', fontWeight: 600, marginBottom: 8 }}>
                                        <span>Submissions ({assign.ungraded_count || 0} pending)</span>
                                        <span>{assign.submissions_count || 0} / {totalStudents}</span>
                                    </div>
                                    <div style={{ width: '100%', height: 6, background: 'var(--t-bg)', borderRadius: 4, overflow: 'hidden', marginBottom: 16 }}>
                                        <div style={{ width: `${progress}%`, height: '100%', background: isClosed ? 'var(--t-text-3)' : 'var(--t-primary)', borderRadius: 4 }} />
                                    </div>

                                    <button
                                        onClick={() => handleOpenGrading(assign)}
                                        className="t-btn"
                                        style={{
                                            width: '100%', minHeight: 44, padding: '10px', background: 'var(--t-primary)', border: 'none',
                                            borderRadius: 10, color: '#fff', fontWeight: 600, fontSize: 13, cursor: 'pointer',
                                            display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 6
                                        }}
                                    >
                                        <FiFileText /> Grade Papers {assign.ungraded_count > 0 ? `(${assign.ungraded_count})` : ''}
                                    </button>
                                </div>
                            </div>
                        )
                    })}

                    {filteredAssignments.length === 0 && (
                        <div style={{
                            gridColumn: '1 / -1', background: 'var(--t-surface)', borderRadius: 16, border: '1px dashed var(--t-border)',
                            padding: '48px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center'
                        }}>
                            <div style={{ width: 64, height: 64, borderRadius: 16, background: 'var(--t-primary-soft)', color: 'var(--t-primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>
                                <FiFileText />
                            </div>
                            <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>No assignments found</h3>
                            <p style={{ margin: '0 0 20px 0', color: 'var(--t-text-3)', fontSize: 14 }}>Create an assignment to get started.</p>
                            <button
                                className="t-btn"
                                onClick={() => setIsCreateModalOpen(true)}
                                style={{
                                    padding: '10px 20px', minHeight: 44, background: 'var(--t-primary)', color: '#fff', border: 'none',
                                    borderRadius: 10, fontWeight: 600, fontSize: 14, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8
                                }}
                            >
                                <FiPlus /> Create Assignment
                            </button>
                        </div>
                    )}
                </div>
            )}

            {/* Grade Papers Drawer / Modal */}
            {gradingModalOpen && (
                <div className="teacher-modal-overlay">
                    <div className="teacher-modal-dialog" style={{ maxWidth: 700 }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '20px 24px', borderBottom: '1px solid var(--t-border)', background: 'var(--t-surface)' }}>
                            <div>
                                <h2 style={{ fontSize: 18, fontWeight: 800, color: 'var(--t-text)', margin: '0 0 4px 0' }}>
                                    Grade Papers: {selectedAssignment?.title}
                                </h2>
                                <p style={{ fontSize: 13, color: 'var(--t-text-3)', margin: 0 }}>
                                    {submissions.length} submission{submissions.length === 1 ? '' : 's'} received
                                </p>
                            </div>
                            <button
                                onClick={() => { setGradingModalOpen(false); setSelectedAssignment(null); }}
                                className="t-icon-btn"
                                aria-label="Close grading modal"
                                style={{
                                    width: 44, height: 44, minWidth: 44, minHeight: 44, borderRadius: '50%', background: 'var(--t-bg)', border: 'none',
                                    display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--t-text-3)', cursor: 'pointer'
                                }}
                            >
                                <FiX />
                            </button>
                        </div>

                        <div className="teacher-modal-body">
                            {loadingSubmissions ? (
                                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                                    <div style={{ width: 40, height: 40, border: '4px solid var(--t-border)', borderTopColor: 'var(--t-primary)', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                                </div>
                            ) : submissions.length === 0 ? (
                                <div style={{ padding: '40px 20px', textAlign: 'center', color: 'var(--t-text-3)', fontSize: 14 }}>
                                    No student submissions received for this assignment yet.
                                </div>
                            ) : (
                                <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                                    {submissions.map(sub => {
                                        const draft = gradeDrafts[sub.id] || { marks: '', feedback: '' }
                                        const isSaving = savingSubId === sub.id
                                        const isGraded = sub.status === 'graded'

                                        return (
                                            <div key={sub.id} style={{
                                                border: '1px solid var(--t-border)', borderRadius: 12, padding: 18,
                                                background: 'var(--t-bg)'
                                            }}>
                                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 12, flexWrap: 'wrap', gap: 8 }}>
                                                    <div>
                                                        <div style={{ fontSize: 15, fontWeight: 700, color: 'var(--t-text)' }}>
                                                            {sub.student?.name || 'Student'}
                                                        </div>
                                                        <div style={{ fontSize: 12, color: 'var(--t-text-3)' }}>
                                                            {sub.student?.email} • Submitted: {sub.submitted_at ? new Date(sub.submitted_at).toLocaleString() : 'N/A'}
                                                        </div>
                                                    </div>
                                                    <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                                        <span style={{
                                                            padding: '4px 10px', borderRadius: 99, fontSize: 12, fontWeight: 700,
                                                            background: isGraded ? 'var(--t-success-bg)' : 'var(--t-warning-bg)',
                                                            color: isGraded ? 'var(--t-success)' : 'var(--t-warning)',
                                                            textTransform: 'capitalize'
                                                        }}>
                                                            {isGraded ? `Graded: ${sub.marks}/100` : 'Pending'}
                                                        </span>
                                                        {sub.file_url ? (
                                                            <a
                                                                href={sub.file_url}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="t-tap"
                                                                style={{
                                                                    minHeight: 44, padding: '8px 12px', borderRadius: 8, background: 'var(--t-surface)',
                                                                    border: '1px solid var(--t-border)', color: 'var(--t-primary)',
                                                                    fontSize: 12, fontWeight: 600, textDecoration: 'none', display: 'flex', alignItems: 'center', gap: 4
                                                                }}
                                                            >
                                                                <FiDownload /> View File
                                                            </a>
                                                        ) : (
                                                            <span style={{ fontSize: 12, color: 'var(--t-text-3)' }}>No attachment</span>
                                                        )}
                                                    </div>
                                                </div>

                                                {/* Grading form */}
                                                <div className="t-grading-row">
                                                    <div>
                                                        <label style={{ display: 'block', fontSize: 11, fontWeight: 600, color: 'var(--t-text-3)', marginBottom: 4 }}>Marks (0-100)</label>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            max="100"
                                                            value={draft.marks}
                                                            onChange={e => setGradeDrafts({
                                                                ...gradeDrafts,
                                                                [sub.id]: { ...draft, marks: e.target.value }
                                                            })}
                                                            placeholder="e.g. 85"
                                                            style={{
                                                                width: '100%', padding: '8px 10px', borderRadius: 8,
                                                                border: '1px solid var(--t-border)', background: 'var(--t-surface)',
                                                                fontSize: 13, outline: 'none', boxSizing: 'border-box'
                                                            }}
                                                        />
                                                    </div>
                                                    <div>
                                                        <label style={{ display: 'block', fontSize: 11, fontWeight: 600, color: 'var(--t-text-3)', marginBottom: 4 }}>Feedback</label>
                                                        <input
                                                            type="text"
                                                            value={draft.feedback}
                                                            onChange={e => setGradeDrafts({
                                                                ...gradeDrafts,
                                                                [sub.id]: { ...draft, feedback: e.target.value }
                                                            })}
                                                            placeholder="Feedback comments..."
                                                            style={{
                                                                width: '100%', padding: '8px 10px', borderRadius: 8,
                                                                border: '1px solid var(--t-border)', background: 'var(--t-surface)',
                                                                fontSize: 13, outline: 'none', boxSizing: 'border-box'
                                                            }}
                                                        />
                                                    </div>
                                                    <div style={{ alignSelf: 'flex-end' }}>
                                                        <button
                                                            onClick={() => handleSaveGrade(sub.id)}
                                                            disabled={isSaving}
                                                            className="t-btn"
                                                            style={{
                                                                minHeight: 44, padding: '8px 16px', background: 'var(--t-primary)', color: '#fff',
                                                                border: 'none', borderRadius: 8, fontSize: 13, fontWeight: 600,
                                                                cursor: isSaving ? 'not-allowed' : 'pointer', opacity: isSaving ? 0.7 : 1
                                                            }}
                                                        >
                                                            {isSaving ? 'Saving...' : 'Save'}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        )
                                    })}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Create Assignment Modal */}
            {isCreateModalOpen && (
                <div className="teacher-modal-overlay">
                    <div className="teacher-modal-dialog" style={{ maxWidth: 500 }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '20px 24px', borderBottom: '1px solid var(--t-border)', background: 'var(--t-surface)' }}>
                            <h2 style={{ fontSize: 18, fontWeight: 800, color: 'var(--t-text)', margin: 0 }}>Create New Assignment</h2>
                            <button
                                onClick={() => setIsCreateModalOpen(false)}
                                className="t-icon-btn"
                                aria-label="Close create assignment modal"
                                style={{
                                    width: 44, height: 44, minWidth: 44, minHeight: 44, borderRadius: '50%', background: 'var(--t-bg)', border: 'none',
                                    display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--t-text-3)', cursor: 'pointer'
                                }}
                            >
                                <FiX />
                            </button>
                        </div>
                        <div className="teacher-modal-body">
                            <form onSubmit={handleCreateSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 6 }}>Title*</label>
                                    <input
                                        type="text" required
                                        value={newAssignment.title}
                                        onChange={e => setNewAssignment({ ...newAssignment, title: e.target.value })}
                                        placeholder="e.g. Weekly Chemistry Homework"
                                        style={{ width: '100%', padding: '10px 14px', borderRadius: 8, border: '1px solid var(--t-border)', background: 'var(--t-bg)', color: 'var(--t-text)', boxSizing: 'border-box' }}
                                    />
                                </div>

                                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(180px, 100%), 1fr))', gap: 12 }}>
                                    <div>
                                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 6 }}>Subject*</label>
                                        <input
                                            type="text" required
                                            value={newAssignment.subject}
                                            onChange={e => setNewAssignment({ ...newAssignment, subject: e.target.value })}
                                            placeholder="Chemistry"
                                            style={{ width: '100%', padding: '10px 14px', borderRadius: 8, border: '1px solid var(--t-border)', background: 'var(--t-bg)', color: 'var(--t-text)', boxSizing: 'border-box' }}
                                        />
                                    </div>
                                    <div>
                                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 6 }}>Grade</label>
                                        <input
                                            type="text"
                                            value={newAssignment.grade}
                                            onChange={e => setNewAssignment({ ...newAssignment, grade: e.target.value })}
                                            placeholder="10"
                                            style={{ width: '100%', padding: '10px 14px', borderRadius: 8, border: '1px solid var(--t-border)', background: 'var(--t-bg)', color: 'var(--t-text)', boxSizing: 'border-box' }}
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 6 }}>Due Date</label>
                                    <input
                                        type="datetime-local"
                                        value={newAssignment.due_date}
                                        onChange={e => setNewAssignment({ ...newAssignment, due_date: e.target.value })}
                                        style={{ width: '100%', padding: '10px 14px', borderRadius: 8, border: '1px solid var(--t-border)', background: 'var(--t-bg)', color: 'var(--t-text)', boxSizing: 'border-box' }}
                                    />
                                </div>

                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 6 }}>Description</label>
                                    <textarea
                                        rows="3"
                                        value={newAssignment.description}
                                        onChange={e => setNewAssignment({ ...newAssignment, description: e.target.value })}
                                        placeholder="Detailed instructions for the students..."
                                        style={{ width: '100%', padding: '10px 14px', borderRadius: 8, border: '1px solid var(--t-border)', background: 'var(--t-bg)', color: 'var(--t-text)', resize: 'vertical', boxSizing: 'border-box' }}
                                    />
                                </div>

                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 6 }}>Attachment (Optional)</label>
                                    <input
                                        type="file"
                                        onChange={e => setAssignmentFile(e.target.files[0])}
                                        style={{ width: '100%', fontSize: 13, color: 'var(--t-text-2)' }}
                                    />
                                </div>

                                <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 12, marginTop: 8 }}>
                                    <button
                                        type="button"
                                        className="t-btn"
                                        onClick={() => setIsCreateModalOpen(false)}
                                        style={{ padding: '10px 18px', minHeight: 44, borderRadius: 8, border: '1px solid var(--t-border)', background: 'var(--t-surface)', color: 'var(--t-text-2)', fontWeight: 600, cursor: 'pointer' }}
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={createLoading}
                                        className="t-btn"
                                        style={{ padding: '10px 20px', minHeight: 44, borderRadius: 8, border: 'none', background: 'var(--t-primary)', color: '#fff', fontWeight: 600, cursor: createLoading ? 'not-allowed' : 'pointer', opacity: createLoading ? 0.7 : 1 }}
                                    >
                                        {createLoading ? 'Please wait...' : 'Create Assignment'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </div>
    )
}
