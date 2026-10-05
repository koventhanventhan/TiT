import React from 'react'
import { NavLink } from 'react-router-dom'
import {
    FiGrid,
    FiShield,
    FiPackage,
    FiTrendingUp,
    FiSettings,
    FiLogOut,
    FiActivity
} from 'react-icons/fi'
import './SuperAdminSidebar.css'

export default function SuperAdminSidebar() {
    const menuGroups = [
        {
            title: 'SaaS Control',
            items: [
                { name: 'Global Overview', icon: <FiGrid />, path: '/super-admin/dashboard' },
                { name: 'Institutes', icon: <FiShield />, path: '/super-admin/institutes' },
                { name: 'Pricing Plans', icon: <FiPackage />, path: '/super-admin/plans' },
            ]
        },
        {
            title: 'System Wide',
            items: [
                { name: 'Global Revenue', icon: <FiTrendingUp />, path: '/super-admin/revenue' },
                { name: 'System Logs', icon: <FiActivity />, path: '/super-admin/logs' },
                { name: 'Global Settings', icon: <FiSettings />, path: '/super-admin/settings' },
            ]
        }
    ]

    return (
        <aside className="super-sidebar">
            <div className="sidebar-header">
                <div className="sidebar-logo">
                    <div className="logo-icon-box super">S</div>
                    <div className="logo-text">
                        <h4>Super Admin</h4>
                        <span>SaaS Controller</span>
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
                                end={item.path === '/super-admin/dashboard'}
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
                    <FiLogOut /> <span>Kill Session</span>
                </button>
            </div>
        </aside>
    )
}
