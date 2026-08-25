import React, { useState, useEffect, useCallback, useRef } from 'react'
import {
    FiSend, FiChevronLeft, FiUser, FiUsers,
    FiSearch, FiMessageCircle, FiFilter, FiPlus
} from 'react-icons/fi'
import echo from '../services/echo'
import './MessagingPage.css' // We might not perfectly align with existing css, we will rely on inline styles for chat
import { useToast } from '../components/shared/ToastContext';


const API = import.meta.env.VITE_API_URL || '/api'

function getAuth() {
    const token = localStorage.getItem('authToken')
    return { headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } }
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
            if (!res.ok) return
            const data = await res.json()
            setRecipients(data)
        } catch(e) { }
    }, [])

    useEffect(() => {
        fetchInbox()
        fetchSent()
        fetchRecipients()

        if (user.id) {
            echo.private(`user.${user.id}`)
                .listen('.new.message', () => { fetchInbox() });

            if (user.role) {
                const roleKey = user.role === 'user' ? 'all_students' : (user.role === 'teacher' ? 'all_teachers' : 'admin');
                echo.channel(`messages.${roleKey}`)
                    .listen('.new.message', () => { fetchInbox() });

                if (user.role === 'user' && user.current_grade) {
                    echo.channel(`messages.grade_${user.current_grade}`)
                        .listen('.new.message', () => { fetchInbox() });
                }
            }
        }
        return () => {
            if (user.id) {
                echo.leave(`user.${user.id}`);
                const roleKey = user.role === 'user' ? 'all_students' : (user.role === 'teacher' ? 'all_teachers' : 'admin');
                echo.leave(`messages.${roleKey}`);
                if (user.role === 'user' && user.current_grade) echo.leave(`messages.grade_${user.current_grade}`);
            }
        };
    }, [fetchInbox, fetchSent, fetchRecipients, user.id, user.role, user.current_grade])

    const markRead = async (id) => {
        try {
            await fetch(`${API}/messages/${id}/read`, { method: 'POST', ...getAuth() })
            // Note: In real life we could update local state immediately
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
            // When we receive a broadcast, do we group under Sender or under Broadcast channel?
            // Grouping under sender makes sense so you can reply to them directly
            const p = msg.sender;
            if (!p) return;
            pId = String(p.id);
            pName = p.name;
            pRole = p.role;
        } else {
            const p = dir === 'in' ? msg.sender : msg.receiver;
            if (!p) return;
            pId = String(p.id);
            pName = p.name;
            pRole = p.role;
        }

        if (!threadsRaw[pId]) {
            threadsRaw[pId] = { id: pId, name: pName, role: pRole, isGroup, messages: [], unread: 0, updated_at: msg.created_at };
        }
        if (dir === 'in' && !msg.is_read) {
            threadsRaw[pId].unread++;
        }
        threadsRaw[pId].messages.push({ ...msg, dir });
        if (new Date(msg.created_at) > new Date(threadsRaw[pId].updated_at)) {
            threadsRaw[pId].updated_at = msg.created_at;
        }
    }

    messages.forEach(m => processMsg(m, 'in'))
    sentMessages.forEach(m => processMsg(m, 'out'))

    const threads = Object.values(threadsRaw).sort((a,b) => new Date(b.updated_at) - new Date(a.updated_at))
    threads.forEach(t => {
        t.messages.sort((a,b) => new Date(a.created_at) - new Date(b.created_at))
        t.lastMessage = t.messages[t.messages.length - 1]
    })

    const filteredThreads = threads.filter(t => t.name.toLowerCase().includes(search.toLowerCase()))

    const activeChat = threads.find(t => t.id === activeChatId)

    // Scroll to bottom when chat content changes
    useEffect(() => {
        if (chatEndRef.current) {
            chatEndRef.current.scrollIntoView({ behavior: 'smooth' })
        }
    }, [activeChat?.messages?.length])

    // Auto mark read when opening/updating chat
    useEffect(() => {
        if (activeChat && activeChat.unread > 0) {
            activeChat.messages.filter(m => m.dir === 'in' && !m.is_read).forEach(m => {
                markRead(m.id)
            })
        }
    }, [activeChat])

    const handleSendMessage = async () => {
        if (!replyBody.trim()) return;

        let payload = {};
        if (isComposeMode) {
            if (!composeType) return;
            payload = {
                subject: composeSubject.trim() || 'Message',
                body: replyBody,
                recipient_type: composeType,
                recipient_id: composeType === 'individual' ? composeId : null,
                grade: composeType === 'grade' ? composeGrade : null,
            };
        } else {
            if (!activeChat) return;
            if (activeChat.isGroup) {
                // Sent as broadcast previously, reply should be another broadcast
                payload = {
                    subject: activeChat.lastMessage?.subject || 'Reply',
                    body: replyBody,
                    recipient_type: activeChat.id.replace('broadcast_', '').startsWith('grade_') ? 'grade' : activeChat.id.replace('broadcast_', ''),
                    recipient_id: null,
                    grade: activeChat.id.replace('broadcast_', '').startsWith('grade_') ? activeChat.id.replace('broadcast_grade_', '') : null,
                }
            } else {
                payload = {
                    subject: activeChat.lastMessage?.subject ? (activeChat.lastMessage.subject.startsWith('Re:') ? activeChat.lastMessage.subject : `Re: ${activeChat.lastMessage.subject}`) : 'Message',
                    body: replyBody,
                    recipient_type: 'individual',
                    recipient_id: activeChat.id,
                    grade: null,
                }
            }
        }

        setReplying(true)
        try {
            const res = await fetch(`${API}/messages`, {
                method: 'POST',
                ...getAuth(),
                headers: { ...getAuth().headers, 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            })
            if (res.ok) {
                setReplyBody('')
                setComposeSubject('')
                if (isComposeMode) {
                    setIsComposeMode(false)
                }
                fetchSent()
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
        <div className="messaging-container" style={{ maxWidth: 1200, margin: '0 auto', padding: '24px', height: 'calc(100vh - 40px)', fontFamily: "'Inter', sans-serif" }}>
            <div className="messaging-wrapper" style={{ background: '#fff', borderRadius: 20, border: '1px solid #e2e8f0', boxShadow: '0 10px 30px -10px rgba(0,0,0,0.05)', display: 'flex', height: '100%', overflow: 'hidden' }}>
                
                {/* Left Sidebar (Contacts/Threads List) */}
                <div className={`chat-sidebar ${activeChatId || isComposeMode ? 'hide-on-mobile' : ''}`} style={{ width: 350, borderRight: '1px solid #e2e8f0', display: 'flex', flexDirection: 'column', background: '#f8fafc' }}>
                    <div style={{ padding: '20px', background: '#fff', borderBottom: '1px solid #e2e8f0' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                            <h2 style={{ fontSize: 22, fontWeight: 700, color: '#1e293b', margin: 0, display: 'flex', alignItems: 'center', gap: 8 }}><FiMessageCircle /> Chats</h2>
                            <button onClick={() => { setIsComposeMode(true); setActiveChatId(null) }} style={{ width: 40, height: 40, borderRadius: '50%', background: '#0ea5e9', color: '#fff', border: 'none', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20, boxShadow: '0 4px 10px rgba(14, 165, 233, 0.3)' }} title="New Chat">
                                <FiPlus />
                            </button>
                        </div>
                        <div style={{ position: 'relative' }}>
                            <FiSearch style={{ position: 'absolute', left: 14, top: 12, color: '#94a3b8' }} />
                            <input 
                                type="text" placeholder="Search chats..." 
                                value={search} onChange={e => setSearch(e.target.value)}
                                style={{ width: '100%', padding: '10px 16px 10px 40px', borderRadius: 12, border: '1px solid #cbd5e1', background: '#f1f5f9', outline: 'none', fontSize: 14 }} 
                            />
                        </div>
                    </div>

                    <div style={{ flex: 1, overflowY: 'auto' }} className="hide-scrollbar">
                        {filteredThreads.map(t => (
                            <div key={t.id} onClick={() => { setActiveChatId(t.id); setIsComposeMode(false); }} style={{ padding: '16px 20px', display: 'flex', gap: 16, cursor: 'pointer', background: activeChatId === t.id ? '#e0f2fe' : 'transparent', borderBottom: '1px solid #f1f5f9', transition: 'background 0.2s', alignItems: 'center' }}>
                                <div style={{ width: 50, height: 50, borderRadius: '50%', background: 'linear-gradient(135deg, #0ea5e9, #6366f1)', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20, fontWeight: 700, flexShrink: 0 }}>
                                    {t.isGroup ? '📢' : t.name.charAt(0).toUpperCase()}
                                </div>
                                <div style={{ flex: 1, minWidth: 0 }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 }}>
                                        <h4 style={{ margin: 0, fontSize: 16, fontWeight: 600, color: '#1e293b', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{t.name}</h4>
                                        {t.lastMessage && <span style={{ fontSize: 12, color: t.unread > 0 ? '#0ea5e9' : '#94a3b8', fontWeight: t.unread > 0 ? 600:400 }}>{formatDate(t.lastMessage.created_at)}</span>}
                                    </div>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                        <p style={{ margin: 0, fontSize: 14, color: '#64748b', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis', flex: 1 }}>
                                            {t.lastMessage?.dir === 'out' && <FiSend style={{ fontSize: 10, marginRight: 4, color: '#94a3b8' }} />}
                                            {t.lastMessage?.body || 'No messages'}
                                        </p>
                                        {t.unread > 0 && <span style={{ background: '#0ea5e9', color: '#fff', borderRadius: '50%', width: 20, height: 20, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 12, fontWeight: 700, marginLeft: 8 }}>{t.unread}</span>}
                                    </div>
                                </div>
                            </div>
                        ))}
                        {filteredThreads.length === 0 && <div style={{ padding: 40, textAlign: 'center', color: '#94a3b8' }}>No chats found.</div>}
                    </div>
                </div>

                {/* Right Area (Chat View or Compose) */}
                <div style={{ flex: 1, display: 'flex', flexDirection: 'column', background: '#fff' }} className={`chat-main ${!activeChatId && !isComposeMode ? 'hide-on-mobile' : ''}`}>
                    
                    {!activeChatId && !isComposeMode ? (
                        <div style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', color: '#94a3b8' }}>
                            <div style={{ width: 120, height: 120, borderRadius: '50%', background: '#f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 48, marginBottom: 20 }}>
                                <FiMessageCircle />
                            </div>
                            <h2 style={{ color: '#475569', margin: '0 0 8px 0' }}>Your Messages</h2>
                            <p style={{ margin: 0 }}>Select a chat from the left or start a new conversation.</p>
                        </div>
                    ) : isComposeMode ? (
                        /* COMPOSE NEW CHAT */
                        <div style={{ flex: 1, display: 'flex', flexDirection: 'column' }}>
                            <div style={{ padding: '20px 24px', borderBottom: '1px solid #e2e8f0', display: 'flex', alignItems: 'center', gap: 16 }}>
                                <button className="mobile-only" onClick={() => setIsComposeMode(false)} style={{ border: 'none', background: 'none', fontSize: 24, color: '#64748b', cursor: 'pointer' }}><FiChevronLeft /></button>
                                <div>
                                    <h3 style={{ margin: 0, fontSize: 18, color: '#1e293b' }}>New Conversation</h3>
                                    <p style={{ margin: '4px 0 0 0', fontSize: 13, color: '#64748b' }}>Select recipient and send your first message</p>
                                </div>
                            </div>
                            
                            <div className="compose-body" style={{ flex: 1, padding: 32, overflowY: 'auto' }}>
                                <div style={{ marginBottom: 24 }}>
                                    <label style={{ display: 'block', fontSize: 14, fontWeight: 600, color: '#475569', marginBottom: 12 }}>Send To</label>
                                    <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                                        <button onClick={() => setComposeType('individual')} style={{ padding: '10px 20px', borderRadius: 10, border: composeType === 'individual' ? '2px solid #0ea5e9' : '1px solid #cbd5e1', background: composeType === 'individual' ? '#f0f9ff' : '#fff', color: composeType === 'individual' ? '#0ea5e9' : '#64748b', fontWeight: 600, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6, transition: 'all 0.2s' }}><FiUser /> Individual</button>
                                        {nonGradeBroadcasts.map(o => (
                                            <button key={o.value} onClick={() => setComposeType(o.value)} style={{ padding: '10px 20px', borderRadius: 10, border: composeType === o.value ? '2px solid #0ea5e9' : '1px solid #cbd5e1', background: composeType === o.value ? '#f0f9ff' : '#fff', color: composeType === o.value ? '#0ea5e9' : '#64748b', fontWeight: 600, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6, transition: 'all 0.2s' }}><FiUsers /> {o.label}</button>
                                        ))}
                                        {gradeOptions.length > 0 && <button onClick={() => setComposeType('grade')} style={{ padding: '10px 20px', borderRadius: 10, border: composeType === 'grade' ? '2px solid #0ea5e9' : '1px solid #cbd5e1', background: composeType === 'grade' ? '#f0f9ff' : '#fff', color: composeType === 'grade' ? '#0ea5e9' : '#64748b', fontWeight: 600, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6, transition: 'all 0.2s' }}><FiFilter /> By Grade</button>}
                                    </div>
                                </div>

                                {composeType === 'individual' && (
                                    <div style={{ marginBottom: 24 }}>
                                        <label style={{ display: 'block', fontSize: 14, fontWeight: 600, color: '#475569', marginBottom: 8 }}>Select Recipient</label>
                                        <select value={composeId} onChange={e => setComposeId(e.target.value)} style={{ width: '100%', padding: '12px', borderRadius: 10, border: '1px solid #cbd5e1', fontSize: 14, outline: 'none', background: '#fff' }}>
                                            <option value="">-- Select --</option>
                                            {individualList.map(r => <option key={r.id} value={r.id}>{r.name} ({r.role}) — {r.email}</option>)}
                                        </select>
                                    </div>
                                )}

                                {composeType === 'grade' && (
                                    <div style={{ marginBottom: 24 }}>
                                        <label style={{ display: 'block', fontSize: 14, fontWeight: 600, color: '#475569', marginBottom: 8 }}>Select Grade</label>
                                        <select value={composeGrade} onChange={e => setComposeGrade(e.target.value)} style={{ width: '100%', padding: '12px', borderRadius: 10, border: '1px solid #cbd5e1', fontSize: 14, outline: 'none', background: '#fff' }}>
                                            <option value="">-- Select Grade --</option>
                                            {gradeOptions.map(g => <option key={g.value} value={g.value}>{g.label}</option>)}
                                        </select>
                                    </div>
                                )}

                                <div style={{ marginBottom: 24 }}>
                                    <label style={{ display: 'block', fontSize: 14, fontWeight: 600, color: '#475569', marginBottom: 8 }}>Subject</label>
                                    <input type="text" placeholder="Message subject..." value={composeSubject} onChange={e => setComposeSubject(e.target.value)} style={{ width: '100%', padding: '12px', borderRadius: 10, border: '1px solid #cbd5e1', fontSize: 14, outline: 'none', background: '#fff' }} />
                                </div>
                            </div>

                            <div className="compose-footer" style={{ padding: 20, borderTop: '1px solid #e2e8f0', background: '#f8fafc' }}>
                                <div style={{ display: 'flex', gap: 12 }}>
                                    <textarea 
                                        rows="2" placeholder="Type a message to start..." 
                                        value={replyBody} onChange={e => setReplyBody(e.target.value)}
                                        style={{ flex: 1, padding: '12px 16px', borderRadius: 24, border: '1px solid #cbd5e1', outline: 'none', resize: 'none', fontSize: 15, fontFamily: 'inherit' }}
                                    />
                                    <button onClick={handleSendMessage} disabled={replying || (!composeId && composeType==='individual') || (!composeGrade && composeType==='grade') || !replyBody.trim()} style={{ width: 50, height: 50, borderRadius: '50%', background: '#0ea5e9', color: '#fff', border: 'none', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20, opacity: replying ? 0.7 : 1 }}>
                                        <FiSend />
                                    </button>
                                </div>
                            </div>
                        </div>
                    ) : (
                        /* CHAT THREAD VIEW */
                        <>
                            <div style={{ padding: '16px 24px', borderBottom: '1px solid #e2e8f0', background: '#fff', display: 'flex', alignItems: 'center', gap: 16 }}>
                                <button className="mobile-only" onClick={() => setActiveChatId(null)} style={{ border: 'none', background: 'none', fontSize: 24, color: '#64748b', cursor: 'pointer' }}><FiChevronLeft /></button>
                                <div style={{ width: 44, height: 44, borderRadius: '50%', background: 'linear-gradient(135deg, #0ea5e9, #6366f1)', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 18, fontWeight: 700 }}>
                                    {activeChat.isGroup ? '📢' : activeChat.name.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <h3 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: '#1e293b' }}>{activeChat.name}</h3>
                                    <span style={{ fontSize: 13, color: '#64748b' }}>{getRoleBadge(activeChat.role)}</span>
                                </div>
                            </div>

                            <div className="chat-messages-area" style={{ flex: 1, padding: 24, overflowY: 'auto', background: 'url("https://www.transparenttextures.com/patterns/always-grey.png"), #f4f7f6', display: 'flex', flexDirection: 'column', gap: 16 }}>
                                {activeChat.messages.map(m => {
                                    const isMe = m.dir === 'out';
                                    return (
                                        <div key={m.id} style={{ display: 'flex', flexDirection: 'column', alignItems: isMe ? 'flex-end' : 'flex-start', maxWidth: '80%', alignSelf: isMe ? 'flex-end' : 'flex-start' }}>
                                            <div style={{ 
                                                background: isMe ? '#0ea5e9' : '#fff', color: isMe ? '#fff' : '#1e293b', 
                                                padding: '12px 16px', borderRadius: isMe ? '20px 20px 4px 20px' : '20px 20px 20px 4px',
                                                boxShadow: '0 2px 5px rgba(0,0,0,0.05)', fontSize: 15, lineHeight: 1.5, wordWrap: 'break-word', whiteSpace: 'pre-wrap'
                                            }}>
                                                {m.body}
                                            </div>
                                            <div style={{ fontSize: 11, color: '#94a3b8', marginTop: 4, padding: '0 4px', display: 'flex', alignItems: 'center', gap: 4 }}>
                                                {formatDate(m.created_at)}
                                                {isMe && <span style={{ fontSize: 14 }}>✓</span>}
                                            </div>
                                        </div>
                                    )
                                })}
                                <div ref={chatEndRef} />
                            </div>

                            <div className="chat-reply-bar" style={{ padding: 20, background: '#fff', borderTop: '1px solid #e2e8f0' }}>
                                <div style={{ display: 'flex', gap: 12, alignItems: 'flex-end' }}>
                                    <textarea 
                                        rows="1" placeholder="Type your message..." 
                                        value={replyBody} onChange={e => {
                                            setReplyBody(e.target.value);
                                            e.target.style.height = 'auto';
                                            e.target.style.height = Math.min(e.target.scrollHeight, 120) + 'px';
                                        }}
                                        style={{ flex: 1, padding: '14px 20px', borderRadius: 24, border: '1px solid #cbd5e1', outline: 'none', resize: 'none', fontSize: 15, fontFamily: 'inherit', background: '#f8fafc', maxHeight: 120, overflowY: 'auto' }}
                                    />
                                    <button onClick={handleSendMessage} disabled={replying || !replyBody.trim()} style={{ width: 50, height: 50, borderRadius: '50%', background: '#0ea5e9', color: '#fff', border: 'none', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20, opacity: replying ? 0.7 : 1, flexShrink: 0, transition: 'transform 0.1s' }} onMouseDown={e => e.currentTarget.style.transform='scale(0.95)'} onMouseUp={e => e.currentTarget.style.transform='scale(1)'}>
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
                    .mobile-only { display: block !important; }
                    .chat-sidebar { width: 100% !important; border-right: none !important; }
                    .chat-main { width: 100% !important; }
                }
                @media (min-width: 769px) {
                    .mobile-only { display: none !important; }
                }
                /* Fix input/select/textarea overflow on mobile */
                .chat-main input,
                .chat-main select,
                .chat-main textarea {
                    box-sizing: border-box !important;
                    max-width: 100% !important;
                }
                /* Fix compose area padding on mobile */
                @media (max-width: 768px) {
                    .messaging-container {
                        padding: 0 !important;
                        height: calc(100vh - 64px) !important;
                    }
                    .messaging-wrapper {
                        border-radius: 0 !important;
                        border: none !important;
                    }
                    .compose-body {
                        padding: 16px !important;
                    }
                    .compose-footer {
                        padding: 12px !important;
                    }
                    .compose-footer > div {
                        gap: 8px !important;
                    }
                    .chat-reply-bar {
                        padding: 12px !important;
                    }
                    .chat-reply-bar > div {
                        gap: 8px !important;
                    }
                    .chat-messages-area {
                        padding: 16px !important;
                    }
                }
            `}</style>
        </div>
    )
}
