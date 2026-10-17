import React, { useState, useEffect } from 'react'
import { FiCalendar, FiClock, FiVideo, FiMapPin, FiExternalLink } from 'react-icons/fi'
import { getStudentUpcomingSchedules } from '../../services/dashboardService'
import useJoinClass from '../../hooks/useJoinClass'
import { useMediaQuery, PHONE_MEDIA_QUERY } from '../../hooks/useMediaQuery'

export default function StudentSchedule() {
    const [schedules, setSchedules] = useState([])
    const [loading, setLoading] = useState(true)
    const { joinClass, isJoining } = useJoinClass()
    const isPhone = useMediaQuery(PHONE_MEDIA_QUERY)

    useEffect(() => {
        async function load() {
            try {
                let data = await getStudentUpcomingSchedules().catch(() => null)
                if (!data || data.length === 0) {
                    data = []
                }
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

    const formatTimeRange = (startTime, durationMinutes = 60) => {
        const start = new Date(startTime)
        const end = new Date(start.getTime() + durationMinutes * 60000)
        const startStr = start.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
        const endStr = end.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
        return `${startStr} – ${endStr}`
    }

    const grouped = schedules.reduce((acc, cls) => {
        const date = new Date(cls.scheduled_at || cls.start_time).toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        })
        if (!acc[date]) acc[date] = []
        acc[date].push(cls)
        return acc
    }, {})

    return (
        <div style={{ paddingBottom: 80 }}>
            {/* Header Section */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24
            }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: 'var(--t-text)', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>My Schedule</h1>
                    <p style={{ color: 'var(--t-text-muted)', margin: 0, fontSize: 14 }}>View your upcoming physical and online classes</p>
                </div>
            </div>

            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid var(--t-border)', borderTopColor: 'var(--t-primary)', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : Object.keys(grouped).length === 0 ? (
                <div style={{
                    background: '#fff', borderRadius: 16, border: '1px dashed var(--t-border)',
                    padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center'
                }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: 'var(--t-bg)', color: 'var(--t-text-muted)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>
                        <FiCalendar />
                    </div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>No upcoming classes</h3>
                    <p style={{ margin: '0 0 20px 0', color: 'var(--t-text-muted)', fontSize: 14 }}>You don't have any classes scheduled in the near future.</p>
                </div>
            ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 32 }}>
                    {Object.entries(grouped).map(([date, items]) => (
                        <div key={date}>
                            <h3 style={{
                                fontSize: 16, color: 'var(--t-text)', fontWeight: 800, marginBottom: 16,
                                display: 'flex', alignItems: 'center', gap: 10
                            }}>
                                <span style={{ width: 32, height: 32, borderRadius: 8, background: 'var(--t-primary-soft)', color: 'var(--t-primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 16 }}>
                                    <FiCalendar />
                                </span>
                                {date}
                            </h3>
                            
                            <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                                {items.map(cls => {
                                    const time = new Date(cls.scheduled_at || cls.start_time)
                                    const now = new Date()
                                    const duration = cls.duration || 60
                                    const end = time.getTime() + duration * 60000
                                    const isEnded = end <= now.getTime()
                                    const isLive = time.getTime() <= now.getTime() && !isEnded
                                    const canJoin = !isEnded && now.getTime() >= (time.getTime() - 10 * 60 * 1000)
                                    const isOnline = cls.type === 'online' || Boolean(cls.zoom_link || cls.join_url)
                                    const hasLink = Boolean(cls.zoom_link || cls.join_url)
                                    const busy = isJoining(cls.id)
                                    const startTimeStr = time.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })

                                    if (isPhone) {
                                        return (
                                            <div
                                                key={cls.id}
                                                className="schedule-card t-card"
                                                style={{
                                                    background: '#fff',
                                                    borderRadius: 16,
                                                    border: isLive ? '1px solid var(--t-primary-border, rgba(79, 11, 217, 0.3))' : '1px solid var(--t-border)',
                                                    boxShadow: isLive ? '0 4px 12px rgba(79, 11, 217, 0.12)' : 'var(--t-shadow-sm)',
                                                    padding: 16,
                                                    display: 'flex',
                                                    flexDirection: 'column',
                                                    gap: 12,
                                                    opacity: isEnded ? 0.75 : 1
                                                }}
                                            >
                                                {/* Top row: Subject & title (left) + status badge (right) */}
                                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: 12 }}>
                                                    <div style={{ minWidth: 0, flex: 1 }}>
                                                        <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginBottom: 4 }}>
                                                            <span style={{
                                                                padding: '2px 8px',
                                                                borderRadius: 6,
                                                                background: 'var(--t-bg)',
                                                                color: 'var(--t-text-muted)',
                                                                fontSize: 11,
                                                                fontWeight: 700,
                                                                textTransform: 'uppercase'
                                                            }}>
                                                                {cls.subject || 'Subject'}
                                                            </span>
                                                            {cls.grade && (
                                                                <span style={{
                                                                    padding: '2px 8px',
                                                                    borderRadius: 6,
                                                                    background: 'var(--t-bg)',
                                                                    color: 'var(--t-text-muted)',
                                                                    fontSize: 11,
                                                                    fontWeight: 600
                                                                }}>
                                                                    Grade {cls.grade}
                                                                </span>
                                                            )}
                                                        </div>
                                                        <h4 style={{
                                                            margin: 0,
                                                            fontSize: 16,
                                                            fontWeight: 700,
                                                            color: 'var(--t-text)',
                                                            lineHeight: 1.3,
                                                            display: '-webkit-box',
                                                            WebkitLineClamp: 2,
                                                            WebkitBoxOrient: 'vertical',
                                                            overflow: 'hidden'
                                                        }}>
                                                            {cls.title || 'Class Session'}
                                                        </h4>
                                                    </div>

                                                    {/* Status badge at right, never wraps */}
                                                    <span style={{
                                                        flexShrink: 0,
                                                        whiteSpace: 'nowrap',
                                                        padding: '4px 10px',
                                                        borderRadius: 999,
                                                        fontSize: 11,
                                                        fontWeight: 800,
                                                        textTransform: 'uppercase',
                                                        letterSpacing: '0.04em',
                                                        background: isLive
                                                            ? 'var(--t-success, #15803d)'
                                                            : isEnded
                                                                ? 'var(--t-bg)'
                                                                : 'var(--t-primary-soft, rgba(79, 11, 217, 0.08))',
                                                        color: isLive
                                                            ? '#ffffff'
                                                            : isEnded
                                                                ? 'var(--t-text-muted)'
                                                                : 'var(--t-primary, #4f0bd9)',
                                                        border: isEnded ? '1px solid var(--t-border)' : 'none'
                                                    }}>
                                                        {isLive ? 'LIVE NOW' : isEnded ? 'ENDED' : 'UPCOMING'}
                                                    </span>
                                                </div>

                                                {/* Second row: clock icon + time range on ONE line + teacher/mode meta below */}
                                                <div>
                                                    <div
                                                        className="schedule-time-text"
                                                        style={{
                                                            display: 'inline-flex',
                                                            alignItems: 'center',
                                                            gap: 6,
                                                            whiteSpace: 'nowrap',
                                                            fontVariantNumeric: 'tabular-nums',
                                                            fontSize: 14,
                                                            fontWeight: 700,
                                                            color: 'var(--t-text)',
                                                            lineHeight: 1.2
                                                        }}
                                                    >
                                                        <FiClock style={{ flexShrink: 0, color: 'var(--t-primary, #4f0bd9)' }} />
                                                        <span style={{ whiteSpace: 'nowrap' }}>{formatTimeRange(time, duration)}</span>
                                                    </div>

                                                    <div style={{
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        gap: 10,
                                                        marginTop: 6,
                                                        flexWrap: 'wrap',
                                                        fontSize: 13,
                                                        color: 'var(--t-text-muted)'
                                                    }}>
                                                        {cls.teacher && <span style={{ fontWeight: 500 }}>{cls.teacher}</span>}
                                                        <span style={{
                                                            display: 'inline-flex',
                                                            alignItems: 'center',
                                                            gap: 4,
                                                            padding: '2px 8px',
                                                            borderRadius: 6,
                                                            background: isOnline ? 'var(--t-primary-soft, rgba(79, 11, 217, 0.08))' : 'var(--t-success-bg, #f0fdf4)',
                                                            color: isOnline ? 'var(--t-primary, #4f0bd9)' : 'var(--t-success, #15803d)',
                                                            fontSize: 11,
                                                            fontWeight: 700,
                                                            textTransform: 'uppercase'
                                                        }}>
                                                            {isOnline ? <FiVideo /> : <FiMapPin />} {isOnline ? 'Online' : 'Physical'}
                                                        </span>
                                                        {!isOnline && cls.location && <span>• {cls.location}</span>}
                                                    </div>
                                                </div>

                                                {/* Bottom: full-width Join class button (>=48px) when live/joinable, disabled state with reason otherwise */}
                                                {isOnline && (
                                                    <div style={{ marginTop: 4 }}>
                                                        {isEnded ? (
                                                            <button
                                                                disabled
                                                                style={{
                                                                    width: '100%',
                                                                    minHeight: 48,
                                                                    height: 48,
                                                                    borderRadius: 10,
                                                                    background: 'var(--t-bg)',
                                                                    border: '1px solid var(--t-border)',
                                                                    color: 'var(--t-text-muted)',
                                                                    fontSize: 14,
                                                                    fontWeight: 600,
                                                                    cursor: 'not-allowed',
                                                                    display: 'flex',
                                                                    alignItems: 'center',
                                                                    justifyContent: 'center',
                                                                    boxSizing: 'border-box'
                                                                }}
                                                            >
                                                                Class Ended
                                                            </button>
                                                        ) : canJoin ? (
                                                            <button
                                                                onClick={() => joinClass(cls)}
                                                                disabled={busy || !hasLink}
                                                                style={{
                                                                    width: '100%',
                                                                    minHeight: 48,
                                                                    height: 48,
                                                                    borderRadius: 10,
                                                                    background: 'var(--t-primary, #4f0bd9)',
                                                                    color: '#fff',
                                                                    border: 'none',
                                                                    fontSize: 14,
                                                                    fontWeight: 700,
                                                                    cursor: (busy || !hasLink) ? 'not-allowed' : 'pointer',
                                                                    display: 'flex',
                                                                    alignItems: 'center',
                                                                    justifyContent: 'center',
                                                                    gap: 8,
                                                                    boxShadow: '0 4px 12px rgba(79, 11, 217, 0.2)',
                                                                    opacity: busy ? 0.7 : 1,
                                                                    boxSizing: 'border-box'
                                                                }}
                                                            >
                                                                <FiExternalLink /> {busy ? 'Joining...' : (isLive ? 'Join Live Class' : 'Join Class')}
                                                            </button>
                                                        ) : (
                                                            <button
                                                                disabled
                                                                style={{
                                                                    width: '100%',
                                                                    minHeight: 48,
                                                                    height: 48,
                                                                    borderRadius: 10,
                                                                    background: 'var(--t-bg)',
                                                                    border: '1px solid var(--t-border)',
                                                                    color: 'var(--t-text-muted)',
                                                                    fontSize: 13,
                                                                    fontWeight: 600,
                                                                    cursor: 'not-allowed',
                                                                    display: 'flex',
                                                                    alignItems: 'center',
                                                                    justifyContent: 'center',
                                                                    boxSizing: 'border-box'
                                                                }}
                                                            >
                                                                Starts at {startTimeStr}
                                                            </button>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        )
                                    }

                                    // Desktop / Tablet layout (>=640px)
                                    return (
                                        <div key={cls.id} className="schedule-card t-card" style={{
                                            display: 'flex', alignItems: 'stretch',
                                            background: '#fff', borderRadius: 16,
                                            border: isLive ? '1px solid var(--t-primary-border, rgba(79, 11, 217, 0.3))' : '1px solid var(--t-border)',
                                            boxShadow: isLive ? '0 4px 12px rgba(79, 11, 217, 0.1)' : 'var(--t-shadow-sm)',
                                            overflow: 'hidden',
                                            opacity: isEnded ? 0.65 : 1
                                        }}>
                                            {/* Side Date Chip */}
                                            <div style={{
                                                minWidth: 80, padding: '16px 12px',
                                                background: isLive ? 'var(--t-primary, #4f0bd9)' : 'var(--t-bg)',
                                                borderRight: '1px solid var(--t-border)',
                                                display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
                                                color: isLive ? '#fff' : 'var(--t-text)',
                                                flexShrink: 0
                                            }}>
                                                <span style={{ fontSize: 11, fontWeight: 700, textTransform: 'uppercase', opacity: 0.85 }}>
                                                    {time.toLocaleDateString('en-US', { weekday: 'short' })}
                                                </span>
                                                <span style={{ fontSize: 22, fontWeight: 800, lineHeight: 1.1 }}>
                                                    {time.getDate()}
                                                </span>
                                                {isLive && (
                                                    <span style={{ padding: '2px 6px', borderRadius: 10, background: 'var(--t-success, #15803d)', color: '#fff', fontSize: 9, fontWeight: 800, textTransform: 'uppercase', marginTop: 6 }}>
                                                        Live
                                                    </span>
                                                )}
                                            </div>

                                            {/* Info Block */}
                                            <div style={{ flex: 1, padding: 20, display: 'flex', flexDirection: 'column', justifyContent: 'center', minWidth: 200 }}>
                                                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 6 }}>
                                                    <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                                        <span style={{ padding: '2px 8px', borderRadius: 6, background: 'var(--t-bg)', color: 'var(--t-text-muted)', fontSize: 11, fontWeight: 700, textTransform: 'uppercase' }}>{cls.subject || 'Subject'}</span>
                                                        <span style={{ padding: '2px 8px', borderRadius: 6, background: isOnline ? 'var(--t-primary-soft)' : 'var(--t-success-bg)', color: isOnline ? 'var(--t-primary)' : 'var(--t-success)', fontSize: 11, fontWeight: 700, textTransform: 'uppercase', display: 'flex', alignItems: 'center', gap: 4 }}>
                                                            {isOnline ? <FiVideo /> : <FiMapPin />} {isOnline ? 'Online' : 'Physical'}
                                                        </span>
                                                    </div>
                                                    <span style={{
                                                        padding: '3px 8px', borderRadius: 999, fontSize: 11, fontWeight: 700, textTransform: 'uppercase',
                                                        background: isLive ? 'var(--t-success)' : isEnded ? 'var(--t-bg)' : 'var(--t-primary-soft)',
                                                        color: isLive ? '#fff' : isEnded ? 'var(--t-text-muted)' : 'var(--t-primary)'
                                                    }}>
                                                        {isLive ? 'LIVE NOW' : isEnded ? 'ENDED' : 'UPCOMING'}
                                                    </span>
                                                </div>
                                                <h4 style={{ margin: '0 0 6px 0', fontSize: 18, color: 'var(--t-text)', fontWeight: 700 }}>
                                                    {cls.title || 'Class Session'}
                                                </h4>
                                                <div style={{ color: 'var(--t-text-muted)', fontSize: 13, fontWeight: 500, marginBottom: 8 }}>{cls.teacher}</div>
                                                
                                                <div style={{ display: 'flex', gap: 16, alignItems: 'center', color: 'var(--t-text-muted)', fontSize: 13, fontWeight: 500, flexWrap: 'wrap' }}>
                                                    <div
                                                        className="schedule-time-text"
                                                        style={{
                                                            display: 'inline-flex',
                                                            alignItems: 'center',
                                                            gap: 6,
                                                            whiteSpace: 'nowrap',
                                                            fontVariantNumeric: 'tabular-nums',
                                                            fontSize: 13,
                                                            fontWeight: 700,
                                                            color: 'var(--t-text)'
                                                        }}
                                                    >
                                                        <FiClock style={{ color: 'var(--t-primary)' }} />
                                                        <span>{formatTimeRange(time, duration)}</span>
                                                    </div>
                                                    <span>• {duration} min duration</span>
                                                    {!isOnline && cls.location && <span>• {cls.location}</span>}
                                                </div>
                                            </div>

                                            {/* Action Block */}
                                            {isOnline && (
                                                <div style={{ padding: '16px 20px', display: 'flex', alignItems: 'center', justifyContent: 'flex-end', borderLeft: '1px solid var(--t-border)', flexShrink: 0 }}>
                                                    {isEnded ? (
                                                        <span style={{ padding: '6px 14px', borderRadius: 8, background: 'var(--t-bg)', color: 'var(--t-text-muted)', fontSize: 12, fontWeight: 700 }}>
                                                            Ended
                                                        </span>
                                                    ) : canJoin ? (
                                                        <button
                                                            onClick={() => joinClass(cls)}
                                                            disabled={busy || !hasLink}
                                                            style={{
                                                                minHeight: 44,
                                                                padding: '10px 18px', borderRadius: 10,
                                                                background: 'var(--t-primary)',
                                                                color: '#fff',
                                                                border: 'none',
                                                                fontWeight: 700, fontSize: 13,
                                                                cursor: (busy || !hasLink) ? 'not-allowed' : 'pointer',
                                                                display: 'flex', alignItems: 'center', gap: 6,
                                                                boxShadow: '0 4px 12px rgba(79, 11, 217, 0.2)',
                                                                opacity: busy ? 0.7 : 1
                                                            }}
                                                        >
                                                            <FiExternalLink /> {busy ? 'Joining...' : (isLive ? 'Join Live Class' : 'Join Class')}
                                                        </button>
                                                    ) : (
                                                        <span style={{ padding: '6px 12px', borderRadius: 8, background: 'var(--t-bg)', border: '1px solid var(--t-border)', color: 'var(--t-text-muted)', fontSize: 12, fontWeight: 600 }}>
                                                            Starts at {startTimeStr}
                                                        </span>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    )
                                })}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    )
}

