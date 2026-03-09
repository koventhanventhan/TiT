import React, { useState, useEffect } from 'react'
import { FiCalendar, FiClock, FiUser, FiVideo } from 'react-icons/fi'
import { getStudentUpcomingSchedules, studentAttend } from '../../services/dashboardService'
import './StudentSections.css'

export default function StudentSchedule() {
    const [classes, setClasses] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                const data = await getStudentUpcomingSchedules()
                const arr = Array.isArray(data) ? data : data.data || []
                // Sort by date ascending
                arr.sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at))
                setClasses(arr)
            } catch (e) {
                console.error('Error loading schedule:', e)
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
            if (link) {
                window.open(link, '_blank')
            }
        } catch (e) {
            console.error('Error joining class:', e)
            const link = cls.join_url || cls.zoom_link;
            if (link) window.open(link, '_blank')
        }
    }

    // Group by date
    const grouped = classes.reduce((acc, cls) => {
        const date = new Date(cls.scheduled_at || cls.start_time).toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        })
        if (!acc[date]) acc[date] = []
        acc[date].push(cls)
        return acc
    }, {})

    return (
        <div className="student-section">
            <div className="section-top">
                <h2>My Schedule</h2>
            </div>

            {loading ? (
                <div className="loading-shimmer">Loading schedule...</div>
            ) : Object.keys(grouped).length === 0 ? (
                <div className="empty-state">
                    <FiCalendar />
                    <p>No classes scheduled.</p>
                </div>
            ) : (
                Object.entries(grouped).map(([date, items]) => (
                    <div key={date} style={{ marginBottom: 28 }}>
                        <h3 style={{ fontSize: '1rem', color: '#2563eb', fontWeight: 600, marginBottom: 12, display: 'flex', alignItems: 'center', gap: 8 }}>
                            <FiCalendar /> {date}
                        </h3>
                        <div className="schedule-grid">
                            {items.map(cls => {
                                const time = new Date(cls.scheduled_at || cls.start_time)
                                return (
                                    <div key={cls.id} className="schedule-item">
                                        <div className="schedule-time">
                                            {time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                                        </div>
                                        <div className="schedule-details">
                                            <h4>{cls.title || cls.subject || 'Class'}</h4>
                                            <span>
                                                {cls.teacher_name && <><FiUser style={{ marginRight: 4 }} />{cls.teacher_name}</>}
                                                {cls.duration && ` • ${cls.duration} min`}
                                            </span>
                                        </div>
                                        {(cls.join_url || cls.zoom_link) && (
                                            <button className="btn-join" onClick={() => handleJoin(cls)}>
                                                <FiVideo style={{ marginRight: 4 }} /> Join
                                            </button>
                                        )}
                                    </div>
                                )
                            })}
                        </div>
                    </div>
                ))
            )}
        </div>
    )
}
