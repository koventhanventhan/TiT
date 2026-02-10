import React, { useEffect, useState } from 'react'
import './CustomCursor.css'

const CustomCursor = () => {
  const [position, setPosition] = useState({ x: -100, y: -100 })
  const [isHovering, setIsHovering] = useState(false)
  const [isVisible, setIsVisible] = useState(false)

  useEffect(() => {
    const updateCursor = (e) => {
      setPosition({ x: e.clientX, y: e.clientY })
      setIsVisible(true)
      
      // Check if hovering over interactive elements
      const target = e.target
      const isInteractive = 
        target.tagName === 'A' ||
        target.tagName === 'BUTTON' ||
        target.closest('a') ||
        target.closest('button') ||
        target.closest('.btn') ||
        target.closest('input') ||
        target.closest('textarea') ||
        target.closest('select') ||
        window.getComputedStyle(target).cursor === 'pointer'
      
      setIsHovering(isInteractive)
    }

    const handleMouseLeave = () => {
      setIsVisible(false)
    }

    const handleMouseEnter = () => {
      setIsVisible(true)
    }

    // Only add on desktop/laptop (not touch devices)
    if (window.matchMedia('(pointer: fine)').matches) {
      document.addEventListener('mousemove', updateCursor)
      document.addEventListener('mouseenter', handleMouseEnter)
      document.body.addEventListener('mouseleave', handleMouseLeave)
      
      return () => {
        document.removeEventListener('mousemove', updateCursor)
        document.removeEventListener('mouseenter', handleMouseEnter)
        document.body.removeEventListener('mouseleave', handleMouseLeave)
      }
    }
  }, [])

  // Only render on non-touch devices
  if (typeof window !== 'undefined' && window.matchMedia('(pointer: coarse)').matches) {
    return null
  }

  if (!isVisible) return null

  return (
    <div className={`custom-cursor-wrapper ${isHovering ? 'cursor-hover' : ''}`}>
      <div
        className="custom-cursor-ring"
        style={{
          left: `${position.x}px`,
          top: `${position.y}px`,
        }}
      />
      <div
        className="custom-cursor-dot"
        style={{
          left: `${position.x}px`,
          top: `${position.y}px`,
        }}
      />
    </div>
  )
}

export default CustomCursor

