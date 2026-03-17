import React, { useState, useEffect, useRef } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { FiMenu, FiX, FiChevronDown, FiChevronDown as FiChevronDownIcon } from 'react-icons/fi'
import { FaFacebook, FaInstagram, FaYoutube } from 'react-icons/fa'
import { getCurrentUser, isAuthenticated } from '../services/authService'
import AnimatedAuth from './AnimatedAuth'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './Header.css'

const Header = () => {
  const { getSetting } = useSettings()
  const { language, setLanguage, t } = useLanguage()
  const [isLangDropdownOpen, setIsLangDropdownOpen] = useState(false)
  const langDropdownRef = useRef(null)
  const [isScrolled, setIsScrolled] = useState(false)
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)
  const [isAuthOpen, setIsAuthOpen] = useState(false)
  const [authTab, setAuthTab] = useState('login')
  const [isClassesDropdownOpen, setIsClassesDropdownOpen] = useState(false)
  const [classesTimeout, setClassesTimeout] = useState(null)
  const [isLearningSuiteDropdownOpen, setIsLearningSuiteDropdownOpen] = useState(false)
  const [learningSuiteTimeout, setLearningSuiteTimeout] = useState(null)
  const [selectedGrade, setSelectedGrade] = useState(null)
  const [isUserDropdownOpen, setIsUserDropdownOpen] = useState(false)
  const [currentUser, setCurrentUser] = useState(null)
  const [isTopBarVisible, setIsTopBarVisible] = useState(true)
  const [lastScrollY, setLastScrollY] = useState(0)
  const location = useLocation()
  const classesDropdownRef = useRef(null)
  const learningSuiteDropdownRef = useRef(null)
  const userDropdownRef = useRef(null)

  useEffect(() => {
    const handleScroll = () => {
      const currentScrollY = window.scrollY

      // Update scrolled state for main header
      setIsScrolled(currentScrollY > 50)

      // Hide topbar when scrolling down, show when scrolling up
      if (currentScrollY > lastScrollY && currentScrollY > 100) {
        // Scrolling down
        setIsTopBarVisible(false)
      } else if (currentScrollY < lastScrollY) {
        // Scrolling up
        setIsTopBarVisible(true)
      }

      setLastScrollY(currentScrollY)
    }

    window.addEventListener('scroll', handleScroll, { passive: true })
    return () => window.removeEventListener('scroll', handleScroll)
  }, [lastScrollY])

  useEffect(() => {
    // Check if user is logged in
    if (isAuthenticated()) {
      setCurrentUser(getCurrentUser())
    }
  }, [])

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (classesDropdownRef.current && !classesDropdownRef.current.contains(event.target)) {
        if (classesTimeout) {
          clearTimeout(classesTimeout)
          setClassesTimeout(null)
        }
        setIsClassesDropdownOpen(false)
      }
      if (learningSuiteDropdownRef.current && !learningSuiteDropdownRef.current.contains(event.target)) {
        if (learningSuiteTimeout) {
          clearTimeout(learningSuiteTimeout)
          setLearningSuiteTimeout(null)
        }
        setIsLearningSuiteDropdownOpen(false)
        setSelectedGrade(null)
      }
      if (userDropdownRef.current && !userDropdownRef.current.contains(event.target)) {
        setIsUserDropdownOpen(false)
      }
      if (langDropdownRef.current && !langDropdownRef.current.contains(event.target)) {
        setIsLangDropdownOpen(false)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    document.addEventListener('touchstart', handleClickOutside)
    return () => {
      document.removeEventListener('mousedown', handleClickOutside)
      document.removeEventListener('touchstart', handleClickOutside)
      if (classesTimeout) {
        clearTimeout(classesTimeout)
      }
      if (learningSuiteTimeout) {
        clearTimeout(learningSuiteTimeout)
      }
    }
  }, [classesTimeout, learningSuiteTimeout])

  const handleNavClick = (e, href, isRoute = false) => {
    // Close all dropdowns when clicking other menu items
    setIsClassesDropdownOpen(false)
    setIsLearningSuiteDropdownOpen(false)
    setSelectedGrade(null)
    if (classesTimeout) {
      clearTimeout(classesTimeout)
      setClassesTimeout(null)
    }
    if (learningSuiteTimeout) {
      clearTimeout(learningSuiteTimeout)
      setLearningSuiteTimeout(null)
    }

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
    { nameKey: 'nav_home', name: getSetting('nav_home', t('nav_home')), href: '/', isRoute: true },
    { nameKey: 'nav_about', name: getSetting('nav_about', t('nav_about')), href: '/about', isRoute: true },
    { nameKey: 'nav_classes', name: getSetting('nav_classes', t('nav_classes')), href: '/classes', isRoute: true, hasDropdown: true },
    { nameKey: 'nav_learning_suite', name: getSetting('nav_learning_suite', t('nav_learning_suite')), href: '#learning-suite', isRoute: false, hasDropdown: true },
    { nameKey: 'nav_contact', name: getSetting('nav_contact', t('nav_contact')), href: '/contact', isRoute: true },
  ]

  const classesCategories = [
    { nameKey: 'nav_direct_classes', name: 'Direct classes', href: '/classes?type=direct' },
    { nameKey: 'nav_online_classes', name: 'Online classes', href: '/classes?type=online' }
  ]

  const learningSuiteGrades = [
    `${t('grade')}-1`, `${t('grade')}-2`, `${t('grade')}-3`, `${t('grade')}-4`, `${t('grade')}-5`,
    `${t('grade')}-6`, `${t('grade')}-7`, `${t('grade')}-8`, `${t('grade')}-9`, `${t('grade')}-10`, 'O/L', 'A/L'
  ]

  const gradeSubmenuItems = [
    { nameKey: 'nav_notes', name: 'Notes', href: '/notes' },
    { nameKey: 'nav_past_papers', name: 'Past papers', href: '/past-papers' },
    { nameKey: 'nav_recordings', name: 'Recording section', href: '/recordings' }
  ]

  return (
    <>
      {/* Top Bar */}
      <div className={`top-bar ${isTopBarVisible ? 'visible' : 'hidden'}`}>
        <div className="container">
          <div className="top-bar-content">
            <div className="top-bar-left">
              {(getSetting('topbar_show_fb', 'yes') === 'yes' ||
                getSetting('topbar_show_insta', 'yes') === 'yes' ||
                getSetting('topbar_show_youtube', 'yes') === 'yes') && (
                  <>
                    {getSetting('topbar_show_fb', 'yes') === 'yes' && (
                      <a
                        href={getSetting('social_facebook', 'https://facebook.com')}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="top-social-icon"
                        aria-label="Facebook"
                      >
                        <FaFacebook />
                      </a>
                    )}
                    {getSetting('topbar_show_insta', 'yes') === 'yes' && (
                      <a
                        href={getSetting('social_instagram', 'https://instagram.com')}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="top-social-icon"
                        aria-label="Instagram"
                      >
                        <FaInstagram />
                      </a>
                    )}
                    {getSetting('topbar_show_youtube', 'yes') === 'yes' && (
                      <a
                        href={getSetting('social_youtube', 'https://youtube.com')}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="top-social-icon"
                        aria-label="YouTube"
                      >
                        <FaYoutube />
                      </a>
                    )}
                    <span className="top-bar-separator"></span>
                  </>
                )}
              {getSetting('topbar_show_email', 'yes') === 'yes' && (
                <a href={`mailto:${getSetting('footer_email', 'info@titonline.lk')}`} className="top-bar-email">
                  {getSetting('footer_email', 'info@titonline.lk')}
                </a>
              )}
            </div>
            <div className="top-bar-right">
              {getSetting('topbar_show_lang', 'yes') === 'yes' && (
                <>
                  <div
                    className="top-bar-language"
                    ref={langDropdownRef}
                    onClick={() => setIsLangDropdownOpen(!isLangDropdownOpen)}
                  >
                    <span className="top-bar-language-current">
                      {language === 'en' ? t('lang_english') : language === 'ta' ? t('lang_tamil') : t('lang_sinhala')}
                    </span>
                    <FiChevronDownIcon className={`language-dropdown-icon ${isLangDropdownOpen ? 'open' : ''}`} />
                    {isLangDropdownOpen && (
                      <div className="language-dropdown-menu">
                        <button type="button" className={`language-dropdown-item ${language === 'en' ? 'active' : ''}`} onClick={() => { setLanguage('en'); setIsLangDropdownOpen(false); }}>English</button>
                        <button type="button" className={`language-dropdown-item ${language === 'ta' ? 'active' : ''}`} onClick={() => { setLanguage('ta'); setIsLangDropdownOpen(false); }}>தமிழ்</button>
                        <button type="button" className={`language-dropdown-item ${language === 'si' ? 'active' : ''}`} onClick={() => { setLanguage('si'); setIsLangDropdownOpen(false); }}>සිංහල</button>
                      </div>
                    )}
                  </div>
                  <span className="top-bar-separator top-bar-separator-lang"></span>
                </>
              )}
              {currentUser ? (
                <div
                  className="top-bar-user"
                  ref={userDropdownRef}
                  onClick={() => setIsUserDropdownOpen(!isUserDropdownOpen)}
                >
                  <span className="user-greeting">{t('hi_user')}, {currentUser.username || currentUser.name || 'User'}</span>
                  <FiChevronDownIcon className={`user-dropdown-icon ${isUserDropdownOpen ? 'open' : ''}`} />
                  {isUserDropdownOpen && (
                    <div className="user-dropdown-menu">
                      <Link to="/profile" className="user-dropdown-item">{t('nav_profile')}</Link>
                      <Link to="/settings" className="user-dropdown-item">{t('nav_settings')}</Link>
                      <button
                        className="user-dropdown-item"
                        onClick={() => {
                          localStorage.removeItem('authToken')
                          localStorage.removeItem('user')
                          setCurrentUser(null)
                          setIsUserDropdownOpen(false)
                          window.location.href = '/'
                        }}
                      >
                        {t('nav_logout')}
                      </button>
                    </div>
                  )}
                </div>
              ) : (
                <div className="top-bar-auth">
                  <button
                    className="top-bar-btn"
                    onClick={() => {
                      setAuthTab('register')
                      setIsAuthOpen(true)
                    }}
                  >
                    {t('nav_register')}
                  </button>
                  <button
                    className="top-bar-btn"
                    onClick={() => {
                      setAuthTab('login')
                      setIsAuthOpen(true)
                    }}
                  >
                    {t('nav_login')}
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Main Header */}
      <header className={`header ${isScrolled ? 'scrolled' : ''}`} style={{ top: isTopBarVisible ? '40px' : '0' }}>
        <div className="container">
          <div className="header-content">
            <Link to="/" className="logo" style={{ textDecoration: 'none' }}>
              {getSetting('logo_url') ? (
                <img src={getSetting('logo_url')} alt="Logo" className="site-logo" />
              ) : (
                <span className="logo-text">{getSetting('site_name', 'TiT')}</span>
              )}
              <span className="logo-tagline">{getSetting('site_tagline', t('logo_tagline'))}</span>
            </Link>

            <nav className={`nav ${isMobileMenuOpen ? 'open' : ''}`}>
              {menuItems.map((item, index) => (
                item.nameKey === 'nav_classes' && item.hasDropdown ? (
                  <div
                    key={index}
                    className="nav-item-dropdown"
                    ref={classesDropdownRef}
                    onMouseEnter={() => {
                      if (classesTimeout) {
                        clearTimeout(classesTimeout)
                        setClassesTimeout(null)
                      }
                      setIsClassesDropdownOpen(true)
                    }}
                    onMouseLeave={() => {
                      // Add delay before closing on desktop
                      const timeout = setTimeout(() => {
                        setIsClassesDropdownOpen(false)
                      }, 300)
                      setClassesTimeout(timeout)
                    }}
                    onTouchStart={(e) => {
                      // Prevent closing on touch
                      e.stopPropagation()
                    }}
                  >
                    <Link
                      to={item.href}
                      className={`nav-link nav-link-with-dropdown ${location.pathname === item.href ? 'active' : ''}`}
                      onClick={(e) => {
                        e.preventDefault()
                        e.stopPropagation()
                        if (classesTimeout) {
                          clearTimeout(classesTimeout)
                          setClassesTimeout(null)
                        }
                        setIsClassesDropdownOpen(!isClassesDropdownOpen)
                        setIsMobileMenuOpen(false)
                      }}
                      onTouchStart={(e) => {
                        e.stopPropagation()
                      }}
                    >
                      {item.name}
                      <FiChevronDown className={`dropdown-icon ${isClassesDropdownOpen ? 'open' : ''}`} />
                    </Link>
                    {isClassesDropdownOpen && (
                      <div
                        className="dropdown-menu classes-dropdown-menu"
                        onMouseEnter={() => {
                          if (classesTimeout) {
                            clearTimeout(classesTimeout)
                            setClassesTimeout(null)
                          }
                        }}
                        onMouseLeave={() => {
                          const timeout = setTimeout(() => {
                            setIsClassesDropdownOpen(false)
                          }, 300)
                          setClassesTimeout(timeout)
                        }}
                        onTouchStart={(e) => {
                          e.stopPropagation()
                        }}
                      >
                        {classesCategories && classesCategories.length > 0 ? (
                          classesCategories.map((category, catIndex) => (
                            <Link
                              key={catIndex}
                              to={category.href}
                              className="dropdown-item"
                              onClick={(e) => {
                                e.stopPropagation()
                                setIsClassesDropdownOpen(false)
                                setIsMobileMenuOpen(false)
                              }}
                              onTouchStart={(e) => {
                                e.stopPropagation()
                              }}
                            >
                              {t(category.nameKey)}
                            </Link>
                          ))
                        ) : (
                          <>
                            <Link
                              to="/classes?type=direct"
                              className="dropdown-item"
                              onClick={(e) => {
                                e.stopPropagation()
                                setIsClassesDropdownOpen(false)
                                setIsMobileMenuOpen(false)
                              }}
                            >
                              {t('nav_direct_classes')}
                            </Link>
                            <Link
                              to="/classes?type=online"
                              className="dropdown-item"
                              onClick={(e) => {
                                e.stopPropagation()
                                setIsClassesDropdownOpen(false)
                                setIsMobileMenuOpen(false)
                              }}
                            >
                              {t('nav_online_classes')}
                            </Link>
                          </>
                        )}
                      </div>
                    )}
                  </div>
                ) : item.nameKey === 'nav_learning_suite' && item.hasDropdown ? (
                  <div
                    key={index}
                    className="nav-item-dropdown learning-suite-dropdown"
                    ref={learningSuiteDropdownRef}
                    onMouseEnter={() => {
                      if (learningSuiteTimeout) {
                        clearTimeout(learningSuiteTimeout)
                        setLearningSuiteTimeout(null)
                      }
                      setIsLearningSuiteDropdownOpen(true)
                    }}
                    onMouseLeave={() => {
                      // Add delay before closing on desktop
                      const timeout = setTimeout(() => {
                        setIsLearningSuiteDropdownOpen(false)
                        setSelectedGrade(null)
                      }, 300)
                      setLearningSuiteTimeout(timeout)
                    }}
                    onTouchStart={(e) => {
                      // Prevent closing on touch
                      e.stopPropagation()
                    }}
                  >
                    <a
                      href={item.href}
                      className="nav-link nav-link-with-dropdown"
                      onClick={(e) => {
                        e.preventDefault()
                        e.stopPropagation()
                        if (learningSuiteTimeout) {
                          clearTimeout(learningSuiteTimeout)
                          setLearningSuiteTimeout(null)
                        }
                        setIsLearningSuiteDropdownOpen(!isLearningSuiteDropdownOpen)
                        // Don't close mobile menu on mobile - let user interact with dropdown
                        // Only close when user selects a submenu item
                      }}
                      onTouchStart={(e) => {
                        e.stopPropagation()
                      }}
                    >
                      {getSetting('learning_menu_label', t('nav_learning_suite'))}
                      <FiChevronDown className={`dropdown-icon ${isLearningSuiteDropdownOpen ? 'open' : ''}`} />
                    </a>
                    {isLearningSuiteDropdownOpen && (
                      <div
                        className="dropdown-menu learning-suite-menu"
                        onMouseEnter={() => {
                          if (learningSuiteTimeout) {
                            clearTimeout(learningSuiteTimeout)
                            setLearningSuiteTimeout(null)
                          }
                        }}
                        onMouseLeave={() => {
                          const timeout = setTimeout(() => {
                            setIsLearningSuiteDropdownOpen(false)
                            setSelectedGrade(null)
                          }, 300)
                          setLearningSuiteTimeout(timeout)
                        }}
                        onTouchStart={(e) => {
                          e.stopPropagation()
                        }}
                      >
                        {/* Submenu buttons at the top */}
                        {selectedGrade && (
                          <div className="grade-submenu-top">
                            <div className="selected-grade-title">{t('nav_selected')}: {selectedGrade}</div>
                            <div className="grade-submenu-buttons">
                              {gradeSubmenuItems.map((subItem, subIndex) => (
                                <Link
                                  key={subIndex}
                                  to={`${subItem.href}?grade=${encodeURIComponent(selectedGrade.toLowerCase().replace(/\s+/g, '-').replace(/\//g, '-'))}`}
                                  className="grade-submenu-button"
                                  onClick={(e) => {
                                    e.stopPropagation()
                                    setIsLearningSuiteDropdownOpen(false)
                                    setSelectedGrade(null)
                                    setIsMobileMenuOpen(false)
                                  }}
                                  onTouchStart={(e) => {
                                    e.stopPropagation()
                                  }}
                                >
                                  {t(subItem.nameKey)}
                                </Link>
                              ))}
                            </div>
                          </div>
                        )}

                        {/* Grade items */}
                        <div className="grades-grid">
                          {learningSuiteGrades.map((grade, gradeIndex) => (
                            <div
                              key={gradeIndex}
                              className={`dropdown-item grade-item ${selectedGrade === grade ? 'active' : ''}`}
                              onClick={(e) => {
                                e.preventDefault()
                                e.stopPropagation()
                                setSelectedGrade(selectedGrade === grade ? null : grade)
                              }}
                              onTouchStart={(e) => {
                                e.stopPropagation()
                              }}
                            >
                              {grade}
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                ) : item.isRoute ? (
                  <Link
                    key={index}
                    to={item.href}
                    className={`nav-link ${location.pathname === item.href ? 'active' : ''}`}
                    onClick={(e) => {
                      // Close Learning Suite dropdown when clicking other menu items
                      setIsLearningSuiteDropdownOpen(false)
                      setSelectedGrade(null)
                      if (learningSuiteTimeout) {
                        clearTimeout(learningSuiteTimeout)
                        setLearningSuiteTimeout(null)
                      }
                      // Close Classes dropdown too
                      setIsClassesDropdownOpen(false)
                      if (classesTimeout) {
                        clearTimeout(classesTimeout)
                        setClassesTimeout(null)
                      }
                      handleNavClick(e, item.href, true)
                    }}
                  >
                    {item.name}
                  </Link>
                ) : (
                  <a
                    key={index}
                    href={item.href}
                    className="nav-link"
                    onClick={(e) => {
                      // Close Learning Suite dropdown when clicking other menu items
                      setIsLearningSuiteDropdownOpen(false)
                      setSelectedGrade(null)
                      if (learningSuiteTimeout) {
                        clearTimeout(learningSuiteTimeout)
                        setLearningSuiteTimeout(null)
                      }
                      // Close Classes dropdown too
                      setIsClassesDropdownOpen(false)
                      if (classesTimeout) {
                        clearTimeout(classesTimeout)
                        setClassesTimeout(null)
                      }
                      handleNavClick(e, item.href, false)
                    }}
                  >
                    {item.name}
                  </a>
                )
              ))}
            </nav>

            <div className="header-actions">
              <button
                className="mobile-menu-toggle"
                onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
              >
                {isMobileMenuOpen ? <FiX /> : <FiMenu />}
              </button>
            </div>
          </div>
        </div>
      </header>

      <AnimatedAuth
        isOpen={isAuthOpen}
        onClose={() => setIsAuthOpen(false)}
        defaultTab={authTab}
      />
    </>
  )
}

export default Header

