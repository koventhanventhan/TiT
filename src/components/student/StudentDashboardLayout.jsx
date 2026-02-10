import React from 'react'
import StudentSidebar from './StudentSidebar'
import './StudentDashboardLayout.css'

export default function StudentDashboardLayout({ children, user }) {
    return (
        <div className="student-console-layout">
            <StudentSidebar />
            <main className="student-console-main">
                <header className="student-console-header">
                    <div className="header-search">
                        <input type="text" placeholder="Search for classes, assignments..." />
                    </div>
                    <div className="header-user">
                        <div className="user-info">
                            <span className="user-name">{user?.full_name || user?.username || 'Student'}</span>
                            <span className="user-role">Student Portal</span>
                        </div>
                        <div className="user-avatar student">
                            {(user?.full_name || user?.username || 'S').charAt(0).toUpperCase()}
                        </div>
                    </div>
                </header>
                <div className="student-console-content">
                    {children}
                </div>
            </main>
        </div>
    )
}
