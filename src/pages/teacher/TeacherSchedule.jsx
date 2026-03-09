import React, { useState, useEffect } from 'react'
import { FiCalendar, FiClock, FiUsers, FiVideo } from 'react-icons/fi'
import { getTeacherUpcomingSchedules, teacherAttend } from '../../services/dashboardService'
import './TeacherSections.css'

export default function TeacherSchedule() {
    const [schedules, setSchedules] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                const data = await getTeacherUpcomingSchedules()
                const arr = Array.isArray(data) ? data : data.data || []
                arr.sort((a, b) => new Date(a.scheduled_at || a.start_time) - new Date(b.scheduled_at || b.start_time))
                setSchedules(arr)
            } catch (e) {
                console.error('Error loading schedule:', e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    // Group by date
    const grouped = schedules.reduce((acc, cls) => {
        const date = new Date(cls.scheduled_at || cls.start_time).toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        })
        if (!acc[date]) acc[date] = []
        acc[date].push(cls)
        return acc
    }, {})

    const handleJoin = async (cls) => {
        try {
            await teacherAttend(cls.id)
            const link = cls.start_url || cls.zoom_link;
            if (link) {
                window.open(link, '_blank')
            }
        } catch (e) {
            console.error('Error joining class:', e)
            // Still try to open the link if attendance fails
            const link = cls.start_url || cls.zoom_link;
            if (link) {
                window.open(link, '_blank')
            }
        }
    }

    return (
        <div className="teacher-section">
            <div className="section-top">
                <h2>My Schedule</h2>
            </div>

            {loading ? (
                <div className="loading-shimmer">Loading schedule...</div>
            ) : Object.keys(grouped).length === 0 ? (
                <div className="empty-state-large">
                    <FiCalendar />
                    <p>No upcoming classes scheduled.</p>
                </div>
            ) : (
                Object.entries(grouped).map(([date, items]) => (
                    <div key={date} style={{ marginBottom: 28 }}>
                        <h3 style={{ fontSize: '1rem', color: '#2563eb', fontWeight: 600, marginBottom: 12, display: 'flex', alignItems: 'center', gap: 8 }}>
                            <FiCalendar /> {date}
                        </h3>
                        <div style={{ display: 'grid', gap: 12 }}>
                            {items.map(cls => {
                                const time = new Date(cls.scheduled_at || cls.start_time)
                                return (
                                    <div key={cls.id} style={{
                                        display: 'flex', alignItems: 'center', gap: 16,
                                        background: '#fff', borderRadius: 12, padding: '16px 20px',
                                        border: '1px solid #e2e8f0'
                                    }}>
                                        <div style={{
                                            minWidth: 80, textAlign: 'center', padding: '8px 12px',
                                            background: '#ede9fe', color: '#2563eb', borderRadius: 8,
                                            fontWeight: 700, fontSize: '0.85rem'
                                        }}>
                                            {time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                                        </div>
                                        <div style={{ flex: 1 }}>
                                            <h4 style={{ margin: '0 0 4px 0', fontSize: '1rem', color: '#1e293b' }}>
                                                {cls.title || cls.subject || 'Class'}
                                            </h4>
                                            <span style={{ color: '#64748b', fontSize: '0.85rem' }}>
                                                {cls.student_count && <><FiUsers style={{ marginRight: 4 }} />{cls.student_count} students</>}
                                                {cls.duration && ` • ${cls.duration} min`}
                                            </span>
                                        </div>
                                        {cls.zoom_link && (
                                            <button
                                                className="btn-primary"
                                                onClick={() => handleJoin(cls)}
                                                style={{ padding: '8px 16px' }}
                                            >
                                                <FiVideo /> Start
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
