import React from 'react'
import { NavLink } from 'react-router-dom'
import {
    FiGrid,
    FiUsers,
    FiUserCheck,
    FiCalendar,
    FiCreditCard,
    FiBook,
    FiMessageSquare,
    FiSettings,
    FiLogOut,
    FiBarChart2,
    FiFileText,
    FiVideo
} from 'react-icons/fi'
import { useSettings } from '../../context/SettingsContext'
import './AdminSidebar.css'

export default function AdminSidebar({ isOpen, setIsOpen }) {
    const menuGroups = [
        {
            title: 'Main',
            items: [
                { name: 'Dashboard', icon: <FiGrid />, path: '/admin/dashboard' },
                { name: 'Analytics', icon: <FiBarChart2 />, path: '/admin/analytics' },
            ]
        },
        {
            title: 'Management',
            items: [
                { name: 'Students', icon: <FiUsers />, path: '/admin/students' },
                { name: 'Teachers', icon: <FiUserCheck />, path: '/admin/teachers' },
                { name: 'Calendar', icon: <FiCalendar />, path: '/admin/calendar' },
                { name: 'Zoom Classes', icon: <FiVideo />, path: '/admin/zoom' },
                { name: 'Attendance', icon: <FiBarChart2 />, path: '/admin/attendance' },
            ]
        },
        {
            title: 'LMS Content',
            items: [
                { name: 'Materials', icon: <FiBook />, path: '/admin/materials' },
                { name: 'Assignments', icon: <FiFileText />, path: '/admin/assignments' },
                { name: 'Messages', icon: <FiMessageSquare />, path: '/admin/messages' },
            ]
        },
        {
            title: 'Finance',
            items: [
                { name: 'Payments', icon: <FiCreditCard />, path: '/admin/payments' },
            ]
        },
        {
            title: 'System',
            items: [
                { name: 'Settings', icon: <FiSettings />, path: '/admin/settings' },
            ]
        }
    ]

    const { getSetting } = useSettings()
    const instituteName = getSetting('site_name', 'Institute Panel')
    const instituteLogo = getSetting('logo_url', null)

    return (
        <aside className={`admin-sidebar ${isOpen ? 'mobile-open' : ''}`}>
            <div className="sidebar-header">
                <div className="sidebar-logo">
                    <div className="logo-icon-box admin">
                        {instituteLogo ? (
                            <img src={instituteLogo} alt="Logo" className="sidebar-avatar" />
                        ) : (
                            instituteName.charAt(0)
                        )}
                    </div>
                    <div className="logo-text">
                        <h4>{instituteName}</h4>
                        <span>Academy Console</span>
                    </div>
                </div>
            </div>

            <nav className="sidebar-nav">
                {menuGroups.map((group, idx) => (
                    <div key={idx} className="nav-group">
                        <h5 className="group-title">{group.title}</h5>
                        {group.items.map((item) => (
                            <NavLink
                                key={item.name}
                                to={item.path}
                                className={({ isActive }) => `sidebar-link ${isActive ? 'active' : ''}`}
                                end={item.path === '/admin/dashboard'}
                            >
                                <span className="link-icon">{item.icon}</span>
                                <span className="link-text">{item.name}</span>
                            </NavLink>
                        ))}
                    </div>
                ))}
            </nav>

            <div className="sidebar-footer">
                <button className="logout-btn" onClick={() => window.location.href = '/?logout=1'}>
                    <FiLogOut /> <span>Exit Console</span>
                </button>
            </div>
        </aside>
    )
}
