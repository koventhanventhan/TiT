import React from 'react'
import { FiUser, FiMail, FiLock, FiBell, FiSave } from 'react-icons/fi'
import './StudentSections.css'

export default function StudentSettings() {
    const user = JSON.parse(localStorage.getItem('user') || '{}')

    return (
        <div className="student-section">
            <div className="section-top">
                <h2>Settings</h2>
            </div>

            <div style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))',
                gap: 24
            }}>
                {/* Profile Section */}
                <div style={{
                    background: '#fff', borderRadius: 14, padding: 28,
                    border: '1px solid #e2e8f0'
                }}>
                    <h3 style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 20, color: '#1e293b' }}>
                        <FiUser style={{ color: '#2563eb' }} /> Profile Information
                    </h3>
                    <div style={{ display: 'grid', gap: 16 }}>
                        <div>
                            <label style={{ display: 'block', fontSize: '0.85rem', color: '#64748b', marginBottom: 6, fontWeight: 500 }}>Full Name</label>
                            <input
                                type="text"
                                defaultValue={user.name || ''}
                                style={{
                                    width: '100%', padding: '10px 14px', border: '1px solid #e2e8f0',
                                    borderRadius: 10, fontSize: '0.9rem', color: '#334155', outline: 'none',
                                    boxSizing: 'border-box'
                                }}
                            />
                        </div>
                        <div>
                            <label style={{ display: 'block', fontSize: '0.85rem', color: '#64748b', marginBottom: 6, fontWeight: 500 }}>Email</label>
                            <input
                                type="email"
                                defaultValue={user.email || ''}
                                disabled
                                style={{
                                    width: '100%', padding: '10px 14px', border: '1px solid #e2e8f0',
                                    borderRadius: 10, fontSize: '0.9rem', color: '#94a3b8', background: '#f8fafc',
                                    boxSizing: 'border-box'
                                }}
                            />
                        </div>
                        <div>
                            <label style={{ display: 'block', fontSize: '0.85rem', color: '#64748b', marginBottom: 6, fontWeight: 500 }}>Phone</label>
                            <input
                                type="tel"
                                defaultValue={user.phone_number || ''}
                                style={{
                                    width: '100%', padding: '10px 14px', border: '1px solid #e2e8f0',
                                    borderRadius: 10, fontSize: '0.9rem', color: '#334155', outline: 'none',
                                    boxSizing: 'border-box'
                                }}
                            />
                        </div>
                    </div>
                    <button style={{
                        marginTop: 20, display: 'flex', alignItems: 'center', gap: 6,
                        padding: '10px 20px', background: 'linear-gradient(135deg, #2563eb, #3b82f6)',
                        color: '#fff', border: 'none', borderRadius: 10, fontWeight: 600,
                        fontSize: '0.9rem', cursor: 'pointer'
                    }}>
                        <FiSave /> Save Changes
                    </button>
                </div>

                {/* Notifications */}
                <div style={{ display: 'grid', gap: 24 }}>
                    <div style={{
                        background: '#fff', borderRadius: 14, padding: 28,
                        border: '1px solid #e2e8f0'
                    }}>
                        <h3 style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 20, color: '#1e293b' }}>
                            <FiBell style={{ color: '#f59e0b' }} /> Notifications
                        </h3>
                        {[
                            { label: 'Email notifications for new assignments', checked: true },
                            { label: 'SMS alerts for upcoming classes', checked: false },
                            { label: 'Weekly progress summary', checked: true }
                        ].map((item, i) => (
                            <div key={i} style={{
                                display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                                padding: '12px 0', borderBottom: i < 2 ? '1px solid #f1f5f9' : 'none'
                            }}>
                                <span style={{ color: '#334155', fontSize: '0.9rem' }}>{item.label}</span>
                                <label style={{ position: 'relative', display: 'inline-block', width: 44, height: 24 }}>
                                    <input type="checkbox" defaultChecked={item.checked} style={{ opacity: 0, width: 0, height: 0 }} />
                                    <span style={{
                                        position: 'absolute', cursor: 'pointer', top: 0, left: 0, right: 0, bottom: 0,
                                        background: item.checked ? '#2563eb' : '#cbd5e1', borderRadius: 24,
                                        transition: '0.3s'
                                    }}>
                                        <span style={{
                                            position: 'absolute', height: 18, width: 18, left: item.checked ? 22 : 3, bottom: 3,
                                            background: '#fff', borderRadius: '50%', transition: '0.3s'
                                        }} />
                                    </span>
                                </label>
                            </div>
                        ))}
                    </div>

                    <div style={{
                        background: '#fff', borderRadius: 14, padding: 28,
                        border: '1px solid #e2e8f0'
                    }}>
                        <h3 style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 20, color: '#1e293b' }}>
                            <FiLock style={{ color: '#ef4444' }} /> Security
                        </h3>
                        <button style={{
                            display: 'flex', alignItems: 'center', gap: 6,
                            padding: '10px 20px', background: '#fff',
                            color: '#334155', border: '1px solid #e2e8f0', borderRadius: 10, fontWeight: 500,
                            fontSize: '0.9rem', cursor: 'pointer'
                        }}>
                            <FiLock /> Change Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
    )
}
