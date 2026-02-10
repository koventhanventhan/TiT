import React from 'react'
import TeacherSidebar from './TeacherSidebar'
import './TeacherDashboardLayout.css'

export default function TeacherDashboardLayout({ children, user }) {
    return (
        <div className="teacher-console-layout">
            <TeacherSidebar />
            <main className="teacher-console-main">
                <header className="teacher-console-header">
                    <div className="header-search">
                        <input type="text" placeholder="Search for students, classes..." />
                    </div>
                    <div className="header-user">
                        <div className="user-info">
                            <span className="user-name">{user?.name || 'Teacher'}</span>
                            <span className="user-role">Premium Instructor</span>
                        </div>
                        <div className="user-avatar">
                            {user?.name?.charAt(0) || 'T'}
                        </div>
                    </div>
                </header>
                <div className="teacher-console-content">
                    {children}
                </div>
            </main>
        </div>
    )
}
