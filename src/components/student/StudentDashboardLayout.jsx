import React, { useState } from 'react'
import { NavLink, useNavigate, useLocation } from 'react-router-dom'
import {
    FiHome, FiCalendar, FiVideo, FiFileText, FiBookOpen,
    FiBarChart2, FiMessageSquare, FiSettings, FiLogOut,
    FiMenu, FiX, FiSearch, FiBell, FiChevronRight
} from 'react-icons/fi'

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
    const [isSidebarOpen, setIsSidebarOpen] = useState(false)
    const location = useLocation()

    const toggleSidebar = () => setIsSidebarOpen(!isSidebarOpen)
    const initial = (user?.full_name || user?.username || 'S').charAt(0).toUpperCase()

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
                background: 'linear-gradient(180deg, #0f172a 0%, #1e293b 100%)',
                display: 'flex', flexDirection: 'column',
                position: isSidebarOpen ? 'fixed' : undefined,
                inset: isSidebarOpen ? '0 auto 0 0' : undefined,
                zIndex: 999,
                transform: !isSidebarOpen ? undefined : undefined,
                boxShadow: '4px 0 24px rgba(0,0,0,0.15)',
                transition: 'transform 0.3s ease',
                ...(window.innerWidth < 1024 && !isSidebarOpen ? { display: 'none' } : {})
            }}>
                {/* Logo */}
                <div style={{
                    padding: '24px 20px', display: 'flex', alignItems: 'center',
                    gap: 12, borderBottom: '1px solid rgba(255,255,255,0.08)'
                }}>
                    <div style={{
                        width: 40, height: 40, borderRadius: 12,
                        background: 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        color: '#fff', fontWeight: 800, fontSize: 18,
                        boxShadow: '0 4px 12px rgba(99,102,241,0.4)'
                    }}>T</div>
                    <div>
                        <div style={{ color: '#fff', fontWeight: 700, fontSize: 18, letterSpacing: '-0.5px' }}>
                            TiT<span style={{ color: '#818cf8' }}>Edu</span>
                        </div>
                        <div style={{ color: '#64748b', fontSize: 11, fontWeight: 500 }}>Student Portal</div>
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
                            (item.path === '/student/dashboard' && (location.pathname === '/student' || location.pathname === '/student/overview'))
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
                                    background: isActive ? 'linear-gradient(135deg, rgba(99,102,241,0.15) 0%, rgba(139,92,246,0.1) 100%)' : 'transparent',
                                    color: isActive ? '#a5b4fc' : '#94a3b8',
                                    borderLeft: isActive ? '3px solid #818cf8' : '3px solid transparent',
                                }}
                            >
                                <Icon style={{ fontSize: 18, color: isActive ? '#818cf8' : '#64748b' }} />
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
                            background: 'linear-gradient(135deg, #6366f1, #a855f7)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            color: '#fff', fontWeight: 700, fontSize: 14
                        }}>{initial}</div>
                        <div>
                            <div style={{ color: '#e2e8f0', fontSize: 13, fontWeight: 600 }}>{user?.full_name || 'Student'}</div>
                            <div style={{ color: '#64748b', fontSize: 11, fontWeight: 500 }}>Student</div>
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
            <main style={{ flex: 1, display: 'flex', flexDirection: 'column', minWidth: 0, overflow: 'hidden' }}>
                {/* Header */}
                <header style={{
                    height: 64, display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    padding: '0 24px', background: '#fff',
                    borderBottom: '1px solid #e2e8f0', zIndex: 30
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                        <button
                            onClick={toggleSidebar}
                            className="lg-hidden"
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
                                placeholder="Search classes, materials..."
                                style={{
                                    width: 320, padding: '10px 16px 10px 42px', borderRadius: 12,
                                    border: '1px solid #e2e8f0', background: '#f8fafc', fontSize: 14,
                                    outline: 'none', color: '#334155',
                                    transition: 'all 0.2s'
                                }}
                                onFocus={e => { e.target.style.borderColor = '#818cf8'; e.target.style.boxShadow = '0 0 0 3px rgba(129,140,248,0.1)' }}
                                onBlur={e => { e.target.style.borderColor = '#e2e8f0'; e.target.style.boxShadow = 'none' }}
                            />
                        </div>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
                        <button style={{
                            position: 'relative', width: 40, height: 40, borderRadius: 10,
                            border: '1px solid #e2e8f0', background: '#fff',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            color: '#64748b', cursor: 'pointer', fontSize: 18
                        }}>
                            <FiBell />
                            <span style={{
                                position: 'absolute', top: 8, right: 8, width: 8, height: 8,
                                borderRadius: '50%', background: '#ef4444', border: '2px solid #fff'
                            }} />
                        </button>

                        <div style={{
                            display: 'flex', alignItems: 'center', gap: 10,
                            paddingLeft: 16, borderLeft: '1px solid #e2e8f0'
                        }}>
                            <div style={{ textAlign: 'right', display: window.innerWidth < 640 ? 'none' : 'block' }}>
                                <div style={{ fontSize: 14, fontWeight: 600, color: '#1e293b' }}>{user?.full_name || 'Student'}</div>
                                <div style={{ fontSize: 11, color: '#94a3b8', fontWeight: 500 }}>Student Portal</div>
                            </div>
                            <div style={{
                                width: 38, height: 38, borderRadius: '50%',
                                background: 'linear-gradient(135deg, #6366f1, #a855f7)',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                color: '#fff', fontWeight: 700, fontSize: 15,
                                boxShadow: '0 2px 8px rgba(99,102,241,0.3)',
                                border: '2px solid #fff'
                            }}>{initial}</div>
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
