import React, { useState, useEffect } from 'react'
import { useSearchParams, Link } from 'react-router-dom'
import { FiPlay, FiBook, FiArrowRight, FiCalendar, FiLoader, FiMail, FiMapPin, FiVideo, FiExternalLink } from 'react-icons/fi'
import { FaHome, FaWhatsapp } from 'react-icons/fa'
import { getLearningMaterials } from '../services/materialService'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './RecordingsPage.css'

const RecordingsPage = () => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()
  const [searchParams] = useSearchParams()
  const grade = searchParams.get('grade') || 'All Grades'
  const [dynamicMaterials, setDynamicMaterials] = useState([])
  const [loadingMaterials, setLoadingMaterials] = useState(true)
  const isAllGrades = grade === 'All Grades' || grade === 'All'

  const [title, setTitle] = useState(getSetting('learning_recordings_title', t('recordings')))
  const [desc, setDesc] = useState(getSetting('learning_recordings_description', t('recordings_description')))
  const [missionTitle, setMissionTitle] = useState(getSetting('mission_title', t('need_more_resources_title')))
  const [missionDesc, setMissionDesc] = useState(getSetting('mission_desc', t('need_more_resources_desc')))

  useEffect(() => {
    const fetchMaterials = async () => {
      setLoadingMaterials(true)
      try {
        const params = { type: 'recording' }
        if (!isAllGrades) {
          params.grade = grade.toLowerCase().replace(' ', '-')
        }
        const data = await getLearningMaterials(params)
        setDynamicMaterials(data)
      } catch (err) {
        console.error('Error fetching papers:', err)
      } finally {
        setLoadingMaterials(false)
      }
    }
    fetchMaterials()
  }, [grade, isAllGrades])

  useEffect(() => {
    if (language !== 'en') {
      const translateAll = async () => {
        setTitle(await translate(getSetting('learning_recordings_title', t('recordings'))))
        setDesc(await translate(getSetting('learning_recordings_description', t('recordings_description'))))
        setMissionTitle(await translate(getSetting('learning_cta_title', t('need_more_resources_title'))))
        setMissionDesc(await translate(getSetting('learning_cta_desc', t('need_more_resources_desc'))))
      }
      translateAll()
    } else {
      setTitle(getSetting('learning_recordings_title', t('recordings')))
      setDesc(getSetting('learning_recordings_description', t('recordings_description')))
      setMissionTitle(getSetting('learning_cta_title', t('need_more_resources_title')))
      setMissionDesc(getSetting('learning_cta_desc', t('need_more_resources_desc')))
    }
  }, [language, translate, getSetting, t])

  const backendUrl = import.meta.env.VITE_API_URL?.replace('/api', '/') || (window.location.origin + '/')

  // Dynamic settings
  const heroImage = '/assets/images/Voicemail.svg'
  const ctaImage = '/assets/images/zxdhD87HrW.svg'
  const ctaWhatsapp = getSetting('learning_cta_whatsapp', getSetting('footer_phone', '+94 77 123 4567'))
  const ctaEmail = getSetting('learning_cta_email', getSetting('footer_email', 'info@titjafna.lk'))
  const ctaLocation = getSetting('learning_cta_location', getSetting('contact_location', 'Kokuvil, Jaffna, Sri Lanka'))
  const ctaBtnText = getSetting('learning_cta_btn', t('btn_contact') || 'Get in Touch')
  const ctaBtnLink = getSetting('learning_cta_link', '/contact')

  const formatGradeDisplay = (g) => {
    if (!g || g.toLowerCase() === 'all grades' || g.toLowerCase() === 'all-grades') return t('all_grades')
    const num = g.match(/\d+/)
    if (num) {
      return `${t('grade_prefix')} ${num[0]}`
    }
    return g.replace(/-/g, ' ').toUpperCase()
  }

  const resolveImage = (img) => {
    if (!img) return ''
    if (img.startsWith('http')) return img
    if (img.startsWith('/')) return img
    return `${backendUrl}${img}`
  }

  return (
    <div className="recordings-page">
      {/* ── Hero ── */}
      <div className="recordings-page-hero">
        <div className="container">
          <div className="rec-hero-inner">
            <div className="rec-hero-text">
              <h1 className="recordings-page-title">{title}</h1>
              <p className="recordings-page-subtitle">
                {t('recordings_for') || 'Class recordings for'} {formatGradeDisplay(grade)}
              </p>

              {/* Breadcrumb matching design */}
              <nav className="rec-breadcrumb">
                <Link to="/" className="rec-breadcrumb-home">
                  <FaHome />
                </Link>
                <span className="rec-breadcrumb-separator">/</span>
                <Link to="/recordings">{t('recordings') || 'Recordings'}</Link>
                <span className="rec-breadcrumb-separator">/</span>
                <span className="rec-breadcrumb-current">{formatGradeDisplay(grade)}</span>
              </nav>
            </div>

            <div className="rec-hero-image">
              <img src={resolveImage(heroImage)} alt="Class Recordings" />
            </div>
          </div>
        </div>

        {/* Curved bottom */}
        <div className="rec-hero-curve">
          <svg viewBox="0 0 1440 120" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
             <path d="M0,0 C480,160 960,-40 1440,80 L1440,120 L0,120 Z" fill="var(--bg-light)" />
          </svg>
        </div>
      </div>

      {/* ── Content ── */}
      <div className="container" style={{ position: 'relative', zIndex: 5, marginTop: '-1.5rem' }}>
        <div className="recordings-content">
          <div className="recordings-header">
            <div className="grade-badge">
              <div className="grade-badge-icon">
                <FiBook />
              </div>
              <span>{formatGradeDisplay(grade)}</span>
            </div>
            <p className="recordings-description">
              Watch past class recordings to <span className="highlight-text">revise your lessons.</span>
            </p>
            <div className="rec-dot-divider">
              <span className="line left" />
              <span className="diamond" />
              <span className="line right" />
            </div>
          </div>

          {/* Recording Cards */}
          <div className="recordings-grid">
            {loadingMaterials && (
              <div className="papers-loading">
                <FiLoader className="spin" />
                <p>{t('loading_recordings') || 'Loading Recordings...'}</p>
              </div>
            )}

            {!loadingMaterials && dynamicMaterials.length > 0 ? dynamicMaterials.map((recording) => (
              <div key={recording.id} className="recording-card">
                {/* Video preview area */}
                <div className="recording-card-preview">
                  {recording.url ? (
                    <div className="recording-embed-wrap">
                      {recording.url.includes('youtube.com') || recording.url.includes('youtu.be') ? (
                        <iframe
                          src={`https://www.youtube.com/embed/${recording.url.includes('youtu.be') ? recording.url.split('/').pop().split('?')[0] : new URLSearchParams(new URL(recording.url).search).get('v')}`}
                          title={recording.title}
                          frameBorder="0"
                          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                          allowFullScreen
                          className="recording-iframe"
                        />
                      ) : recording.url.includes('vimeo.com') ? (
                        <iframe
                          src={`https://player.vimeo.com/video/${recording.url.split('/').pop()}`}
                          title={recording.title}
                          frameBorder="0"
                          allow="autoplay; fullscreen; picture-in-picture"
                          allowFullScreen
                          className="recording-iframe"
                        />
                      ) : (
                        <div className="recording-url-preview">
                          <FiVideo className="recording-url-icon" />
                          <span>{t('external_video') || 'External Video'}</span>
                        </div>
                      )}
                    </div>
                  ) : recording.file_path ? (
                    <video
                      className="recording-video-player"
                      controls
                      preload="metadata"
                    >
                      <source src={`${backendUrl}${recording.file_path}`} />
                      Your browser does not support the video tag.
                    </video>
                  ) : (
                    <div className="recording-url-preview">
                      <FiVideo className="recording-url-icon" />
                      <span>{t('no_video') || 'No video available'}</span>
                    </div>
                  )}
                </div>

                {/* Recording info */}
                <div className="recording-card-info">
                  <div className="recording-info-left">
                    <div className="recording-doc-icon">
                      <FiVideo />
                    </div>
                    <div className="recording-content">
                      <h3 className="recording-title">{recording.title}</h3>
                      <div className="recording-meta-row">
                        <span className="recording-date">
                          <FiCalendar /> {new Date(recording.created_at).toLocaleDateString("en-US", { year: 'numeric', month: 'short', day: 'numeric' })}
                        </span>
                        {recording.file_size && (
                          <span className="recording-size">{recording.file_size}</span>
                        )}
                      </div>
                    </div>
                  </div>

                  <div className="recording-card-actions">
                    <div className="recording-type-tag">{recording.url ? 'URL' : 'VIDEO'}</div>
                    {recording.url ? (
                      <a
                        href={recording.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="btn-watch"
                      >
                        <FiExternalLink />
                        {t('watch') || 'Watch'}
                      </a>
                    ) : recording.file_path && (
                      <a
                        href={`${backendUrl}${recording.file_path}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="btn-watch"
                      >
                        <FiPlay />
                        {t('play') || 'Play'}
                      </a>
                    )}
                  </div>
                </div>
              </div>
            )) : !loadingMaterials && (
              <div className="no-papers">
                <p>{t('no_recordings_found') || 'No Recordings found for this grade.'}</p>
              </div>
            )}
            
          </div>

          {/* ── CTA Section — Glassmorphic Contact Banner ── */}
          <div className="recordings-cta-banner">
            <div className="cta-left">
              <div className="cta-headset-img">
                <img src={resolveImage(ctaImage)} alt="Headset" />
              </div>
              <div className="cta-text">
                <div className="cta-subtitle">{missionTitle}</div>
                <h3 className="cta-title">{getSetting('learning_cta_heading', "We're here to help you!")}</h3>
                <p className="cta-desc">
                  {missionDesc}
                </p>
              </div>
            </div>

            <div className="cta-right">
              <div className="cta-contact-box">
                {/* Grid Layout inside white box */}
                <div className="contact-item border-right border-bottom">
                  <div className="contact-icon icon-whatsapp">
                    <svg className="animated-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
                    </svg>
                  </div>
                  <div className="contact-details">
                    <span className="label">WhatsApp</span>
                    <span className="value">{ctaWhatsapp}</span>
                  </div>
                </div>

                <div className="contact-item border-bottom">
                  <div className="contact-icon icon-email">
                    <svg className="animated-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                      <rect x="2" y="4" width="20" height="16" rx="2" />
                      <path d="M22 4l-10 8L2 4" />
                    </svg>
                  </div>
                  <div className="contact-details">
                    <span className="label">Email</span>
                    <span className="value">{ctaEmail}</span>
                  </div>
                </div>

                <div className="contact-item border-right">
                  <div className="contact-icon icon-location">
                    <svg className="animated-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                      <circle cx="12" cy="10" r="3" />
                    </svg>
                  </div>
                  <div className="contact-details">
                    <span className="label">Location</span>
                    <span className="value">{ctaLocation}</span>
                  </div>
                </div>

                <div className="contact-item action-item">
                  <a href={ctaBtnLink} className="btn-get-in-touch">
                    {ctaBtnText} <FiArrowRight />
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default RecordingsPage
