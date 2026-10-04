import React, { useState, useEffect } from 'react'
import { getSuperAdminAnnouncements, createSuperAdminAnnouncement } from '../../services/dashboardService'
import { FiPlus, FiAlertCircle } from 'react-icons/fi'
import './SuperAdminInstitutes.css' // Reuse styles

export default function SuperAdminAnnouncements() {
    const [announcements, setAnnouncements] = useState([])
    const [loading, setLoading] = useState(true)
    const [formData, setFormData] = useState({ title: '', message: '', is_active: true })
    const [submitting, setSubmitting] = useState(false)

    useEffect(() => {
        loadData()
    }, [])

    async function loadData() {
        try {
            const res = await getSuperAdminAnnouncements()
            setAnnouncements(res)
        } catch (error) {
            console.error(error)
        } finally {
            setLoading(false)
        }
    }

    const handleSubmit = async (e) => {
        e.preventDefault()
        setSubmitting(true)
        try {
            await createSuperAdminAnnouncement(formData)
            setFormData({ title: '', message: '', is_active: true })
            loadData()
            alert('Announcement created successfully')
        } catch (error) {
            alert('Failed to create announcement')
        } finally {
            setSubmitting(false)
        }
    }

    if (loading) return <div className="loading-shimmer">Loading announcements...</div>

    return (
        <div className="super-institutes">
            <div className="page-header">
                <div className="header-info">
                    <h1>Platform Announcements</h1>
                    <p>Broadcast messages to all institutes and users.</p>
                </div>
            </div>

            <div className="admin-form-card" style={{ marginBottom: '2rem', padding: '1.5rem', background: 'var(--card-bg)', borderRadius: '12px' }}>
                <h3><FiPlus /> New Announcement</h3>
                <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '1rem', marginTop: '1rem' }}>
                    <input 
                        type="text" 
                        placeholder="Announcement Title" 
                        value={formData.title} 
                        onChange={e => setFormData({...formData, title: e.target.value})} 
                        required 
                        style={{ padding: '0.75rem', borderRadius: '8px', border: '1px solid var(--border-color)' }}
                    />
                    <textarea 
                        placeholder="Announcement Message" 
                        value={formData.message} 
                        onChange={e => setFormData({...formData, message: e.target.value})} 
                        required 
                        rows="4"
                        style={{ padding: '0.75rem', borderRadius: '8px', border: '1px solid var(--border-color)', resize: 'vertical' }}
                    />
                    <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                        <input 
                            type="checkbox" 
                            checked={formData.is_active} 
                            onChange={e => setFormData({...formData, is_active: e.target.checked})} 
                        />
                        Active (will be displayed immediately)
                    </label>
                    <button type="submit" className="add-btn" disabled={submitting} style={{ alignSelf: 'flex-start' }}>
                        {submitting ? 'Broadcasting...' : 'Broadcast Announcement'}
                    </button>
                </form>
            </div>

            <div className="institutes-grid">
                {announcements.map(a => (
                    <div key={a.id} className="institute-card">
                        <div className="inst-header">
                            <span className={`status-pill ${a.is_active ? 'active' : 'suspended'}`}>
                                {a.is_active ? 'Active' : 'Inactive'}
                            </span>
                            <span className="plan-pill">{new Date(a.created_at).toLocaleDateString()}</span>
                        </div>
                        <h3>{a.title}</h3>
                        <p style={{ marginTop: '1rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>{a.message}</p>
                    </div>
                ))}
            </div>
        </div>
    )
}
