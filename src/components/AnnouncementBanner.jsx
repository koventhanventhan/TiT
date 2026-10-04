import React, { useState, useEffect } from 'react'
import { getActiveAnnouncement } from '../../services/dashboardService'
import { FiX, FiInfo } from 'react-icons/fi'

export default function AnnouncementBanner() {
    const [announcement, setAnnouncement] = useState(null)
    const [dismissed, setDismissed] = useState(false)

    useEffect(() => {
        async function fetchAnnouncement() {
            try {
                const res = await getActiveAnnouncement()
                if (res && res.id) {
                    const dismissedKey = `dismissed_announcement_${res.id}`
                    if (!localStorage.getItem(dismissedKey)) {
                        setAnnouncement(res)
                    }
                }
            } catch (error) {
                console.error('Failed to fetch active announcement')
            }
        }
        fetchAnnouncement()
    }, [])

    const handleDismiss = () => {
        if (announcement) {
            localStorage.setItem(`dismissed_announcement_${announcement.id}`, 'true')
            setDismissed(true)
        }
    }

    if (!announcement || dismissed) return null

    return (
        <div style={{
            background: 'linear-gradient(135deg, #3b82f6, #1d4ed8)',
            color: 'white',
            padding: '12px 24px',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
            zIndex: 100,
            position: 'relative'
        }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                <FiInfo size={20} />
                <div>
                    <strong style={{ display: 'block', fontSize: '1rem', marginBottom: '2px' }}>{announcement.title}</strong>
                    <span style={{ fontSize: '0.9rem', opacity: 0.9 }}>{announcement.message}</span>
                </div>
            </div>
            <button 
                onClick={handleDismiss}
                style={{ 
                    background: 'transparent', 
                    border: 'none', 
                    color: 'white', 
                    cursor: 'pointer',
                    padding: '8px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    borderRadius: '50%',
                    transition: 'background 0.2s'
                }}
                onMouseOver={e => e.currentTarget.style.background = 'rgba(255,255,255,0.2)'}
                onMouseOut={e => e.currentTarget.style.background = 'transparent'}
            >
                <FiX size={20} />
            </button>
        </div>
    )
}
