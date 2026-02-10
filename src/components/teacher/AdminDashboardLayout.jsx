import React from 'react'
import AdminSidebar from './AdminSidebar'
import './AdminDashboardLayout.css'

export default function AdminDashboardLayout({ children, user }) {
    return (
        <div className="admin-console-layout">
            <AdminSidebar />
            <main className="admin-console-main">
                <header className="admin-console-header">
                    <div className="header-left">
                        <h3>Campus Governance</h3>
                    </div>
                    <div className="header-right">
                        <div className="header-search">
                            <input type="text" placeholder="Search across system..." />
                        </div>
                        <div className="admin-user-info">
                            <div className="text-right mr-3">
                                <p className="admin-name">{user?.first_name} {user?.last_name || 'Admin'}</p>
                                <p className="admin-role">System Master</p>
                            </div>
                            <div className="admin-avatar">A</div>
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
