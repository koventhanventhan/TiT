import React, { useState, useEffect, useRef, useCallback } from 'react'
import { FiBell, FiX, FiCheckCircle, FiMessageSquare, FiRefreshCw } from 'react-icons/fi'

const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'

import { getAuthHeaders } from '../../services/apiClient'

function timeAgo(dateStr) {
  const now = new Date()
  const date = new Date(dateStr)
  const diff = Math.floor((now - date) / 1000)
  if (diff < 60) return 'Just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  return `${Math.floor(diff / 86400)}d ago`
}

export default function NotificationBell({ apiPrefix = 'student' }) {
  const [isOpen, setIsOpen] = useState(false)
  const [notifications, setNotifications] = useState([])
  const [loading, setLoading] = useState(false)
  const [markingRead, setMarkingRead] = useState(false)
  const [error, setError] = useState(null)
  const dropdownRef = useRef(null)

  const unreadCount = notifications.filter(n => !n.read).length

  const fetchNotifications = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const res = await fetch(`${API_BASE_URL}/${apiPrefix}/messages`, {
        headers: getAuthHeaders(),
        credentials: 'include',
      })
      if (!res.ok) throw new Error('Failed to fetch')
      const data = await res.json()
      setNotifications(data.messages || [])
    } catch (err) {
      setError('Could not load notifications')
    } finally {
      setLoading(false)
    }
  }, [apiPrefix])

  // Fetch on mount and every 60s
  useEffect(() => {
    fetchNotifications()
    const interval = setInterval(fetchNotifications, 60000)
    return () => clearInterval(interval)
  }, [fetchNotifications])

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
    if (markingRead) return
    setMarkingRead(true)
    // Optimistically update UI
    setNotifications(prev =>
      prev.map(n => n.id === id ? { ...n, read: true } : n)
    )
    try {
      await fetch(`${API_BASE_URL}/${apiPrefix}/messages/${id}/read`, {
        method: 'POST',
        headers: getAuthHeaders(),
        credentials: 'include',
      })
    } catch (err) {
      // Revert on failure
      setNotifications(prev =>
        prev.map(n => n.id === id ? { ...n, read: false } : n)
      )
    } finally {
      setMarkingRead(false)
    }
  }

  const markAllAsRead = async () => {
    if (markingRead) return
    setMarkingRead(true)
    const unread = notifications.filter(n => !n.read)
    // Optimistic update
    setNotifications(prev => prev.map(n => ({ ...n, read: true })))
    try {
      await Promise.all(
        unread.map(n =>
          fetch(`${API_BASE_URL}/${apiPrefix}/messages/${n.id}/read`, {
            method: 'POST',
            headers: getAuthHeaders(),
            credentials: 'include',
          })
        )
      )
    } catch (err) {
      fetchNotifications() // Re-fetch on error
    } finally {
      setMarkingRead(false)
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
          width: 44, height: 44, borderRadius: 10,
          border: isOpen ? '1px solid var(--t-primary-border)' : '1px solid var(--t-border)',
          background: isOpen ? 'var(--t-primary-soft)' : '#fff',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          color: isOpen ? 'var(--t-primary)' : 'var(--t-text-3)',
          cursor: 'pointer', fontSize: 18,
          transition: 'all 0.2s ease',
          boxShadow: isOpen ? '0 0 0 3px rgba(79,11,217,0.15)' : 'none',
        }}
      >
        <FiBell />
        {unreadCount > 0 && (
          <span style={{
            position: 'absolute', top: 6, right: 6,
            minWidth: 18, height: 18, borderRadius: 99,
            background: 'var(--t-danger)', border: '2px solid #fff',
            color: '#fff', fontSize: 9, fontWeight: 800,
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            padding: '0 3px', lineHeight: 1,
            animation: 'notifPulse 2s ease-in-out infinite',
          }}>
            {unreadCount > 99 ? '99+' : unreadCount}
          </span>
        )}
      </button>

      {/* Dropdown */}
      {isOpen && (
        <div className="student-notif-dropdown">

          {/* Header */}
          <div style={{
            padding: '16px 20px', display: 'flex', alignItems: 'center',
            justifyContent: 'space-between',
            borderBottom: '1px solid var(--t-border)',
            background: 'var(--t-bg)',
          }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <FiBell style={{ color: 'var(--t-primary)', fontSize: 16 }} />
              <span style={{ fontWeight: 700, fontSize: 15, color: 'var(--t-text)' }}>
                Notifications
              </span>
              {unreadCount > 0 && (
                <span style={{
                  background: 'var(--t-primary)', color: '#fff',
                  borderRadius: 99, fontSize: 11, fontWeight: 700,
                  padding: '1px 7px',
                }}>
                  {unreadCount} new
                </span>
              )}
            </div>
            <div style={{ display: 'flex', gap: 6, alignItems: 'center' }}>
              {unreadCount > 0 && (
                <button
                  onClick={markAllAsRead}
                  disabled={markingRead}
                  title="Mark all as read"
                  style={{
                    background: 'var(--t-primary-soft)', border: 'none',
                    borderRadius: 8, padding: '6px 12px', minHeight: 44,
                    color: 'var(--t-primary)', fontSize: 12, fontWeight: 600,
                    cursor: markingRead ? 'not-allowed' : 'pointer', display: 'flex', alignItems: 'center', gap: 4,
                  }}
                >
                  <FiCheckCircle style={{ fontSize: 13 }} /> Mark all read
                </button>
              )}
              <button
                onClick={fetchNotifications}
                title="Refresh"
                aria-label="Refresh notifications"
                style={{
                  background: 'none', border: 'none', borderRadius: 8,
                  width: 44, height: 44, minWidth: 44, minHeight: 44,
                  color: 'var(--t-muted)', cursor: 'pointer',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                }}
              >
                <FiRefreshCw style={{ fontSize: 14, animation: loading ? 'spin 1s linear infinite' : 'none' }} />
              </button>
              <button
                onClick={() => setIsOpen(false)}
                aria-label="Close notifications"
                style={{
                  background: 'none', border: 'none', borderRadius: 8,
                  width: 44, height: 44, minWidth: 44, minHeight: 44,
                  color: 'var(--t-muted)', cursor: 'pointer',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                }}
              >
                <FiX style={{ fontSize: 16 }} />
              </button>
            </div>
          </div>

          {/* Body */}
          <div style={{ maxHeight: 360, overflowY: 'auto' }}>
            {loading && notifications.length === 0 ? (
              <div style={{ padding: 40, textAlign: 'center' }}>
                <div style={{
                  width: 32, height: 32, borderRadius: '50%',
                  border: '3px solid var(--t-border)', borderTopColor: 'var(--t-primary)',
                  margin: '0 auto 12px', animation: 'spin 0.8s linear infinite',
                }} />
                <div style={{ color: 'var(--t-text-3)', fontSize: 13 }}>Loading notifications...</div>
              </div>
            ) : error ? (
              <div style={{ padding: 40, textAlign: 'center' }}>
                <div style={{ fontSize: 28, marginBottom: 8 }}>⚠️</div>
                <div style={{ color: 'var(--t-danger)', fontSize: 13, fontWeight: 500 }}>{error}</div>
                <button
                  onClick={fetchNotifications}
                  style={{
                    marginTop: 12, background: 'var(--t-primary)', color: '#fff',
                    border: 'none', borderRadius: 8, padding: '6px 16px',
                    fontSize: 12, fontWeight: 600, cursor: 'pointer',
                  }}
                >
                  Retry
                </button>
              </div>
            ) : notifications.length === 0 ? (
              <div style={{ padding: 40, textAlign: 'center' }}>
                <FiMessageSquare style={{ fontSize: 36, color: 'var(--t-border)', marginBottom: 10 }} />
                <div style={{ color: 'var(--t-text-3)', fontSize: 13, fontWeight: 500 }}>
                  No notifications yet
                </div>
                <div style={{ color: 'var(--t-muted)', fontSize: 12, marginTop: 4 }}>
                  You'll see messages from your admin here
                </div>
              </div>
            ) : (
              notifications.map((notif, idx) => (
                <div
                  key={notif.id}
                  onClick={() => !notif.read && markAsRead(notif.id)}
                  style={{
                    padding: '14px 20px',
                    borderBottom: idx < notifications.length - 1 ? '1px solid var(--t-bg)' : 'none',
                    background: notif.read ? '#fff' : 'var(--t-primary-soft)',
                    cursor: notif.read ? 'default' : 'pointer',
                    transition: 'background 0.15s ease',
                    display: 'flex', gap: 12, alignItems: 'flex-start',
                  }}
                >
                  {/* Icon */}
                  <div style={{
                    width: 36, height: 36, borderRadius: 10, flexShrink: 0,
                    background: notif.read ? 'var(--t-bg)' : 'var(--t-primary)',
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    fontSize: 15,
                  }}>
                    <FiMessageSquare style={{ color: notif.read ? 'var(--t-muted)' : '#fff' }} />
                  </div>

                  {/* Content */}
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{
                      fontSize: 13, fontWeight: notif.read ? 500 : 700,
                      color: notif.read ? 'var(--t-text-2)' : 'var(--t-text)',
                      marginBottom: 3,
                      overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap',
                    }}>
                      {notif.title}
                    </div>
                    <div style={{
                      fontSize: 12, color: 'var(--t-text-3)', lineHeight: 1.4,
                      display: '-webkit-box', WebKitLineClamp: 2,
                      WebkitBoxOrient: 'vertical', overflow: 'hidden',
                    }}>
                      {notif.body}
                    </div>
                    <div style={{
                      fontSize: 10, color: 'var(--t-muted)', marginTop: 4,
                      fontWeight: 500, display: 'flex', alignItems: 'center', gap: 6,
                    }}>
                      {timeAgo(notif.created_at)}
                      {notif.target_type === 'broadcast' && (
                        <span style={{
                          background: 'var(--t-success-bg)', color: 'var(--t-success)',
                          padding: '1px 6px', borderRadius: 99, fontSize: 9, fontWeight: 700,
                        }}>
                          Broadcast
                        </span>
                      )}
                    </div>
                  </div>

                  {/* Unread dot */}
                  {!notif.read && (
                    <div style={{
                      width: 8, height: 8, borderRadius: '50%',
                      background: 'var(--t-primary)', flexShrink: 0, marginTop: 4,
                    }} />
                  )}
                </div>
              ))
            )}
          </div>

          {/* Footer */}
          {notifications.length > 0 && (
            <div style={{
              padding: '10px 20px', borderTop: '1px solid var(--t-border)',
              background: 'var(--t-bg)', textAlign: 'center',
            }}>
              <span style={{ fontSize: 12, color: 'var(--t-muted)', fontWeight: 500 }}>
                {notifications.length} total • {unreadCount} unread
              </span>
            </div>
          )}
        </div>
      )}

      <style>{`
        .student-notif-dropdown {
          position: absolute;
          top: calc(100% + 10px);
          right: 0;
          width: 320px;
          max-width: calc(100vw - 24px);
          max-height: 480px;
          background: #fff;
          border-radius: 16px;
          box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 4px 16px rgba(0,0,0,0.08);
          border: 1px solid var(--t-border);
          z-index: 10000;
          overflow: hidden;
          animation: notifSlideDown 0.2s ease;
        }
        @media (max-width: 768px) {
          .student-notif-dropdown {
            position: fixed !important;
            top: 70px !important;
            left: 12px !important;
            right: 12px !important;
            width: auto !important;
            max-width: none !important;
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
