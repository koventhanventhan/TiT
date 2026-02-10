import React, { useState, useEffect } from 'react'
import {
    FiVideo,
    FiPlus,
    FiCalendar,
    FiClock,
    FiLink,
    FiUser,
    FiTrash2,
    FiEdit
} from 'react-icons/fi'
import { getAdminZoom } from '../../services/dashboardService'
import './AdminZoom.css'

export default function AdminZoom() {
    const [classes, setClasses] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function loadZoom() {
            try {
                const data = await getAdminZoom()
                setClasses(data.data || [])
            } catch (error) {
                console.error('Error loading zoom classes:', error)
            } finally {
                setLoading(false)
            }
        }
        loadZoom()
    }, [])

    if (loading) return <div className="loading-shimmer">Indexing Zoom schedules...</div>

    return (
        <div className="admin-zoom">
            <div className="page-header">
                <div className="header-info">
                    <h1>Zoom Class Control</h1>
                    <p>Schedule live sessions, manage meeting links, and assign instructors.</p>
                </div>
                <button className="add-btn"><FiPlus /> Schedule New Session</button>
            </div>

            <div className="zoom-schedules-grid">
                {classes.map((cls) => (
                    <div key={cls.id} className="zoom-class-card">
                        <div className="card-badge">{cls.grade}</div>
                        <div className="card-top">
                            <h3>{cls.title}</h3>
                            <div className="class-meta">
                                <span><FiCalendar /> {new Date(cls.scheduled_at).toLocaleDateString()}</span>
                                <span><FiClock /> {new Date(cls.scheduled_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                            </div>
                        </div>

                        <div className="teacher-info">
                            <div className="t-avatar">{cls.teachers?.[0]?.name?.charAt(0) || 'T'}</div>
                            <div className="t-details">
                                <p>{cls.teachers?.[0]?.name || 'No Teacher Assigned'}</p>
                                <span>Main Instructor</span>
                            </div>
                        </div>

                        <div className="zoom-link-row">
                            <FiLink />
                            <input type="text" readOnly value={cls.zoom_link} />
                            <button onClick={() => window.open(cls.zoom_link, '_blank')}>Join</button>
                        </div>

                        <div className="card-actions">
                            <button className="edit-btn"><FiEdit /> Edit</button>
                            <button className="delete-btn"><FiTrash2 /> Remove</button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    )
}
