import React, { useState, useEffect, useRef } from 'react'
import {
    FiFileText, FiClock, FiCheckCircle, FiUpload, FiAlertCircle,
    FiX, FiExternalLink, FiRefreshCw
} from 'react-icons/fi'
import { getStudentAssignments, submitStudentAssignment } from '../../services/dashboardService'
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
                style={{
                    background: '#fff', borderRadius: 16, width: '100%', maxWidth: 500,
                    boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1)', overflow: 'hidden',
                    maxHeight: '90dvh', display: 'flex', flexDirection: 'column'
                }}
                onClick={(e) => e.stopPropagation()}
            >
                <div style={{
                    padding: '20px 24px', borderBottom: '1px solid #e2e8f0',
                    display: 'flex', justifyContent: 'space-between', alignItems: 'center'
                }}>
                    <div>
                        <h3 style={{ margin: 0, fontSize: 18, fontWeight: 700, color: '#1e293b' }}>
                            Submit Assignment
                        </h3>
                        <p style={{ margin: '4px 0 0', fontSize: 13, color: '#64748b' }}>
                            {assignment.title}
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        aria-label="Close"
                        style={{
                            background: '#f1f5f9', border: 'none', width: 36, height: 36,
                            borderRadius: '50%', display: 'flex', alignItems: 'center',
                            justifyContent: 'center', cursor: 'pointer', color: '#64748b'
                        }}
                    >
                        <FiX size={18} />
                    </button>
                </div>

                <form onSubmit={handleSubmit} style={{ padding: '24px', overflowY: 'auto' }}>
                    {error && (
                        <div style={{
                            marginBottom: 16, padding: '12px 14px', borderRadius: 10,
                            background: '#fef2f2', border: '1px solid #fecaca',
                            color: '#b91c1c', fontSize: 13, display: 'flex', alignItems: 'center', gap: 8
                        }}>
                            <FiAlertCircle size={16} style={{ flexShrink: 0 }} />
                            <span>{error}</span>
                        </div>
                    )}

                    <div style={{ marginBottom: 20 }}>
                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#334155', marginBottom: 8 }}>
                            Upload Work (.pdf, .doc, .docx, .jpg, .png, .zip — max 10MB)
                        </label>
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"
                            onChange={handleFileChange}
                            style={{
                                width: '100%', padding: '10px', borderRadius: 10,
                                border: '1px dashed #cbd5e1', background: '#f8fafc',
                                fontSize: 14, color: '#475569', cursor: 'pointer'
                            }}
                        />
                        {file && (
                            <div style={{ marginTop: 8, fontSize: 13, color: '#059669', fontWeight: 600 }}>
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
                                padding: '10px 18px', borderRadius: 10, border: '1px solid #cbd5e1',
                                background: '#fff', color: '#475569', fontWeight: 600,
                                fontSize: 14, cursor: submitting ? 'not-allowed' : 'pointer'
                            }}
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={!file || submitting}
                            style={{
                                padding: '10px 20px', borderRadius: 10, border: 'none',
                                background: submitting || !file ? '#94a3b8' : '#4f0bd9',
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
        load()
    }, [])

    const pendingCount = assignments.filter(a => {
        const sub = a.submissions && a.submissions.length > 0 ? a.submissions[0] : null
        return !sub
    }).length

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
                        <div style={{ width: 6, height: 6, borderRadius: '50%', background: '#f97316' }} /> {pendingCount} Pending
                    </div>
                </div>
            </div>

            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#4f0bd9', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : error ? (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid #fee2e2', padding: '40px 20px', textAlign: 'center' }}>
                    <FiAlertCircle size={36} style={{ color: '#ef4444', marginBottom: 12 }} />
                    <h3 style={{ margin: '0 0 8px', fontSize: 18, fontWeight: 700, color: '#991b1b' }}>Failed to load assignments</h3>
                    <p style={{ margin: '0 0 20px', color: '#64748b', fontSize: 14 }}>{error}</p>
                    <button
                        onClick={load}
                        style={{
                            padding: '10px 20px', borderRadius: 10, background: '#4f0bd9',
                            color: '#fff', border: 'none', fontWeight: 600, cursor: 'pointer',
                            display: 'inline-flex', alignItems: 'center', gap: 8
                        }}
                    >
                        <FiRefreshCw /> Retry
                    </button>
                </div>
            ) : assignments.length === 0 ? (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px dashed #cbd5e1', padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center' }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: '#f1f5f9', color: '#94a3b8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}><FiFileText /></div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#334155' }}>No assignments</h3>
                    <p style={{ margin: 0, color: '#64748b', fontSize: 14 }}>You have no assignments at the moment. Great job!</p>
                </div>
            ) : (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(320px, 100%), 1fr))', gap: 20 }}>
                    {assignments.map(assign => {
                        const submission = assign.submissions && assign.submissions.length > 0 ? assign.submissions[0] : null
                        const hasSubmission = Boolean(submission)
                        const isGraded = hasSubmission && submission.status === 'graded'
                        const isAwaitingGrade = hasSubmission && !isGraded

                        let statusColor = '#f59e0b'; let statusBg = '#fffbeb'; let statusText = 'Pending'
                        if (isAwaitingGrade) { statusColor = '#4f0bd9'; statusBg = '#f3e8ff'; statusText = 'Submitted - awaiting grade'; }
                        if (isGraded) { statusColor = '#10b981'; statusBg = '#ecfdf5'; statusText = 'Graded'; }

                        const dueDate = assign.due_date ? new Date(assign.due_date) : null
                        const isOverdue = !hasSubmission && dueDate && dueDate < new Date()

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
                                <div style={{ color: '#64748b', fontSize: 13, fontWeight: 500, marginBottom: 16 }}>By {assign.teacher?.full_name || assign.teacher?.name || 'Your Teacher'}</div>
                                
                                <p style={{ margin: '0 0 16px 0', color: '#475569', fontSize: 13, lineHeight: 1.5, display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                                    {assign.description}
                                </p>

                                {isAwaitingGrade && (
                                    <div style={{ marginBottom: 12, fontSize: 12, color: '#64748b' }}>
                                        Submitted on: {new Date(submission.submitted_at || submission.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}
                                    </div>
                                )}

                                {isGraded && submission?.feedback && (
                                    <div style={{ marginBottom: 12, padding: '10px 12px', background: '#f8fafc', borderRadius: 8, border: '1px solid #e2e8f0', fontSize: 13, color: '#334155' }}>
                                        <div style={{ fontWeight: 700, fontSize: 11, color: '#64748b', textTransform: 'uppercase', marginBottom: 4 }}>Teacher Feedback</div>
                                        <div>{submission.feedback}</div>
                                    </div>
                                )}

                                <div style={{ marginTop: 'auto', paddingTop: 16, borderTop: '1px solid #f1f5f9' }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 6, color: isOverdue ? '#ef4444' : '#64748b', fontSize: 13, fontWeight: 600 }}>
                                            {isOverdue ? <FiAlertCircle /> : <FiClock />} 
                                            {dueDate ? `${isOverdue ? 'Overdue' : 'Due'} ${dueDate.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}` : 'No due date'}
                                        </div>
                                        {isGraded && (
                                            <div style={{ fontSize: 14, fontWeight: 800, color: '#10b981' }}>{submission.marks}/100</div>
                                        )}
                                    </div>

                                    {!hasSubmission && (
                                        <button 
                                            onClick={() => setSelectedAssignment(assign)}
                                            style={{
                                                width: '100%', padding: '10px', background: '#4f0bd9',
                                                border: 'none', borderRadius: 10, color: '#fff', fontWeight: 600, fontSize: 14, cursor: 'pointer',
                                                display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                                                boxShadow: '0 4px 12px rgba(79,11,217,0.25)', transition: 'all 0.2s'
                                            }}
                                            onMouseEnter={e => e.currentTarget.style.transform = 'translateY(-1px)'}
                                            onMouseLeave={e => e.currentTarget.style.transform = 'translateY(0)'}
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
                                                        flex: 1, padding: '10px', background: '#f8fafc', border: '1px solid #e2e8f0',
                                                        borderRadius: 10, color: '#475569', fontWeight: 600, fontSize: 13, cursor: 'pointer',
                                                        display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 6
                                                    }}
                                                >
                                                    <FiCheckCircle style={{ color: '#4f0bd9' }} /> View Submission
                                                </button>
                                            )}
                                            <button 
                                                onClick={() => setSelectedAssignment(assign)}
                                                style={{
                                                    flex: 1, padding: '10px', background: '#fff', border: '1px solid #4f0bd9',
                                                    borderRadius: 10, color: '#4f0bd9', fontWeight: 600, fontSize: 13, cursor: 'pointer',
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
                                                width: '100%', padding: '10px', background: '#f8fafc', border: '1px solid #e2e8f0',
                                                borderRadius: 10, color: '#475569', fontWeight: 600, fontSize: 14, cursor: 'pointer',
                                                display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8
                                            }}
                                        >
                                            <FiCheckCircle style={{ color: '#10b981' }} /> View Submission
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
