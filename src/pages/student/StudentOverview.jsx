import React, { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import {
    FiVideo,
    FiCalendar,
    FiCheckCircle,
    FiClock,
    FiAlertCircle,
    FiArrowRight,
    FiBook,
    FiFileText
} from 'react-icons/fi'
import { getStudentStats } from '../../services/dashboardService'
import StudentPaymentModule from '../../components/student/StudentPaymentModule'
import './StudentOverview.css'

export default function StudentOverview() {
    const [stats, setStats] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function loadStats() {
            try {
                const data = await getStudentStats()
                setStats(data)
            } catch (error) {
                console.error('Error loading student stats:', error)
            } finally {
                setLoading(false)
            }
        }
        loadStats()
    }, [])

    if (loading) return <div className="loading-shimmer">Loading dashboard...</div>

    return (
        <div className="student-overview">
            <header className="overview-header">
                <h1>Welcome Back, {stats?.user_name || 'Student'}!</h1>
                <p>Here's what's happening with your learning today.</p>
            </header>

            <StudentPaymentModule />

            <div className="stats-grid">
                <div className="overview-stat-card purple">
                    <div className="stat-icon"><FiVideo /></div>
                    <div className="stat-content">
                        <span className="stat-value">{stats?.today_classes || 0}</span>
                        <span className="stat-label">Today's Classes</span>
                    </div>
                </div>
                <div className="overview-stat-card blue">
                    <div className="stat-icon"><FiFileText /></div>
                    <div className="stat-content">
                        <span className="stat-value">{stats?.pending_assignments || 0}</span>
                        <span className="stat-label">Pending Tasks</span>
                    </div>
                </div>
                <div className="overview-stat-card green">
                    <div className="stat-icon"><FiCheckCircle /></div>
                    <div className="stat-content">
                        <span className="stat-value">{stats?.attendance_rate || 0}%</span>
                        <span className="stat-label">Attendance</span>
                    </div>
                </div>
                <div className="overview-stat-card orange">
                    <div className="stat-icon"><FiCalendar /></div>
                    <div className="stat-content">
                        <span className="stat-value">{stats?.upcoming_classes || 0}</span>
                        <span className="stat-label">Next 7 Days</span>
                    </div>
                </div>
            </div>

            <div className="overview-sections-grid">
                <section className="dashboard-section-box">
                    <div className="section-header">
                        <h3>Latest Announcements</h3>
                        <Link to="/student/announcements" className="view-all">View All <FiArrowRight /></Link>
                    </div>
                    <div className="announcements-list">
                        {stats?.recent_announcements?.map((ann) => (
                            <div key={ann.id} className={`announcement-item ${ann.type}`}>
                                <div className="ann-icon">
                                    {ann.type === 'important' ? <FiAlertCircle /> : <FiBook />}
                                </div>
                                <div className="ann-content">
                                    <h4>{ann.title}</h4>
                                    <span className="ann-date">{new Date(ann.date).toLocaleDateString()}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="dashboard-section-box">
                    <div className="section-header">
                        <h3>Today's Learning Path</h3>
                        <Link to="/student/schedule" className="view-all">Schedule <FiArrowRight /></Link>
                    </div>
                    <div className="learning-path-list">
                        <div className="path-item">
                            <div className="path-time">09:00 AM</div>
                            <div className="path-detail">
                                <h4>Mathematics - Calculus Intro</h4>
                                <span>Prof. Kumara • Grade 12</span>
                            </div>
                            <button className="join-tiny">Join Class</button>
                        </div>
                        <div className="path-item">
                            <div className="path-time">11:30 AM</div>
                            <div className="path-detail">
                                <h4>Physics - Thermodynamics</h4>
                                <span>Prof. Silva • Grade 12</span>
                            </div>
                            <button className="join-tiny disabled">Upcoming</button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    )
}
