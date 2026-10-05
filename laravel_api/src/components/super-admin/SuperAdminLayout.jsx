import React from 'react'
import SuperAdminSidebar from './SuperAdminSidebar'
import './SuperAdminLayout.css'

export default function SuperAdminLayout({ children, user }) {
    return (
        <div className="super-console-layout">
            <SuperAdminSidebar />
            <main className="super-console-main">
                <header className="super-console-header">
                    <div className="header-left">
                        <h3>LMS SaaS Control Tower</h3>
                    </div>
                    <div className="header-right">
                        <div className="super-user-info">
                            <div className="text-right mr-3">
                                <p className="super-name">{user?.full_name || user?.name || 'System Owner'}</p>
                                <p className="super-role">Full Authorization</p>
                            </div>
                            <div className="super-avatar">S</div>
                        </div>
                    </div>
                </header>
                <div className="super-console-content">
                    {children}
                </div>
            </main>
        </div>
    )
}
