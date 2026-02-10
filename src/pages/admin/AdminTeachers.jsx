import React, { useState, useEffect } from 'react'
import {
    FiSearch,
    FiUserPlus,
    FiVideo,
    FiFileText,
    FiActivity,
    FiMail,
    FiPhone,
    FiMoreVertical
} from 'react-icons/fi'
import { getAdminTeachers } from '../../services/dashboardService'
import './AdminTeachers.css'

export default function AdminTeachers() {
    const [teachers, setTeachers] = useState([])
    const [loading, setLoading] = useState(true)
    const [searchTerm, setSearchTerm] = useState('')

    useEffect(() => {
        async function loadTeachers() {
            try {
                const data = await getAdminTeachers()
                setTeachers(data || [])
            } catch (error) {
                console.error('Error loading teachers:', error)
            } finally {
                setLoading(false)
            }
        }
        loadTeachers()
    }, [])

    if (loading) return <div className="loading-shimmer">Accessing teacher records...</div>

    return (
        <div className="admin-teachers">
            <div className="page-header">
                <div className="header-info">
                    <h1>Faculty Management</h1>
                    <p>Supervise teaching staff, assign classes, and track performance.</p>
                </div>
                <button className="add-btn"><FiUserPlus /> Add Instructor</button>
            </div>

            <div className="teachers-grid">
                {teachers.map((teacher) => (
                    <div key={teacher.id} className="teacher-card">
                        <div className="card-top">
                            <div className="teacher-main">
                                <div className="avatar">{teacher.name?.charAt(0)}</div>
                                <div className="info">
                                    <h3>{teacher.name}</h3>
                                    <span>Senior Instructor</span>
                                </div>
                            </div>
                            <button className="more-btn"><FiMoreVertical /></button>
                        </div>

                        <div className="card-stats">
                            <div className="stat-item">
                                <span className="val">{teacher.schedules_count || 0}</span>
                                <span className="lbl">Upcoming Classes</span>
                            </div>
                            <div className="stat-item">
                                <span className="val">{teacher.assignments_count || 0}</span>
                                <span className="lbl">Total Assignments</span>
                            </div>
                        </div>

                        <div className="contact-links">
                            <a href={`mailto:${teacher.email}`} className="contact-pill"><FiMail /> Email</a>
                            <a href={`tel:${teacher.phone_number}`} className="contact-pill"><FiPhone /> Call</a>
                        </div>

                        <div className="card-footer">
                            <button className="action-link"><FiVideo /> View Schedule</button>
                            <button className="action-link"><FiActivity /> Performance</button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    )
}
