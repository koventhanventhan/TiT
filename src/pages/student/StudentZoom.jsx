import React, { useState, useEffect } from 'react'
import { FiVideo, FiClock, FiUser, FiExternalLink } from 'react-icons/fi'
import { getStudentZoomClasses, studentAttend } from '../../services/dashboardService'
import './StudentSections.css'

export default function StudentZoom() {
    const [classes, setClasses] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                const data = await getStudentZoomClasses()
                setClasses(Array.isArray(data) ? data : data.data || [])
            } catch (e) {
                console.error('Error loading zoom classes:', e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    const handleJoin = async (cls) => {
        try {
            await studentAttend(cls.id)
            const link = cls.join_url || cls.zoom_link;
            console.log('Opening Zoom link:', link);
            if (link) {
                window.open(link, '_blank')
            } else {
                alert('Zoom link not available for this session.')
            }
        } catch (e) {
            console.error('Error joining class:', e)
        }
    }

    const getStatus = (cls) => {
        const now = new Date()
        const start = new Date(cls.scheduled_at || cls.start_time)
        const diffMin = (start - now) / 60000
        if (diffMin < -120) return 'completed'
        if (diffMin <= 15 && diffMin >= -120) return 'live'
        return 'upcoming'
    }

    return (
        <div className="student-section">
            <div className="section-top">
                <h2>Zoom Classes</h2>
            </div>

            {loading ? (
                <div className="loading-shimmer">Loading zoom classes...</div>
            ) : classes.length === 0 ? (
                <div className="empty-state">
                    <FiVideo />
                    <p>No zoom classes scheduled for your grade today.</p>
                </div>
            ) : (
                <div className="cards-grid">
                    {classes.map(cls => {
                        const status = getStatus(cls)
                        const hasLink = !!(cls.join_url || cls.zoom_link);

                        return (
                            <div key={cls.id} className="section-card">
                                <span className={`status-badge ${status}`}>
                                    {status === 'live' ? '● Live Now' : status === 'upcoming' ? '⏰ Upcoming' : '✓ Completed'}
                                </span>
                                <h3>{cls.subject || cls.title || 'Zoom Class'}</h3>
                                <p>{cls.description || `Grade ${cls.grade} Class`}</p>
                                <div className="card-footer">
                                    <div className="meta-item">
                                        <FiClock />
                                        <span>{new Date(cls.scheduled_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                                    </div>
                                    {cls.teacher_name && (
                                        <div className="meta-item">
                                            <FiUser />
                                            <span>{cls.teacher_name}</span>
                                        </div>
                                    )}
                                </div>
                                {status !== 'completed' && (
                                    <button
                                        className={`btn-join ${status === 'live' && hasLink ? '' : 'disabled'}`}
                                        onClick={() => handleJoin(cls)}
                                        disabled={status !== 'live' || !hasLink}
                                        style={{ marginTop: 12, width: '100%' }}
                                    >
                                        <FiExternalLink style={{ marginRight: 6 }} />
                                        {status === 'live' ? (hasLink ? 'Join Now' : 'Link Generating...') : 'Not Started Yet'}
                                    </button>
                                )}
                            </div>
                        )
                    })}
                </div>
            )}
        </div>
    )
}
