import React, { useState, useEffect } from 'react'
import { FiAlertCircle } from 'react-icons/fi'
import StudentRegistrationForm from '../../components/StudentRegistrationForm'
import SubjectUpdateModal, { formatPendingMediumMessage } from '../../components/student/SubjectUpdateModal'
import { getStudentStats, cancelSubjectTopup } from '../../services/dashboardService'
import { useLanguage } from '../../context/LanguageContext'

export default function StudentSettings() {
    const [isAddSiblingModalOpen, setIsAddSiblingModalOpen] = useState(false)
    const [isSubjectModalOpen, setIsSubjectModalOpen] = useState(false)
    const [stats, setStats] = useState(null)
    const [loading, setLoading] = useState(true)

    let t = (k, def) => def
    let language = 'en'
    try {
        const lang = useLanguage()
        if (lang) {
            t = lang.t
            language = lang.language
        }
    } catch (_) {}

    useEffect(() => {
        async function loadData() {
            try {
                const data = await getStudentStats()
                setStats(data)
            } catch (err) {
                console.error("Failed to load student stats", err)
            } finally {
                setLoading(false)
            }
        }
        loadData()
    }, [])

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24
            }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: 'var(--t-text)', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Settings</h1>
                    <p style={{ color: 'var(--t-text-muted)', margin: 0, fontSize: 14 }}>Manage your profile and linked accounts</p>
                </div>
            </div>

            {/* Content Area */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: 24 }}>
                
                {/* Subjects & Medium */}
                <div className="t-card" style={{ background: '#fff', borderRadius: 16, border: '1px solid var(--t-border)', padding: '32px', boxShadow: 'var(--t-shadow-sm)' }}>
                    <h3 style={{ margin: '0 0 24px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>Subjects & Medium</h3>

                    {stats?.user?.pending_topup && (
                        <div
                            className="status-card-topup-pending"
                            data-testid="settings-topup-pending-card"
                            style={{
                                background: '#fffbeb',
                                color: '#92400e',
                                border: '1px solid #fde68a',
                                borderRadius: 12,
                                padding: '16px 18px',
                                fontSize: 14,
                                fontWeight: 500,
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                                gap: 14,
                                marginBottom: 20,
                                lineHeight: 1.5,
                                boxSizing: 'border-box',
                                flexWrap: 'wrap'
                            }}
                        >
                            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                <FiAlertCircle style={{ flexShrink: 0, fontSize: 20, color: '#d97706' }} />
                                <div>
                                    <div style={{ fontWeight: 700, color: '#92400e' }}>
                                        Subject Top-up Pending (Rs {parseFloat(stats.user.pending_topup.amount || 0).toFixed(0)} for {(stats.user.pending_topup.added_subjects || []).join(', ')})
                                    </div>
                                    <div style={{ fontSize: 13, color: '#b45309', marginTop: 2 }}>
                                        Added subjects remain locked until paid.
                                    </div>
                                </div>
                            </div>
                            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                                <button
                                    onClick={() => setIsSubjectModalOpen(true)}
                                    data-testid="settings-pay-topup-btn"
                                    style={{
                                        padding: '8px 16px', borderRadius: 8, border: 'none', background: '#d97706',
                                        color: '#fff', fontSize: 13, fontWeight: 600, cursor: 'pointer', whiteSpace: 'nowrap'
                                    }}
                                >
                                    Pay Top-up
                                </button>
                                <button
                                    onClick={async () => {
                                        if (window.confirm('Remove added subjects and cancel top-up?')) {
                                            try {
                                                await cancelSubjectTopup(stats.user.pending_topup.id);
                                                const refreshed = await getStudentStats();
                                                setStats(refreshed);
                                            } catch (e) {
                                                alert(e.message || 'Failed to cancel');
                                            }
                                        }
                                    }}
                                    data-testid="settings-cancel-topup-btn"
                                    style={{
                                        padding: '7px 14px', borderRadius: 8, border: '1px solid #d1d5db', background: '#fff',
                                        color: '#4b5563', fontSize: 13, fontWeight: 500, cursor: 'pointer', whiteSpace: 'nowrap'
                                    }}
                                >
                                    Remove Added Subjects
                                </button>
                            </div>
                        </div>
                    )}
                    
                    {stats?.user?.pending_medium && (
                        <div
                            className="status-card-pending-medium"
                            data-testid="settings-pending-medium-card"
                            style={{
                                background: '#fffbeb',
                                color: '#92400e',
                                border: '1px solid #fde68a',
                                borderRadius: 12,
                                padding: '14px 16px',
                                fontSize: 14,
                                fontWeight: 500,
                                display: 'flex',
                                alignItems: 'center',
                                gap: 10,
                                marginBottom: 20,
                                lineHeight: 1.5,
                                boxSizing: 'border-box'
                            }}
                        >
                            <FiAlertCircle style={{ flexShrink: 0, fontSize: 18, color: '#d97706' }} />
                            <span>
                                {formatPendingMediumMessage(stats.user.medium, language, t)}
                            </span>
                        </div>
                    )}

                    <div style={{ marginBottom: 24 }}>
                        <p style={{ fontSize: 14, color: 'var(--t-text-muted)' }}>
                            Update your medium of learning and manage the subjects you are enrolled in. Any changes to your subjects may affect your monthly fee.
                        </p>
                    </div>
                    
                    <div style={{ background: 'var(--t-bg)', padding: 24, borderRadius: 16, border: '1px solid var(--t-border)' }}>
                        <h4 style={{ margin: '0 0 16px 0', fontSize: 16, fontWeight: 700, color: 'var(--t-text)' }}>Update Learning Preferences</h4>
                        <button 
                            disabled={loading}
                            onClick={() => setIsSubjectModalOpen(true)} 
                            style={{
                                minHeight: 44, padding: '10px 24px', background: 'var(--t-primary)', color: '#fff', border: 'none', borderRadius: 10,
                                fontWeight: 600, fontSize: 14, cursor: loading ? 'not-allowed' : 'pointer',
                                opacity: loading ? 0.7 : 1, boxShadow: '0 4px 12px rgba(79, 11, 217, 0.2)'
                            }}>
                            {loading ? 'Loading...' : 'Edit Subjects & Medium'}
                        </button>
                    </div>
                </div>

                {/* Siblings */}
                <div className="t-card" style={{ background: '#fff', borderRadius: 16, border: '1px solid var(--t-border)', padding: '32px', boxShadow: 'var(--t-shadow-sm)' }}>
                    <h3 style={{ margin: '0 0 24px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>Siblings</h3>
                    <div style={{ marginBottom: 24 }}>
                        <p style={{ fontSize: 14, color: 'var(--t-text-muted)' }}>
                            If you have siblings studying with us, you can add them to this same account. This avoids needing separate emails and passwords. Each sibling will have their own dashboard and payment portal.
                        </p>
                    </div>
                    
                    <div style={{ background: 'var(--t-bg)', padding: 24, borderRadius: 16, border: '1px solid var(--t-border)' }}>
                        <h4 style={{ margin: '0 0 16px 0', fontSize: 16, fontWeight: 700, color: 'var(--t-text)' }}>Add New Sibling</h4>
                        <button onClick={() => setIsAddSiblingModalOpen(true)} style={{
                            minHeight: 44, padding: '10px 24px', background: 'var(--t-primary)', color: '#fff', border: 'none', borderRadius: 10,
                            fontWeight: 600, fontSize: 14, cursor: 'pointer', boxShadow: '0 4px 12px rgba(79, 11, 217, 0.2)'
                        }}>
                            Open Add Sibling Form
                        </button>
                    </div>
                </div>
            </div>

            {/* Modals are rendered OUTSIDE .t-card: its hover transform would otherwise
                turn position:fixed overlays into card-relative (broken layout). */}
            {isSubjectModalOpen && stats?.user && (
                <SubjectUpdateModal
                    isOpen={isSubjectModalOpen}
                    onClose={() => setIsSubjectModalOpen(false)}
                    currentGrade={stats.user.current_grade}
                    medium={stats.user.medium}
                    pendingMedium={stats.user.pending_medium}
                    stream={stats.user.stream}
                    initialSubjects={stats.user.selected_subjects}
                    onUpdateSuccess={(newSubjects, newPendingMedium) => {
                        setStats(prev => ({
                            ...prev,
                            user: {
                                ...prev.user,
                                needs_subject_review: false,
                                selected_subjects: newSubjects,
                                ...(newPendingMedium ? { pending_medium: newPendingMedium } : {})
                            }
                        }))
                        setIsSubjectModalOpen(false)
                    }}
                />
            )}

            {isAddSiblingModalOpen && (
                <StudentRegistrationForm
                    isOpen={isAddSiblingModalOpen}
                    onClose={() => setIsAddSiblingModalOpen(false)}
                    isAddSiblingMode={true}
                />
            )}
        </div>
    )
}
