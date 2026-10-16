import React, { useState, useEffect, useRef } from 'react'
import { NavLink, useNavigate, useLocation } from 'react-router-dom'
import {
    FiHome, FiCalendar, FiVideo, FiFileText, FiBookOpen,
    FiBarChart2, FiMessageSquare, FiSettings, FiLogOut,
    FiMenu, FiX, FiSearch, FiChevronRight, FiUser, FiHelpCircle
} from 'react-icons/fi'
import NotificationBell from './NotificationBell'
import SidebarShell from '../shared/SidebarShell'
import { useToast } from '../../components/shared/ToastContext';
import { getActiveStorage, getAuthHeaders } from '../../services/apiClient';
import useMediaQuery from '../../hooks/useMediaQuery';
import '../shared/dashboard-theme.css';

const menuItems = [
    { name: 'Dashboard', icon: FiHome, path: '/student/dashboard' },
    { name: 'Schedule', icon: FiCalendar, path: '/student/schedule' },
    { name: 'Zoom Classes', icon: FiVideo, path: '/student/zoom' },
    { name: 'Assignments', icon: FiFileText, path: '/student/assignments' },
    { name: 'Materials', icon: FiBookOpen, path: '/student/materials' },
    { name: 'Performance', icon: FiBarChart2, path: '/student/performance' },
    { name: 'Messages', icon: FiMessageSquare, path: '/student/messages' },
    { name: 'Settings', icon: FiSettings, path: '/student/settings' },
]

export default function StudentDashboardLayout({ children, user }) {
    const toast = useToast();

    const [searchQuery, setSearchQuery] = useState('')
    const [isSearchFocused, setIsSearchFocused] = useState(false)
    const [showProfileMenu, setShowProfileMenu] = useState(false)
    const [uploadingAvatar, setUploadingAvatar] = useState(false)
    const [localAvatar, setLocalAvatar] = useState(null)
    const fileInputRef = useRef(null)
    const profileMenuRef = useRef(null)
    
    const location = useLocation()
    const navigate = useNavigate()

    const isDesktop = useMediaQuery('(min-width: 1024px)')
    const isMobile = useMediaQuery('(max-width: 640px)')

    const searchResults = menuItems.filter(item =>
        item.name.toLowerCase().includes(searchQuery.toLowerCase())
    )
    const initial = (user?.full_name || user?.username || 'S').charAt(0).toUpperCase()

    const getAvatarUrl = () => {
        if (localAvatar) return localAvatar;
        if (user?.avatar) {
            if (user.avatar.startsWith('http')) return user.avatar;
            const baseUrl = (import.meta.env.VITE_API_URL || '').replace(/\/api$/, '');
            return baseUrl ? `${baseUrl}${user.avatar}` : user.avatar;
        }
        return null;
    }
    const avatarUrl = getAvatarUrl();

    let displayEmail = user?.email || ''
    if (user?.parent_id) {
        try {
            const storage = getActiveStorage()
            const availableProfiles = JSON.parse(storage.getItem('availableProfiles') || '[]')
            const parentProfile = availableProfiles.find(p => !p.parent_id)
            if (parentProfile && parentProfile.email) {
                displayEmail = parentProfile.email
            }
        } catch (e) {
            console.error('Failed to parse availableProfiles', e)
        }
    }

    const handleAvatarChange = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        setUploadingAvatar(true);
        const formData = new FormData();
        formData.append('avatar', file);

        try {
            const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';
            const res = await fetch(`${API_BASE_URL}/auth/update-avatar`, {
                method: 'POST',
                headers: getAuthHeaders(true),
                body: formData
            });
            if (!res.ok) throw new Error('Upload failed');
            const data = await res.json();
            
            const baseUrl = (import.meta.env.VITE_API_URL || '').replace(/\/api$/, '');
            const newAvatar = data.avatar.startsWith('http') ? data.avatar : `${baseUrl}${data.avatar}`;
            setLocalAvatar(newAvatar);
            
            const storage = getActiveStorage();
            
            // Update active user in storage
            const currentUser = JSON.parse(storage.getItem('user') || '{}');
            if (currentUser.id) {
                currentUser.avatar = data.avatar;
                storage.setItem('user', JSON.stringify(currentUser));
            }
            
            // Update in availableProfiles array
            try {
                const availableProfiles = JSON.parse(storage.getItem('availableProfiles') || '[]');
                const updatedProfiles = availableProfiles.map(p => 
                    p.id === currentUser.id ? { ...p, avatar: data.avatar } : p
                );
                storage.setItem('availableProfiles', JSON.stringify(updatedProfiles));
            } catch (err) {
                console.error("Could not update availableProfiles with new avatar", err);
            }

            toast.success("Profile photo updated successfully!");
        } catch (err) {
            console.error(err);
            toast.error("Failed to update profile photo.");
        } finally {
            setUploadingAvatar(false);
        }
    };

    // Close profile menu when clicking outside
    useEffect(() => {
        function handleClickOutside(event) {
            if (profileMenuRef.current && !profileMenuRef.current.contains(event.target)) {
                setShowProfileMenu(false)
            }
        }
        if (showProfileMenu) {
            document.addEventListener('mousedown', handleClickOutside)
        }
        return () => document.removeEventListener('mousedown', handleClickOutside)
    }, [showProfileMenu])

    return (
        <div className="student-console-layout" style={{ display: 'flex', height: '100dvh', background: 'var(--t-bg)', fontFamily: "'Inter', sans-serif" }}>
            {/* SIDEBAR */}
            <SidebarShell
                brandSubtitle="Student Portal"
                items={menuItems}
                user={user}
                avatarUrl={avatarUrl}
            />

            {/* MAIN CONTENT */}
            <main style={{ flex: 1, display: 'flex', flexDirection: 'column', minWidth: 0, overflow: 'visible' }}>
                {/* Header */}
                <header style={{
                    height: 64, display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    padding: '0 24px', background: '#fff',
                    borderBottom: '1px solid var(--t-border)', zIndex: 30,
                    position: 'relative', overflow: 'visible', flexShrink: 0
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>

                        {!isMobile && (
                            <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                                <FiSearch style={{ position: 'absolute', left: 14, color: 'var(--t-muted)', fontSize: 16 }} />
                                <input
                                    type="text"
                                    placeholder="Search portal..."
                                    value={searchQuery}
                                    onChange={e => setSearchQuery(e.target.value)}
                                    style={{
                                        width: 320, padding: '10px 16px 10px 42px', borderRadius: 12,
                                        border: '1px solid var(--t-border)', background: 'var(--t-bg)', fontSize: 14,
                                        outline: 'none', color: 'var(--t-text)',
                                        transition: 'all 0.2s'
                                    }}
                                    onFocus={e => {
                                        setIsSearchFocused(true)
                                        e.target.style.borderColor = 'var(--t-primary)'
                                        e.target.style.boxShadow = '0 0 0 3px rgba(79,11,217,0.1)'
                                    }}
                                    onBlur={e => {
                                        setTimeout(() => setIsSearchFocused(false), 200)
                                        e.target.style.borderColor = 'var(--t-border)'
                                        e.target.style.boxShadow = 'none'
                                    }}
                                />

                                {/* Command Palette Dropdown */}
                                {isSearchFocused && searchQuery && (
                                    <div style={{
                                        position: 'absolute', top: '100%', left: 0, right: 0, marginTop: 8,
                                        background: '#fff', borderRadius: 12,
                                        boxShadow: '0 10px 30px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05)',
                                        border: '1px solid var(--t-border)', zIndex: 100, overflow: 'hidden',
                                        animation: 'slideDown 0.2s ease'
                                    }}>
                                        {searchResults.length > 0 ? searchResults.map(item => {
                                            const Icon = item.icon
                                            return (
                                                <div
                                                    key={item.path}
                                                    onClick={() => navigate(item.path)}
                                                    className="t-menu-item"
                                                    style={{
                                                        padding: '12px 16px', display: 'flex', alignItems: 'center', gap: 12,
                                                        cursor: 'pointer', borderBottom: '1px solid var(--t-border)',
                                                        transition: 'background 0.2s'
                                                    }}
                                                >
                                                    <div style={{
                                                        width: 32, height: 32, borderRadius: 8, background: 'var(--t-primary-soft)',
                                                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                                                        color: 'var(--t-primary)'
                                                    }}>
                                                        <Icon />
                                                    </div>
                                                    <span style={{ fontSize: 14, fontWeight: 600, color: 'var(--t-text)' }}>
                                                        {item.name}
                                                    </span>
                                                </div>
                                            )
                                        }) : (
                                            <div style={{ padding: '24px 16px', textAlign: 'center', color: 'var(--t-muted)', fontSize: 13 }}>
                                                No modules found matching "{searchQuery}"
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}
                        <style>{`@keyframes slideDown { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }`}</style>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
                        {/* Notification Bell */}
                        <NotificationBell />

                        <div style={{
                            display: 'flex', alignItems: 'center', gap: 10,
                            paddingLeft: 16, borderLeft: '1px solid var(--t-border)', position: 'relative'
                        }}>
                            {!isMobile && (
                                <div style={{ textAlign: 'right' }}>
                                    <div style={{ fontSize: 14, fontWeight: 600, color: 'var(--t-text)' }}>{user?.full_name || 'Student'}</div>
                                    <div style={{ fontSize: 11, color: 'var(--t-muted)', fontWeight: 500 }}>Student Portal</div>
                                </div>
                            )}
                            
                            {/* User Profile Dropdown */}
                            <div style={{ position: 'relative' }} ref={profileMenuRef}>
                            <div 
                                onClick={() => setShowProfileMenu(!showProfileMenu)}
                                style={{
                                width: 44, height: 44, borderRadius: '50%',
                                background: 'var(--t-primary)',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                color: '#fff', fontWeight: 700, fontSize: 15,
                                boxShadow: '0 2px 8px rgba(79,11,217,0.3)',
                                border: '2px solid #fff', cursor: 'pointer', overflow: 'hidden',
                                opacity: uploadingAvatar ? 0.6 : 1
                            }}>
                                {avatarUrl && !uploadingAvatar ? (
                                    <img width="800" height="600" src={avatarUrl} alt="Profile" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                                ) : uploadingAvatar ? (
                                    <div style={{ width: 14, height: 14, border: '2px solid #fff', borderTopColor: 'transparent', borderRadius: '50%', animation: 'spin 1s linear infinite' }} />
                                ) : (
                                    initial
                                )}
                            </div>

                            {/* Hidden File Input */}
                            <input 
                                type="file" 
                                accept="image/jpeg,image/png,image/jpg" 
                                style={{ display: 'none' }} 
                                ref={fileInputRef} 
                                onChange={handleAvatarChange} 
                            />

                            {/* Profile Menu Dropdown */}
                            {showProfileMenu && (
                                <div style={{
                                    position: 'absolute', top: '100%', right: 0, marginTop: 12,
                                    width: 200, background: '#fff', borderRadius: 12,
                                    boxShadow: '0 10px 30px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05)',
                                    border: '1px solid var(--t-border)', zIndex: 100, overflow: 'hidden',
                                    animation: 'slideDown 0.2s ease'
                                }}>
                                    <div style={{ padding: '16px', borderBottom: '1px solid var(--t-border)', background: 'var(--t-bg)' }}>
                                        <div style={{ fontSize: 13, fontWeight: 700, color: 'var(--t-text)', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis', overflowWrap: 'anywhere' }}>{user?.full_name || 'Student'}</div>
                                        <div style={{ fontSize: 11, color: 'var(--t-text-3)', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis', overflowWrap: 'anywhere' }}>{displayEmail}</div>
                                    </div>
                                    <div style={{ padding: '8px' }}>
                                        <button 
                                            onClick={() => { setShowProfileMenu(false); fileInputRef.current?.click(); }}
                                            className="t-menu-item"
                                            style={{
                                            width: '100%', display: 'flex', alignItems: 'center', gap: 10,
                                            padding: '10px 12px', background: 'transparent', border: 'none',
                                            color: 'var(--t-text-2)', fontSize: 13, fontWeight: 500, cursor: 'pointer',
                                            borderRadius: 8, textAlign: 'left', minHeight: 44
                                        }}
                                        >
                                            <FiSettings style={{ fontSize: 15 }} /> Upload Photo
                                        </button>

                                        {JSON.parse(getActiveStorage().getItem('availableProfiles') || '[]').length > 1 && (
                                        <button 
                                            onClick={() => { setShowProfileMenu(false); window.location.href = '/select-profile'; }}
                                            className="t-menu-item t-menu-item-primary"
                                            style={{
                                            width: '100%', display: 'flex', alignItems: 'center', gap: 10,
                                            padding: '10px 12px', background: 'transparent', border: 'none',
                                            color: 'var(--t-primary)', fontSize: 13, fontWeight: 600, cursor: 'pointer',
                                            borderRadius: 8, textAlign: 'left', marginTop: 4, minHeight: 44
                                        }}
                                        >
                                            <FiUser style={{ fontSize: 15 }} /> Switch Profile
                                        </button>
                                        )}

                                        <button 
                                            onClick={() => { setShowProfileMenu(false); window.location.href = '/?logout=1'; }}
                                            className="t-menu-item t-menu-item-danger"
                                            style={{
                                            width: '100%', display: 'flex', alignItems: 'center', gap: 10,
                                            padding: '10px 12px', background: 'transparent', border: 'none',
                                            color: 'var(--t-danger)', fontSize: 13, fontWeight: 600, cursor: 'pointer',
                                            borderRadius: 8, textAlign: 'left', marginTop: 4, minHeight: 44
                                        }}
                                        >
                                            <FiLogOut style={{ fontSize: 15 }} /> Sign Out
                                        </button>
                                    </div>
                                </div>
                            )}
                            </div>
                        </div>
                    </div>
                </header>

                {/* Content */}
                <div style={{
                    flex: 1, overflowY: 'auto', padding: isMobile ? '16px' : '24px',
                    background: 'var(--t-bg)'
                }}>
                    <div style={{ maxWidth: 1200, margin: '0 auto' }}>
                        {children}
                    </div>
                </div>
            </main>
        </div>
    )
}
