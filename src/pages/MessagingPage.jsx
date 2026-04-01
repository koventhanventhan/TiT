import React, { useState, useEffect, useCallback } from 'react'
import {
    FiMail, FiSend, FiInbox, FiChevronLeft, FiUser, FiUsers,
    FiCheckCircle, FiClock, FiSearch, FiPlus, FiArrowRight,
    FiMessageSquare, FiFilter
} from 'react-icons/fi'
import echo from '../services/echo'
import './MessagingPage.css'

const API = import.meta.env.VITE_API_URL || '/api'

function getAuth() {
    const token = localStorage.getItem('authToken')
    return { headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } }
}

export default function MessagingPage() {
    const [tab, setTab] = useState('inbox') // inbox | compose | sent | view
    const [messages, setMessages] = useState([])
    const [sentMessages, setSentMessages] = useState([])
    const [recipients, setRecipients] = useState({})
    const [loading, setLoading] = useState(false)
    const [selectedMessage, setSelectedMessage] = useState(null)
    const [unreadCount, setUnreadCount] = useState(0)
    const [search, setSearch] = useState('')

    // Compose state
    const [recipientType, setRecipientType] = useState('individual')
    const [recipientId, setRecipientId] = useState('')
    const [grade, setGrade] = useState('')
    const [subject, setSubject] = useState('')
    const [body, setBody] = useState('')
    const [sending, setSending] = useState(false)

    const user = JSON.parse(localStorage.getItem('user') || '{}')

    const fetchInbox = useCallback(async () => {
        setLoading(true)
        try {
            const res = await fetch(`${API}/messages`, getAuth())
            const data = await res.json()
            setMessages(data.messages || [])
        } catch { }
        setLoading(false)
    }, [])

    const fetchSent = useCallback(async () => {
        setLoading(true)
        try {
            const res = await fetch(`${API}/messages/sent`, getAuth())
            const data = await res.json()
            setSentMessages(data.messages || [])
        } catch { }
        setLoading(false)
    }, [])

    const fetchRecipients = useCallback(async () => {
        try {
            const res = await fetch(`${API}/messages/recipients`, getAuth())
            const data = await res.json()
            setRecipients(data)
        } catch { }
    }, [])

    const fetchUnread = useCallback(async () => {
        try {
            const res = await fetch(`${API}/messages/unread-count`, getAuth())
            const data = await res.json()
            setUnreadCount(data.unread_count || 0)
        } catch { }
    }, [])

    useEffect(() => {
        fetchInbox()
        fetchRecipients()
        fetchUnread()

        // Real-time listeners
        if (user.id) {
            // Listen for direct messages
            echo.private(`user.${user.id}`)
                .listen('.new.message', (e) => {
                    console.log('Real-time message received:', e);
                    fetchInbox();
                    fetchUnread();
                });

            // Listen for role-based broadcasts
            if (user.role) {
                const roleKey = user.role === 'user' ? 'all_students' : (user.role === 'teacher' ? 'all_teachers' : 'admin');
                const roleChannel = `messages.${roleKey}`;

                echo.channel(roleChannel)
                    .listen('.new.message', () => {
                        fetchInbox();
                        fetchUnread();
                    });

                // Grade-specific broadcasts
                if (user.role === 'user' && user.current_grade) {
                    echo.channel(`messages.grade_${user.current_grade}`)
                        .listen('.new.message', () => {
                            fetchInbox();
                            fetchUnread();
                        });
                }
            }
        }

        return () => {
            if (user.id) {
                echo.leave(`user.${user.id}`);
                const roleKey = user.role === 'user' ? 'all_students' : (user.role === 'teacher' ? 'all_teachers' : 'admin');
                echo.leave(`messages.${roleKey}`);
                if (user.role === 'user' && user.current_grade) {
                    echo.leave(`messages.grade_${user.current_grade}`);
                }
            }
        };
    }, [fetchInbox, fetchRecipients, fetchUnread, user.id, user.role, user.current_grade])

    const handleSend = async () => {
        if (!subject.trim() || !body.trim()) return alert('Please fill subject and body.')
        setSending(true)
        try {
            const payload = {
                subject, body,
                recipient_type: recipientType,
                recipient_id: recipientType === 'individual' ? recipientId : null,
                grade: recipientType === 'grade' ? grade : null,
            }
            const res = await fetch(`${API}/messages`, {
                method: 'POST',
                ...getAuth(),
                headers: { ...getAuth().headers, 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            })
            if (res.ok) {
                setSubject('')
                setBody('')
                setRecipientId('')
                setGrade('')
                setTab('sent')
                fetchSent()
            } else {
                const err = await res.json()
                alert(err.message || 'Failed to send.')
            }
        } catch { alert('Network error.') }
        setSending(false)
    }

    const markRead = async (id) => {
        try {
            await fetch(`${API}/messages/${id}/read`, { method: 'POST', ...getAuth() })
            fetchUnread()
        } catch { }
    }

    const openMessage = (msg) => {
        setSelectedMessage(msg)
        setTab('view')
        if (!msg.is_read) markRead(msg.id)
    }

    const formatDate = (iso) => {
        const d = new Date(iso)
        const now = new Date()
        const diff = now - d
        if (diff < 60000) return 'Just now'
        if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`
        if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })
    }

    const getRoleBadge = (role) => {
        const map = { admin: '🛡️ Admin', teacher: '👨‍🏫 Teacher', user: '🎓 Student' }
        return map[role] || role
    }

    const getBroadcastLabel = (role) => {
        if (role === 'all_students') return '📢 All Students'
        if (role === 'all_teachers') return '📢 All Teachers'
        if (role === 'admin') return '📢 Admin'
        if (role?.startsWith('grade_')) return `📢 Grade ${role.replace('grade_', '')}`
        return role
    }

    const filteredMessages = messages.filter(m =>
        m.subject?.toLowerCase().includes(search.toLowerCase()) ||
        m.sender?.name?.toLowerCase().includes(search.toLowerCase())
    )

    const filteredSent = sentMessages.filter(m =>
        m.subject?.toLowerCase().includes(search.toLowerCase())
    )

    // Get available individual recipients
    const individualList = []
    if (recipients.teachers) individualList.push(...recipients.teachers.map(t => ({ ...t, role: 'teacher' })))
    if (recipients.students) individualList.push(...recipients.students.map(s => ({ ...s, role: 'student' })))
    if (recipients.admins) individualList.push(...recipients.admins.map(a => ({ ...a, role: 'admin' })))

    // Get broadcast options
    const broadcastOptions = recipients.broadcast || []

    // Extract grade options from broadcast
    const gradeOptions = broadcastOptions
        .filter(o => o.value.startsWith('grade_'))
        .map(o => ({ value: o.value.replace('grade_', ''), label: o.label }))

    const nonGradeBroadcasts = broadcastOptions.filter(o => !o.value.startsWith('grade_'))

    return (
        <div className="messaging-page">
            <div className="msg-header">
                <div className="msg-header-left">
                    <FiMessageSquare className="msg-header-icon" />
                    <div>
                        <h1>Messages</h1>
                        <p>Send and receive messages across the platform</p>
                    </div>
                </div>
                {unreadCount > 0 && <span className="msg-unread-badge">{unreadCount} unread</span>}
            </div>

            <div className="msg-layout">
                {/* Sidebar */}
                <div className="msg-sidebar">
                    <button className="msg-compose-btn" onClick={() => setTab('compose')}>
                        <FiPlus /> New Message
                    </button>

                    <nav className="msg-nav">
                        <button className={tab === 'inbox' ? 'active' : ''} onClick={() => { setTab('inbox'); fetchInbox() }}>
                            <FiInbox /> Inbox
                            {unreadCount > 0 && <span className="nav-badge">{unreadCount}</span>}
                        </button>
                        <button className={tab === 'sent' ? 'active' : ''} onClick={() => { setTab('sent'); fetchSent() }}>
                            <FiSend /> Sent
                        </button>
                    </nav>

                    <div className="msg-search">
                        <FiSearch />
                        <input
                            type="text"
                            placeholder="Search messages..."
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                        />
                    </div>
                </div>

                {/* Main Content */}
                <div className="msg-content">
                    {/* INBOX */}
                    {tab === 'inbox' && (
                        <div className="msg-list-view">
                            <h2><FiInbox /> Inbox</h2>
                            {loading ? (
                                <div className="msg-loading">Loading...</div>
                            ) : filteredMessages.length === 0 ? (
                                <div className="msg-empty">
                                    <FiMail className="empty-icon" />
                                    <p>No messages yet</p>
                                </div>
                            ) : (
                                <div className="msg-list">
                                    {filteredMessages.map(msg => (
                                        <div
                                            key={msg.id}
                                            className={`msg-item ${!msg.is_read ? 'unread' : ''}`}
                                            onClick={() => openMessage(msg)}
                                        >
                                            <div className="msg-item-avatar">
                                                {msg.sender?.name?.charAt(0)?.toUpperCase() || '?'}
                                            </div>
                                            <div className="msg-item-body">
                                                <div className="msg-item-top">
                                                    <span className="msg-sender">{msg.sender?.name}</span>
                                                    <span className="msg-role-tag">{getRoleBadge(msg.sender?.role)}</span>
                                                    {msg.is_broadcast && <span className="msg-broadcast-tag">{getBroadcastLabel(msg.receiver_role)}</span>}
                                                </div>
                                                <div className="msg-subject">{msg.subject}</div>
                                                <div className="msg-preview">{msg.body?.substring(0, 80)}...</div>
                                            </div>
                                            <div className="msg-item-meta">
                                                <span className="msg-time">{formatDate(msg.created_at)}</span>
                                                {!msg.is_read && <span className="msg-dot" />}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* SENT */}
                    {tab === 'sent' && (
                        <div className="msg-list-view">
                            <h2><FiSend /> Sent Messages</h2>
                            {loading ? (
                                <div className="msg-loading">Loading...</div>
                            ) : filteredSent.length === 0 ? (
                                <div className="msg-empty">
                                    <FiSend className="empty-icon" />
                                    <p>No sent messages</p>
                                </div>
                            ) : (
                                <div className="msg-list">
                                    {filteredSent.map(msg => (
                                        <div key={msg.id} className="msg-item sent-item" onClick={() => { setSelectedMessage(msg); setTab('view') }}>
                                            <div className="msg-item-avatar sent-avatar">
                                                {msg.receiver?.name?.charAt(0)?.toUpperCase() || '📢'}
                                            </div>
                                            <div className="msg-item-body">
                                                <div className="msg-item-top">
                                                    <span className="msg-sender">To: {msg.receiver?.name || getBroadcastLabel(msg.receiver_role)}</span>
                                                </div>
                                                <div className="msg-subject">{msg.subject}</div>
                                                <div className="msg-preview">{msg.body?.substring(0, 80)}...</div>
                                            </div>
                                            <div className="msg-item-meta">
                                                <span className="msg-time">{formatDate(msg.created_at)}</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* COMPOSE */}
                    {tab === 'compose' && (
                        <div className="msg-compose-view">
                            <h2><FiPlus /> Compose Message</h2>

                            <div className="compose-form">
                                <div className="compose-group">
                                    <label>Send To</label>
                                    <div className="recipient-type-selector">
                                        <button
                                            className={recipientType === 'individual' ? 'active' : ''}
                                            onClick={() => setRecipientType('individual')}
                                        >
                                            <FiUser /> Individual
                                        </button>
                                        {nonGradeBroadcasts.map(opt => (
                                            <button
                                                key={opt.value}
                                                className={recipientType === opt.value ? 'active' : ''}
                                                onClick={() => setRecipientType(opt.value)}
                                            >
                                                <FiUsers /> {opt.label}
                                            </button>
                                        ))}
                                        {gradeOptions.length > 0 && (
                                            <button
                                                className={recipientType === 'grade' ? 'active' : ''}
                                                onClick={() => setRecipientType('grade')}
                                            >
                                                <FiFilter /> By Grade
                                            </button>
                                        )}
                                    </div>
                                </div>

                                {recipientType === 'individual' && (
                                    <div className="compose-group">
                                        <label>Select Recipient</label>
                                        <select value={recipientId} onChange={e => setRecipientId(e.target.value)}>
                                            <option value="">-- Select --</option>
                                            {individualList.map(r => (
                                                <option key={r.id} value={r.id}>
                                                    {r.name} ({r.role}) — {r.email}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                )}

                                {recipientType === 'grade' && (
                                    <div className="compose-group">
                                        <label>Select Grade</label>
                                        <select value={grade} onChange={e => setGrade(e.target.value)}>
                                            <option value="">-- Select Grade --</option>
                                            {gradeOptions.map(g => (
                                                <option key={g.value} value={g.value}>{g.label}</option>
                                            ))}
                                        </select>
                                    </div>
                                )}

                                <div className="compose-group">
                                    <label>Subject</label>
                                    <input
                                        type="text"
                                        placeholder="Message subject..."
                                        value={subject}
                                        onChange={e => setSubject(e.target.value)}
                                    />
                                </div>

                                <div className="compose-group">
                                    <label>Message</label>
                                    <textarea
                                        rows="8"
                                        placeholder="Type your message here..."
                                        value={body}
                                        onChange={e => setBody(e.target.value)}
                                    />
                                </div>

                                <div className="compose-actions">
                                    <button className="btn-cancel" onClick={() => setTab('inbox')}>Cancel</button>
                                    <button className="btn-send" onClick={handleSend} disabled={sending}>
                                        {sending ? 'Sending...' : <><FiSend /> Send Message</>}
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* VIEW MESSAGE */}
                    {tab === 'view' && selectedMessage && (
                        <div className="msg-detail-view">
                            <button className="msg-back-btn" onClick={() => setTab('inbox')}>
                                <FiChevronLeft /> Back to Inbox
                            </button>

                            <div className="msg-detail-card">
                                <div className="msg-detail-header">
                                    <div className="msg-detail-avatar">
                                        {(selectedMessage.sender?.name || selectedMessage.receiver?.name || '?').charAt(0).toUpperCase()}
                                    </div>
                                    <div className="msg-detail-meta">
                                        <h3>{selectedMessage.subject}</h3>
                                        <p>
                                            {selectedMessage.sender
                                                ? <>From: <strong>{selectedMessage.sender.name}</strong> ({getRoleBadge(selectedMessage.sender.role)})</>
                                                : <>To: <strong>{selectedMessage.receiver?.name || getBroadcastLabel(selectedMessage.receiver_role)}</strong></>
                                            }
                                        </p>
                                        <span className="msg-detail-time">
                                            <FiClock /> {new Date(selectedMessage.created_at).toLocaleString()}
                                        </span>
                                    </div>
                                </div>
                                <div className="msg-detail-body">
                                    {selectedMessage.body}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    )
}
