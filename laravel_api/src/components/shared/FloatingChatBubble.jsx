import React from 'react'
import { FaWhatsapp } from 'react-icons/fa'
import { FiMessageSquare } from 'react-icons/fi'

/**
 * FloatingChatBubble
 * WhatsApp / Chat support bubble pinned to bottom-right of viewport.
 * 
 * Rules:
 * - Position: right: 16px, bottom: calc(16px + env(safe-area-inset-bottom))
 * - Size: 52px x 52px
 * - Color: #25D366 (WhatsApp green)
 * - z-index: 35 (below drawer z-index 50/999 and modal z-index 1000)
 * - Hidden when drawer or any modal is open
 * - Never overlaps the left sidebar or logout
 */
export default function FloatingChatBubble({ isDrawerOpen = false, whatsappNumber = '+94771234567' }) {
  if (isDrawerOpen) return null

  const handleClick = () => {
    const cleanNum = whatsappNumber.replace(/[^0-9]/g, '')
    const url = `https://wa.me/${cleanNum}?text=${encodeURIComponent('Hello, I need help with TiT Education.')}`
    window.open(url, '_blank', 'noopener,noreferrer')
  }

  return (
    <button
      type="button"
      className="floating-chat-bubble"
      aria-label="WhatsApp support"
      title="Chat support"
      onClick={handleClick}
      style={{
        position: 'fixed',
        right: 16,
        bottom: 'calc(16px + env(safe-area-inset-bottom, 0px))',
        width: 52,
        height: 52,
        minWidth: 52,
        minHeight: 52,
        borderRadius: '50%',
        background: '#25D366',
        color: '#ffffff',
        border: 'none',
        boxShadow: '0 4px 14px rgba(37, 211, 102, 0.4)',
        cursor: 'pointer',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontSize: 26,
        zIndex: 35,
        touchAction: 'manipulation',
        WebkitTapHighlightColor: 'transparent',
        transition: 'transform 0.15s ease, box-shadow 0.15s ease',
      }}
      onMouseEnter={e => {
        e.currentTarget.style.transform = 'scale(1.06)'
        e.currentTarget.style.boxShadow = '0 6px 20px rgba(37, 211, 102, 0.55)'
      }}
      onMouseLeave={e => {
        e.currentTarget.style.transform = 'scale(1)'
        e.currentTarget.style.boxShadow = '0 4px 14px rgba(37, 211, 102, 0.4)'
      }}
    >
      <FaWhatsapp style={{ fontSize: 28 }} />
    </button>
  )
}
