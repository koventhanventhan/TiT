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
import { getAdminZoom, bulkDeleteAdminZoomClasses } from '../../services/dashboardService'
import { BASE_URL } from '../../services/authService'
import './AdminZoom.css'

export default function AdminZoom() {
    const [classes, setClasses] = useState([])
    const [loading, setLoading] = useState(true)
    const [selectedClasses, setSelectedClasses] = useState([])
    const [isDeleting, setIsDeleting] = useState(false)

    const handleSelectAll = (e) => {
        if (e.target.checked) setSelectedClasses(classes.map(c => c.id));
        else setSelectedClasses([]);
    };

    const handleSelect = (id) => {
        if (selectedClasses.includes(id)) setSelectedClasses(selectedClasses.filter(cid => cid !== id));
        else setSelectedClasses([...selectedClasses, id]);
    };

    const handleDeleteSelected = async () => {
        if (!window.confirm(`Are you sure you want to delete ${selectedClasses.length} zoom classes?`)) return
        setIsDeleting(true)
        try {
            await bulkDeleteAdminZoomClasses(selectedClasses)
            setClasses(classes.filter(c => !selectedClasses.includes(c.id)))
            setSelectedClasses([])
        } catch (error) {
            console.error('Failed to delete zoom classes:', error)
            alert('Error deleting zoom classes')
        } finally {
            setIsDeleting(false)
        }
    }

    const handleNewSession = () => {
        window.location.href = `${BASE_URL}/admin/timetables`
    }

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
                <div className="header-actions" style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
                    {selectedClasses.length > 0 && (
                        <button className="delete-btn" onClick={handleDeleteSelected} disabled={isDeleting} style={{ backgroundColor: '#ef4444', color: 'white', padding: '0.5rem 1rem', borderRadius: '4px', border: 'none', cursor: 'pointer' }}>
                            <FiTrash2 /> Delete Selected ({selectedClasses.length})
                        </button>
                    )}
                    <label style={{ display: 'flex', alignItems: 'center', gap: '5px', cursor: 'pointer', background: 'white', padding: '0.5rem 1rem', borderRadius: '4px', border: '1px solid #e2e8f0' }}>
                        <input type="checkbox" onChange={handleSelectAll} checked={classes.length > 0 && selectedClasses.length === classes.length} />
                        Select All
                    </label>
                    <button className="add-btn" onClick={handleNewSession}><FiPlus /> Schedule New Session</button>
                </div>
            </div>

            <div className="zoom-schedules-grid">
                {classes.map((cls) => (
                    <div key={cls.id} className="zoom-class-card" style={selectedClasses.includes(cls.id) ? { border: '2px solid #3b82f6', position: 'relative' } : { position: 'relative' }}>
                        <input
                            type="checkbox"
                            onChange={() => handleSelect(cls.id)}
                            checked={selectedClasses.includes(cls.id)}
                            style={{ position: 'absolute', top: '10px', right: '10px', zIndex: 10, cursor: 'pointer', transform: 'scale(1.2)' }}
                        />
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

                        {(cls.meeting_id || cls.password) && (
                            <div className="meeting-creds">
                                {cls.meeting_id && <span>ID: {cls.meeting_id}</span>}
                                {cls.password && <span>Pass: {cls.password}</span>}
                            </div>
                        )}

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
