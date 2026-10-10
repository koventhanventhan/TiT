import React, { useState, useEffect } from 'react'
import { FiUsers, FiVideo, FiFileText, FiClock, FiCheckCircle } from 'react-icons/fi'
import { getTeacherDashboardStats, getTeacherUpcomingSchedules, getActiveAnnouncement } from '../../services/dashboardService'
import './TeacherOverview.css'

export default function TeacherOverview() {
    const [stats, setStats] = useState(null)
    const [upcoming, setUpcoming] = useState([])
    const [announcement, setAnnouncement] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function loadData() {
            try {
                const [s, u, a] = await Promise.all([
                    getTeacherDashboardStats(),
                    getTeacherUpcomingSchedules(),
                    getActiveAnnouncement()
                ])
                setStats(s)
                setUpcoming(u)
                setAnnouncement(a)
            } catch (e) {
                console.error(e)
            } finally {
                setLoading(false)
            }
        }
        loadData()
    }, [])

    if (loading) return <div className="loading-shimmer">Loading overview...</div>

    const statCards = [
        { label: "Today's Classes", value: stats?.today_classes || 0, icon: <FiVideo />, color: '#3b82f6' },
        { label: "Total Students", value: stats?.total_students || 0, icon: <FiUsers />, color: '#10b981' },
        { label: "Pending Assignments", value: stats?.pending_assignments || 0, icon: <FiFileText />, color: '#f59e0b' },
        { label: "Upcoming (7d)", value: stats?.upcoming_classes || 0, icon: <FiClock />, color: '#3b82f6' },
    ]

    return (
        <div className="teacher-overview">
            <div className="stats-grid">
                {statCards.map((card, i) => (
                    <div key={i} className="stat-card" style={{ '--accent-color': card.color }}>
                        <div className="stat-icon">{card.icon}</div>
                        <div className="stat-content">
                            <h3>{card.value}</h3>
                            <p>{card.label}</p>
                        </div>
                    </div>
                ))}
            </div>

            <div className="overview-content-grid">
                <section className="overview-section upcoming-classes">
                    <div className="section-header">
                        <h2>Upcoming Sessions</h2>
                        <button className="view-all">View Full Schedule</button>
                    </div>
                    <div className="class-list">
                        {upcoming.length > 0 ? upcoming.map(cls => (
                            <div key={cls.id} className="class-item">
                                <div className="class-time">
                                    {new Date(cls.scheduled_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                </div>
                                <div className="class-details">
                                    <h4>{cls.title}</h4>
                                    <p>{cls.subject} • Grade {cls.grade}</p>
                                </div>
                                <button className="btn-join-quick">Start</button>
                            </div>
                        )) : <p className="empty-msg">No upcoming sessions found.</p>}
                    </div>
                </section>

                <section className="overview-section announcement-feed">
                    <div className="section-header">
                        <h2>Announcements</h2>
                    </div>
                    <div className="announcement-list">
                        {announcement ? (
                            <div className="announcement-item">
                                <div className="announcement-meta">{new Date(announcement.created_at).toLocaleString()}</div>
                                <h4>{announcement.title}</h4>
                                <p>{announcement.message}</p>
                            </div>
                        ) : (
                            <p className="empty-msg text-sm py-4">No active announcements.</p>
                        )}
                    </div>
                </section>
            </div>
        </div>
    )
}
