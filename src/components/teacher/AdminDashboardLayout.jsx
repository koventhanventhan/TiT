import React, { useState } from 'react'
import AdminSidebar from './AdminSidebar'
import { useSettings } from '../../context/SettingsContext'
import './AdminDashboardLayout.css'
import { FiBell, FiMessageSquare, FiSearch, FiChevronDown, FiUser, FiSettings, FiLogOut, FiCalendar, FiMenu, FiX } from 'react-icons/fi'

export default function AdminDashboardLayout({ children, user }) {
    const { getSetting } = useSettings()
    const [showProfileDropdown, setShowProfileDropdown] = useState(false)
    const [isSidebarOpen, setIsSidebarOpen] = useState(false)
    const instituteName = getSetting('site_name', 'Campus Governance')

    const host = window.location.origin
    const frontendUrl = import.meta.env.VITE_FRONTEND_URL || 'http://localhost:4000'

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

                            <div className="action-icon-wrapper">
                                <FiBell className="header-icon" />
                                <span className="icon-badge">0</span>
                            </div>

                            <div className="admin-profile-trigger" onClick={() => setShowProfileDropdown(!showProfileDropdown)}>
                                <div className="admin-avatar">
                                    {user?.avatar ? (
                                        <img src={`${host}/${user.avatar}`} alt="Avatar" />
                                    ) : (
                                        user?.first_name?.charAt(0) || 'A'
                                    )}
                                </div>
                                <div className="admin-user-meta">
                                    <span className="admin-name">{user?.first_name || 'Admin'}</span>
                                    <FiChevronDown className={`chevron ${showProfileDropdown ? 'open' : ''}`} />
                                </div>

                                {showProfileDropdown && (
                                    <div className="profile-dropdown-menu">
                                        <div className="dropdown-header">
                                            <p className="user-full-name">{user?.first_name} {user?.last_name}</p>
                                            <p className="user-email">{user?.email}</p>
                                        </div>
                                        <div className="dropdown-divider"></div>
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
