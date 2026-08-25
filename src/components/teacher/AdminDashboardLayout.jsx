import React, { useState } from 'react'
import AdminSidebar from './AdminSidebar'
import { useSettings } from '../../context/SettingsContext'
import './AdminDashboardLayout.css'
import { FiBell, FiMessageSquare, FiSearch, FiChevronDown, FiUser, FiSettings, FiLogOut, FiCalendar, FiMenu, FiX } from 'react-icons/fi'
import GlobalNotificationBell from '../shared/GlobalNotificationBell'
import { useToast } from '../../components/shared/ToastContext';


export default function AdminDashboardLayout({ children, user }) {
  const toast = useToast();

    const { getSetting } = useSettings()
    const [showProfileDropdown, setShowProfileDropdown] = useState(false)
    const [isSidebarOpen, setIsSidebarOpen] = useState(false)
    const [uploadingAvatar, setUploadingAvatar] = useState(false)
    const [localAvatar, setLocalAvatar] = useState(null)
    const fileInputRef = React.useRef(null)
    
    const instituteName = getSetting('site_name', 'Campus Governance')

    const host = window.location.origin
    const frontendUrl = import.meta.env.VITE_FRONTEND_URL || 'http://localhost:4000'

    const getAvatarUrl = () => {
        if (localAvatar) return localAvatar;
        if (user?.avatar) {
            if (user.avatar.startsWith('http')) return user.avatar;
            const baseUrl = (import.meta.env.VITE_API_URL || '').replace(/\/api$/, '');
            return baseUrl ? `${baseUrl}${user.avatar}` : `${host}/${user.avatar.replace('public/', 'storage/')}`;
        }
        return null;
    }
    const avatarUrl = getAvatarUrl();

    const handleAvatarChange = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        setUploadingAvatar(true);
        const formData = new FormData();
        formData.append('avatar', file);

        const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken');
        
        try {
            const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';
            const res = await fetch(`${API_BASE_URL}/auth/update-avatar`, {
                method: 'POST',
                headers: {
                    ...(token && { 'Authorization': `Bearer ${token}` })
                },
                body: formData
            });
            if (!res.ok) throw new Error('Upload failed');
            const data = await res.json();
            
            const baseUrl = (import.meta.env.VITE_API_URL || '').replace(/\/api$/, '');
            const newAvatar = data.avatar.startsWith('http') ? data.avatar : `${baseUrl}${data.avatar}`;
            setLocalAvatar(newAvatar);
        } catch (err) {
            toast.error('Failed to update profile picture.');
        } finally {
            setUploadingAvatar(false);
            setShowProfileDropdown(false);
        }
    }

    return (
        <div className={`admin-console-layout ${isSidebarOpen ? 'sidebar-open' : ''}`}>
             <div className="mobile-sidebar-overlay" onClick={() => setIsSidebarOpen(false)}></div>
            <AdminSidebar isOpen={isSidebarOpen} setIsOpen={setIsSidebarOpen} />
            <main className="admin-console-main">
                <header className="admin-console-header">
                    <div className="header-left">
                        <button className="mobile-toggle" onClick={() => setIsSidebarOpen(!isSidebarOpen)}>
                            {isSidebarOpen ? <FiX /> : <FiMenu />}
                        </button>
                        <div className="academy-badge">
                            <span className="badge-dot"></span>
                            <span className="badge-text">{instituteName}</span>
                        </div>
                    </div>

                    <div className="header-right">
                        <div className="header-actions">
                            <a href={frontendUrl} target="_blank" rel="noopener noreferrer" className="home-link">
                                Home
                            </a>
                            
                            <div className="action-icon-wrapper">
                                <FiMessageSquare className="header-icon" />
                                <span className="icon-badge">0</span>
                            </div>

                            <GlobalNotificationBell />

                            <div className="admin-profile-trigger" onClick={() => setShowProfileDropdown(!showProfileDropdown)}>
                                <div className="admin-avatar" style={{ opacity: uploadingAvatar ? 0.6 : 1, position: 'relative' }}>
                                    {avatarUrl && !uploadingAvatar ? (
                                        <img src={avatarUrl} alt="Avatar" style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: '50%' }} />
                                    ) : uploadingAvatar ? (
                                        <div style={{ width: 14, height: 14, border: '2px solid #fff', borderTopColor: 'transparent', borderRadius: '50%', animation: 'spin 1s linear infinite' }} />
                                    ) : (
                                        user?.first_name?.charAt(0) || 'A'
                                    )}
                                </div>
                                <div className="admin-user-meta">
                                    <span className="admin-name">{user?.first_name || 'Admin'}</span>
                                    <FiChevronDown className={`chevron ${showProfileDropdown ? 'open' : ''}`} />
                                </div>

                                {/* Hidden File Input */}
                                <input 
                                    type="file" 
                                    accept="image/jpeg,image/png,image/jpg" 
                                    style={{ display: 'none' }} 
                                    ref={fileInputRef} 
                                    onChange={handleAvatarChange} 
                                />

                                {showProfileDropdown && (
                                    <div className="profile-dropdown-menu">
                                        <div className="dropdown-header">
                                            <p className="user-full-name">{user?.first_name} {user?.last_name}</p>
                                            <p className="user-email">{user?.email}</p>
                                        </div>
                                        <div className="dropdown-divider"></div>
                                        <button 
                                            onClick={(e) => { e.stopPropagation(); fileInputRef.current?.click(); }}
                                            className="dropdown-item" style={{ width: '100%', border: 'none', background: 'transparent', cursor: 'pointer', textAlign: 'left' }}
                                        >
                                            <FiSettings /> Upload Photo
                                        </button>
                                        <a href="/admin/profile/settings" className="dropdown-item">
                                            <FiSettings /> Settings
                                        </a>
                                        <a href="/admin/profile/settings?tab=calendar" className="dropdown-item">
                                            <FiCalendar /> Calendar
                                        </a>
                                        <div className="dropdown-divider"></div>
                                        <button onClick={() => {
                                            const form = document.createElement('form');
                                            form.method = 'POST';
                                            form.action = '/admin/logout';
                                            const csrf = document.createElement('input');
                                            csrf.type = 'hidden';
                                            csrf.name = '_token';
                                            csrf.value = document.querySelector('meta[name="csrf-token"]')?.content;
                                            form.appendChild(csrf);
                                            document.body.appendChild(form);
                                            form.submit();
                                        }} className="dropdown-item text-danger">
                                            <FiLogOut /> Sign Out
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </header>
                <div className="admin-console-content">
                    {children}
                </div>
            </main>
        </div>
    )
}
