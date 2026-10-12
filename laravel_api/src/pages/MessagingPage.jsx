import React, { useState, useEffect, useCallback, useRef } from 'react'
import {
    FiSend, FiChevronLeft, FiUser, FiUsers,
    FiSearch, FiMessageCircle, FiFilter, FiPlus
} from 'react-icons/fi'
import echo from '../services/echo'
import './MessagingPage.css'
import { useToast } from '../components/shared/ToastContext';

const API = import.meta.env.VITE_API_URL || '/api'

import { getAuthHeaders } from '../services/apiClient'

function getAuth() {
    return { headers: getAuthHeaders() }
}

export default function MessagingPage() {
    const toast = useToast();

    const [messages, setMessages] = useState([])
    const [sentMessages, setSentMessages] = useState([])
    const [recipients, setRecipients] = useState({})

    const [search, setSearch] = useState('')
    const [activeChatId, setActiveChatId] = useState(null)
    const [replyBody, setReplyBody] = useState('')
    const [replying, setReplying] = useState(false)
    const [isComposeMode, setIsComposeMode] = useState(false)

    // Compose state
    const [composeType, setComposeType] = useState('individual')
    const [composeId, setComposeId] = useState('')
    const [composeGrade, setComposeGrade] = useState('')
    const [composeSubject, setComposeSubject] = useState('')

    const user = JSON.parse(localStorage.getItem('user') || '{}')
    const chatEndRef = useRef(null)

    const fetchInbox = useCallback(async () => {
        try {
            const res = await fetch(`${API}/messages`, getAuth())
            const data = await res.json()
            setMessages(data.messages || [])
        } catch { }
    }, [])

    const fetchSent = useCallback(async () => {
        try {
            const res = await fetch(`${API}/messages/sent`, getAuth())
            const data = await res.json()
            setSentMessages(data.messages || [])
        } catch { }
    }, [])

    const fetchRecipients = useCallback(async () => {
        try {
            const res = await fetch(`${API}/messages/recipients`, getAuth())
            const data = await res.json()
            setRecipients(data)
        } catch { }
    }, [])

    useEffect(() => {
        fetchInbox()
        fetchSent()
        fetchRecipients()
    }, [fetchInbox, fetchSent, fetchRecipients])

    // Real-time listener
    useEffect(() => {
        if (!user.id || !echo) return;

        const channel = echo.private(`messages.user.${user.id}`);
        channel.listen('.message.sent', (e) => {
            fetchInbox();
            if (activeChatId) {
                markRead(e.message.id);
            }
        });

        // Broadcast channels based on role
        let roleKey = null;
        if (user.role === 'admin' || user.role === 'super_admin') roleKey = 'admin';
        else if (user.role === 'teacher') roleKey = 'all_teachers';
        else if (user.role === 'user') roleKey = 'all_students';

        if (roleKey) {
            echo.private(`messages.${roleKey}`).listen('.message.sent', () => fetchInbox());
        }

        if (user.role === 'user' && user.current_grade) {
            echo.private(`messages.grade_${user.current_grade}`).listen('.message.sent', () => fetchInbox());
        }

        return () => {
            channel.stopListening('.message.sent');
            echo.leave(`messages.user.${user.id}`);
            if (roleKey) {
                echo.leave(`messages.${roleKey}`);
                if (user.role === 'user' && user.current_grade) echo.leave(`messages.grade_${user.current_grade}`);
            }
        };
    }, [fetchInbox, fetchSent, fetchRecipients, user.id, user.role, user.current_grade, activeChatId])

    useEffect(() => {
        if (activeChatId) {
            chatEndRef.current?.scrollIntoView({ behavior: 'auto' })
        }
    }, [activeChatId, messages, sentMessages])

    const markRead = async (id) => {
        try {
            await fetch(`${API}/messages/${id}/read`, { method: 'POST', ...getAuth() })
            fetchInbox()
        } catch { }
    }

    const getBroadcastLabel = (role) => {
        if (role === 'all_students') return 'All Students'
        if (role === 'all_teachers') return 'All Teachers'
        if (role === 'admin') return 'Admin'
        if (role?.startsWith('grade_')) return `Grade ${role.replace('grade_', '')}`
        return role
    }

    const getRoleBadge = (role) => {
        const map = { admin: '🛡️ Admin', teacher: '👨‍🏫 Teacher', user: '🎓 Student', broadcast: '📢 Broadcast' }
        return map[role] || role
    }

    // Process all messages into grouped conversational threads
    const threadsRaw = {}
    const processMsg = (msg, dir) => {
        let pId, pName, pRole, isGroup = false;

        if (dir === 'out' && msg.is_broadcast) {
            pId = `broadcast_${msg.receiver_role}`;
            pName = getBroadcastLabel(msg.receiver_role);
            pRole = 'broadcast';
            isGroup = true;
        } else if (dir === 'in' && msg.is_broadcast) {
            const p = msg.sender;
            if (!p) return;
            pId = String(p.id);
            pName = p.full_name || p.name;
            pRole = p.role;
        } else {
            const p = dir === 'out' ? msg.receiver : msg.sender;
            if (!p) return;
            pId = String(p.id);
            pName = p.full_name || p.name;
            pRole = p.role;
        }

        if (!threadsRaw[pId]) {
            threadsRaw[pId] = {
                id: pId,
                name: pName,
                role: pRole,
                isGroup,
                unread: 0,
                messages: []
            }
        }

        threadsRaw[pId].messages.push({ ...msg, dir })
        if (dir === 'in' && !msg.is_read) {
            threadsRaw[pId].unread += 1
        }
    }

    messages.forEach(m => processMsg(m, 'in'))
    sentMessages.forEach(m => processMsg(m, 'out'))

    const threads = Object.values(threadsRaw).map(t => {
        t.messages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
        t.lastMessage = t.messages[t.messages.length - 1]
        return t
    })

    threads.sort((a, b) => {
        const timeA = a.lastMessage ? new Date(a.lastMessage.created_at) : 0
        const timeB = b.lastMessage ? new Date(b.lastMessage.created_at) : 0
        return timeB - timeA
    })

    const filteredThreads = threads.filter(t => 
        t.name?.toLowerCase().includes(search.toLowerCase())
    )

    const activeChat = threads.find(t => t.id === activeChatId)

    const handleSendMessage = async () => {
        if (!replyBody.trim()) return

        setReplying(true)
        const payload = {
            body: replyBody,
            subject: composeSubject || 'Message'
        }

        if (isComposeMode) {
            if (composeType === 'individual') {
                if (!composeId) { toast.error('Select a recipient'); setReplying(false); return; }
                payload.receiver_id = composeId
            } else if (composeType === 'grade') {
                if (!composeGrade) { toast.error('Select a grade'); setReplying(false); return; }
                payload.is_broadcast = true
                payload.broadcast_type = `grade_${composeGrade}`
            } else {
                payload.is_broadcast = true
                payload.broadcast_type = composeType
            }
        } else if (activeChat) {
            if (activeChat.isGroup) {
                payload.is_broadcast = true
                payload.broadcast_type = activeChat.id.replace('broadcast_', '')
            } else {
                payload.receiver_id = activeChat.id
            }
        }

        try {
            const res = await fetch(`${API}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...getAuthHeaders()
                },
                body: JSON.stringify(payload)
            })
            if (res.ok) {
                setReplyBody('')
                toast.success('Message sent!')
                await fetchSent()
                await fetchInbox()

                if (isComposeMode) {
                    setIsComposeMode(false)
                    if (payload.receiver_id) setActiveChatId(String(payload.receiver_id))
                    else if (payload.is_broadcast) setActiveChatId(`broadcast_${payload.broadcast_type}`)
                }
            } else {
                toast.error('Failed to send message.')
            }
        } catch { toast.error('Network error.') }
        setReplying(false)
    }

    const formatDate = (dateStr) => {
        const d = new Date(dateStr)
        const now = new Date()
        if (d.toDateString() === now.toDateString()) {
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        }
        return d.toLocaleDateString([], { day: '2-digit', month: 'short' })
    }

    // Recipients preparation
    const individualList = []
    if (recipients.teachers) individualList.push(...recipients.teachers.map(t => ({ ...t, role: 'teacher' })))
    if (recipients.students) individualList.push(...recipients.students.map(s => ({ ...s, role: 'student' })))
    if (recipients.admins) individualList.push(...recipients.admins.map(a => ({ ...a, role: 'admin' })))

    const broadcastOptions = recipients.broadcast || []
    const gradeOptions = broadcastOptions.filter(o => o.value.startsWith('grade_')).map(o => ({ value: o.value.replace('grade_', ''), label: o.label }))
    const nonGradeBroadcasts = broadcastOptions.filter(o => !o.value.startsWith('grade_'))

    return (
        <div className="messaging-container" style={{ maxWidth: 1200, margin: '0 auto', padding: '24px', height: 'calc(100dvh - 64px - 24px)', maxHeight: '100%', boxSizing: 'border-box', fontFamily: "'Inter', sans-serif" }}>
            <div className="messaging-wrapper" style={{ background: '#fff', borderRadius: 20, border: '1px solid var(--m-border)', boxShadow: '0 10px 30px -10px rgba(0,0,0,0.05)', display: 'flex', height: '100%', overflow: 'hidden' }}>
                
                {/* Left Sidebar (Contacts/Threads List) */}
                <div className={`chat-sidebar ${activeChatId || isComposeMode ? 'hide-on-mobile' : ''}`} style={{ width: 350, borderRight: '1px solid var(--m-border)', display: 'flex', flexDirection: 'column', background: 'var(--m-bg)' }}>
                    <div style={{ padding: '20px', background: '#fff', borderBottom: '1px solid var(--m-border)' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                            <h2 style={{ fontSize: 22, fontWeight: 700, color: 'var(--m-text)', margin: 0, display: 'flex', alignItems: 'center', gap: 8 }}><FiMessageCircle /> Chats</h2>
                            <button
                                onClick={() => { setIsComposeMode(true); setActiveChatId(null) }}
                                className="t-icon-btn"
                                aria-label="New Chat"
                                style={{ width: 44, height: 44, minWidth: 44, minHeight: 44, borderRadius: '50%', background: 'var(--m-primary)', color: '#fff', border: 'none', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20, boxShadow: '0 4px 10px rgba(0, 0, 0, 0.1)' }}
                                title="New Chat"
                            >
                                <FiPlus />
                            </button>
                        </div>
                        <div style={{ position: 'relative' }}>
                            <FiSearch style={{ position: 'absolute', left: 14, top: 12, color: 'var(--m-text-3)' }} />
                            <input 
                                type="text" placeholder="Search chats..." 
                                value={search} onChange={e => setSearch(e.target.value)}
                                style={{ width: '100%', padding: '10px 16px 10px 40px', borderRadius: 12, border: '1px solid var(--m-border)', background: 'var(--m-bg)', outline: 'none', fontSize: 14, color: 'var(--m-text)', boxSizing: 'border-box' }} 
                            />
                        </div>
                    </div>

                    <div style={{ flex: 1, overflowY: 'auto' }} className="hide-scrollbar">
                        {filteredThreads.map(t => (
                            <div key={t.id} onClick={() => { setActiveChatId(t.id); setIsComposeMode(false); }} style={{ padding: '16px 20px', display: 'flex', gap: 16, cursor: 'pointer', background: activeChatId === t.id ? 'var(--m-primary-soft)' : 'transparent', borderBottom: '1px solid var(--m-border)', transition: 'background 0.2s', alignItems: 'center' }}>
                                <div style={{ width: 48, height: 48, borderRadius: '50%', background: 'var(--m-primary)', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20, fontWeight: 700, flexShrink: 0 }}>
                                    {t.isGroup ? '📢' : t.name.charAt(0).toUpperCase()}
                                </div>
                                <div style={{ flex: 1, minWidth: 0 }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 }}>
                                        <h4 style={{ margin: 0, fontSize: 16, fontWeight: 600, color: 'var(--m-text)', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{t.name}</h4>
                                        {t.lastMessage && <span style={{ fontSize: 12, color: t.unread > 0 ? 'var(--m-primary)' : 'var(--m-text-3)', fontWeight: t.unread > 0 ? 600:400 }}>{formatDate(t.lastMessage.created_at)}</span>}
                                    </div>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                        <p style={{ margin: 0, fontSize: 14, color: 'var(--m-text-3)', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis', flex: 1 }}>
                                            {t.lastMessage?.dir === 'out' && <FiSend style={{ fontSize: 10, marginRight: 4, color: 'var(--m-text-3)' }} />}
                                            {t.lastMessage?.body || 'No messages'}
                                        </p>
                                        {t.unread > 0 && <span style={{ background: 'var(--m-primary)', color: '#fff', borderRadius: '50%', width: 20, height: 20, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 12, fontWeight: 700, marginLeft: 8 }}>{t.unread}</span>}
                                    </div>
                                </div>
                            </div>
                        ))}
                        {filteredThreads.length === 0 && <div style={{ padding: 40, textAlign: 'center', color: 'var(--m-text-3)' }}>No chats found.</div>}
                    </div>
                </div>

                {/* Right Area (Chat View or Compose) */}
                <div style={{ flex: 1, display: 'flex', flexDirection: 'column', background: '#fff' }} className={`chat-main ${!activeChatId && !isComposeMode ? 'hide-on-mobile' : ''}`}>
                    
                    {!activeChatId && !isComposeMode ? (
                        <div style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', color: 'var(--m-text-3)', padding: 24 }}>
                            <div style={{ width: 100, height: 100, borderRadius: '50%', background: 'var(--m-bg)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 44, marginBottom: 20, color: 'var(--m-text-3)' }}>
                                <FiMessageCircle />
                            </div>
                            <h2 style={{ color: 'var(--m-text)', margin: '0 0 8px 0', fontSize: 20 }}>Your Messages</h2>
                            <p style={{ margin: 0, fontSize: 14, textAlign: 'center' }}>Select a chat from the left or start a new conversation.</p>
                        </div>
                    ) : isComposeMode ? (
                        /* COMPOSE NEW CHAT */
                        <div style={{ flex: 1, display: 'flex', flexDirection: 'column' }}>
                            <div style={{ padding: '16px 20px', borderBottom: '1px solid var(--m-border)', display: 'flex', alignItems: 'center', gap: 12 }}>
                                <button
                                    className="mobile-only t-icon-btn"
                                    onClick={() => setIsComposeMode(false)}
                                    aria-label="Back to conversations list"
                                    style={{ border: 'none', background: 'none', fontSize: 24, color: 'var(--m-text-3)', cursor: 'pointer', width: 44, height: 44, minWidth: 44, minHeight: 44, display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                                >
                                    <FiChevronLeft />
                                </button>
                                <div>
                                    <h3 style={{ margin: 0, fontSize: 18, color: 'var(--m-text)' }}>New Conversation</h3>
                                    <p style={{ margin: '2px 0 0 0', fontSize: 12, color: 'var(--m-text-3)' }}>Select recipient and send your first message</p>
                                </div>
                            </div>
                            
                            <div className="compose-body" style={{ flex: 1, padding: 24, overflowY: 'auto' }}>
                                <div style={{ marginBottom: 20 }}>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--m-text)', marginBottom: 8 }}>Send To</label>
                                    <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                                        <button
                                            className="t-btn"
                                            onClick={() => setComposeType('individual')}
                                            style={{
                                                flex: '1 1 auto', minHeight: 44, minWidth: 0, padding: '10px 14px', borderRadius: 10,
                                                border: composeType === 'individual' ? '2px solid var(--m-primary)' : '1px solid var(--m-border)',
                                                background: composeType === 'individual' ? 'var(--m-primary-soft)' : '#fff',
                                                color: composeType === 'individual' ? 'var(--m-primary)' : 'var(--m-text-3)',
                                                fontWeight: 600, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 6, fontSize: 13
                                            }}
                                        >
                                            <FiUser /> Individual
                                        </button>
                                        {nonGradeBroadcasts.map(o => (
                                            <button
                                                key={o.value}
                                                className="t-btn"
                                                onClick={() => setComposeType(o.value)}
                                                style={{
                                                    flex: '1 1 auto', minHeight: 44, minWidth: 0, padding: '10px 14px', borderRadius: 10,
                                                    border: composeType === o.value ? '2px solid var(--m-primary)' : '1px solid var(--m-border)',
                                                    background: composeType === o.value ? 'var(--m-primary-soft)' : '#fff',
                                                    color: composeType === o.value ? 'var(--m-primary)' : 'var(--m-text-3)',
                                                    fontWeight: 600, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 6, fontSize: 13
                                                }}
                                            >
                                                <FiUsers /> {o.label}
                                            </button>
                                        ))}
                                        {gradeOptions.length > 0 && (
                                            <button
                                                className="t-btn"
                                                onClick={() => setComposeType('grade')}
                                                style={{
                                                    flex: '1 1 auto', minHeight: 44, minWidth: 0, padding: '10px 14px', borderRadius: 10,
                                                    border: composeType === 'grade' ? '2px solid var(--m-primary)' : '1px solid var(--m-border)',
                                                    background: composeType === 'grade' ? 'var(--m-primary-soft)' : '#fff',
                                                    color: composeType === 'grade' ? 'var(--m-primary)' : 'var(--m-text-3)',
                                                    fontWeight: 600, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 6, fontSize: 13
                                                }}
                                            >
                                                <FiFilter /> By Grade
                                            </button>
                                        )}
                                    </div>
                                </div>

                                {composeType === 'individual' && (
                                    <div style={{ marginBottom: 20 }}>
                                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--m-text)', marginBottom: 6 }}>Select Recipient</label>
                                        <select value={composeId} onChange={e => setComposeId(e.target.value)} style={{ width: '100%', minHeight: 44, padding: '10px 12px', borderRadius: 10, border: '1px solid var(--m-border)', fontSize: 14, outline: 'none', background: '#fff', color: 'var(--m-text)', boxSizing: 'border-box' }}>
                                            <option value="">-- Select --</option>
                                            {individualList.map(r => <option key={r.id} value={r.id}>{r.full_name || r.name} ({r.role}) — {r.email}</option>)}
                                        </select>
                                    </div>
                                )}

                                {composeType === 'grade' && (
                                    <div style={{ marginBottom: 20 }}>
                                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--m-text)', marginBottom: 6 }}>Select Grade</label>
                                        <select value={composeGrade} onChange={e => setComposeGrade(e.target.value)} style={{ width: '100%', minHeight: 44, padding: '10px 12px', borderRadius: 10, border: '1px solid var(--m-border)', fontSize: 14, outline: 'none', background: '#fff', color: 'var(--m-text)', boxSizing: 'border-box' }}>
                                            <option value="">-- Select Grade --</option>
                                            {gradeOptions.map(g => <option key={g.value} value={g.value}>{g.label}</option>)}
                                        </select>
                                    </div>
                                )}

                                <div style={{ marginBottom: 20 }}>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--m-text)', marginBottom: 6 }}>Subject</label>
                                    <input type="text" placeholder="Message subject..." value={composeSubject} onChange={e => setComposeSubject(e.target.value)} style={{ width: '100%', minHeight: 44, padding: '10px 12px', borderRadius: 10, border: '1px solid var(--m-border)', fontSize: 14, outline: 'none', background: '#fff', color: 'var(--m-text)', boxSizing: 'border-box' }} />
                                </div>
                            </div>

                            <div className="compose-footer" style={{ padding: 16, borderTop: '1px solid var(--m-border)', background: 'var(--m-bg)' }}>
                                <div style={{ display: 'flex', gap: 10, alignItems: 'flex-end' }}>
                                    <textarea 
                                        rows="2" placeholder="Type a message to start..." 
                                        value={replyBody} onChange={e => setReplyBody(e.target.value)}
                                        style={{ flex: 1, padding: '10px 14px', borderRadius: 20, border: '1px solid var(--m-border)', outline: 'none', resize: 'none', fontSize: 15, fontFamily: 'inherit', color: 'var(--m-text)', background: '#fff', boxSizing: 'border-box', maxHeight: 120, overflowY: 'auto' }}
                                    />
                                    <button
                                        onClick={handleSendMessage}
                                        disabled={replying || (!composeId && composeType==='individual') || (!composeGrade && composeType==='grade') || !replyBody.trim()}
                                        className="t-icon-btn"
                                        aria-label="Send message"
                                        style={{ width: 44, height: 44, minWidth: 44, minHeight: 44, borderRadius: '50%', background: 'var(--m-primary)', color: '#fff', border: 'none', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 18, opacity: replying ? 0.7 : 1, flexShrink: 0 }}
                                    >
                                        <FiSend />
                                    </button>
                                </div>
                            </div>
                        </div>
                    ) : (
                        /* CHAT THREAD VIEW */
                        <>
                            <div style={{ padding: '16px 20px', borderBottom: '1px solid var(--m-border)', background: '#fff', display: 'flex', alignItems: 'center', gap: 12 }}>
                                <button
                                    className="mobile-only t-icon-btn"
                                    onClick={() => setActiveChatId(null)}
                                    aria-label="Back to conversations list"
                                    style={{ border: 'none', background: 'none', fontSize: 24, color: 'var(--m-text-3)', cursor: 'pointer', width: 44, height: 44, minWidth: 44, minHeight: 44, display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                                >
                                    <FiChevronLeft />
                                </button>
                                <div style={{ width: 44, height: 44, minWidth: 44, minHeight: 44, borderRadius: '50%', background: 'var(--m-primary)', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 18, fontWeight: 700, flexShrink: 0 }}>
                                    {activeChat.isGroup ? '📢' : activeChat.name.charAt(0).toUpperCase()}
                                </div>
                                <div style={{ minWidth: 0, flex: 1 }}>
                                    <h3 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: 'var(--m-text)', overflowWrap: 'anywhere' }}>{activeChat.name}</h3>
                                    <span style={{ fontSize: 13, color: 'var(--m-text-3)' }}>{getRoleBadge(activeChat.role)}</span>
                                </div>
                            </div>

                            <div className="chat-messages-area" style={{ flex: 1, padding: 20, overflowY: 'auto', background: 'var(--m-bg)', display: 'flex', flexDirection: 'column', gap: 14 }}>
                                {activeChat.messages.map(m => {
                                    const isMe = m.dir === 'out';
                                    return (
                                        <div key={m.id} style={{ display: 'flex', flexDirection: 'column', alignItems: isMe ? 'flex-end' : 'flex-start', maxWidth: '85%', alignSelf: isMe ? 'flex-end' : 'flex-start' }}>
                                            <div style={{ 
                                                background: isMe ? 'var(--m-primary)' : '#fff', color: isMe ? '#fff' : 'var(--m-text)', 
                                                border: isMe ? 'none' : '1px solid var(--m-border)',
                                                padding: '10px 14px', borderRadius: isMe ? '18px 18px 4px 18px' : '18px 18px 18px 4px',
                                                boxShadow: '0 1px 3px rgba(0,0,0,0.04)', fontSize: 15, lineHeight: 1.5, wordWrap: 'break-word', whiteSpace: 'pre-wrap'
                                            }}>
                                                {m.body}
                                            </div>
                                            <div style={{ fontSize: 11, color: 'var(--m-text-3)', marginTop: 4, padding: '0 4px', display: 'flex', alignItems: 'center', gap: 4 }}>
                                                {formatDate(m.created_at)}
                                                {isMe && <span style={{ fontSize: 13 }}>✓</span>}
                                            </div>
                                        </div>
                                    )
                                })}
                                <div ref={chatEndRef} />
                            </div>

                            <div className="chat-reply-bar" style={{ padding: 16, background: '#fff', borderTop: '1px solid var(--m-border)' }}>
                                <div style={{ display: 'flex', gap: 10, alignItems: 'flex-end' }}>
                                    <textarea 
                                        rows="1" placeholder="Type your message..." 
                                        value={replyBody} onChange={e => {
                                            setReplyBody(e.target.value);
                                            e.target.style.height = 'auto';
                                            e.target.style.height = Math.min(e.target.scrollHeight, 120) + 'px';
                                        }}
                                        style={{ flex: 1, padding: '12px 18px', borderRadius: 24, border: '1px solid var(--m-border)', outline: 'none', resize: 'none', fontSize: 15, fontFamily: 'inherit', background: 'var(--m-bg)', color: 'var(--m-text)', maxHeight: 120, overflowY: 'auto', boxSizing: 'border-box' }}
                                    />
                                    <button
                                        onClick={handleSendMessage}
                                        disabled={replying || !replyBody.trim()}
                                        className="t-icon-btn"
                                        aria-label="Send reply"
                                        style={{ width: 44, height: 44, minWidth: 44, minHeight: 44, borderRadius: '50%', background: 'var(--m-primary)', color: '#fff', border: 'none', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 18, opacity: replying ? 0.7 : 1, flexShrink: 0 }}
                                    >
                                        <FiSend />
                                    </button>
                                </div>
                            </div>
                        </>
                    )}
                </div>

            </div>

            <style>{`
                .hide-scrollbar::-webkit-scrollbar { display: none; }
                .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
                @media (max-width: 768px) {
                    .hide-on-mobile { display: none !important; }
                    .mobile-only { display: flex !important; }
                    .chat-sidebar { width: 100% !important; border-right: none !important; }
                    .chat-main { width: 100% !important; }
                    .messaging-container {
                        padding: 12px !important;
                        height: calc(100dvh - 64px - 24px) !important;
                        box-sizing: border-box !important;
                    }
                    .messaging-wrapper {
                        border-radius: 16px !important;
                    }
                    .compose-body {
                        padding: 16px !important;
                    }
                    .compose-footer {
                        padding: 12px !important;
                        padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px)) !important;
                    }
                    .chat-reply-bar {
                        padding: 12px !important;
                        padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px)) !important;
                    }
                    .chat-messages-area {
                        padding: 14px !important;
                    }
                }
                @media (min-width: 769px) {
                    .mobile-only { display: none !important; }
                }
                .chat-main input,
                .chat-main select,
                .chat-main textarea {
                    box-sizing: border-box !important;
                    max-width: 100% !important;
                }
            `}</style>
        </div>
    )
}
