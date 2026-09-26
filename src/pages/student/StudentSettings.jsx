import React, { useState, useEffect } from 'react'
import StudentRegistrationForm from '../../components/StudentRegistrationForm'
import SubjectUpdateModal from '../../components/student/SubjectUpdateModal'
import { getStudentStats } from '../../services/dashboardService'

export default function StudentSettings() {
    const [isAddSiblingModalOpen, setIsAddSiblingModalOpen] = useState(false)
    const [isSubjectModalOpen, setIsSubjectModalOpen] = useState(false)
    const [stats, setStats] = useState(null)
    const [loading, setLoading] = useState(true)

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
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24,
                '@media (minWidth: 640px)': { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }
            }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: '#1e293b', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Settings</h1>
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>Manage your profile and linked accounts</p>
                </div>
            </div>

            {/* Content Area */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: 24 }}>
                
                {/* Subjects & Medium */}
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0', padding: '32px', boxShadow: '0 1px 3px rgba(0,0,0,0.02)' }}>
                    <h3 style={{ margin: '0 0 24px 0', fontSize: 18, fontWeight: 700, color: '#1e293b' }}>Subjects & Medium</h3>
                    <div style={{ marginBottom: 24 }}>
                        <p style={{ fontSize: 14, color: '#64748b' }}>
                            Update your medium of learning and manage the subjects you are enrolled in. Any changes to your subjects may affect your monthly fee.
                        </p>
                    </div>
                    
                    <div style={{ background: '#f8fafc', padding: 24, borderRadius: 16, border: '1px solid #e2e8f0' }}>
                        <h4 style={{ margin: '0 0 16px 0', fontSize: 16, fontWeight: 700, color: '#334155' }}>Update Learning Preferences</h4>
                        <button 
                            disabled={loading}
                            onClick={() => setIsSubjectModalOpen(true)} 
                            style={{
                                padding: '10px 24px', background: '#6366f1', color: '#fff', border: 'none', borderRadius: 10,
                                fontWeight: 600, fontSize: 14, cursor: loading ? 'not-allowed' : 'pointer',
                                opacity: loading ? 0.7 : 1
                            }}>
                            {loading ? 'Loading...' : 'Edit Subjects & Medium'}
                        </button>

                        {isSubjectModalOpen && stats?.user && (
                            <SubjectUpdateModal 
                                isOpen={isSubjectModalOpen}
                                onClose={() => setIsSubjectModalOpen(false)}
                                currentGrade={stats.user.current_grade}
                                medium={stats.user.medium}
                                initialSubjects={stats.user.selected_subjects}
                                onUpdateSuccess={(newSubjects) => {
                                    setStats(prev => ({
                                        ...prev,
                                        user: {
                                            ...prev.user,
                                            needs_subject_review: false,
                                            selected_subjects: newSubjects
                                        }
                                    }))
                                    setIsSubjectModalOpen(false)
                                }}
                            />
                        )}
                    </div>
                </div>

                {/* Family / Siblings */}
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0', padding: '32px', boxShadow: '0 1px 3px rgba(0,0,0,0.02)' }}>
                    <h3 style={{ margin: '0 0 24px 0', fontSize: 18, fontWeight: 700, color: '#1e293b' }}>Manage Siblings</h3>
                    <div style={{ marginBottom: 24 }}>
                        <p style={{ fontSize: 14, color: '#64748b' }}>
                            If you have siblings studying with us, you can add them to this same account. This avoids needing separate emails and passwords. Each sibling will have their own dashboard and payment portal.
                        </p>
                    </div>
                    
                    <div style={{ background: '#f8fafc', padding: 24, borderRadius: 16, border: '1px solid #e2e8f0' }}>
                        <h4 style={{ margin: '0 0 16px 0', fontSize: 16, fontWeight: 700, color: '#334155' }}>Add New Sibling</h4>
                        <button onClick={() => setIsAddSiblingModalOpen(true)} style={{
                            padding: '10px 24px', background: '#6366f1', color: '#fff', border: 'none', borderRadius: 10,
                            fontWeight: 600, fontSize: 14, cursor: 'pointer'
                        }}>
                            Open Add Sibling Form
                        </button>
                        
                        {isAddSiblingModalOpen && (
                            <StudentRegistrationForm
                                isOpen={isAddSiblingModalOpen}
                                onClose={() => setIsAddSiblingModalOpen(false)}
                                isAddSiblingMode={true}
                            />
                        )}
                    </div>
                </div>
            </div>
        </div>
    )
}
