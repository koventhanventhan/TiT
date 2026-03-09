import React from 'react'
import AdminSidebar from './AdminSidebar'
import { useSettings } from '../../context/SettingsContext'
import './AdminDashboardLayout.css'

export default function AdminDashboardLayout({ children, user }) {
    const { getSetting } = useSettings()
    const instituteName = getSetting('site_name', 'Campus Governance')

    return (
        <div className="admin-console-layout">
            <AdminSidebar />
            <main className="admin-console-main">
                <header className="admin-console-header">
                    <div className="header-left">
                        <h3>{instituteName}</h3>
                    </div>
                    <div className="header-right">
                        <div className="header-search">
                            <input type="text" placeholder="Search across system..." />
                        </div>
                        <div className="admin-user-info">
                            <div className="text-right mr-3">
                                <p className="admin-name">{user?.first_name} {user?.last_name || 'Admin'}</p>
                                <p className="admin-role">Institute Admin</p>
                            </div>
                            <div className="admin-avatar">
                                {user?.first_name?.charAt(0) || 'A'}
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
