import React from 'react'
import { NavLink } from 'react-router-dom'
import {
    FiHome,
    FiCalendar,
    FiVideo,
    FiFileText,
    FiBookOpen,
    FiBarChart2,
    FiSettings,
    FiLogOut
} from 'react-icons/fi'
import './StudentSidebar.css'

export default function StudentSidebar() {
    const menuItems = [
        { name: 'Dashboard', icon: <FiHome />, path: '/student/dashboard' },
        { name: 'Schedule', icon: <FiCalendar />, path: '/student/schedule' },
        { name: 'Zoom Classes', icon: <FiVideo />, path: '/student/zoom' },
        { name: 'Assignments', icon: <FiFileText />, path: '/student/assignments' },
        { name: 'Materials', icon: <FiBookOpen />, path: '/student/materials' },
        { name: 'Performance', icon: <FiBarChart2 />, path: '/student/performance' },
        { name: 'Settings', icon: <FiSettings />, path: '/student/settings' },
    ]

    return (
        <aside className="student-sidebar">
            <div className="sidebar-header">
                <div className="sidebar-logo">
                    <span className="logo-icon student">S</span>
                    <span className="logo-text">Student Portal</span>
                </div>
            </div>

            <nav className="sidebar-nav">
                {menuItems.map((item) => (
                    <NavLink
                        key={item.name}
                        to={item.path}
                        className={({ isActive }) => `sidebar-link ${isActive ? 'active' : ''}`}
                        end={item.path === '/student/dashboard'}
                    >
                        <span className="link-icon">{item.icon}</span>
                        <span className="link-text">{item.name}</span>
                    </NavLink>
                ))}
            </nav>

            <div className="sidebar-footer">
                <button className="logout-btn" onClick={() => window.location.href = '/?logout=1'}>
                    <FiLogOut /> <span>Logout</span>
                </button>
            </div>
        </aside>
    )
}
