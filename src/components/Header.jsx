import React, { useState, useEffect, useRef } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { FiMenu, FiX, FiChevronDown, FiChevronLeft, FiChevronDown as FiChevronDownIcon, FiLayout } from 'react-icons/fi'
import { FaFacebook, FaInstagram, FaYoutube } from 'react-icons/fa'
import { getCurrentUser, isAuthenticated, logout, BASE_URL } from '../services/authService'
import AnimatedAuth from './AnimatedAuth'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import { useAuthModal } from '../context/AuthModalContext'
import './Header.css'

const Header = () => {
  const { getSetting } = useSettings()
  const { language, setLanguage, t } = useLanguage()
  const [isLangDropdownOpen, setIsLangDropdownOpen] = useState(false)
  const langDropdownRef = useRef(null)
  const [isScrolled, setIsScrolled] = useState(false)
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)
  const [isMobileLangOpen, setIsMobileLangOpen] = useState(false)
  const { isAuthOpen, openLogin, openRegister, closeAuth, authTab, setAuthTab } = useAuthModal()
  const [isClassesDropdownOpen, setIsClassesDropdownOpen] = useState(false)
  const [classesTimeout, setClassesTimeout] = useState(null)
  const [isLearningSuiteDropdownOpen, setIsLearningSuiteDropdownOpen] = useState(false)
  const [learningSuiteTimeout, setLearningSuiteTimeout] = useState(null)
  const [selectedGrade, setSelectedGrade] = useState(null)
  const [isUserDropdownOpen, setIsUserDropdownOpen] = useState(false)
  const [currentUser, setCurrentUser] = useState(null)
  const [isTopBarVisible, setIsTopBarVisible] = useState(true)
  const [lastScrollY, setLastScrollY] = useState(0)
  const [topBarHeight, setTopBarHeight] = useState(0)
  const topBarRef = useRef(null)
  const location = useLocation()
  const classesDropdownRef = useRef(null)
  const learningSuiteDropdownRef = useRef(null)
  const userDropdownRef = useRef(null)

  const getDashboardLink = () => {
    if (!currentUser) return null;
    const role = (currentUser.role || '').toLowerCase();
    if (role === 'admin') return `${BASE_URL}/admin/dashboard`;
    if (role === 'teacher') return '/teacher/dashboard';
    if (role === 'user' || role === 'student') return '/student/dashboard';
    return null;
  };

  // Measure top bar height dynamically
  useEffect(() => {
    const measureTopBar = () => {
      if (topBarRef.current) {
        setTopBarHeight(topBarRef.current.offsetHeight)
      }
    }
    measureTopBar()
    window.addEventListener('resize', measureTopBar)
    return () => window.removeEventListener('resize', measureTopBar)
  }, [])

  useEffect(() => {
    const handleScroll = () => {
      const currentScrollY = window.scrollY
      setIsScrolled(currentScrollY > 50)
      // Hide topbar on scroll down, show on scroll up
      if (currentScrollY > lastScrollY && currentScrollY > 100) {
        setIsTopBarVisible(false)
      } else if (currentScrollY < lastScrollY) {
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
      // First, check localStorage for immediate display
      const savedUser = localStorage.getItem('user')
      if (savedUser) {
        try {
          setCurrentUser(JSON.parse(savedUser))
        } catch (e) {
          console.error("Error parsing saved user:", e)
        }
      }

      // Then, fetch fresh user data from API
      const fetchUser = async () => {
        const user = await getCurrentUser()
        if (user) {
          setCurrentUser(user)
        }
      }
      fetchUser()
    }
  }, [])

  // Dynamic Favicon Update
  useEffect(() => {
    const faviconUrl = getSetting('site_favicon_url');
    if (faviconUrl) {
      let link = document.querySelector("link[rel~='icon']");
      if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.getElementsByTagName('head')[0].appendChild(link);
      }
      link.href = faviconUrl;
    }
  }, [getSetting('site_favicon_url')]);

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      // Only close desktop dropdowns if we are in desktop view
      if (window.innerWidth > 968) {
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
    { nameKey: 'nav_notes', name: getSetting('learning_notes_title', t('nav_notes') || 'Notes'), href: '/notes' },
    { nameKey: 'nav_past_papers', name: getSetting('learning_pastpapers_title', t('nav_past_papers') || 'Past papers'), href: '/past-papers' },
    { nameKey: 'nav_recordings', name: getSetting('learning_recordings_title', t('nav_recordings') || 'Recordings'), href: '/recordings' }
  ]

  return (
    <>
      {/* Top Bar Wrapper — slides UP and hides on scroll down */}
      <div
        className="top-bar-wrapper"
        ref={topBarRef}
        style={{
          transform: isTopBarVisible ? 'translateY(0)' : 'translateY(-100%)'
        }}
      >
        <div className="top-bar">
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
                  <span className="user-greeting">{t('hi_user')}, {currentUser.name || currentUser.username || 'User'}</span>
                  <FiChevronDownIcon className={`user-dropdown-icon ${isUserDropdownOpen ? 'open' : ''}`} />
                  {isUserDropdownOpen && (
                    <div className="user-dropdown-menu">
                      {getDashboardLink() && (
                        currentUser?.role?.toLowerCase() === 'admin' ? (
                          <a href={getDashboardLink()} className="user-dropdown-item" onClick={() => setIsUserDropdownOpen(false)}>
                            {t('nav_dashboard')}
                          </a>
                        ) : (
                          <Link to={getDashboardLink()} className="user-dropdown-item" onClick={() => setIsUserDropdownOpen(false)}>
                            {t('nav_dashboard')}
                          </Link>
                        )
                      )}

                      <button
                        className="user-dropdown-item"
                        onClick={async () => {
                          try {
                            await logout()
                            setCurrentUser(null)
                            setIsUserDropdownOpen(false)
                          } catch (error) {
                            console.error('Logout failed:', error)
                            // Fallback in case API logout fails
                            localStorage.removeItem('authToken')
                            localStorage.removeItem('user')
                            setCurrentUser(null)
                            setIsUserDropdownOpen(false)
                            window.location.href = '/'
                          }
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
                    onClick={openRegister}
                  >
                    {t('nav_register')}
                  </button>
                  <button
                    className="top-bar-btn"
                    onClick={openLogin}
                  >
                    {t('nav_login')}
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>{/* end top-bar */}
      </div>{/* end top-bar-wrapper */}

      {/* Main Header — always visible, slides up by topBarHeight when topbar hides */}
      <header
        className={`header ${isScrolled ? 'scrolled' : ''}`}
        style={{
          top: `${topBarHeight}px`,
          transform: isTopBarVisible ? 'translateY(0)' : `translateY(-${topBarHeight}px)`
        }}
      >
        <div className="container">
          <div className="header-content">
            <Link to="/" className="logo" style={{ textDecoration: 'none' }}>
              {getSetting('logo_url') ? (
                <img src={getSetting('logo_url')} alt="Logo" className="site-logo" />
              ) : (
                <span className="logo-text">{getSetting('site_name', 'TiT')}</span>
              )}
              {/* <span className="logo-tagline">{getSetting('site_tagline', t('logo_tagline'))}</span> */}
            </Link>

            <nav className={`nav ${isMobileMenuOpen ? 'open' : ''}`}>
              {/* Desktop Navigation (Original Structure) */}
              <div className="desktop-nav-content">
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
                        const timeout = setTimeout(() => {
                          setIsClassesDropdownOpen(false)
                        }, 300)
                        setClassesTimeout(timeout)
                      }}
                    >
                      <Link
                        to={item.href}
                        className={`nav-link nav-link-with-dropdown ${location.pathname === item.href ? 'active' : ''}`}
                        onClick={(e) => {
                          if (window.innerWidth <= 968) {
                            e.preventDefault();
                            setIsClassesDropdownOpen(!isClassesDropdownOpen);
                          } else {
                            handleNavClick(e, item.href, true);
                          }
                        }}
                      >
                        {item.name}
                        <FiChevronDown className={`dropdown-icon ${isClassesDropdownOpen ? 'open' : ''}`} />
                      </Link>
                      {isClassesDropdownOpen && (
                        <div className="dropdown-menu">
                          {classesCategories.map((category, catIndex) => (
                            <Link key={catIndex} to={category.href} className="dropdown-item" onClick={() => { setIsClassesDropdownOpen(false); setIsMobileMenuOpen(false); }}>
                              {t(category.nameKey)}
                            </Link>
                          ))}
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
                        const timeout = setTimeout(() => {
                          setIsLearningSuiteDropdownOpen(false)
                          setSelectedGrade(null)
                        }, 300)
                        setLearningSuiteTimeout(timeout)
                      }}
                    >
                      <a
                        href={item.href}
                        className="nav-link nav-link-with-dropdown"
                        onClick={(e) => {
                          if (window.innerWidth <= 968) {
                            e.preventDefault();
                            setIsLearningSuiteDropdownOpen(!isLearningSuiteDropdownOpen);
                          } else {
                            handleNavClick(e, item.href, false);
                          }
                        }}
                      >
                        {getSetting('learning_menu_label', t('nav_learning_suite'))}
                        <FiChevronDown className={`dropdown-icon ${isLearningSuiteDropdownOpen ? 'open' : ''}`} />
                      </a>
                      {isLearningSuiteDropdownOpen && (
                        <div className="dropdown-menu learning-suite-menu">
                          {selectedGrade ? (
                            <div className="grade-submenu-top">
                              <div className="selected-grade-title">{t('nav_selected')}: {selectedGrade}</div>
                              <div className="grade-submenu-buttons">
                                {gradeSubmenuItems.map((subItem, subIndex) => (
                                  <Link
                                    key={subIndex}
                                    to={`${subItem.href}?grade=${encodeURIComponent(selectedGrade.toLowerCase().replace(/\s+/g, '-').replace(/\//g, '-'))}`}
                                    className="grade-submenu-button"
                                    onClick={() => { setIsLearningSuiteDropdownOpen(false); setSelectedGrade(null); setIsMobileMenuOpen(false); }}
                                  >
                                    {subItem.name}
                                  </Link>
                                ))}
                              </div>
                            </div>
                          ) : (
                            <div className="grades-grid">
                              {learningSuiteGrades.map((grade, gradeIndex) => (
                                <div key={gradeIndex} className={`dropdown-item grade-item ${selectedGrade === grade ? 'active' : ''}`} onClick={(e) => { e.preventDefault(); setSelectedGrade(grade); }}>
                                  {grade}
                                </div>
                              ))}
                            </div>
                          )}
                        </div>
                      )}
                    </div>
                  ) : (
                    <Link
                      key={index}
                      to={item.href}
                      className={`nav-link ${location.pathname === item.href ? 'active' : ''}`}
                      onClick={(e) => handleNavClick(e, item.href, item.isRoute)}
                    >
                      {item.name}
                    </Link>
                  )
                ))}
              </div>

              {/* Mobile Menu Enhancement Content (Isolated) */}
              <div className="mobile-only-menu-content">
                <div className="mobile-top-bar">
                  <button className="mobile-top-bar-close" onClick={() => setIsMobileMenuOpen(false)}>
                    <FiX />
                  </button>
                </div>

                <div className="mobile-nav-scroll">
                  {/* 1. User Info / Auth Section */}
                  {currentUser ? (
                    <div className="mobile-section-group">
                      <div className="mobile-section-header">{t('hi_user')}, {currentUser.name || currentUser.username}</div>
                      <div className="mobile-nav-list">
                        {getDashboardLink() && (
                          currentUser?.role?.toLowerCase() === 'admin' ? (
                            <a
                              href={getDashboardLink()}
                              className="mobile-nav-link-item secondary"
                              onClick={() => setIsMobileMenuOpen(false)}
                            >
                              {t('nav_dashboard')}
                            </a>
                          ) : (
                            <Link
                              to={getDashboardLink()}
                              className="mobile-nav-link-item secondary"
                              onClick={() => setIsMobileMenuOpen(false)}
                            >
                              {t('nav_dashboard')}
                            </Link>
                          )
                        )}
                        <button className="mobile-nav-link-item logout-btn" onClick={async () => { await logout(); setCurrentUser(null); setIsMobileMenuOpen(false); }}>
                          {t('nav_logout')}
                        </button>
                      </div>
                    </div>
                  ) : (
                    <div className="mobile-section-group auth-group">
                      <button className="mobile-auth-btn-list register" onClick={() => { openRegister(); setIsMobileMenuOpen(false); }}>{t('nav_register')}</button>
                      <button className="mobile-auth-btn-list login" onClick={() => { openLogin(); setIsMobileMenuOpen(false); }}>{t('nav_login')}</button>
                    </div>
                  )}

                  {/* 2. Language Selection Toggle */}
                  {getSetting('topbar_show_lang', 'yes') === 'yes' && (
                    <div className="mobile-section-group">
                      <button
                        className="mobile-nav-link-wrapper"
                        onClick={(e) => { e.stopPropagation(); setIsMobileLangOpen(!isMobileLangOpen); }}
                      >
                        <span className="mobile-nav-link">
                          {t('language')}: {language === 'en' ? 'English' : language === 'ta' ? 'தமிழ்' : 'සිංහල'}
                        </span>
                        <FiChevronDown className={`mobile-dropdown-icon ${isMobileLangOpen ? 'open' : ''}`} />
                      </button>
                      {isMobileLangOpen && (
                        <div className="mobile-dropdown-menu">
                          <button className={`mobile-lang-link ${language === 'en' ? 'active' : ''}`} onClick={() => { setLanguage('en'); setIsMobileLangOpen(false); setIsMobileMenuOpen(false); }}>English</button>
                          <button className={`mobile-lang-link ${language === 'ta' ? 'active' : ''}`} onClick={() => { setLanguage('ta'); setIsMobileLangOpen(false); setIsMobileMenuOpen(false); }}>தமிழ்</button>
                          <button className={`mobile-lang-link ${language === 'si' ? 'active' : ''}`} onClick={() => { setLanguage('si'); setIsMobileLangOpen(false); setIsMobileMenuOpen(false); }}>සිංහල</button>
                        </div>
                      )}
                    </div>
                  )}

                  {/* 3. Main Navigation */}
                  <div className="mobile-nav-list">
                    {menuItems.map((item, index) => (
                      item.nameKey === 'nav_classes' && item.hasDropdown ? (
                        <div key={`m-${index}`} className="mobile-nav-item-dropdown">
                          <div className="mobile-nav-link-wrapper" onClick={(e) => { e.stopPropagation(); setIsClassesDropdownOpen(!isClassesDropdownOpen); }}>
                            <span className="mobile-nav-link">{item.name}</span>
                            <FiChevronDown className={`mobile-dropdown-icon ${isClassesDropdownOpen ? 'open' : ''}`} />
                          </div>
                          {isClassesDropdownOpen && (
                            <div className="mobile-dropdown-menu">
                              {classesCategories.map((category, catIndex) => (
                                <Link
                                  key={catIndex}
                                  to={category.href}
                                  className="mobile-dropdown-item"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    setIsClassesDropdownOpen(false);
                                    setIsMobileMenuOpen(false);
                                  }}
                                >
                                  {t(category.nameKey)}
                                </Link>
                              ))}
                            </div>
                          )}
                        </div>
                      ) : item.nameKey === 'nav_learning_suite' && item.hasDropdown ? (
                        <div key={`m-${index}`} className="mobile-nav-item-dropdown">
                          <div className="mobile-nav-link-wrapper" onClick={(e) => { e.stopPropagation(); setIsLearningSuiteDropdownOpen(!isLearningSuiteDropdownOpen); }}>
                            <span className="mobile-nav-link">{getSetting('learning_menu_label', t('nav_learning_suite'))}</span>
                            <FiChevronDown className={`mobile-dropdown-icon ${isLearningSuiteDropdownOpen ? 'open' : ''}`} />
                          </div>
                          {isLearningSuiteDropdownOpen && (
                            <div className="mobile-dropdown-menu learning-suite-mobile">
                              {selectedGrade ? (
                                <div className="mobile-grade-submenu">
                                  <button className="mobile-back-btn" onClick={(e) => { e.stopPropagation(); setSelectedGrade(null); }}>
                                    <FiChevronLeft /> {t('nav_back_to_grades')}
                                  </button>
                                  <div className="mobile-selected-grade">{selectedGrade}</div>
                                  {gradeSubmenuItems.map((subItem, subIndex) => (
                                    <Link
                                      key={subIndex}
                                      to={`${subItem.href}?grade=${encodeURIComponent(selectedGrade.toLowerCase().replace(/\s+/g, '-').replace(/\//g, '-'))}`}
                                      className="mobile-dropdown-item"
                                      onClick={(e) => {
                                        e.stopPropagation();
                                        setIsLearningSuiteDropdownOpen(false);
                                        setSelectedGrade(null);
                                        setIsMobileMenuOpen(false);
                                      }}
                                    >
                                      {subItem.name}
                                    </Link>
                                  ))}
                                </div>
                              ) : (
                                <div className="mobile-grades-list">
                                  {learningSuiteGrades.map((grade, gradeIndex) => (
                                    <div key={gradeIndex} className="mobile-dropdown-item grade-picker" onClick={(e) => { e.stopPropagation(); setSelectedGrade(grade); }}>
                                      {grade} <span className="arrow">→</span>
                                    </div>
                                  ))}
                                </div>
                              )}
                            </div>
                          )}
                        </div>
                      ) : (
                        <Link key={`m-${index}`} to={item.href} className="mobile-nav-link-item" onClick={(e) => handleNavClick(e, item.href, item.isRoute)}>
                          {item.name}
                        </Link>
                      )
                    ))}
                  </div>

                  {/* 4. Social Links */}
                  <div className="mobile-section-group contact-group">
                    <div className="mobile-social-row">
                      <a href={getSetting('social_facebook', '#')} className="mobile-social-link fb"><FaFacebook /></a>
                      <a href={getSetting('social_instagram', '#')} className="mobile-social-link insta"><FaInstagram /></a>
                      <a href={getSetting('social_youtube', '#')} className="mobile-social-link yt"><FaYoutube /></a>
                    </div>
                  </div>
                </div>
              </div>
            </nav>

            <div className={`header-actions ${isMobileMenuOpen ? 'mobile-menu-open' : ''}`}>
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
        onClose={closeAuth}
        defaultTab={authTab}
      />
    </>
  )
}

export default Header

