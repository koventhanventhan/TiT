import React, { useState } from 'react'
import {
    FiSend,
    FiUsers,
    FiUser,
    FiMessageSquare,
    FiBell,
    FiCheckCircle
} from 'react-icons/fi'
import './AdminMessages.css'
import { useToast } from '../../components/shared/ToastContext';


export default function AdminMessages() {
  const toast = useToast();

    const [targetType, setTargetType] = useState('broadcast')
    const [message, setMessage] = useState({ title: '', body: '' })
    const [sending, setSending] = useState(false)

    const handleSend = async () => {
        setSending(true)
        // Simulate API call
        setTimeout(() => {
            setSending(false)
            toast.success('Message sent successfully!')
            setMessage({ title: '', body: '' })
        }, 1500)
    }

    return (
        <div className="admin-messages">
            <div className="page-header">
                <div className="header-info">
                    <h1>Announcement Broadcaster</h1>
                    <p>Send notifications, alerts, and personalized messages to the community.</p>
                </div>
            </div>

            <div className="messages-container">
                <div className="message-form-section">
                    <div className="form-card">
                        <h3>Compose New Message</h3>

                        <div className="target-selector">
                            <button
                                className={targetType === 'broadcast' ? 'active' : ''}
                                onClick={() => setTargetType('broadcast')}
                            >
                                <FiUsers /> Broadcast to All
                            </button>
                            <button
                                className={targetType === 'individual' ? 'active' : ''}
                                onClick={() => setTargetType('individual')}
                            >
                                <FiUser /> Individual Student
                            </button>
                        </div>

                        <div className="input-group">
                            <label>Message Title</label>
                            <input
                                type="text"
                                placeholder="e.g. Schedule Update for Grade 10"
                                value={message.title}
                                onChange={(e) => setMessage({ ...message, title: e.target.value })}
                            />
                        </div>

                        <div className="input-group">
                            <label>Message Body</label>
                            <textarea
                                rows="6"
                                placeholder="Type your announcement here..."
                                value={message.body}
                                onChange={(e) => setMessage({ ...message, body: e.target.value })}
                            ></textarea>
                        </div>

                        <div className="notification-options">
                            <div className="opt">
                                <input type="checkbox" id="whatsapp" defaultChecked />
                                <label htmlFor="whatsapp">Send via WhatsApp</label>
                            </div>
                            <div className="opt">
                                <input type="checkbox" id="email" defaultChecked />
                                <label htmlFor="email">Send via Email</label>
                            </div>
                            <div className="opt">
                                <input type="checkbox" id="push" defaultChecked />
                                <label htmlFor="push">Dashboard Notification</label>
                            </div>
                        </div>

                        <button className="send-btn" onClick={handleSend} disabled={sending}>
                            {sending ? 'Dispatching...' : <><FiSend /> Dispatch Message</>}
                        </button>
                    </div>
                </div>

                <div className="history-section">
                    <div className="history-card">
                        <h3>Recent Dispatches</h3>
                        <div className="message-history-list">
                            {[1, 2, 3].map((i) => (
                                <div key={i} className="history-item">
                                    <div className="h-icon"><FiBell /></div>
                                    <div className="h-info">
                                        <h4>Grade 12 Zoom link updated</h4>
                                        <p>Sent to 45 students • 2 hours ago</p>
                                    </div>
                                    <div className="h-status"><FiCheckCircle /></div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}
