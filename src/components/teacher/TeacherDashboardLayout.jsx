import React, { useState } from 'react'
import { NavLink, useLocation } from 'react-router-dom'
import {
    FiHome, FiUsers, FiCalendar, FiBookOpen, FiFileText,
    FiPieChart, FiMessageSquare, FiSettings, FiLogOut,
    FiMenu, FiX, FiSearch, FiBell, FiChevronRight
} from 'react-icons/fi'

const menuItems = [
    { name: 'Dashboard', icon: FiHome, path: '/teacher/dashboard' },
    { name: 'My Students', icon: FiUsers, path: '/teacher/students' },
    { name: 'Schedule', icon: FiCalendar, path: '/teacher/schedule' },
    { name: 'Materials', icon: FiBookOpen, path: '/teacher/materials' },
    { name: 'Assignments', icon: FiFileText, path: '/teacher/assignments' },
    { name: 'Reports', icon: FiPieChart, path: '/teacher/reports' },
    { name: 'Messages', icon: FiMessageSquare, path: '/teacher/messages' },
    { name: 'Settings', icon: FiSettings, path: '/teacher/settings' },
]

export default function TeacherDashboardLayout({ children, user }) {
    const [isSidebarOpen, setIsSidebarOpen] = useState(false)
    const location = useLocation()

    const toggleSidebar = () => setIsSidebarOpen(!isSidebarOpen)
    const initial = (user?.full_name || user?.username || 'T').charAt(0).toUpperCase()

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
                    }}>T</div>
                    <div>
                        <div style={{ color: '#fff', fontWeight: 700, fontSize: 18, letterSpacing: '-0.5px' }}>
                            TiT<span style={{ color: '#38bdf8' }}>Edu</span>
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
                <nav style={{ flex: 1, padding: '16px 12px', overflowY: 'auto' }}>
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
                            color: '#fff', fontWeight: 700, fontSize: 14
                        }}>{initial}</div>
                        <div>
                            <div style={{ color: '#e2e8f0', fontSize: 13, fontWeight: 600 }}>Prof. {user?.full_name || 'Teacher'}</div>
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
                                placeholder="Search students, assignments..."
                                style={{
                                    width: 320, padding: '10px 16px 10px 42px', borderRadius: 12,
                                    border: '1px solid #e2e8f0', background: '#f8fafc', fontSize: 14,
                                    outline: 'none', color: '#334155', transition: 'all 0.2s'
                                }}
                                onFocus={e => { e.target.style.borderColor = '#38bdf8'; e.target.style.boxShadow = '0 0 0 3px rgba(56,189,248,0.1)' }}
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
                        </button>

                        <div style={{
                            display: 'flex', alignItems: 'center', gap: 10,
                            paddingLeft: 16, borderLeft: '1px solid #e2e8f0'
                        }}>
                            <div style={{ textAlign: 'right', display: window.innerWidth < 640 ? 'none' : 'block' }}>
                                <div style={{ fontSize: 14, fontWeight: 600, color: '#1e293b' }}>Prof. {user?.full_name || 'Teacher'}</div>
                                <div style={{ fontSize: 11, color: '#94a3b8', fontWeight: 500 }}>Instructor</div>
                            </div>
                            <div style={{
                                width: 38, height: 38, borderRadius: '50%',
                                background: 'linear-gradient(135deg, #0ea5e9, #06b6d4)',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                color: '#fff', fontWeight: 700, fontSize: 15,
                                boxShadow: '0 2px 8px rgba(14,165,233,0.3)',
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
