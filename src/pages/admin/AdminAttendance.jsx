import React, { useState, useEffect } from 'react'
import {
    FiCheckCircle,
    FiXCircle,
    FiCalendar,
    FiUser,
    FiBarChart2,
    FiActivity
} from 'react-icons/fi'
import { getAdminAttendance } from '../../services/dashboardService'
import './AdminAttendance.css'

export default function AdminAttendance() {
    const [data, setData] = useState({ records: [], stats: {} })
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function loadAttendance() {
            try {
                const res = await getAdminAttendance()
                setData({ records: res.records.data || [], stats: res.stats })
            } catch (error) {
                console.error('Error loading attendance:', error)
            } finally {
                setLoading(false)
            }
        }
        loadAttendance()
    }, [])

    if (loading) return <div className="loading-shimmer">Analyzing attendance trends...</div>

    return (
        <div className="admin-attendance">
            <div className="page-header">
                <div className="header-info">
                    <h1>Attendance Tracking</h1>
                    <p>Global monitoring of student participation across all live classes.</p>
                </div>
            </div>

            <div className="attendance-stats-row">
                <div className="att-stat-card">
                    <FiCheckCircle className="green" />
                    <div className="info">
                        <h3>{data.stats.total_present}</h3>
                        <span>Total Present Instances</span>
                    </div>
                </div>
                <div className="att-stat-card">
                    <FiXCircle className="red" />
                    <div className="info">
                        <h3>{data.stats.total_absent}</h3>
                        <span>Total Absent Instances</span>
                    </div>
                </div>
                <div className="att-stat-card">
                    <FiActivity className="blue" />
                    <div className="info">
                        <h3>{Math.round((data.stats.total_present / (data.stats.total_present + data.stats.total_absent || 1)) * 100)}%</h3>
                        <span>Avg. Participation Rate</span>
                    </div>
                </div>
            </div>

            <div className="attendance-list-container">
                <div className="section-header">
                    <h3>Recent Attendance Records</h3>
                </div>
                <table className="admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class Session</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.records.map((rec) => (
                            <tr key={rec.id}>
                                <td>
                                    <div className="user-info">
                                        <FiUser />
                                        <span>{rec.user?.full_name || rec.user?.name}</span>
                                    </div>
                                </td>
                                <td>{rec.zoom_schedule?.title || 'General Session'}</td>
                                <td>
                                    <div className="time-info">
                                        <span>{new Date(rec.created_at).toLocaleDateString()}</span>
                                        <small>{new Date(rec.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</small>
                                    </div>
                                </td>
                                <td>
                                    <span className={`status-pill ${rec.status}`}>
                                        {rec.status}
                                    </span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    )
}
