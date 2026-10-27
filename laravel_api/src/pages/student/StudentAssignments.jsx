import React, { useState, useEffect, useRef } from 'react'
import {
    FiFileText, FiClock, FiCheckCircle, FiUpload, FiAlertCircle,
    FiX, FiExternalLink, FiRefreshCw, FiLock
} from 'react-icons/fi'
import { getStudentAssignments, submitStudentAssignment } from '../../services/dashboardService'
import { getCurrentUser } from '../../services/authService'
import { useToast } from '../../components/shared/ToastContext'

const MAX_FILE_SIZE = 10 * 1024 * 1024 // 10MB
const ALLOWED_EXTENSIONS = ['.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png', '.zip']

function SubmitModal({ assignment, isOpen, onClose, onSuccess }) {
    const toast = useToast()
    const [file, setFile] = useState(null)
    const [error, setError] = useState(null)
    const [submitting, setSubmitting] = useState(false)
    const fileInputRef = useRef(null)

    useEffect(() => {
        if (!isOpen) {
            setFile(null)
            setError(null)
            setSubmitting(false)
        }
    }, [isOpen])

    if (!isOpen || !assignment) return null

    const handleFileChange = (e) => {
        setError(null)
        const selected = e.target.files?.[0]
        if (!selected) return

        const ext = '.' + selected.name.split('.').pop().toLowerCase()
        if (!ALLOWED_EXTENSIONS.includes(ext)) {
            setError(`Invalid file type. Allowed: ${ALLOWED_EXTENSIONS.join(', ')}`)
            if (fileInputRef.current) fileInputRef.current.value = ''
            setFile(null)
            return
        }

        if (selected.size > MAX_FILE_SIZE) {
            setError('File size exceeds the 10 MB limit')
            if (fileInputRef.current) fileInputRef.current.value = ''
            setFile(null)
            return
        }

        setFile(selected)
    }

    const handleSubmit = async (e) => {
        e.preventDefault()
        if (!file || submitting) return

        setSubmitting(true)
        setError(null)
        try {
            await submitStudentAssignment(assignment.id, file)
            toast.success('Assignment submitted successfully!')
            onSuccess()
            onClose()
        } catch (err) {
            setError(err.message || 'Failed to submit assignment')
        } finally {
            setSubmitting(false)
        }
    }

    return (
        <div
            style={{
                position: 'fixed', inset: 0, zIndex: 1000,
                background: 'rgba(15, 23, 42, 0.6)',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                padding: 16
            }}
            onClick={onClose}
        >
            <div
                className="t-modal-container shared-modal-dialog"
                style={{
                    background: '#fff', borderRadius: 16, width: 'calc(100% - 24px)', maxWidth: 500,
                    boxShadow: 'var(--t-shadow-lg)', overflow: 'hidden',
                    maxHeight: '90dvh', display: 'flex', flexDirection: 'column'
                }}
                onClick={(e) => e.stopPropagation()}
            >
                <div style={{
                    padding: '20px 24px', borderBottom: '1px solid var(--t-border)',
                    display: 'flex', justifyContent: 'space-between', alignItems: 'center'
                }}>
                    <div>
                        <h3 style={{ margin: 0, fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>
                            Submit Assignment
                        </h3>
                        <p style={{ margin: '4px 0 0', fontSize: 13, color: 'var(--t-text-muted)' }}>
                            {assignment.title}
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        aria-label="Close submit modal"
                        style={{
                            background: 'var(--t-bg)', border: 'none', width: 44, height: 44,
                            borderRadius: '50%', display: 'flex', alignItems: 'center',
                            justifyContent: 'center', cursor: 'pointer', color: 'var(--t-text-muted)'
                        }}
                    >
                        <FiX size={18} />
                    </button>
                </div>

                <form onSubmit={handleSubmit} style={{ padding: '24px', overflowY: 'auto' }}>
                    {error && (
                        <div style={{
                            marginBottom: 16, padding: '12px 14px', borderRadius: 10,
                            background: 'var(--t-danger-bg)', border: '1px solid var(--t-danger)',
                            color: 'var(--t-danger)', fontSize: 13, display: 'flex', alignItems: 'center', gap: 8
                        }}>
                            <FiAlertCircle size={16} style={{ flexShrink: 0 }} />
                            <span>{error}</span>
                        </div>
                    )}

                    <div style={{ marginBottom: 20 }}>
                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text)', marginBottom: 8 }}>
                            Upload Work (.pdf, .doc, .docx, .jpg, .png, .zip — max 10MB)
                        </label>
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"
                            onChange={handleFileChange}
                            style={{
                                width: '100%', padding: '10px', borderRadius: 10,
                                border: '1px dashed var(--t-border)', background: 'var(--t-bg)',
                                fontSize: 16, color: 'var(--t-text-muted)', cursor: 'pointer'
                            }}
                        />
                        {file && (
                            <div style={{ marginTop: 8, fontSize: 13, color: 'var(--t-success)', fontWeight: 600 }}>
                                Selected: {file.name} ({(file.size / (1024 * 1024)).toFixed(2)} MB)
                            </div>
                        )}
                    </div>

                    <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
                        <button
                            type="button"
                            onClick={onClose}
                            disabled={submitting}
                            style={{
                                minHeight: 44, padding: '10px 18px', borderRadius: 10, border: '1px solid var(--t-border)',
                                background: '#fff', color: 'var(--t-text)', fontWeight: 600,
                                fontSize: 14, cursor: submitting ? 'not-allowed' : 'pointer'
                            }}
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={!file || submitting}
                            style={{
                                minHeight: 44, padding: '10px 20px', borderRadius: 10, border: 'none',
                                background: submitting || !file ? 'var(--t-border)' : 'var(--t-primary)',
                                color: '#fff', fontWeight: 700, fontSize: 14,
                                cursor: submitting || !file ? 'not-allowed' : 'pointer',
                                display: 'flex', alignItems: 'center', gap: 8
                            }}
                        >
                            <FiUpload /> {submitting ? 'Submitting...' : 'Upload & Submit'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    )
}

export default function StudentAssignments() {
    const [assignments, setAssignments] = useState([])
    const [lockedSubjects, setLockedSubjects] = useState([])
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState(null)
    const [selectedAssignment, setSelectedAssignment] = useState(null)

    const load = async () => {
        setLoading(true)
        setError(null)
        try {
            const data = await getStudentAssignments()
            setAssignments(Array.isArray(data) ? data : data?.data || [])
        } catch (e) {
            console.error('Failed to load assignments:', e)
            setError(e.message || 'Failed to load assignments')
        } finally {
            setLoading(false)
        }
    }

    useEffect(() => {
        getCurrentUser().then(u => {
            if (u && Array.isArray(u.locked_subjects)) {
                setLockedSubjects(u.locked_subjects)
            }
        }).catch(() => {})
        load()
    }, [])

    const pendingCount = assignments.filter(a => {
        const sub = a.submissions && a.submissions.length > 0 ? a.submissions[0] : null
        return !sub
    }).length

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24 }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: 'var(--t-text)', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Assignments</h1>
                    <p style={{ color: 'var(--t-text-muted)', margin: 0, fontSize: 14 }}>Track and submit your class homework</p>
                </div>
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'center' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '6px 12px', background: 'var(--t-warning-bg)', color: 'var(--t-warning)', borderRadius: 20, fontSize: 12, fontWeight: 700 }}>
                        <div style={{ width: 6, height: 6, borderRadius: '50%', background: 'var(--t-warning)' }} /> {pendingCount} Pending
                    </div>
                    {lockedSubjects.map(sub => (
                        <div
                            key={sub}
                            data-testid={`chip-subject-locked-${sub}`}
                            style={{
                                display: 'flex', alignItems: 'center', gap: 6,
                                padding: '6px 12px', background: '#fef3c7', color: '#92400e',
                                border: '1px solid #fde68a', borderRadius: 20, fontSize: 12, fontWeight: 700
                            }}
                        >
                            <FiLock size={12} /> {sub}: Locked (Top-up pending)
                        </div>
                    ))}
                </div>
            </div>

            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid var(--t-border)', borderTopColor: 'var(--t-primary)', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : error ? (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid var(--t-danger)', padding: '40px 20px', textAlign: 'center' }}>
                    <FiAlertCircle size={36} style={{ color: 'var(--t-danger)', marginBottom: 12 }} />
                    <h3 style={{ margin: '0 0 8px', fontSize: 18, fontWeight: 700, color: 'var(--t-danger)' }}>Failed to load assignments</h3>
                    <p style={{ margin: '0 0 20px', color: 'var(--t-text-muted)', fontSize: 14 }}>{error}</p>
                    <button
                        onClick={load}
                        style={{
                            minHeight: 44, padding: '10px 20px', borderRadius: 10, background: 'var(--t-primary)',
                            color: '#fff', border: 'none', fontWeight: 600, cursor: 'pointer',
                            display: 'inline-flex', alignItems: 'center', gap: 8
                        }}
                    >
                        <FiRefreshCw /> Retry
                    </button>
                </div>
            ) : assignments.length === 0 ? (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px dashed var(--t-border)', padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center' }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: 'var(--t-bg)', color: 'var(--t-text-muted)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}><FiFileText /></div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>No assignments</h3>
                    <p style={{ margin: 0, color: 'var(--t-text-muted)', fontSize: 14 }}>You have no assignments at the moment. Great job!</p>
                </div>
            ) : (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(320px, 100%), 1fr))', gap: 20 }}>
                    {assignments.map(assign => {
                        const submission = (assign.submissions && assign.submissions.length > 0) ? assign.submissions[0] : (assign.submission || null)
                        const hasSubmission = Boolean(submission)
                        const isGraded = hasSubmission && submission.status === 'graded'
                        const isAwaitingGrade = hasSubmission && !isGraded

                        let statusColor = 'var(--t-warning)'; let statusBg = 'var(--t-warning-bg)'; let statusText = 'Pending'
                        if (isAwaitingGrade) { statusColor = 'var(--t-primary)'; statusBg = 'var(--t-primary-soft)'; statusText = 'Submitted - awaiting grade'; }
                        if (isGraded) { statusColor = 'var(--t-success)'; statusBg = 'var(--t-success-bg)'; statusText = 'Graded'; }

                        const dueDate = assign.due_date ? new Date(assign.due_date) : null
                        const isOverdue = !hasSubmission && dueDate && dueDate < new Date()

                        return (
                            <div key={assign.id} className="t-card" style={{
                                background: '#fff', borderRadius: 16, border: '1px solid var(--t-border)',
                                padding: 20, display: 'flex', flexDirection: 'column',
                                boxShadow: 'var(--t-shadow-sm)'
                            }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 12 }}>
                                    <div style={{
                                        background: 'var(--t-bg)', color: 'var(--t-text-muted)',
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

                                <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)', lineHeight: 1.3 }}>{assign.title}</h3>
                                <div style={{ color: 'var(--t-text-muted)', fontSize: 13, fontWeight: 500, marginBottom: 16 }}>By {assign.teacher?.full_name || assign.teacher?.name || 'Your Teacher'}</div>
                                
                                <p style={{ margin: '0 0 16px 0', color: 'var(--t-text-muted)', fontSize: 13, lineHeight: 1.5, display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                                    {assign.description}
                                </p>

                                {isAwaitingGrade && (
                                    <div style={{ marginBottom: 12, fontSize: 12, color: 'var(--t-text-muted)' }}>
                                        Submitted on: {new Date(submission.submitted_at || submission.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}
                                    </div>
                                )}

                                {isGraded && submission?.feedback && (
                                    <div style={{ marginBottom: 12, padding: '10px 12px', background: 'var(--t-bg)', borderRadius: 8, border: '1px solid var(--t-border)', fontSize: 13, color: 'var(--t-text)' }}>
                                        <div style={{ fontWeight: 700, fontSize: 11, color: 'var(--t-text-muted)', textTransform: 'uppercase', marginBottom: 4 }}>Teacher Feedback</div>
                                        <div>{submission.feedback}</div>
                                    </div>
                                )}

                                <div style={{ marginTop: 'auto', paddingTop: 16, borderTop: '1px solid var(--t-border)' }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 6, color: isOverdue ? 'var(--t-danger)' : 'var(--t-text-muted)', fontSize: 13, fontWeight: 600 }}>
                                            {isOverdue ? <FiAlertCircle /> : <FiClock />} 
                                            {dueDate ? `${isOverdue ? 'Overdue' : 'Due'} ${dueDate.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}` : 'No due date'}
                                        </div>
                                        {isGraded && (
                                            <div style={{ fontSize: 14, fontWeight: 800, color: 'var(--t-success)' }}>{submission.marks}/100</div>
                                        )}
                                    </div>

                                    {!hasSubmission && (
                                        <button 
                                            onClick={() => setSelectedAssignment(assign)}
                                            style={{
                                                minHeight: 44, width: '100%', padding: '10px', background: 'var(--t-primary)',
                                                border: 'none', borderRadius: 10, color: '#fff', fontWeight: 600, fontSize: 14, cursor: 'pointer',
                                                display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                                                boxShadow: '0 4px 12px rgba(79, 11, 217, 0.25)', transition: 'opacity 0.2s, transform 0.1s'
                                            }}
                                        >
                                            <FiUpload /> Submit Work
                                        </button>
                                    )}

                                    {isAwaitingGrade && (
                                        <div style={{ display: 'flex', gap: 8 }}>
                                            {submission.file_url && (
                                                <button 
                                                    onClick={() => window.open(submission.file_url, '_blank')}
                                                    style={{
                                                        minHeight: 44, flex: 1, padding: '10px', background: 'var(--t-bg)', border: '1px solid var(--t-border)',
                                                        borderRadius: 10, color: 'var(--t-text)', fontWeight: 600, fontSize: 13, cursor: 'pointer',
                                                        display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 6
                                                    }}
                                                >
                                                    <FiCheckCircle style={{ color: 'var(--t-primary)' }} /> View Submission
                                                </button>
                                            )}
                                            <button 
                                                onClick={() => setSelectedAssignment(assign)}
                                                style={{
                                                    minHeight: 44, flex: 1, padding: '10px', background: '#fff', border: '1px solid var(--t-primary)',
                                                    borderRadius: 10, color: 'var(--t-primary)', fontWeight: 600, fontSize: 13, cursor: 'pointer',
                                                    display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 6
                                                }}
                                            >
                                                <FiUpload /> Replace file
                                            </button>
                                        </div>
                                    )}

                                    {isGraded && submission.file_url && (
                                        <button 
                                            onClick={() => window.open(submission.file_url, '_blank')}
                                            style={{
                                                minHeight: 44, width: '100%', padding: '10px', background: 'var(--t-bg)', border: '1px solid var(--t-border)',
                                                borderRadius: 10, color: 'var(--t-text)', fontWeight: 600, fontSize: 14, cursor: 'pointer',
                                                display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8
                                            }}
                                        >
                                            <FiCheckCircle style={{ color: 'var(--t-success)' }} /> View Submission
                                        </button>
                                    )}
                                </div>
                            </div>
                        )
                    })}
                </div>
            )}

            <SubmitModal
                assignment={selectedAssignment}
                isOpen={Boolean(selectedAssignment)}
                onClose={() => setSelectedAssignment(null)}
                onSuccess={load}
            />
        </div>
    )
}
