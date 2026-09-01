import React, { useState, useContext, useEffect } from 'react'
import { FiUser, FiMail, FiLock, FiBell, FiSave, FiPhone, FiShield, FiCamera, FiBook } from 'react-icons/fi'
import { SelectedChildContext } from '../../context/SelectedChildContext'

export default function StudentSettings() {
    const parent = JSON.parse(localStorage.getItem('user') || '{}')
    const { selectedChild } = useContext(SelectedChildContext)
    const user = selectedChild || parent // Fallback to parent for some info if child missing
    const [activeTab, setActiveTab] = useState('profile')

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24,
                '@media (minWidth: 640px)': { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }
            }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: '#1e293b', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Account Settings</h1>
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>Manage your personal information and preferences</p>
                </div>
                <button style={{
                    display: 'flex', alignItems: 'center', gap: 8, padding: '10px 24px',
                    background: 'linear-gradient(135deg, #6366f1, #8b5cf6)', color: '#fff',
                    border: 'none', borderRadius: 12, fontWeight: 700, fontSize: 14,
                    cursor: 'pointer', boxShadow: '0 4px 12px rgba(99,102,241,0.3)',
                    transition: 'all 0.2s', width: 'fit-content'
                }}
                onMouseEnter={e => e.currentTarget.style.transform = 'translateY(-2px)'}
                onMouseLeave={e => e.currentTarget.style.transform = 'translateY(0)'}
                >
                    <FiSave style={{ fontSize: 18 }} /> Save Changes
                </button>
            </div>

            {/* Settings Layout */}
            <div style={{ display: 'grid', gridTemplateColumns: window.innerWidth > 768 ? '250px 1fr' : '1fr', gap: 24 }}>
                
                {/* Sidebar Nav */}
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0', padding: 12, display: 'flex', flexDirection: 'column', gap: 4, height: 'fit-content' }}>
                    <button onClick={() => setActiveTab('profile')} style={{
                        display: 'flex', alignItems: 'center', gap: 12, padding: '12px 16px', borderRadius: 10,
                        border: 'none', cursor: 'pointer', fontSize: 14, fontWeight: 600, textAlign: 'left',
                        background: activeTab === 'profile' ? '#eef2ff' : 'transparent',
                        color: activeTab === 'profile' ? '#6366f1' : '#64748b', transition: 'all 0.2s'
                    }} onMouseEnter={e => { if (activeTab !== 'profile') e.currentTarget.style.background = '#f8fafc' }} onMouseLeave={e => { if (activeTab !== 'profile') e.currentTarget.style.background = 'transparent' }}>
                        <FiUser style={{ fontSize: 18 }} /> Personal Info
                    </button>
                    <button onClick={() => setActiveTab('academics')} style={{
                        display: 'flex', alignItems: 'center', gap: 12, padding: '12px 16px', borderRadius: 10,
                        border: 'none', cursor: 'pointer', fontSize: 14, fontWeight: 600, textAlign: 'left',
                        background: activeTab === 'academics' ? '#eef2ff' : 'transparent',
                        color: activeTab === 'academics' ? '#6366f1' : '#64748b', transition: 'all 0.2s'
                    }} onMouseEnter={e => { if (activeTab !== 'academics') e.currentTarget.style.background = '#f8fafc' }} onMouseLeave={e => { if (activeTab !== 'academics') e.currentTarget.style.background = 'transparent' }}>
                        <FiBook style={{ fontSize: 18 }} /> Academic Details
                    </button>
                    <button onClick={() => setActiveTab('security')} style={{
                        display: 'flex', alignItems: 'center', gap: 12, padding: '12px 16px', borderRadius: 10,
                        border: 'none', cursor: 'pointer', fontSize: 14, fontWeight: 600, textAlign: 'left',
                        background: activeTab === 'security' ? '#eef2ff' : 'transparent',
                        color: activeTab === 'security' ? '#6366f1' : '#64748b', transition: 'all 0.2s'
                    }} onMouseEnter={e => { if (activeTab !== 'security') e.currentTarget.style.background = '#f8fafc' }} onMouseLeave={e => { if (activeTab !== 'security') e.currentTarget.style.background = 'transparent' }}>
                        <FiShield style={{ fontSize: 18 }} /> Security
                    </button>
                    <button onClick={() => setActiveTab('notifications')} style={{
                        display: 'flex', alignItems: 'center', gap: 12, padding: '12px 16px', borderRadius: 10,
                        border: 'none', cursor: 'pointer', fontSize: 14, fontWeight: 600, textAlign: 'left',
                        background: activeTab === 'notifications' ? '#eef2ff' : 'transparent',
                        color: activeTab === 'notifications' ? '#6366f1' : '#64748b', transition: 'all 0.2s'
                    }} onMouseEnter={e => { if (activeTab !== 'notifications') e.currentTarget.style.background = '#f8fafc' }} onMouseLeave={e => { if (activeTab !== 'notifications') e.currentTarget.style.background = 'transparent' }}>
                        <FiBell style={{ fontSize: 18 }} /> Notifications
                    </button>
                </div>

                {/* Content Area */}
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0', padding: '32px', boxShadow: '0 1px 3px rgba(0,0,0,0.02)' }}>
                    
                    {activeTab === 'profile' && (
                        <div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 24, marginBottom: 32, paddingBottom: 32, borderBottom: '1px solid #f1f5f9' }}>
                                <div style={{ position: 'relative' }}>
                                    <div style={{
                                        width: 100, height: 100, borderRadius: '50%', background: 'linear-gradient(135deg, #6366f1, #ec4899)',
                                        display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#fff', fontSize: 36, fontWeight: 800
                                    }}>
                                        {(user?.full_name || user?.name || 'S').charAt(0).toUpperCase()}
                                    </div>
                                    <button style={{
                                        position: 'absolute', bottom: 0, right: 0, width: 32, height: 32, borderRadius: '50%',
                                        background: '#fff', border: '1px solid #e2e8f0', color: '#475569', display: 'flex',
                                        alignItems: 'center', justifyContent: 'center', cursor: 'pointer', boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                                    }}>
                                        <FiCamera />
                                    </button>
                                </div>
                                <div>
                                    <h3 style={{ margin: '0 0 4px 0', fontSize: 20, fontWeight: 700, color: '#1e293b' }}>Profile Picture</h3>
                                    <p style={{ margin: '0 0 12px 0', color: '#64748b', fontSize: 13 }}>PNG, JPG up to 5MB</p>
                                    <div style={{ display: 'flex', gap: 12 }}>
                                        <button style={{ padding: '8px 16px', borderRadius: 8, background: '#f1f5f9', border: 'none', color: '#475569', fontSize: 13, fontWeight: 600, cursor: 'pointer' }}>Upload New</button>
                                        <button style={{ padding: '8px 16px', borderRadius: 8, background: '#fee2e2', border: 'none', color: '#ef4444', fontSize: 13, fontWeight: 600, cursor: 'pointer' }}>Remove</button>
                                    </div>
                                </div>
                            </div>

                            <div style={{ display: 'grid', gridTemplateColumns: window.innerWidth > 640 ? '1fr 1fr' : '1fr', gap: 24 }}>
                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 8 }}>Full Name</label>
                                    <div style={{ position: 'relative' }}>
                                        <FiUser style={{ position: 'absolute', left: 14, top: 12, color: '#94a3b8' }} />
                                        <input type="text" defaultValue={user.full_name || user.name || ''} style={{
                                            width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10, border: '1px solid #e2e8f0',
                                            background: '#f8fafc', fontSize: 14, color: '#1e293b', outline: 'none', transition: 'border-color 0.2s', boxSizing: 'border-box'
                                        }} onFocus={e => e.target.style.borderColor = '#6366f1'} onBlur={e => e.target.style.borderColor = '#e2e8f0'} />
                                    </div>
                                </div>
                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 8 }}>Email Address</label>
                                    <div style={{ position: 'relative' }}>
                                        <FiMail style={{ position: 'absolute', left: 14, top: 12, color: '#94a3b8' }} />
                                        <input type="email" defaultValue={user.email || ''} disabled style={{
                                            width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10, border: '1px solid #e2e8f0',
                                            background: '#f1f5f9', fontSize: 14, color: '#64748b', outline: 'none', boxSizing: 'border-box', cursor: 'not-allowed'
                                        }} />
                                    </div>
                                </div>
                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 8 }}>Phone Number</label>
                                    <div style={{ position: 'relative' }}>
                                        <FiPhone style={{ position: 'absolute', left: 14, top: 12, color: '#94a3b8' }} />
                                        <input type="tel" defaultValue={user.phone_number || ''} style={{
                                            width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10, border: '1px solid #e2e8f0',
                                            background: '#f8fafc', fontSize: 14, color: '#1e293b', outline: 'none', transition: 'border-color 0.2s', boxSizing: 'border-box'
                                        }} onFocus={e => e.target.style.borderColor = '#6366f1'} onBlur={e => e.target.style.borderColor = '#e2e8f0'} />
                                    </div>
                                </div>
                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 8 }}>Date of Birth</label>
                                    <input type="date" defaultValue={user.date_of_birth || ''} style={{
                                        width: '100%', padding: '10px 16px', borderRadius: 10, border: '1px solid #e2e8f0',
                                        background: '#f8fafc', fontSize: 14, color: '#1e293b', outline: 'none', transition: 'border-color 0.2s', boxSizing: 'border-box'
                                    }} onFocus={e => e.target.style.borderColor = '#6366f1'} onBlur={e => e.target.style.borderColor = '#e2e8f0'} />
                                </div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'academics' && (
                        <div>
                            <h3 style={{ margin: '0 0 24px 0', fontSize: 18, fontWeight: 700, color: '#1e293b' }}>Academic Details</h3>
                            <div style={{ display: 'grid', gridTemplateColumns: window.innerWidth > 640 ? '1fr 1fr' : '1fr', gap: 24 }}>
                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 8 }}>Current Grade / Level</label>
                                    <input type="text" defaultValue={user.current_grade || user.grade || ''} style={{
                                        width: '100%', padding: '10px 16px', borderRadius: 10, border: '1px solid #e2e8f0',
                                        background: '#f8fafc', fontSize: 14, color: '#1e293b', outline: 'none', transition: 'border-color 0.2s', boxSizing: 'border-box'
                                    }} onFocus={e => e.target.style.borderColor = '#6366f1'} onBlur={e => e.target.style.borderColor = '#e2e8f0'} />
                                </div>
                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 8 }}>Stream / Subject Area</label>
                                    <input type="text" defaultValue={user.stream || ''} style={{
                                        width: '100%', padding: '10px 16px', borderRadius: 10, border: '1px solid #e2e8f0',
                                        background: '#f8fafc', fontSize: 14, color: '#1e293b', outline: 'none', transition: 'border-color 0.2s', boxSizing: 'border-box'
                                    }} onFocus={e => e.target.style.borderColor = '#6366f1'} onBlur={e => e.target.style.borderColor = '#e2e8f0'} />
                                </div>
                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 8 }}>School Name</label>
                                    <input type="text" defaultValue={user.school_name || ''} placeholder="Enter your school name" style={{
                                        width: '100%', padding: '10px 16px', borderRadius: 10, border: '1px solid #e2e8f0',
                                        background: '#f8fafc', fontSize: 14, color: '#1e293b', outline: 'none', transition: 'border-color 0.2s', boxSizing: 'border-box'
                                    }} onFocus={e => e.target.style.borderColor = '#6366f1'} onBlur={e => e.target.style.borderColor = '#e2e8f0'} />
                                </div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'security' && (
                        <div>
                            <h3 style={{ margin: '0 0 24px 0', fontSize: 18, fontWeight: 700, color: '#1e293b' }}>Change Password</h3>
                            <div style={{ display: 'grid', gap: 20, maxWidth: 500 }}>
                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 8 }}>Current Password</label>
                                    <div style={{ position: 'relative' }}>
                                        <FiLock style={{ position: 'absolute', left: 14, top: 12, color: '#94a3b8' }} />
                                        <input type="password" placeholder="••••••••" style={{
                                            width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10, border: '1px solid #e2e8f0',
                                            background: '#f8fafc', fontSize: 14, color: '#1e293b', outline: 'none', transition: 'border-color 0.2s', boxSizing: 'border-box'
                                        }} onFocus={e => e.target.style.borderColor = '#6366f1'} onBlur={e => e.target.style.borderColor = '#e2e8f0'} />
                                    </div>
                                </div>
                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 8 }}>New Password</label>
                                    <div style={{ position: 'relative' }}>
                                        <FiLock style={{ position: 'absolute', left: 14, top: 12, color: '#94a3b8' }} />
                                        <input type="password" placeholder="••••••••" style={{
                                            width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10, border: '1px solid #e2e8f0',
                                            background: '#f8fafc', fontSize: 14, color: '#1e293b', outline: 'none', transition: 'border-color 0.2s', boxSizing: 'border-box'
                                        }} onFocus={e => e.target.style.borderColor = '#6366f1'} onBlur={e => e.target.style.borderColor = '#e2e8f0'} />
                                    </div>
                                </div>
                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 8 }}>Confirm New Password</label>
                                    <div style={{ position: 'relative' }}>
                                        <FiLock style={{ position: 'absolute', left: 14, top: 12, color: '#94a3b8' }} />
                                        <input type="password" placeholder="••••••••" style={{
                                            width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10, border: '1px solid #e2e8f0',
                                            background: '#f8fafc', fontSize: 14, color: '#1e293b', outline: 'none', transition: 'border-color 0.2s', boxSizing: 'border-box'
                                        }} onFocus={e => e.target.style.borderColor = '#6366f1'} onBlur={e => e.target.style.borderColor = '#e2e8f0'} />
                                    </div>
                                </div>
                                <button style={{
                                    padding: '12px', background: '#6366f1', color: '#fff', border: 'none', borderRadius: 10,
                                    fontWeight: 700, fontSize: 14, cursor: 'pointer', marginTop: 8, transition: 'background 0.2s'
                                }} onMouseEnter={e => e.currentTarget.style.background = '#4f46e5'} onMouseLeave={e => e.currentTarget.style.background = '#6366f1'}>
                                    Update Password
                                </button>
                            </div>
                        </div>
                    )}

                    {activeTab === 'notifications' && (
                        <div>
                            <h3 style={{ margin: '0 0 24px 0', fontSize: 18, fontWeight: 700, color: '#1e293b' }}>Notification Preferences</h3>
                            <div style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
                                {[
                                    { title: 'Class Reminders', desc: 'Get notified 30 minutes before a class starts', checked: true },
                                    { title: 'New Assignments', desc: 'Email alerts when a teacher uploads new homework', checked: true },
                                    { title: 'Grading Updates', desc: 'Notifications when your assignments are graded', checked: true },
                                    { title: 'Announcements', desc: 'Receive important system and school announcements', checked: false }
                                ].map((item, i) => (
                                    <div key={i} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', paddingBottom: 20, borderBottom: i < 3 ? '1px solid #f1f5f9' : 'none' }}>
                                        <div>
                                            <div style={{ fontSize: 15, fontWeight: 600, color: '#1e293b', marginBottom: 4 }}>{item.title}</div>
                                            <div style={{ fontSize: 13, color: '#64748b' }}>{item.desc}</div>
                                        </div>
                                        <label style={{ position: 'relative', display: 'inline-block', width: 44, height: 24, flexShrink: 0, marginTop: 4 }}>
                                            <input type="checkbox" defaultChecked={item.checked} style={{ opacity: 0, width: 0, height: 0 }} />
                                            <span style={{
                                                position: 'absolute', cursor: 'pointer', top: 0, left: 0, right: 0, bottom: 0,
                                                background: item.checked ? '#6366f1' : '#cbd5e1', borderRadius: 24, transition: '0.3s'
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
                        </div>
                    )}
                </div>
            </div>
        </div>
    )
}
