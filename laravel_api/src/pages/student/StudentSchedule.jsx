import React, { useState, useEffect } from 'react'
import { FiCalendar, FiClock, FiVideo, FiMapPin, FiExternalLink } from 'react-icons/fi'
import { getStudentUpcomingSchedules } from '../../services/dashboardService'
import useJoinClass from '../../hooks/useJoinClass'

export default function StudentSchedule() {
    const [schedules, setSchedules] = useState([])
    const [loading, setLoading] = useState(true)
    const { joinClass, isJoining } = useJoinClass()

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

    const grouped = schedules.reduce((acc, cls) => {
        const date = new Date(cls.scheduled_at || cls.start_time).toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        })
        if (!acc[date]) acc[date] = []
        acc[date].push(cls)
        return acc
    }, {})

    return (
        <div style={{ paddingBottom: 40 }}>
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
                <div className="schedule schedule-container" style={{ display: 'flex', flexDirection: 'column', gap: 32 }}>
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
                            
                            <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
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

                                    const startStr = time.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
                                    const endStr = new Date(end).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
                                    const timeRange = `${startStr} – ${endStr}`
                                    const weekday = time.toLocaleDateString('en-US', { weekday: 'short' })
                                    const dayNum = time.getDate()

                                    return (
                                        <div
                                            key={cls.id}
                                            className={`schedule-card t-card ${isLive ? 'is-live' : ''} ${isEnded ? 'is-ended' : ''}`}
                                        >
                                            {/* Desktop Side Date Chip (>=640px) */}
                                            <div className="schedule-side-chip">
                                                <span style={{ fontSize: 11, fontWeight: 700, textTransform: 'uppercase', opacity: 0.85 }}>{weekday}</span>
                                                <span style={{ fontSize: 20, fontWeight: 800, lineHeight: 1.1 }}>{dayNum}</span>
                                            </div>

                                            {/* Main Card Content */}
                                            <div className="schedule-content-wrap" style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                                                {/* Top row: Subject & Title + Status Badge */}
                                                <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 10 }}>
                                                    <div style={{ flex: 1, minWidth: 0 }}>
                                                        <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginBottom: 4, flexWrap: 'wrap' }}>
                                                            <span style={{ padding: '2px 8px', borderRadius: 6, background: 'var(--t-bg)', color: 'var(--t-text-muted)', fontSize: 11, fontWeight: 700, textTransform: 'uppercase', whiteSpace: 'nowrap' }}>
                                                                {cls.subject || 'Subject'}
                                                            </span>
                                                            <span style={{
                                                                padding: '2px 8px', borderRadius: 6,
                                                                background: isOnline ? 'var(--t-primary-soft)' : 'var(--t-success-bg)',
                                                                color: isOnline ? 'var(--t-primary)' : 'var(--t-success)',
                                                                fontSize: 11, fontWeight: 700, textTransform: 'uppercase',
                                                                display: 'inline-flex', alignItems: 'center', gap: 4, whiteSpace: 'nowrap'
                                                            }}>
                                                                {isOnline ? <FiVideo /> : <FiMapPin />} {isOnline ? 'Online' : 'Physical'}
                                                            </span>
                                                        </div>
                                                        <h4 style={{
                                                            margin: 0, fontSize: 16, fontWeight: 700, color: 'var(--t-text)',
                                                            lineHeight: 1.3, display: '-webkit-box', WebkitLineClamp: 2,
                                                            WebkitBoxOrient: 'vertical', overflow: 'hidden'
                                                        }}>
                                                            {cls.title || 'Class Session'}
                                                        </h4>
                                                    </div>

                                                    {/* Status Badge (Never wraps) */}
                                                    <div style={{ flexShrink: 0, whiteSpace: 'nowrap' }}>
                                                        {isEnded ? (
                                                            <span
                                                                className="schedule-status-badge"
                                                                style={{
                                                                    padding: '4px 10px', borderRadius: 8,
                                                                    background: 'var(--t-bg)', color: 'var(--t-text-muted)',
                                                                    fontSize: 11, fontWeight: 700, textTransform: 'uppercase',
                                                                    whiteSpace: 'nowrap', display: 'inline-block'
                                                                }}
                                                            >
                                                                ENDED
                                                            </span>
                                                        ) : isLive ? (
                                                            <span
                                                                className="schedule-status-badge"
                                                                style={{
                                                                    padding: '4px 10px', borderRadius: 8,
                                                                    background: 'var(--t-danger, #ef4444)', color: '#ffffff',
                                                                    fontSize: 11, fontWeight: 800, textTransform: 'uppercase',
                                                                    letterSpacing: '0.5px', whiteSpace: 'nowrap',
                                                                    display: 'inline-flex', alignItems: 'center', gap: 5
                                                                }}
                                                            >
                                                                <span style={{ width: 6, height: 6, borderRadius: '50%', background: '#fff' }} />
                                                                LIVE NOW
                                                            </span>
                                                        ) : (
                                                            <span
                                                                className="schedule-status-badge"
                                                                style={{
                                                                    padding: '4px 10px', borderRadius: 8,
                                                                    background: 'var(--t-primary-soft)', color: 'var(--t-primary)',
                                                                    fontSize: 11, fontWeight: 700, textTransform: 'uppercase',
                                                                    whiteSpace: 'nowrap', display: 'inline-block'
                                                                }}
                                                            >
                                                                UPCOMING
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>

                                                {/* Second row: Time range on ONE line + Teacher/Mode Meta */}
                                                <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                                                    <div style={{ display: 'flex', alignItems: 'center', gap: 6, color: 'var(--t-text)', fontSize: 14, fontWeight: 600 }}>
                                                        <FiClock style={{ color: 'var(--t-primary)', flexShrink: 0 }} />
                                                        <time
                                                            className="schedule-time-text"
                                                            style={{
                                                                whiteSpace: 'nowrap',
                                                                fontVariantNumeric: 'tabular-nums',
                                                                lineHeight: 1.2
                                                            }}
                                                        >
                                                            {timeRange}
                                                        </time>
                                                        <span style={{ color: 'var(--t-text-muted)', fontSize: 12, fontWeight: 500, whiteSpace: 'nowrap' }}>
                                                            ({duration} mins)
                                                        </span>
                                                    </div>

                                                    <div style={{ display: 'flex', alignItems: 'center', gap: 10, color: 'var(--t-text-muted)', fontSize: 13, flexWrap: 'wrap' }}>
                                                        {cls.teacher && <span>Instructor: <strong style={{ color: 'var(--t-text)' }}>{cls.teacher}</strong></span>}
                                                        {!isOnline && cls.location && (
                                                            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4 }}>
                                                                <FiMapPin /> {cls.location}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Action Block / Button (Full-width on mobile, >=48px) */}
                                            <div className="schedule-action-wrap">
                                                {isEnded ? (
                                                    <button
                                                        type="button"
                                                        disabled
                                                        style={{
                                                            width: '100%', minHeight: 48, borderRadius: 10,
                                                            background: 'var(--t-bg)', border: '1px solid var(--t-border)',
                                                            color: 'var(--t-text-muted)', fontWeight: 600, fontSize: 13,
                                                            cursor: 'not-allowed', display: 'flex', alignItems: 'center',
                                                            justifyContent: 'center'
                                                        }}
                                                    >
                                                        Class Ended
                                                    </button>
                                                ) : isOnline ? (
                                                    canJoin ? (
                                                        <button
                                                            type="button"
                                                            className="schedule-join-btn"
                                                            onClick={() => joinClass(cls)}
                                                            disabled={busy || !hasLink}
                                                            style={{
                                                                width: '100%', minHeight: 48, borderRadius: 10,
                                                                background: isLive ? 'var(--t-primary)' : 'var(--t-primary)',
                                                                color: '#ffffff', border: 'none', fontWeight: 700, fontSize: 14,
                                                                cursor: (busy || !hasLink) ? 'not-allowed' : 'pointer',
                                                                display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                                                                boxShadow: isLive ? '0 4px 14px rgba(79, 11, 217, 0.25)' : 'none',
                                                                opacity: busy ? 0.7 : 1, transition: 'all 0.15s ease'
                                                            }}
                                                        >
                                                            <FiExternalLink /> {busy ? 'Joining Class...' : (isLive ? 'Join Live Class' : 'Join Class')}
                                                        </button>
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            disabled
                                                            style={{
                                                                width: '100%', minHeight: 48, borderRadius: 10,
                                                                background: 'var(--t-bg)', border: '1px solid var(--t-border)',
                                                                color: 'var(--t-text-muted)', fontWeight: 600, fontSize: 13,
                                                                cursor: 'not-allowed', display: 'flex', alignItems: 'center',
                                                                justifyContent: 'center'
                                                            }}
                                                        >
                                                            Starts at {startStr}
                                                        </button>
                                                    )
                                                ) : (
                                                    <div style={{
                                                        minHeight: 48, borderRadius: 10, background: 'var(--t-bg)',
                                                        border: '1px solid var(--t-border)', display: 'flex',
                                                        alignItems: 'center', justifyContent: 'center',
                                                        color: 'var(--t-text-muted)', fontSize: 13, fontWeight: 600,
                                                        padding: '0 12px', textAlign: 'center'
                                                    }}>
                                                        Physical Class
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )
                                })}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <style>{`
                .schedule-card {
                    display: flex;
                    flex-direction: column;
                    gap: 14px;
                    background: #ffffff;
                    border-radius: 16px;
                    padding: 16px;
                    border: 1px solid var(--t-border);
                    box-shadow: var(--t-shadow-sm);
                    box-sizing: border-box;
                    width: 100%;
                    overflow: hidden;
                    transition: border-color 0.2s, box-shadow 0.2s;
                }
                .schedule-card.is-live {
                    border-color: var(--t-primary-border);
                    box-shadow: 0 4px 16px rgba(79, 11, 217, 0.12);
                }
                .schedule-card.is-ended {
                    opacity: 0.65;
                }
                .schedule-side-chip {
                    display: none;
                }
                .schedule-action-wrap {
                    width: 100%;
                    margin-top: 4px;
                }
                @media (min-width: 640px) {
                    .schedule-card {
                        flex-direction: row;
                        align-items: center;
                        gap: 16px;
                        padding: 16px 20px;
                    }
                    .schedule-side-chip {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        min-width: 56px;
                        padding: 10px 8px;
                        border-radius: 12px;
                        background: var(--t-bg);
                        border: 1px solid var(--t-border);
                        color: var(--t-text);
                        flex-shrink: 0;
                    }
                    .schedule-card.is-live .schedule-side-chip {
                        background: var(--t-primary);
                        color: #ffffff;
                        border-color: var(--t-primary);
                    }
                    .schedule-content-wrap {
                        flex: 1;
                        min-width: 0;
                    }
                    .schedule-action-wrap {
                        width: auto;
                        min-width: 160px;
                        margin-top: 0;
                        flex-shrink: 0;
                    }
                }
            `}</style>
        </div>
    )
}

