import React, { useState } from 'react'
import StudentRegistrationForm from '../../components/StudentRegistrationForm'

export default function StudentSettings() {
    const [isAddSiblingModalOpen, setIsAddSiblingModalOpen] = useState(false)

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24,
                '@media (minWidth: 640px)': { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }
            }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: '#1e293b', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Family / Siblings</h1>
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>Manage the students linked to your account</p>
                </div>
            </div>

            {/* Content Area */}
            <div style={{ background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0', padding: '32px', boxShadow: '0 1px 3px rgba(0,0,0,0.02)' }}>
                <div>
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
