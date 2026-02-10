import React, { useState, useEffect } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { FiMenu, FiX } from 'react-icons/fi'
import AnimatedAuth from './AnimatedAuth'
import './Header.css'

const Header = () => {
  const [isScrolled, setIsScrolled] = useState(false)
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)
  const [isAuthOpen, setIsAuthOpen] = useState(false)
  const [authTab, setAuthTab] = useState('login')
  const location = useLocation()

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 50)
    }
    window.addEventListener('scroll', handleScroll)
    return () => window.removeEventListener('scroll', handleScroll)
  }, [])

  const handleNavClick = (e, href, isRoute = false) => {
    if (!isRoute) {
      e.preventDefault()
      setIsMobileMenuOpen(false)
      
      if (href === '#home') {
        window.scrollTo({ top: 0, behavior: 'smooth' })
        return
      }

      const element = document.querySelector(href)
      if (element) {
        const headerHeight = 80
        const elementPosition = element.getBoundingClientRect().top + window.pageYOffset
        const offsetPosition = elementPosition - headerHeight

        window.scrollTo({
          top: offsetPosition,
          behavior: 'smooth'
        })
      }
    } else {
      setIsMobileMenuOpen(false)
    }
  }

  const menuItems = [
    { name: 'Home', href: '/', isRoute: true },
    { name: 'Classes', href: '/classes', isRoute: true },
    { name: 'Learning Suite', href: '#learning-suite', isRoute: false },
    { name: 'Resource Vault', href: '#resource-vault', isRoute: false },
    { name: 'AI Study Buddy', href: '#ai-study-buddy', isRoute: false },
    { name: 'Blogs', href: '/blogs', isRoute: true },
    { name: 'Contact', href: '/contact', isRoute: true },
  ]

  return (
    <header className={`header ${isScrolled ? 'scrolled' : ''}`}>
      <div className="container">
        <div className="header-content">
                <Link to="/" className="logo" style={{ textDecoration: 'none' }}>
                  <span className="logo-text">TiT</span>
                  <span className="logo-tagline">online education</span>
                </Link>

          <nav className={`nav ${isMobileMenuOpen ? 'open' : ''}`}>
            {menuItems.map((item, index) => (
              item.isRoute ? (
                <Link
                  key={index}
                  to={item.href}
                  className={`nav-link ${location.pathname === item.href ? 'active' : ''}`}
                  onClick={(e) => handleNavClick(e, item.href, true)}
                >
                  {item.name}
                </Link>
              ) : (
                <a
                  key={index}
                  href={item.href}
                  className="nav-link"
                  onClick={(e) => handleNavClick(e, item.href, false)}
                >
                  {item.name}
                </a>
              )
            ))}
          </nav>

          <div className="header-actions">
            <button 
              className="btn btn-register"
              onClick={() => {
                setAuthTab('register')
                setIsAuthOpen(true)
                setIsMobileMenuOpen(false)
              }}
            >
              Register
            </button>
            <button 
              className="btn btn-login"
              onClick={() => {
                setAuthTab('login')
                setIsAuthOpen(true)
                setIsMobileMenuOpen(false)
              }}
            >
              Login
            </button>
            <button
              className="mobile-menu-toggle"
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            >
              {isMobileMenuOpen ? <FiX /> : <FiMenu />}
            </button>
          </div>
        </div>
      </div>

      <AnimatedAuth 
        isOpen={isAuthOpen}
        onClose={() => setIsAuthOpen(false)}
        defaultTab={authTab}
      />
    </header>
  )
}

export default Header

