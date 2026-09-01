import React, { useState, useEffect } from 'react'
import { Link, useSearchParams, Navigate, useNavigate } from 'react-router-dom'
import {
  FiBook, FiGlobe, FiArrowRight, FiCheck, FiUsers, FiClock,
  FiAward, FiMonitor, FiMapPin, FiStar, FiPlay, FiFileText, FiShield,
  FiVideo, FiMessageCircle, FiHeadphones, FiFolder, FiEdit, FiCheckCircle
} from 'react-icons/fi'
import { FaGraduationCap } from 'react-icons/fa'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import { useAuthModal } from '../context/AuthModalContext'
import './ClassesPage.css'
import './ClassesPageOcl.css' // We reuse the OCL styling for the Expert Layout

// Comprehensive icon map for dynamic icon rendering
const ICON_MAP = {
  FiMonitor: <FiMonitor />, FiPlay: <FiPlay />, FiFolder: <FiFolder />,
  FiFileText: <FiFileText />, FiMessageCircle: <FiMessageCircle />,
  FiShield: <FiShield />, FiVideo: <FiVideo />, FiUsers: <FiUsers />,
  FiHeadphones: <FiHeadphones />, FiBook: <FiBook />, FiGlobe: <FiGlobe />,
  FiEdit: <FiEdit />, FiStar: <FiStar />, FiClock: <FiClock />,
  FiMapPin: <FiMapPin />, FiAward: <FiAward />, FaGraduationCap: <FaGraduationCap />,
  FiCheck: <FiCheck />, FiCheckCircle: <FiCheckCircle />, FiArrowRight: <FiArrowRight />
}

const ClassesPage = () => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const type = searchParams.get('type') || 'online' // Default to online if missing

  // ── Shared Expert Layout State ──
  const [activeGrade, setActiveGrade] = useState(0)
  const [showAllSubjects, setShowAllSubjects] = useState(false)

  // ── Online Classes State (ocl_) ──
  const [oclText, setOclText] = useState({
    tagline: getSetting('ocl_tagline') || 'TiT KALVI NILAYAM – JAFFNA',
    titleLine1: getSetting('ocl_title_line1') || 'ONLINE',
    titleLine2: getSetting('ocl_title_line2') || 'CLASSES',
    subtitleTamil: getSetting('ocl_subtitle_tamil') || 'இணையவழி வகுப்புகள்',
    subText: getSetting('ocl_sub_text') || 'தரம் 01 முதல் A/L வரை',
    ctaText: getSetting('ocl_cta_text') || 'இப்போது சேருங்கள்',
    gradesTitle: getSetting('ocl_grades_title') || 'தரம் தேர்வு செய்க',
    subjectsTitle: getSetting('ocl_subjects_title') || 'Subjects',
    subjectsLink: getSetting('ocl_subjects_link_text') || 'All Subjects',
  })

  // ── Direct Classes State (dcl_) ──
  const [dclText, setDclText] = useState({
    tagline: getSetting('dcl_tagline') || 'TiT KALVI NILAYAM – JAFFNA',
    titleLine1: getSetting('dcl_title_line1') || 'DIRECT',
    titleLine2: getSetting('dcl_title_line2') || 'CLASSES',
    subtitleTamil: getSetting('dcl_subtitle_tamil') || 'நேரடி வகுப்புகள்',
    subText: getSetting('dcl_sub_text') || 'தரம் 01 முதல் A/L வரை',
    ctaText: getSetting('dcl_cta_text') || 'இப்போது சேருங்கள்',
    gradesTitle: getSetting('dcl_grades_title') || 'தரம் தேர்வு செய்க',
    subjectsTitle: getSetting('dcl_subjects_title') || 'Subjects',
    subjectsLink: getSetting('dcl_subjects_link_text') || 'All Subjects',
  })

  const getImageUrl = (img) => {
    if (!img) return ''
    const baseUrl = import.meta.env.VITE_API_URL ? import.meta.env.VITE_API_URL.replace('/api', '') : ''
    return img.startsWith('http') ? img : `${baseUrl}/${img.replace(/^\/+/, '')}`
  }

  // ── Translation Effect ──
  useEffect(() => {
    const translateLayouts = async () => {
      if (language !== 'en') {
        setOclText({
          tagline: await translate(getSetting('ocl_tagline') || 'TiT KALVI NILAYAM – JAFFNA'),
          titleLine1: await translate(getSetting('ocl_title_line1') || 'ONLINE'),
          titleLine2: await translate(getSetting('ocl_title_line2') || 'CLASSES'),
          subtitleTamil: await translate(getSetting('ocl_subtitle_tamil') || 'இணையவழி வகுப்புகள்'),
          subText: await translate(getSetting('ocl_sub_text') || 'தரம் 01 முதல் A/L வரை'),
          ctaText: await translate(getSetting('ocl_cta_text') || 'இப்போது சேருங்கள்'),
          gradesTitle: await translate(getSetting('ocl_grades_title') || 'தரம் தேர்வு செய்க'),
          subjectsTitle: await translate(getSetting('ocl_subjects_title') || 'Subjects'),
          subjectsLink: await translate(getSetting('ocl_subjects_link_text') || 'All Subjects')
        })
        setDclText({
          tagline: await translate(getSetting('dcl_tagline') || 'TiT KALVI NILAYAM – JAFFNA'),
          titleLine1: await translate(getSetting('dcl_title_line1') || 'DIRECT'),
          titleLine2: await translate(getSetting('dcl_title_line2') || 'CLASSES'),
          subtitleTamil: await translate(getSetting('dcl_subtitle_tamil') || 'நேரடி வகுப்புகள்'),
          subText: await translate(getSetting('dcl_sub_text') || 'தரம் 01 முதல் A/L வரை'),
          ctaText: await translate(getSetting('dcl_cta_text') || 'இப்போது சேருங்கள்'),
          gradesTitle: await translate(getSetting('dcl_grades_title') || 'தரம் தேர்வு செய்க'),
          subjectsTitle: await translate(getSetting('dcl_subjects_title') || 'Subjects'),
          subjectsLink: await translate(getSetting('dcl_subjects_link_text') || 'All Subjects')
        })
      } else {
        setOclText({
          tagline: getSetting('ocl_tagline') || 'TiT KALVI NILAYAM – JAFFNA',
          titleLine1: getSetting('ocl_title_line1') || 'ONLINE',
          titleLine2: getSetting('ocl_title_line2') || 'CLASSES',
          subtitleTamil: getSetting('ocl_subtitle_tamil') || 'இணையவழி வகுப்புகள்',
          subText: getSetting('ocl_sub_text') || 'தரம் 01 முதல் A/L வரை',
          ctaText: getSetting('ocl_cta_text') || 'இப்போது சேருங்கள்',
          gradesTitle: getSetting('ocl_grades_title') || 'தரம் தேர்வு செய்க',
          subjectsTitle: getSetting('ocl_subjects_title') || 'Subjects',
          subjectsLink: getSetting('ocl_subjects_link_text') || 'All Subjects'
        })
        setDclText({
          tagline: getSetting('dcl_tagline') || 'TiT KALVI NILAYAM – JAFFNA',
          titleLine1: getSetting('dcl_title_line1') || 'DIRECT',
          titleLine2: getSetting('dcl_title_line2') || 'CLASSES',
          subtitleTamil: getSetting('dcl_subtitle_tamil') || 'நேரடி வகுப்புகள்',
          subText: getSetting('dcl_sub_text') || 'தரம் 01 முதல் A/L வரை',
          ctaText: getSetting('dcl_cta_text') || 'இப்போது சேருங்கள்',
          gradesTitle: getSetting('dcl_grades_title') || 'தரம் தேர்வு செய்க',
          subjectsTitle: getSetting('dcl_subjects_title') || 'Subjects',
          subjectsLink: getSetting('dcl_subjects_link_text') || 'All Subjects'
        })
      }
    }
    translateLayouts()
  }, [language, translate, getSetting])

  // ── JSON Parsers for OCL ──
  const parseSafe = (val, fallback) => {
    try { return JSON.parse(val) } catch (e) { return fallback }
  }

  const oclHeroImage = getSetting('ocl_hero_image')
  const oclBadgeImage = getSetting('ocl_badge_image')
  const oclHighlights = (getSetting('ocl_highlights') || "LIVE CLASSES\nRECORDED CLASSES\nSTUDY MATERIAL").split('\n').filter(Boolean)
  const oclGrades = (getSetting('ocl_grades') || '01,02,03,04,05,06,07,08,09,10,11,A/L').split(',').map(g => g.trim())
  const oclFeatures = parseSafe(getSetting('ocl_features'), [
    { icon: "FiMonitor", title: "நேரலை வகுப்புகள்", description: "அனுபவமிக்க ஆசிரியர்களின் நேரலை வகுப்புகள்" },
    { icon: "FiPlay", title: "வகுப்பு பதிவு", description: "பதிவு செய்யப்பட்ட வகுப்புகளை மீண்டும் பார்க்கலாம்" },
    { icon: "FiFolder", title: "கல்வி பொருட்கள்", description: "PDF குறிப்புகள் & தேவையான படிப்பு பொருட்கள்" }
  ])
  const oclStats = parseSafe(getSetting('ocl_stats'), [
    { icon: "FaGraduationCap", number: "3000+", label: "மாணவர்கள்" },
    { icon: "FiUsers", number: "30+", label: "ஆசிரியர்கள்" },
    { icon: "FiPlay", number: "500+", label: "வீடியோ வகுப்புகள்" },
    { icon: "FiHeadphones", number: "24/7", label: "ஆதரவு" }
  ])
  const oclSubjects = parseSafe(getSetting('ocl_subjects'), [
    { icon: "FiBook", name: "Tamil" }, { icon: "FiGlobe", name: "English" },
    { icon: "FiEdit", name: "Mathematics" }, { icon: "FiStar", name: "Science" },
    { icon: "FiClock", name: "History" }, { icon: "FiMapPin", name: "Geography" }
  ])

  // ── JSON Parsers for DCL ──
  const dclHeroImage = getSetting('dcl_hero_image')
  const dclBadgeImage = getSetting('dcl_badge_image')
  const dclHighlights = (getSetting('dcl_highlights') || "IN-PERSON CLASSES\nEXPERT TUTORS\nSTUDY MATERIAL").split('\n').filter(Boolean)
  const dclGrades = (getSetting('dcl_grades') || '01,02,03,04,05,06,07,08,09,10,11,A/L').split(',').map(g => g.trim())
  const dclFeatures = parseSafe(getSetting('dcl_features'), [
    { icon: "FiUsers", title: "சிறு குழுக்கள்", description: "சிறிய குழுக்கள் மூலம் ஆசிரியரின் தனிப்பட்ட கவனம்" },
    { icon: "FiCheck", title: "நேரடி கற்றல்", description: "ஆசிரியர்களுடன் நேரடி தொடர்பு மற்றும் உரையாடல்" },
    { icon: "FiFolder", title: "கற்றல் உபகரணங்கள்", description: "தேவையான அனைத்து பௌதீக கற்றல் பொருட்களும் வழங்கப்படும்" }
  ])
  const dclStats = parseSafe(getSetting('dcl_stats'), [
    { icon: "FaGraduationCap", number: "2000+", label: "மாணவர்கள்" },
    { icon: "FiUsers", number: "25+", label: "ஆசிரியர்கள்" },
    { icon: "FiBook", number: "15+", label: "பாடநெறிகள்" },
    { icon: "FiAward", number: "100%", label: "வெற்றி" }
  ])
  const dclSubjects = parseSafe(getSetting('dcl_subjects'), [
    { icon: "FiBook", name: "Tamil" }, { icon: "FiGlobe", name: "English" },
    { icon: "FiEdit", name: "Mathematics" }, { icon: "FiStar", name: "Science" },
    { icon: "FiClock", name: "History" }, { icon: "FiMapPin", name: "Geography" }
  ])

  // Guard for invalid type parameter
  if (type !== 'online' && type !== 'direct') {
    return <Navigate to="/classes?type=online" replace />
  }

  // Define references to the correct scoped data based on type
  const isOnline = type === 'online'
  const currentText = isOnline ? oclText : dclText
  const currentHighlights = isOnline ? oclHighlights : dclHighlights
  const currentHeroImage = isOnline ? oclHeroImage : dclHeroImage
  const currentBadgeImage = isOnline ? oclBadgeImage : dclBadgeImage
  const currentGrades = isOnline ? oclGrades : dclGrades
  const currentFeatures = isOnline ? oclFeatures : dclFeatures
  const currentStats = isOnline ? oclStats : dclStats
  const currentSubjects = isOnline ? oclSubjects : dclSubjects

  return (
    <div className="ocl">
      {/* ══════ HERO SECTION ══════ */}
      <section className={`ocl-hero ${!isOnline ? 'dcl-hero-variant' : ''}`}>
        <div className="ocl-hero-dots"></div>
        <div className="container">
          <div className="ocl-hero-layout">
            {/* Left - Text Content */}
            <div className="ocl-hero-content">
              <span className="ocl-tagline">{currentText.tagline}</span>
              <h1 className="ocl-title">
                <span className="ocl-title-gold">{currentText.titleLine1}</span>{' '}
                <span className="ocl-title-white">{currentText.titleLine2}</span>
              </h1>
              <h2 className="ocl-subtitle-tamil">{currentText.subtitleTamil}</h2>
              <p className="ocl-sub-text">{currentText.subText}</p>
              <div className="ocl-hero-highlights">
                {currentHighlights.map((h, i) => (
                  <React.Fragment key={i}>
                    {i > 0 && <span className="ocl-highlight-sep">|</span>}
                    <span className="ocl-hero-highlight">
                      {isOnline && i === 1 ? <FiPlay /> : <FiCheck />} {h}
                    </span>
                  </React.Fragment>
                ))}
              </div>
              <button onClick={() => navigate('/register')} className="ocl-cta-btn">
                <FiCheckCircle /> {currentText.ctaText} <FiArrowRight />
              </button>
            </div>

            {/* Right - Hero Media */}
            <div className="ocl-hero-media">
              <div className="ocl-live-indicator" style={!isOnline ? { background: 'rgba(235, 129, 83, 0.2)', color: '#EB8153', border: '1px solid rgba(235, 129, 83, 0.3)' } : {}}>
                <span className="ocl-live-dot" style={!isOnline ? { background: '#EB8153' } : {}}></span>
                {isOnline ? 'LIVE' : 'IN-PERSON'}
              </div>
              {currentHeroImage ? (
                <img src={getImageUrl(currentHeroImage)} alt={`${currentText.titleLine1} Classes`} className="ocl-laptop-img" />
              ) : (
                <div className="ocl-hero-placeholder">
                  {isOnline ? <FiMonitor /> : <FiUsers />}
                  <span>Upload Hero Image from Admin</span>
                </div>
              )}
              {currentBadgeImage && (
                <img src={getImageUrl(currentBadgeImage)} alt="Quality Badge" className="ocl-badge-img" />
              )}
            </div>
          </div>
        </div>
      </section>

      {/* ══════ GRADE SELECTOR ══════ */}
      <section className="ocl-grades-section">
        <div className="container">
          <div className="ocl-grades-bar">
            <div className="ocl-grades-label">
              <FiBook /> <span>{currentText.gradesTitle}</span>
            </div>
            <div className="ocl-grade-pills">
              {currentGrades.map((g, i) => (
                <button
                  key={i}
                  className={`ocl-grade-pill ${i === activeGrade ? 'active' : ''}`}
                  onClick={() => setActiveGrade(i)}
                >
                  {g}
                </button>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* ══════ FEATURES GRID ══════ */}
      <section className="ocl-features-section">
        <div className="container">
          <div className="ocl-features-grid">
            {currentFeatures.map((f, i) => (
              <div key={i} className="ocl-feature-card">
                <div className="ocl-feature-icon">
                  {ICON_MAP[f.icon] || <FiCheck />}
                </div>
                <h3 className="ocl-feature-title">{f.title}</h3>
                <p className="ocl-feature-desc">{f.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ══════ STATS BANNER ══════ */}
      <section className="ocl-stats-section">
        <div className="container">
          <div className="ocl-stats-grid">
            {currentStats.map((s, i) => (
              <div key={i} className="ocl-stat-item">
                <div className="ocl-stat-icon">
                  {ICON_MAP[s.icon] || <FiStar />}
                </div>
                <div className="ocl-stat-info">
                  <strong>{s.number}</strong>
                  <span>{s.label}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ══════ SUBJECTS ROW ══════ */}
      <section className="ocl-subjects-section">
        <div className="container">
          <div className="ocl-subjects-header">
            <h3>{currentText.subjectsTitle}</h3>
            {currentSubjects.length > 6 && (
              <button 
                onClick={() => setShowAllSubjects(!showAllSubjects)}
                className="ocl-subjects-link"
                style={{ background: 'transparent', border: 'none', cursor: 'pointer', fontFamily: 'inherit' }}
              >
                {showAllSubjects ? 'Show Less' : currentText.subjectsLink} <FiArrowRight style={{ transform: showAllSubjects ? 'rotate(-90deg)' : 'none', transition: '0.3s' }} />
              </button>
            )}
          </div>
          <div className="ocl-subjects-grid">
            {(showAllSubjects ? currentSubjects : currentSubjects.slice(0, 6)).map((s, i) => (
              <div key={i} className="ocl-subject-chip">
                <div className="ocl-subject-icon">
                  {ICON_MAP[s.icon] || <FiBook />}
                </div>
                <span>{s.name}</span>
              </div>
            ))}
          </div>
        </div>
      </section>
    </div>
  )
}

export default ClassesPage
