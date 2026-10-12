import React, { useState, useRef } from 'react'
import { FiUser, FiMail, FiPhone, FiCamera, FiBookOpen } from 'react-icons/fi'
import { useToast } from '../../components/shared/ToastContext'
import { getAuthHeaders } from '../../services/apiClient'

export default function TeacherSettings() {
    const toast = useToast()
    const storedUser = JSON.parse(localStorage.getItem('user') || '{}')
    const [user, setUser] = useState(storedUser)
    const [uploadingAvatar, setUploadingAvatar] = useState(false)
    const [localAvatar, setLocalAvatar] = useState(null)
    const fileInputRef = useRef(null)

    const displayName = user?.full_name || user?.name || user?.first_name || 'Teacher'
    const initial = displayName.charAt(0).toUpperCase()

    const getAvatarUrl = () => {
        if (localAvatar) return localAvatar
        if (user?.avatar) {
            if (user.avatar.startsWith('http')) return user.avatar
            const baseUrl = (import.meta.env.VITE_API_URL || '').replace(/\/api$/, '')
            return baseUrl ? `${baseUrl}${user.avatar}` : user.avatar
        }
        return null
    }
    const avatarUrl = getAvatarUrl()

    const handleAvatarChange = async (e) => {
        const file = e.target.files[0]
        if (!file) return

        setUploadingAvatar(true)
        const formData = new FormData()
        formData.append('avatar', file)

        try {
            const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'
            const res = await fetch(`${API_BASE_URL}/auth/update-avatar`, {
                method: 'POST',
                headers: getAuthHeaders(true),
                body: formData
            })
            if (!res.ok) throw new Error('Upload failed')
            const data = await res.json()

            const baseUrl = (import.meta.env.VITE_API_URL || '').replace(/\/api$/, '')
            const newAvatar = data.avatar.startsWith('http') ? data.avatar : `${baseUrl}${data.avatar}`
            setLocalAvatar(newAvatar)

            const updatedUser = { ...user, avatar: data.avatar }
            setUser(updatedUser)
            localStorage.setItem('user', JSON.stringify(updatedUser))
            toast.success('Profile photo updated successfully')
        } catch (err) {
            toast.error('Failed to update profile picture.')
        } finally {
            setUploadingAvatar(false)
        }
    }

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div className="teacher-page-header">
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: 'var(--t-text)', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Account Settings</h1>
                    <p style={{ color: 'var(--t-text-3)', margin: 0, fontSize: 14 }}>View your instructor profile and account details</p>
                </div>
            </div>

            {/* Content Area */}
            <div style={{ background: 'var(--t-surface)', borderRadius: 16, border: '1px solid var(--t-border)', padding: '32px', boxShadow: '0 1px 3px rgba(0,0,0,0.02)', maxWidth: 800 }}>
                {/* Profile Picture */}
                <div style={{ display: 'flex', alignItems: 'center', gap: 24, marginBottom: 32, paddingBottom: 32, borderBottom: '1px solid var(--t-border)', flexWrap: 'wrap' }}>
                    <div style={{ position: 'relative' }}>
                        <div style={{
                            width: 100, height: 100, borderRadius: '50%', background: 'var(--t-primary)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#fff', fontSize: 36, fontWeight: 800,
                            overflow: 'hidden'
                        }}>
                            {avatarUrl && !uploadingAvatar ? (
                                <img src={avatarUrl} alt="Profile" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                            ) : uploadingAvatar ? (
                                <div style={{ width: 24, height: 24, border: '3px solid #fff', borderTopColor: 'transparent', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                            ) : (
                                initial
                            )}
                        </div>
                        <button 
                            type="button"
                            onClick={() => fileInputRef.current?.click()}
                            className="t-icon-btn"
                            aria-label="Upload new avatar"
                            style={{
                                position: 'absolute', bottom: 0, right: 0, width: 44, height: 44, minWidth: 44, minHeight: 44, borderRadius: '50%',
                                background: 'var(--t-surface)', border: '1px solid var(--t-border)', color: 'var(--t-text-2)', display: 'flex',
                                alignItems: 'center', justifyContent: 'center', cursor: 'pointer', boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                            }}
                            title="Upload new avatar"
                        >
                            <FiCamera />
                        </button>
                    </div>
                    <div>
                        <h3 style={{ margin: '0 0 4px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>Profile Photo</h3>
                        <p style={{ margin: '0 0 12px 0', color: 'var(--t-text-3)', fontSize: 13 }}>JPG, PNG or GIF up to 5MB</p>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/gif"
                            style={{ display: 'none' }}
                            ref={fileInputRef}
                            onChange={handleAvatarChange}
                        />
                        <button
                            type="button"
                            onClick={() => fileInputRef.current?.click()}
                            disabled={uploadingAvatar}
                            className="t-btn"
                            style={{
                                padding: '8px 16px', minHeight: 44, borderRadius: 8, background: 'var(--t-primary-soft)',
                                border: '1px solid var(--t-primary-border)', color: 'var(--t-primary)',
                                fontSize: 13, fontWeight: 600, cursor: uploadingAvatar ? 'not-allowed' : 'pointer'
                            }}
                        >
                            {uploadingAvatar ? 'Uploading...' : 'Upload New Photo'}
                        </button>
                    </div>
                </div>

                {/* Profile Fields (Read-Only) */}
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(260px, 100%), 1fr))', gap: 20 }}>
                    <div>
                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 8 }}>Full Name</label>
                        <div style={{ position: 'relative' }}>
                            <FiUser style={{ position: 'absolute', left: 14, top: 12, color: 'var(--t-text-3)' }} />
                            <input
                                type="text"
                                readOnly
                                value={displayName}
                                style={{
                                    width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10,
                                    border: '1px solid var(--t-border)', background: 'var(--t-bg)',
                                    fontSize: 14, color: 'var(--t-text)', boxSizing: 'border-box'
                                }}
                            />
                        </div>
                    </div>

                    <div>
                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 8 }}>Email Address (Verified)</label>
                        <div style={{ position: 'relative' }}>
                            <FiMail style={{ position: 'absolute', left: 14, top: 12, color: 'var(--t-text-3)' }} />
                            <input
                                type="email"
                                readOnly
                                value={user?.email || ''}
                                style={{
                                    width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10,
                                    border: '1px solid var(--t-border)', background: 'var(--t-bg)',
                                    fontSize: 14, color: 'var(--t-text-2)', boxSizing: 'border-box'
                                }}
                            />
                        </div>
                    </div>

                    <div>
                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 8 }}>Phone Number</label>
                        <div style={{ position: 'relative' }}>
                            <FiPhone style={{ position: 'absolute', left: 14, top: 12, color: 'var(--t-text-3)' }} />
                            <input
                                type="text"
                                readOnly
                                value={user?.phone_number || 'N/A'}
                                style={{
                                    width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10,
                                    border: '1px solid var(--t-border)', background: 'var(--t-bg)',
                                    fontSize: 14, color: 'var(--t-text-2)', boxSizing: 'border-box'
                                }}
                            />
                        </div>
                    </div>

                    <div>
                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 8 }}>Teaching Subject</label>
                        <div style={{ position: 'relative' }}>
                            <FiBookOpen style={{ position: 'absolute', left: 14, top: 12, color: 'var(--t-text-3)' }} />
                            <input
                                type="text"
                                readOnly
                                value={user?.subject || 'All Subjects'}
                                style={{
                                    width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10,
                                    border: '1px solid var(--t-border)', background: 'var(--t-bg)',
                                    fontSize: 14, color: 'var(--t-text-2)', boxSizing: 'border-box'
                                }}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}
