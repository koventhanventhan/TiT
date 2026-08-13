import React, { useState, useEffect, useRef, useCallback } from 'react'
import { FiBell, FiX, FiCheckCircle, FiMessageSquare, FiRefreshCw } from 'react-icons/fi'

const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'

const getAuthHeaders = () => {
  const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken')
  return {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...(token && { 'Authorization': `Bearer ${token}` }),
  }
}

function timeAgo(dateStr) {
  const now = new Date()
  const date = new Date(dateStr)
  const diff = Math.floor((now - date) / 1000)
  if (diff < 60) return 'Just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  return `${Math.floor(diff / 86400)}d ago`
}

export default function GlobalNotificationBell() {
  const [isOpen, setIsOpen] = useState(false)
  const [notifications, setNotifications] = useState([])
  const [unreadTotalCount, setUnreadTotalCount] = useState(0)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const dropdownRef = useRef(null)

  const fetchUnreadCount = useCallback(async () => {
    try {
      const res = await fetch(`${API_BASE_URL}/messages/unread-count`, {
        headers: getAuthHeaders(),
        credentials: 'include',
      })
      if (res.ok) {
        const data = await res.json()
        setUnreadTotalCount(data.unread_count || 0)
      }
    } catch (err) {}
  }, [])

  const fetchNotifications = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await fetch(`${API_BASE_URL}/messages`, {
        headers: getAuthHeaders(),
        credentials: 'include',
      })
      if (!res.ok) throw new Error('Failed to fetch')
      const data = await res.json()
      setNotifications(data.messages || [])
      setUnreadTotalCount(data.messages?.filter(n => !n.is_read).length || 0)
    } catch (err) {
      setError('Could not load notifications')
    } finally {
      setLoading(false)
    }
  }, [])

  // Fetch count on mount and every 60s
  useEffect(() => {
    fetchUnreadCount()
    const interval = setInterval(fetchUnreadCount, 60000)
    return () => clearInterval(interval)
  }, [fetchUnreadCount])

  // Close on outside click
  useEffect(() => {
    const handleClickOutside = (e) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
        setIsOpen(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const handleOpen = () => {
    setIsOpen(prev => !prev)
    if (!isOpen) fetchNotifications()
  }

  const markAsRead = async (id) => {
    // Optimistically update UI
    setNotifications(prev =>
      prev.map(n => n.id === id ? { ...n, is_read: true } : n)
    )
    setUnreadTotalCount(prev => Math.max(0, prev - 1))
    try {
      await fetch(`${API_BASE_URL}/messages/${id}/read`, {
        method: 'POST',
        headers: getAuthHeaders(),
        credentials: 'include',
      })
    } catch (err) {
      // Revert on failure
      setNotifications(prev =>
        prev.map(n => n.id === id ? { ...n, is_read: false } : n)
      )
      setUnreadTotalCount(prev => prev + 1)
    }
  }

  const markAllAsRead = async () => {
    const unread = notifications.filter(n => !n.is_read)
    // Optimistic update
    setNotifications(prev => prev.map(n => ({ ...n, is_read: true })))
    setUnreadTotalCount(0)
    try {
      await Promise.all(
        unread.map(n =>
          fetch(`${API_BASE_URL}/messages/${n.id}/read`, {
            method: 'POST',
            headers: getAuthHeaders(),
            credentials: 'include',
          })
        )
      )
    } catch (err) {
      fetchNotifications() // Re-fetch on error
    }
  }

  return (
    <div ref={dropdownRef} style={{ position: 'relative' }}>
      {/* Bell Button */}
      <button
        onClick={handleOpen}
        title="Notifications"
        style={{
          position: 'relative',
          width: 40, height: 40, borderRadius: 10,
          border: isOpen ? '1px solid #14b8a6' : '1px solid #e2e8f0',
          background: isOpen ? 'rgba(20,184,166,0.08)' : '#fff',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          color: isOpen ? '#0d9488' : '#64748b',
          cursor: 'pointer', fontSize: 18,
          transition: 'all 0.2s ease',
          boxShadow: isOpen ? '0 0 0 3px rgba(20,184,166,0.15)' : 'none',
        }}
      >
        <FiBell />
        {unreadTotalCount > 0 && (
          <span style={{
            position: 'absolute', top: 6, right: 6,
            minWidth: 18, height: 18, borderRadius: 99,
            background: '#ef4444', border: '2px solid #fff',
            color: '#fff', fontSize: 9, fontWeight: 800,
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            padding: '0 3px', lineHeight: 1,
            animation: 'notifPulse 2s ease-in-out infinite',
          }}>
            {unreadTotalCount > 99 ? '99+' : unreadTotalCount}
          </span>
        )}
      </button>

      {/* Dropdown */}
      {isOpen && (
        <div className="global-notif-dropdown">

          {/* Header */}
          <div style={{
            padding: '16px 20px', display: 'flex', alignItems: 'center',
            justifyContent: 'space-between',
            borderBottom: '1px solid #f1f5f9',
            background: 'linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)',
          }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <FiBell style={{ color: '#0d9488', fontSize: 16 }} />
              <span style={{ fontWeight: 700, fontSize: 15, color: '#1e293b' }}>
                Notifications
              </span>
              {unreadTotalCount > 0 && (
                <span style={{
                  background: '#0d9488', color: '#fff',
                  borderRadius: 99, fontSize: 11, fontWeight: 700,
                  padding: '1px 7px',
                }}>
                  {unreadTotalCount} new
                </span>
              )}
            </div>
            <div style={{ display: 'flex', gap: 6 }}>
              {unreadTotalCount > 0 && (
                <button
                  onClick={markAllAsRead}
                  title="Mark all as read"
                  style={{
                    background: 'rgba(20,184,166,0.08)', border: 'none',
                    borderRadius: 8, padding: '4px 10px',
                    color: '#0d9488', fontSize: 11, fontWeight: 600,
                    cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 4,
                  }}
                >
                  <FiCheckCircle style={{ fontSize: 12 }} /> Mark all read
                </button>
              )}
              <button
                onClick={fetchNotifications}
                title="Refresh"
                style={{
                  background: 'none', border: 'none', borderRadius: 8,
                  padding: '4px 8px', color: '#94a3b8', cursor: 'pointer',
                  display: 'flex', alignItems: 'center',
                }}
              >
                <FiRefreshCw style={{ fontSize: 13, animation: loading ? 'spin 1s linear infinite' : 'none' }} />
              </button>
              <button
                onClick={() => setIsOpen(false)}
                style={{
                  background: 'none', border: 'none', borderRadius: 8,
                  padding: '4px 8px', color: '#94a3b8', cursor: 'pointer',
                  display: 'flex', alignItems: 'center',
                }}
              >
                <FiX style={{ fontSize: 14 }} />
              </button>
            </div>
          </div>

          {/* Body */}
          <div style={{ maxHeight: 360, overflowY: 'auto' }}>
            {loading && notifications.length === 0 ? (
              <div style={{ padding: 40, textAlign: 'center' }}>
                <div style={{
                  width: 32, height: 32, borderRadius: '50%',
                  border: '3px solid #e2e8f0', borderTopColor: '#0d9488',
                  margin: '0 auto 12px', animation: 'spin 0.8s linear infinite',
                }} />
                <div style={{ color: '#94a3b8', fontSize: 13 }}>Loading notifications...</div>
              </div>
            ) : error ? (
              <div style={{ padding: 40, textAlign: 'center' }}>
                <div style={{ fontSize: 28, marginBottom: 8 }}>⚠️</div>
                <div style={{ color: '#ef4444', fontSize: 13, fontWeight: 500 }}>{error}</div>
                <button
                  onClick={fetchNotifications}
                  style={{
                    marginTop: 12, background: '#0d9488', color: '#fff',
                    border: 'none', borderRadius: 8, padding: '6px 16px',
                    fontSize: 12, fontWeight: 600, cursor: 'pointer',
                  }}
                >
                  Retry
                </button>
              </div>
            ) : notifications.length === 0 ? (
              <div style={{ padding: 40, textAlign: 'center' }}>
                <FiMessageSquare style={{ fontSize: 36, color: '#cbd5e1', marginBottom: 10 }} />
                <div style={{ color: '#94a3b8', fontSize: 13, fontWeight: 500 }}>
                  No notifications yet
                </div>
                <div style={{ color: '#cbd5e1', fontSize: 12, marginTop: 4 }}>
                  You're all caught up!
                </div>
              </div>
            ) : (
              notifications.map((notif, idx) => (
                <div
                  key={notif.id}
                  onClick={() => !notif.is_read && markAsRead(notif.id)}
                  style={{
                    padding: '14px 20px',
                    borderBottom: idx < notifications.length - 1 ? '1px solid #f8fafc' : 'none',
                    background: notif.is_read ? '#fff' : 'linear-gradient(135deg, rgba(20,184,166,0.04) 0%, rgba(13,148,136,0.03) 100%)',
                    cursor: notif.is_read ? 'default' : 'pointer',
                    transition: 'background 0.15s ease',
                    display: 'flex', gap: 12, alignItems: 'flex-start',
                  }}
                  onMouseEnter={e => {
                    if (!notif.is_read) e.currentTarget.style.background = 'rgba(20,184,166,0.08)'
                  }}
                  onMouseLeave={e => {
                    if (!notif.is_read) e.currentTarget.style.background = 'linear-gradient(135deg, rgba(20,184,166,0.04) 0%, rgba(13,148,136,0.03) 100%)'
                  }}
                >
                  {/* Icon */}
                  <div style={{
                    width: 36, height: 36, borderRadius: 10, flexShrink: 0,
                    background: notif.is_read ? '#f1f5f9' : 'linear-gradient(135deg, #0ea5e9, #06b6d4)',
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    fontSize: 15,
                  }}>
                    <FiMessageSquare style={{ color: notif.is_read ? '#94a3b8' : '#fff' }} />
                  </div>

                  {/* Content */}
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{
                      fontSize: 13, fontWeight: notif.is_read ? 500 : 700,
                      color: notif.is_read ? '#475569' : '#1e293b',
                      marginBottom: 3,
                    }}>
                      {notif.subject}
                    </div>
                    <div style={{
                      fontSize: 12, color: '#64748b', lineHeight: 1.4,
                      display: '-webkit-box', WebkitLineClamp: 2,
                      WebkitBoxOrient: 'vertical', overflow: 'hidden',
                    }}>
                      {notif.body}
                    </div>
                    <div style={{
                      fontSize: 10, color: '#94a3b8', marginTop: 4,
                      fontWeight: 500, display: 'flex', alignItems: 'center', gap: 6,
                    }}>
                      {timeAgo(notif.created_at)}
                      {notif.is_broadcast && (
                        <span style={{
                          background: '#f0fdf4', color: '#16a34a',
                          padding: '1px 6px', borderRadius: 99, fontSize: 9, fontWeight: 700,
                        }}>
                          Broadcast
                        </span>
                      )}
                      {notif.sender && (
                        <span>From: {notif.sender.name}</span>
                      )}
                    </div>
                  </div>

                  {/* Unread dot */}
                  {!notif.is_read && (
                    <div style={{
                      width: 8, height: 8, borderRadius: '50%',
                      background: '#0d9488', flexShrink: 0, marginTop: 4,
                    }} />
                  )}
                </div>
              ))
            )}
          </div>
        </div>
      )}

      <style>{`
        .global-notif-dropdown {
          position: absolute;
          top: calc(100% + 10px);
          right: 0;
          width: 360px;
          max-height: 480px;
          background: #fff;
          border-radius: 16px;
          box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 4px 16px rgba(0,0,0,0.08);
          border: 1px solid rgba(226,232,240,0.8);
          z-index: 10000;
          overflow: hidden;
          animation: notifSlideDown 0.2s ease;
        }
        @media (max-width: 768px) {
          .global-notif-dropdown {
            position: fixed !important;
            top: 70px !important;
            left: 12px !important;
            right: 12px !important;
            width: auto !important;
            max-width: calc(100vw - 24px) !important;
            max-height: calc(100vh - 90px) !important;
            border-radius: 14px;
          }
        }
        @keyframes notifSlideDown {
          from { opacity: 0; transform: translateY(-8px) scale(0.97); }
          to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes notifPulse {
          0%, 100% { transform: scale(1); }
          50%       { transform: scale(1.15); }
        }
        @keyframes spin {
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  )
}
