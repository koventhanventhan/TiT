import React, { useState } from 'react'
import { NavLink, useLocation, useNavigate } from 'react-router-dom'
import {
    FiHome, FiUsers, FiCalendar, FiBookOpen, FiFileText,
    FiPieChart, FiMessageSquare, FiSettings, FiLogOut,
    FiMenu, FiX, FiSearch, FiBell, FiChevronRight
} from 'react-icons/fi'
import GlobalNotificationBell from '../shared/GlobalNotificationBell'
import { useToast } from '../../components/shared/ToastContext';


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
  const toast = useToast();

    const [isSidebarOpen, setIsSidebarOpen] = useState(false)
    const [searchQuery, setSearchQuery] = useState('')
    const [isSearchFocused, setIsSearchFocused] = useState(false)
    const [showProfileMenu, setShowProfileMenu] = useState(false)
    const [uploadingAvatar, setUploadingAvatar] = useState(false)
    const [localAvatar, setLocalAvatar] = useState(null)
    const fileInputRef = React.useRef(null)
    const location = useLocation()
    const navigate = useNavigate()

    const toggleSidebar = () => setIsSidebarOpen(!isSidebarOpen)

    const searchResults = menuItems.filter(item =>
        item.name.toLowerCase().includes(searchQuery.toLowerCase())
    )
    const displayName = user?.full_name || user?.name || user?.first_name || 'Teacher';
    const initial = displayName.charAt(0).toUpperCase();

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
            setShowProfileMenu(false);
        }
    }

    return (
        <div style={{ display: 'flex', height: '100vh', background: '#f1f5f9', fontFamily: "'Inter', sans-serif" }}>
            {/* Mobile Overlay */}
            {isSidebarOpen && (
                <div
                    onClick={toggleSidebar}
                    style={{
                        position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)',
                        zIndex: 998, backdropFilter: 'blur(4px)'
                    }}
                />
            )}

            {/* ═══════════ SIDEBAR ═══════════ */}
            <aside style={{
                width: 260,
                background: 'linear-gradient(180deg, #0c1322 0%, #162032 100%)',
                display: 'flex', flexDirection: 'column',
                position: isSidebarOpen ? 'fixed' : undefined,
                inset: isSidebarOpen ? '0 auto 0 0' : undefined,
                zIndex: 999,
                boxShadow: '4px 0 24px rgba(0,0,0,0.15)',
                transition: 'transform 0.3s ease',
                ...(window.innerWidth < 1024 && !isSidebarOpen ? { display: 'none' } : {})
            }}>
                {/* Logo */}
                <div style={{
                    padding: '24px 20px', display: 'flex', alignItems: 'center',
                    gap: 12, borderBottom: '1px solid rgba(255,255,255,0.06)'
                }}>
                    <div style={{
                        width: 40, height: 40, borderRadius: 12,
                        background: 'linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        color: '#fff', fontWeight: 800, fontSize: 18,
                        boxShadow: '0 4px 12px rgba(14,165,233,0.4)'
                    }}>TiT</div>
                    <div>
                        <div style={{ color: '#fff', fontWeight: 700, fontSize: 18, letterSpacing: '-0.5px' }}>
                            TiT<span style={{ color: '#38bdf8' }}>Education</span>
                        </div>
                        <div style={{ color: '#64748b', fontSize: 11, fontWeight: 500 }}>Teacher Portal</div>
                    </div>
                    {isSidebarOpen && (
                        <button onClick={toggleSidebar} style={{
                            marginLeft: 'auto', background: 'none', border: 'none',
                            color: '#94a3b8', cursor: 'pointer', fontSize: 22
                        }}><FiX /></button>
                    )}
                </div>

                {/* Nav */}
                <nav className="hide-scrollbar" style={{ flex: 1, padding: '16px 12px', overflowY: 'auto' }}>
                    <div style={{ color: '#475569', fontSize: 10, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '1.5px', padding: '8px 12px', marginBottom: 4 }}>
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
                                onClick={() => setIsSidebarOpen(false)}
                                style={{
                                    display: 'flex', alignItems: 'center', gap: 12,
                                    padding: '10px 14px', borderRadius: 10, marginBottom: 2,
                                    textDecoration: 'none', fontSize: 14, fontWeight: 500,
                                    transition: 'all 0.2s ease',
                                    background: isActive ? 'linear-gradient(135deg, rgba(14,165,233,0.15) 0%, rgba(6,182,212,0.1) 100%)' : 'transparent',
                                    color: isActive ? '#7dd3fc' : '#94a3b8',
                                    borderLeft: isActive ? '3px solid #38bdf8' : '3px solid transparent',
                                }}
                            >
                                <Icon style={{ fontSize: 18, color: isActive ? '#38bdf8' : '#64748b' }} />
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
                            background: 'linear-gradient(135deg, #0ea5e9, #06b6d4)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            color: '#fff', fontWeight: 700, fontSize: 14, overflow: 'hidden'
                        }}>
                            {avatarUrl ? <img src={avatarUrl} alt="Avatar" style={{ width: '100%', height: '100%', objectFit: 'cover' }} /> : initial}
                        </div>
                        <div>
                            <div style={{ color: '#e2e8f0', fontSize: 13, fontWeight: 600 }}>Sir. {displayName}</div>
                            <div style={{ color: '#64748b', fontSize: 11, fontWeight: 500 }}>Instructor</div>
                        </div>
                    </div>
                    <button
                        onClick={() => window.location.href = '/?logout=1'}
                        style={{
                            width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center',
                            gap: 8, padding: '8px', borderRadius: 8, border: '1px solid rgba(239,68,68,0.2)',
                            background: 'rgba(239,68,68,0.08)', color: '#f87171', fontSize: 13,
                            fontWeight: 600, cursor: 'pointer', transition: 'all 0.2s'
                        }}
                    >
                        <FiLogOut /> Logout
                    </button>
                </div>
            </aside>

            {/* ═══════════ MAIN CONTENT ═══════════ */}
            <main style={{ flex: 1, display: 'flex', flexDirection: 'column', minWidth: 0, overflow: 'visible' }}>
                {/* Header */}
                <header style={{
                    height: 64, display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    padding: '0 24px', background: '#fff',
                    borderBottom: '1px solid #e2e8f0', zIndex: 30,
                    position: 'relative', overflow: 'visible', flexShrink: 0
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                        <button
                            onClick={toggleSidebar}
                            style={{
                                display: window.innerWidth < 1024 ? 'flex' : 'none',
                                alignItems: 'center', justifyContent: 'center',
                                width: 40, height: 40, borderRadius: 10, border: 'none',
                                background: '#f1f5f9', color: '#475569', cursor: 'pointer', fontSize: 20
                            }}
                        ><FiMenu /></button>

                        <div style={{ position: 'relative', display: window.innerWidth < 640 ? 'none' : 'flex', alignItems: 'center' }}>
                            <FiSearch style={{ position: 'absolute', left: 14, color: '#94a3b8', fontSize: 16 }} />
                            <input
                                type="text"
                                placeholder="Search portal..."
                                value={searchQuery}
                                onChange={e => setSearchQuery(e.target.value)}
                                style={{
                                    width: 320, padding: '10px 16px 10px 42px', borderRadius: 12,
                                    border: '1px solid #e2e8f0', background: '#f8fafc', fontSize: 14,
                                    outline: 'none', color: '#334155', transition: 'all 0.2s'
                                }}
                                onFocus={e => {
                                    setIsSearchFocused(true)
                                    e.target.style.borderColor = '#38bdf8'
                                    e.target.style.boxShadow = '0 0 0 3px rgba(56,189,248,0.1)'
                                }}
                                onBlur={e => {
                                    setTimeout(() => setIsSearchFocused(false), 200)
                                    e.target.style.borderColor = '#e2e8f0'
                                    e.target.style.boxShadow = 'none'
                                }}
                            />

                            {/* Command Palette Dropdown */}
                            {isSearchFocused && searchQuery && (
                                <div style={{
                                    position: 'absolute', top: '100%', left: 0, right: 0, marginTop: 8,
                                    background: '#fff', borderRadius: 12,
                                    boxShadow: '0 10px 30px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05)',
                                    border: '1px solid #e2e8f0', zIndex: 100, overflow: 'hidden',
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
                                                    cursor: 'pointer', borderBottom: '1px solid #f1f5f9',
                                                    transition: 'background 0.2s'
                                                }}
                                                onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
                                                onMouseLeave={e => e.currentTarget.style.background = '#fff'}
                                            >
                                                <div style={{
                                                    width: 32, height: 32, borderRadius: 8, background: '#f0f9ff',
                                                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                                                    color: '#0ea5e9'
                                                }}>
                                                    <Icon />
                                                </div>
                                                <span style={{ fontSize: 14, fontWeight: 600, color: '#1e293b' }}>
                                                    {item.name}
                                                </span>
                                            </div>
                                        )
                                    }) : (
                                        <div style={{ padding: '24px 16px', textAlign: 'center', color: '#94a3b8', fontSize: 13 }}>
                                            No modules found matching "{searchQuery}"
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                        <style>{`@keyframes slideDown { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }`}</style>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
                        {/* Notification Bell */}
                        <GlobalNotificationBell />

                        <div style={{
                            display: 'flex', alignItems: 'center', gap: 10,
                            paddingLeft: 16, borderLeft: '1px solid #e2e8f0', position: 'relative'
                        }}>
                            <div style={{ textAlign: 'right', display: window.innerWidth < 640 ? 'none' : 'block' }}>
                                <div style={{ fontSize: 14, fontWeight: 600, color: '#1e293b' }}>{displayName} </div>
                                <div style={{ fontSize: 11, color: '#94a3b8', fontWeight: 500 }}>Instructor</div>
                            </div>
                            
                            {/* Profile Dropdown Trigger */}
                            <div 
                                onClick={() => setShowProfileMenu(!showProfileMenu)}
                                style={{
                                width: 38, height: 38, borderRadius: '50%',
                                background: 'linear-gradient(135deg, #0ea5e9, #06b6d4)',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                color: '#fff', fontWeight: 700, fontSize: 15,
                                boxShadow: '0 2px 8px rgba(14,165,233,0.3)',
                                border: '2px solid #fff', cursor: 'pointer', overflow: 'hidden',
                                opacity: uploadingAvatar ? 0.6 : 1
                            }}>
                                {avatarUrl && !uploadingAvatar ? (
                                    <img src={avatarUrl} alt="Profile" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
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
                                    border: '1px solid #e2e8f0', zIndex: 100, overflow: 'hidden',
                                    animation: 'slideDown 0.2s ease'
                                }}>
                                    <div style={{ padding: '16px', borderBottom: '1px solid #f1f5f9', background: '#f8fafc' }}>
                                        <div style={{ fontSize: 13, fontWeight: 700, color: '#1e293b', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{displayName}</div>
                                        <div style={{ fontSize: 11, color: '#64748b', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{user?.email || ''}</div>
                                    </div>
                                    <div style={{ padding: '8px' }}>
                                        <button 
                                            onClick={() => fileInputRef.current?.click()}
                                            style={{
                                            width: '100%', display: 'flex', alignItems: 'center', gap: 10,
                                            padding: '10px 12px', background: 'transparent', border: 'none',
                                            color: '#475569', fontSize: 13, fontWeight: 500, cursor: 'pointer',
                                            borderRadius: 8, transition: 'background 0.2s', textAlign: 'left'
                                        }}
                                        onMouseEnter={e => e.currentTarget.style.background = '#f1f5f9'}
                                        onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                                        >
                                            <FiSettings style={{ fontSize: 15 }} /> Upload Photo
                                        </button>
                                        <button 
                                            onClick={() => window.location.href = '/?logout=1'}
                                            style={{
                                            width: '100%', display: 'flex', alignItems: 'center', gap: 10,
                                            padding: '10px 12px', background: 'transparent', border: 'none',
                                            color: '#ef4444', fontSize: 13, fontWeight: 600, cursor: 'pointer',
                                            borderRadius: 8, transition: 'background 0.2s', textAlign: 'left', marginTop: 4
                                        }}
                                        onMouseEnter={e => e.currentTarget.style.background = '#fef2f2'}
                                        onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                                        >
                                            <FiLogOut style={{ fontSize: 15 }} /> Sign Out
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </header>

                {/* Content */}
                <div style={{
                    flex: 1, overflowY: 'auto', padding: '24px',
                    background: 'linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%)'
                }}>
                    <div style={{ maxWidth: 1200, margin: '0 auto' }}>
                        {children}
                    </div>
                </div>
            </main>
        </div>
    )
}
