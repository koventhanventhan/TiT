import React, { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { FiVideo, FiClock, FiCalendar, FiExternalLink, FiSearch } from 'react-icons/fi'
import { getStudentZoomClasses, studentAttend } from '../../services/dashboardService'

export default function StudentZoom() {
    const [classes, setClasses] = useState([])
    const [loading, setLoading] = useState(true)
    const [paymentRequired, setPaymentRequired] = useState(false)

    useEffect(() => {
        async function load() {
            try {
                const response = await getStudentZoomClasses().catch(() => null)
                
                if (response && (response.message === "Complete payment to access classes." || response.has_paid === false)) {
                    setPaymentRequired(true)
                }
                
                const arr = response ? (Array.isArray(response) ? response : response.data || []) : []
                arr.sort((a, b) => new Date(a.scheduled_at || a.start_time) - new Date(b.scheduled_at || b.start_time))
                setClasses(arr)
            } catch (e) {
                console.error(e)
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
            if (link) window.open(link, '_blank')
        } catch (e) {
            console.error('Error joining:', e)
            const link = cls.join_url || cls.zoom_link;
            if (link) window.open(link, '_blank')
        }
    }

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24, '@media (minWidth: 640px)': { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' } }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: '#1e293b', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Zoom Classes</h1>
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>Join your live online classes</p>
                </div>
            </div>

            {/* Content Area */}
            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#6366f1', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : paymentRequired ? (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0', padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center', boxShadow: '0 4px 6px -1px rgba(0,0,0,0.05)' }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: '#fee2e2', color: '#ef4444', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>🔒</div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#991b1b' }}>Payment Required</h3>
                    <p style={{ margin: '0 0 24px 0', color: '#64748b', fontSize: 15, maxWidth: 400 }}>Complete this month's payment to unlock your live classes.</p>
                    <Link to="/student" style={{ padding: '12px 24px', background: '#ef4444', color: '#fff', textDecoration: 'none', borderRadius: 8, fontWeight: 600, boxShadow: '0 4px 12px rgba(239,68,68,0.3)' }}>Go to Payment Section</Link>
                </div>
            ) : classes.length === 0 ? (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px dashed #cbd5e1', padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center' }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: '#f1f5f9', color: '#94a3b8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}><FiVideo /></div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#334155' }}>No upcoming classes</h3>
                    <p style={{ margin: 0, color: '#64748b', fontSize: 14 }}>There are no live classes scheduled for you right now.</p>
                </div>
            ) : (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(340px, 1fr))', gap: 20 }}>
                    {classes.map(cls => {
                        const time = new Date(cls.scheduled_at || cls.start_time)
                        const now = new Date()
                        const isLive = time.getTime() <= now.getTime() && time.getTime() + (cls.duration || 60)*60000 > now.getTime()

                        return (
                            <div key={cls.id} style={{
                                background: '#fff', borderRadius: 16, padding: 24,
                                border: isLive ? '1px solid #c7d2fe' : '1px solid #e2e8f0',
                                boxShadow: isLive ? '0 4px 12px rgba(99,102,241,0.15)' : '0 1px 3px rgba(0,0,0,0.02)',
                                transition: 'transform 0.2s, box-shadow 0.2s', cursor: 'default'
                            }}
                            onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-4px)'; e.currentTarget.style.boxShadow = '0 12px 24px rgba(0,0,0,0.08)' }}
                            onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = isLive ? '0 4px 12px rgba(99,102,241,0.15)' : '0 1px 3px rgba(0,0,0,0.02)' }}
                            >
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 16 }}>
                                    <div style={{
                                        background: isLive ? '#ef4444' : '#f1f5f9',
                                        color: isLive ? '#fff' : '#64748b',
                                        padding: '4px 12px', borderRadius: 20, fontSize: 11, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.5px'
                                    }}>
                                        {isLive ? '● Live Now' : cls.subject}
                                    </div>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 6, color: '#6366f1', fontSize: 13, fontWeight: 700, background: '#eef2ff', padding: '4px 10px', borderRadius: 8 }}>
                                        <FiCalendar /> {time.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
                                    </div>
                                </div>

                                <h3 style={{ margin: '0 0 6px 0', fontSize: 18, fontWeight: 700, color: '#1e293b' }}>{cls.title}</h3>
                                <div style={{ color: '#64748b', fontSize: 14, fontWeight: 500, marginBottom: 20 }}>{cls.teacher || 'Instructor'}</div>

                                <div style={{ display: 'flex', gap: 16, marginBottom: 24 }}>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, color: '#475569', fontSize: 13, fontWeight: 600 }}>
                                        <div style={{ width: 32, height: 32, borderRadius: 8, background: '#f8fafc', border: '1px solid #e2e8f0', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#94a3b8' }}><FiClock /></div>
                                        <div>
                                            <div style={{ color: '#1e293b' }}>{time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</div>
                                            <div style={{ fontSize: 11, color: '#94a3b8' }}>{cls.duration || 60} mins</div>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    onClick={() => handleJoin(cls)}
                                    disabled={!cls.zoom_link && !cls.join_url}
                                    style={{
                                        width: '100%', padding: '12px', borderRadius: 12,
                                        background: isLive ? 'linear-gradient(135deg, #6366f1, #8b5cf6)' : '#f8fafc',
                                        color: isLive ? '#fff' : '#475569', fontSize: 14, fontWeight: 700,
                                        display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                                        cursor: (!cls.zoom_link && !cls.join_url) ? 'not-allowed' : 'pointer',
                                        boxShadow: isLive ? '0 4px 12px rgba(99,102,241,0.3)' : 'none',
                                        transition: 'all 0.2s', border: isLive ? 'none' : '1px solid #e2e8f0'
                                    }}
                                >
                                    <FiExternalLink /> {isLive ? 'Join Class Now' : 'Join Link (Available at start time)'}
                                </button>
                            </div>
                        )
                    })}
                </div>
            )}
        </div>
    )
}
