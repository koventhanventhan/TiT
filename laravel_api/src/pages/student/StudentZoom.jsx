import React, { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { FiVideo, FiClock, FiCalendar, FiExternalLink, FiSearch } from 'react-icons/fi'
import { getStudentZoomClasses } from '../../services/dashboardService'
import useJoinClass from '../../hooks/useJoinClass'

const UPCOMING_DAYS = 2

export default function StudentZoom() {
    const [todayList, setTodayList] = useState([])
    const [upcomingList, setUpcomingList] = useState([])
    const [hasAny, setHasAny] = useState(false)
    const [loading, setLoading] = useState(true)
    const [paymentRequired, setPaymentRequired] = useState(false)
    const { joinClass, isJoining } = useJoinClass()

    useEffect(() => {
        async function load() {
            try {
                const response = await getStudentZoomClasses().catch(() => null)
                
                if (response && (response.message === "Complete payment to access classes." || response.has_paid === false)) {
                    setPaymentRequired(true)
                }
                
                const arr = response ? (Array.isArray(response) ? response : response.data || []) : []
                setHasAny(arr.length > 0)

                const now = new Date()
                const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate())
                const windowEnd = new Date(todayStart.getTime() + (UPCOMING_DAYS + 1) * 24 * 60 * 60 * 1000)

                const todayArr = []
                const upcomingArr = []

                arr.forEach(cls => {
                    const time = new Date(cls.scheduled_at || cls.start_time)
                    if (time.getTime() >= todayStart.getTime() && time.getTime() < windowEnd.getTime()) {
                        if (time.getTime() < todayStart.getTime() + 24 * 60 * 60 * 1000) {
                            todayArr.push(cls)
                        } else {
                            upcomingArr.push(cls)
                        }
                    }
                })

                todayArr.sort((a, b) => {
                    const timeA = new Date(a.scheduled_at || a.start_time).getTime()
                    const timeB = new Date(b.scheduled_at || b.start_time).getTime()
                    const endA = timeA + (a.duration || 60) * 60000
                    const endB = timeB + (b.duration || 60) * 60000
                    const nowTime = now.getTime()

                    const isLiveA = timeA <= nowTime && nowTime < endA
                    const isLiveB = timeB <= nowTime && nowTime < endB
                    const isEndedA = endA <= nowTime
                    const isEndedB = endB <= nowTime

                    if (isLiveA && !isLiveB) return -1
                    if (!isLiveA && isLiveB) return 1

                    if (!isEndedA && !isLiveA && isEndedB) return -1
                    if (isEndedA && !isEndedB && !isLiveB) return 1

                    return timeA - timeB
                })

                upcomingArr.sort((a, b) => new Date(a.scheduled_at || a.start_time).getTime() - new Date(b.scheduled_at || b.start_time).getTime())

                setTodayList(todayArr)
                setUpcomingList(upcomingArr)
            } catch (e) {
                console.error(e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    const renderClassCard = (cls) => {
        const time = new Date(cls.scheduled_at || cls.start_time)
        const now = new Date()
        const end = time.getTime() + (cls.duration || 60)*60000
        const isEnded = end <= now.getTime()
        const isLive = time.getTime() <= now.getTime() && !isEnded
        const canJoin = !isEnded && now.getTime() >= (time.getTime() - 10 * 60 * 1000)
        const hasLink = Boolean(cls.zoom_link || cls.join_url)
        const busy = isJoining(cls.id)
        const isDisabled = isEnded || !canJoin || !hasLink || busy

        let buttonText = 'Join Class'
        if (isEnded) {
            buttonText = 'Class ended'
        } else if (!canJoin) {
            buttonText = `Starts at ${time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}`
        } else if (busy) {
            buttonText = 'Joining...'
        } else if (isLive) {
            buttonText = 'Join Class Now'
        }

        return (
            <div key={cls.id} className="t-card" style={{
                background: '#fff', borderRadius: 16, padding: 24,
                border: isLive ? '1px solid var(--t-primary-border)' : '1px solid var(--t-border)',
                boxShadow: isLive ? '0 4px 12px rgba(79, 11, 217, 0.12)' : 'var(--t-shadow-sm)',
                cursor: 'default',
                opacity: isEnded ? 0.6 : 1
            }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 16 }}>
                    <div style={{
                        background: isLive ? 'var(--t-success)' : 'var(--t-bg)',
                        color: isLive ? '#fff' : 'var(--t-text-muted)',
                        padding: '4px 12px', borderRadius: 20, fontSize: 11, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.5px'
                    }}>
                        {isEnded ? 'Ended' : (isLive ? '● Live Now' : cls.subject)}
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 6, color: 'var(--t-primary)', fontSize: 13, fontWeight: 700, background: 'var(--t-primary-soft)', padding: '4px 10px', borderRadius: 8 }}>
                        <FiCalendar /> {time.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
                    </div>
                </div>

                <h3 style={{ margin: '0 0 6px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>{cls.title}</h3>
                <div style={{ color: 'var(--t-text-muted)', fontSize: 14, fontWeight: 500, marginBottom: 20 }}>{cls.teacher || 'Instructor'}</div>

                <div style={{ display: 'flex', gap: 16, marginBottom: 24 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, color: 'var(--t-text-muted)', fontSize: 13, fontWeight: 600 }}>
                        <div style={{ width: 32, height: 32, borderRadius: 8, background: 'var(--t-bg)', border: '1px solid var(--t-border)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--t-text-muted)' }}><FiClock /></div>
                        <div>
                            <div style={{ color: 'var(--t-text)' }}>{time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</div>
                            <div style={{ fontSize: 11, color: 'var(--t-text-muted)' }}>{cls.duration || 60} mins</div>
                        </div>
                    </div>
                </div>

                <button
                    onClick={() => joinClass(cls)}
                    disabled={isDisabled}
                    style={{
                        width: '100%', padding: '12px', borderRadius: 12,
                        background: isLive ? 'var(--t-primary)' : 'var(--t-bg)',
                        color: isLive ? '#fff' : 'var(--t-text-muted)', fontSize: 14, fontWeight: 700,
                        display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                        cursor: isDisabled ? 'not-allowed' : 'pointer',
                        boxShadow: isLive ? '0 4px 12px rgba(79, 11, 217, 0.2)' : 'none',
                        transition: 'opacity 0.2s, transform 0.1s', border: isLive ? 'none' : '1px solid var(--t-border)'
                    }}
                >
                    <FiExternalLink /> {buttonText}
                </button>
            </div>
        )
    }

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24 }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: 'var(--t-text)', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Zoom Classes</h1>
                    <p style={{ color: 'var(--t-text-muted)', margin: 0, fontSize: 14 }}>Join your live online classes</p>
                </div>
            </div>

            {/* Content Area */}
            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid var(--t-border)', borderTopColor: 'var(--t-primary)', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : paymentRequired ? (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid var(--t-border)', padding: '40px 16px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center', boxShadow: 'var(--t-shadow-sm)' }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: 'var(--t-danger-bg)', color: 'var(--t-danger)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>🔒</div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-danger)' }}>Payment Required</h3>
                    <p style={{ margin: '0 0 24px 0', color: 'var(--t-text-muted)', fontSize: 15, maxWidth: 400 }}>Complete this month's payment to unlock your live classes.</p>
                    <Link to="/student" style={{ padding: '12px 24px', background: 'var(--t-primary)', color: '#fff', textDecoration: 'none', borderRadius: 8, fontWeight: 600, boxShadow: '0 4px 12px rgba(79, 11, 217, 0.25)' }}>Go to Payment Section</Link>
                </div>
            ) : todayList.length === 0 && upcomingList.length === 0 ? (
                hasAny ? (
                    <div style={{ background: '#fff', borderRadius: 16, border: '1px dashed var(--t-border)', padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center' }}>
                        <div style={{ width: 64, height: 64, borderRadius: 16, background: 'var(--t-bg)', color: 'var(--t-text-muted)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}><FiVideo /></div>
                        <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>No classes in the next 2 days</h3>
                        <p style={{ margin: '0 0 24px 0', color: 'var(--t-text-muted)', fontSize: 14 }}>There are no live classes scheduled for you right now.</p>
                        <Link to="/student/schedule" style={{ padding: '10px 20px', background: 'var(--t-bg)', color: 'var(--t-text)', textDecoration: 'none', borderRadius: 8, fontWeight: 600, border: '1px solid var(--t-border)' }}>View full schedule</Link>
                    </div>
                ) : (
                    <div style={{ background: '#fff', borderRadius: 16, border: '1px dashed var(--t-border)', padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center' }}>
                        <div style={{ width: 64, height: 64, borderRadius: 16, background: 'var(--t-bg)', color: 'var(--t-text-muted)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}><FiVideo /></div>
                        <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>No upcoming classes</h3>
                        <p style={{ margin: 0, color: 'var(--t-text-muted)', fontSize: 14 }}>There are no live classes scheduled for you right now.</p>
                    </div>
                )
            ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 40 }}>
                    {todayList.length > 0 && (
                        <div>
                            <h2 style={{ fontSize: 18, fontWeight: 700, color: 'var(--t-text)', marginBottom: 16 }}>Today</h2>
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(340px, 100%), 1fr))', gap: 20 }}>
                                {todayList.map(cls => renderClassCard(cls))}
                            </div>
                        </div>
                    )}
                    {upcomingList.length > 0 && (
                        <div>
                            <h2 style={{ fontSize: 18, fontWeight: 700, color: 'var(--t-text)', marginBottom: 16 }}>Coming up (next 2 days)</h2>
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(340px, 100%), 1fr))', gap: 20 }}>
                                {upcomingList.map(cls => renderClassCard(cls))}
                            </div>
                        </div>
                    )}
                </div>
            )}
        </div>
    )
}
