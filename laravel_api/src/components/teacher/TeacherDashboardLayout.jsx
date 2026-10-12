import React, { useState, useEffect, useRef } from 'react'
import { NavLink, useLocation, useNavigate } from 'react-router-dom'
import {
    FiHome, FiUsers, FiUser, FiCalendar, FiBookOpen, FiFileText,
    FiPieChart, FiMessageSquare, FiSettings, FiLogOut,
    FiMenu, FiX, FiSearch, FiBell, FiChevronRight
} from 'react-icons/fi'
import GlobalNotificationBell from '../shared/GlobalNotificationBell'
import { useToast } from '../../components/shared/ToastContext'
import { getAuthHeaders } from '../../services/apiClient'
import { useMediaQuery } from '../../hooks/useMediaQuery'
import './teacher-theme.css'

const menuItems = [
    { name: 'Dashboard', icon: FiHome, path: '/teacher/dashboard' },
    { name: 'My Students', icon: FiUsers, path: '/teacher/students' },
    { name: 'Schedule', icon: FiCalendar, path: '/teacher/schedule' },
    { name: 'Materials', icon: FiBookOpen, path: '/teacher/materials' },
    { name: 'Assignments', icon: FiFileText, path: '/teacher/assignments' },
    { name: 'Reports', icon: FiPieChart, path: '/teacher/reports' },
    { name: 'Messages', icon: FiMessageSquare, path: '/teacher/messages' },
]

export default function TeacherDashboardLayout({ children, user }) {
    const toast = useToast()

    const [isSidebarOpen, setIsSidebarOpen] = useState(false)
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
    const isTabletOrDesktop = useMediaQuery('(min-width: 640px)')
    const isMobile = !isTabletOrDesktop

    const toggleSidebar = () => setIsSidebarOpen(prev => !prev)

    // Close drawer on route change
    useEffect(() => {
        setIsSidebarOpen(false)
    }, [location.pathname])

    // Close drawer on Escape
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                setIsSidebarOpen(false)
            }
        }
        if (isSidebarOpen) {
            window.addEventListener('keydown', handleKeyDown)
        }
        return () => window.removeEventListener('keydown', handleKeyDown)
    }, [isSidebarOpen])

    // Lock page scroll while sidebar drawer is open
    useEffect(() => {
        if (isSidebarOpen && !isDesktop) {
            const originalOverflow = document.body.style.overflow
            document.body.style.overflow = 'hidden'
            return () => {
                document.body.style.overflow = originalOverflow
            }
        }
    }, [isSidebarOpen, isDesktop])

    const searchResults = menuItems.filter(item =>
        item.name.toLowerCase().includes(searchQuery.toLowerCase())
    )
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
        } catch (err) {
            toast.error('Failed to update profile picture.')
        } finally {
            setUploadingAvatar(false)
            setShowProfileMenu(false)
        }
    }

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
        <div className="teacher-console-layout" style={{ display: 'flex', height: '100dvh', background: 'var(--t-bg)', fontFamily: "'Inter', sans-serif" }}>
            {/* Mobile/Tablet Drawer Overlay (no backdrop-filter blur for performance) */}
            {isSidebarOpen && !isDesktop && (
                <div
                    onClick={() => setIsSidebarOpen(false)}
                    aria-label="Close sidebar overlay"
                    style={{
                        position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)',
                        zIndex: 998
                    }}
                />
            )}

            {/* ══════════════ SIDEBAR ══════════════ */}
            <aside style={{
                width: 260,
                background: 'var(--t-sidebar)',
                display: (!isDesktop && !isSidebarOpen) ? 'none' : 'flex',
                flexDirection: 'column',
                position: !isDesktop ? 'fixed' : 'relative',
                inset: !isDesktop ? '0 auto 0 0' : undefined,
                height: '100dvh',
                zIndex: 999,
                boxShadow: '4px 0 24px rgba(0,0,0,0.15)',
                transition: 'transform 0.3s ease',
                paddingBottom: 'env(safe-area-inset-bottom, 0px)'
            }}>
                {/* Logo */}
                <div style={{
                    padding: '24px 20px', display: 'flex', alignItems: 'center',
                    gap: 12, borderBottom: '1px solid rgba(255,255,255,0.06)'
                }}>
                    <div style={{
                        width: 40, height: 40, borderRadius: 12,
                        background: 'var(--t-primary)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        color: '#fff', fontWeight: 800, fontSize: 18
                    }}>TiT</div>
                    <div>
                        <div style={{ color: '#fff', fontWeight: 700, fontSize: 18, letterSpacing: '-0.5px' }}>
                            TiT<span style={{ color: 'var(--t-primary-border)' }}>Education</span>
                        </div>
                        <div style={{ color: 'var(--t-sidebar-text)', fontSize: 11, fontWeight: 500 }}>Teacher Portal</div>
                    </div>
                    {!isDesktop && (
                        <button
                            onClick={() => setIsSidebarOpen(false)}
                            className="t-icon-btn"
                            aria-label="Close sidebar"
                            style={{
                                marginLeft: 'auto', background: 'none', border: 'none',
                                color: 'var(--t-sidebar-text)', cursor: 'pointer', fontSize: 22,
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                width: 44, height: 44, minWidth: 44, minHeight: 44
                            }}
                        >
                            <FiX />
                        </button>
                    )}
                </div>

                {/* Nav */}
                <nav className="hide-scrollbar" style={{ flex: 1, padding: '16px 12px', overflowY: 'auto' }}>
                    <div style={{ color: 'var(--t-sidebar-text)', opacity: 0.7, fontSize: 10, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '1.5px', padding: '8px 12px', marginBottom: 4 }}>
                        Menu
                    </div>
                    {menuItems.map((item) => {
                        const Icon = item.icon
                        const isActive = location.pathname === item.path ||
                            (item.path === '/teacher/dashboard' && location.pathname === '/teacher')
                        return (
                            <NavLink
                                key={item.name}
                                to={item.path}
                                onClick={() => { if (!isDesktop) setIsSidebarOpen(false) }}
                                className="t-tap"
                                style={{
                                    display: 'flex', alignItems: 'center', gap: 12,
                                    padding: '10px 14px', borderRadius: 10, marginBottom: 2,
                                    minHeight: 48, boxSizing: 'border-box',
                                    textDecoration: 'none', fontSize: 14, fontWeight: 500,
                                    transition: 'all 0.2s ease',
                                    background: isActive ? 'var(--t-sidebar-active-bg)' : 'transparent',
                                    color: isActive ? '#fff' : 'var(--t-sidebar-text)',
                                    borderLeft: isActive ? '3px solid var(--t-primary-soft)' : '3px solid transparent',
                                }}
                            >
                                <Icon style={{ fontSize: 18, color: isActive ? '#fff' : 'var(--t-sidebar-text)' }} />
                                <span>{item.name}</span>
                                {isActive && <FiChevronRight style={{ marginLeft: 'auto', fontSize: 14 }} />}
                            </NavLink>
                        )
                    })}
                </nav>

                {/* User Card */}
                <div style={{
                    margin: '0 12px 12px', padding: '14px',
                    background: 'rgba(255,255,255,0.05)', borderRadius: 12,
                    border: '1px solid rgba(255,255,255,0.06)'
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 10 }}>
                        <div style={{
                            width: 36, height: 36, borderRadius: '50%',
                            background: 'var(--t-primary)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            color: '#fff', fontWeight: 700, fontSize: 14, overflow: 'hidden'
                        }}>
                            {avatarUrl ? <img width="800" height="600" src={avatarUrl} alt="Avatar" style={{ width: '100%', height: '100%', objectFit: 'cover' }} /> : initial}
                        </div>
                        <div>
                            <div style={{ color: '#fff', fontSize: 13, fontWeight: 600 }}>{displayName}</div>
                            <div style={{ color: 'var(--t-sidebar-text)', fontSize: 11, fontWeight: 500 }}>Instructor</div>
                        </div>
                    </div>
                    <button
                        onClick={() => window.location.href = '/?logout=1'}
                        className="t-btn"
                        style={{
                            width: '100%', minHeight: 44, display: 'flex', alignItems: 'center', justifyContent: 'center',
                            gap: 8, padding: '8px', borderRadius: 8, border: '1px solid rgba(185,28,28,0.3)',
                            background: 'rgba(185,28,28,0.1)', color: 'var(--t-danger)', fontSize: 13,
                            fontWeight: 600, cursor: 'pointer'
                        }}
                    >
                        <FiLogOut /> Logout
                    </button>
                </div>
            </aside>

            {/* ══════════════ MAIN CONTENT ══════════════ */}
            <main style={{ flex: 1, display: 'flex', flexDirection: 'column', minWidth: 0, overflow: 'visible' }}>
                {/* Header */}
                <header style={{
                    height: 64, display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    padding: isMobile ? '0 12px' : '0 24px',
                    paddingTop: 'env(safe-area-inset-top, 0px)',
                    background: 'var(--t-surface)',
                    borderBottom: '1px solid var(--t-border)', zIndex: 30,
                    position: 'relative', overflow: 'visible', flexShrink: 0
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                        {!isDesktop && (
                            <button
                                onClick={toggleSidebar}
                                className="t-icon-btn"
                                aria-label="Toggle navigation menu"
                                style={{
                                    display: 'flex',
                                    alignItems: 'center', justifyContent: 'center',
                                    width: 44, height: 44, minWidth: 44, minHeight: 44, borderRadius: 10, border: 'none',
                                    background: 'var(--t-bg)', color: 'var(--t-text-2)', cursor: 'pointer', fontSize: 20
                                }}
                            ><FiMenu /></button>
                        )}

                        {isMobile && (
                            <div style={{ fontWeight: 700, fontSize: 16, color: 'var(--t-text)', display: 'flex', alignItems: 'center', gap: 6 }}>
                                TiT <span style={{ color: 'var(--t-primary)' }}>Teacher</span>
                            </div>
                        )}

                        {isTabletOrDesktop && (
                            <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                                <FiSearch style={{ position: 'absolute', left: 14, color: 'var(--t-text-3)', fontSize: 16 }} />
                                <input
                                    type="text"
                                    placeholder="Search portal..."
                                    value={searchQuery}
                                    onChange={e => setSearchQuery(e.target.value)}
                                    style={{
                                        width: 320, padding: '10px 16px 10px 42px', borderRadius: 12,
                                        border: '1px solid var(--t-border)', background: 'var(--t-bg)', fontSize: 14,
                                        outline: 'none', color: 'var(--t-text)', transition: 'all 0.2s'
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
                                        background: 'var(--t-surface)', borderRadius: 12,
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
                                                    style={{
                                                        padding: '12px 16px', display: 'flex', alignItems: 'center', gap: 12,
                                                        cursor: 'pointer', borderBottom: '1px solid var(--t-border)',
                                                        transition: 'background 0.2s'
                                                    }}
                                                    onMouseEnter={e => e.currentTarget.style.background = 'var(--t-bg)'}
                                                    onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
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
                                            <div style={{ padding: '24px 16px', textAlign: 'center', color: 'var(--t-text-3)', fontSize: 13 }}>
                                                No modules found matching "{searchQuery}"
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}
                        <style>{`@keyframes slideDown { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }`}</style>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', gap: isMobile ? 8 : 16 }}>
                        {/* Notification Bell */}
                        <GlobalNotificationBell />

                        <div style={{
                            display: 'flex', alignItems: 'center', gap: 10,
                            paddingLeft: isMobile ? 8 : 16, borderLeft: '1px solid var(--t-border)', position: 'relative'
                        }}>
                            {isTabletOrDesktop && (
                                <div style={{ textAlign: 'right' }}>
                                    <div style={{ fontSize: 14, fontWeight: 600, color: 'var(--t-text)' }}>{displayName} </div>
                                    <div style={{ fontSize: 11, color: 'var(--t-text-3)', fontWeight: 500 }}>Instructor</div>
                                </div>
                            )}
                            {/* User Profile Dropdown */}
                            <div style={{ position: 'relative' }} ref={profileMenuRef}>
                                <div 
                                    onClick={() => setShowProfileMenu(!showProfileMenu)}
                                    className="t-icon-btn"
                                    role="button"
                                    tabIndex={0}
                                    onKeyDown={e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setShowProfileMenu(!showProfileMenu); } }}
                                    aria-label="User profile menu"
                                    style={{
                                        width: 44, height: 44, minWidth: 44, minHeight: 44, borderRadius: '50%',
                                        background: 'var(--t-primary)',
                                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                                        color: '#fff', fontWeight: 700, fontSize: 15,
                                        border: '2px solid var(--t-surface)', cursor: 'pointer', overflow: 'hidden',
                                        opacity: uploadingAvatar ? 0.6 : 1
                                    }}
                                >
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
                                        width: 200, background: 'var(--t-surface)', borderRadius: 12,
                                        boxShadow: '0 10px 30px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05)',
                                        border: '1px solid var(--t-border)', zIndex: 100, overflow: 'hidden',
                                        animation: 'slideDown 0.2s ease'
                                    }}>
                                        <div style={{ padding: '16px', borderBottom: '1px solid var(--t-border)', background: 'var(--t-bg)' }}>
                                            <div style={{ fontSize: 13, fontWeight: 700, color: 'var(--t-text)', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{displayName}</div>
                                            <div style={{ fontSize: 11, color: 'var(--t-text-3)', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{user?.email || ''}</div>
                                        </div>
                                        <div style={{ padding: '8px' }}>
                                            <button 
                                                onClick={() => { setShowProfileMenu(false); navigate('/teacher/settings'); }}
                                                className="t-btn"
                                                style={{
                                                    width: '100%', minHeight: 44, display: 'flex', alignItems: 'center', gap: 10,
                                                    padding: '10px 12px', background: 'transparent', border: 'none',
                                                    color: 'var(--t-text-2)', fontSize: 13, fontWeight: 500, cursor: 'pointer',
                                                    borderRadius: 8, textAlign: 'left'
                                                }}
                                            >
                                                <FiSettings style={{ fontSize: 15 }} /> Settings
                                            </button>
                                            <button 
                                                onClick={() => { setShowProfileMenu(false); fileInputRef.current?.click(); }}
                                                className="t-btn"
                                                style={{
                                                    width: '100%', minHeight: 44, display: 'flex', alignItems: 'center', gap: 10,
                                                    padding: '10px 12px', background: 'transparent', border: 'none',
                                                    color: 'var(--t-text-2)', fontSize: 13, fontWeight: 500, cursor: 'pointer',
                                                    borderRadius: 8, textAlign: 'left', marginTop: 2
                                                }}
                                            >
                                                <FiUser style={{ fontSize: 15 }} /> Upload Photo
                                            </button>
                                            <button 
                                                onClick={() => { setShowProfileMenu(false); window.location.href = '/?logout=1'; }}
                                                className="t-btn"
                                                style={{
                                                    width: '100%', minHeight: 44, display: 'flex', alignItems: 'center', gap: 10,
                                                    padding: '10px 12px', background: 'transparent', border: 'none',
                                                    color: 'var(--t-danger)', fontSize: 13, fontWeight: 600, cursor: 'pointer',
                                                    borderRadius: 8, textAlign: 'left', marginTop: 4
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
                <div className="teacher-content-container">
                    <div style={{ maxWidth: 1200, margin: '0 auto' }}>
                        {children}
                    </div>
                </div>
            </main>
        </div>
    )
}
