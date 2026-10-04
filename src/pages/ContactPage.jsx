import React, { useState, useEffect } from 'react'
import {
  FiMail, FiPhone, FiMapPin, FiSend, FiMessageCircle,
  FiUser, FiMessageSquare, FiClock, FiGlobe,
  FiFacebook, FiInstagram, FiArrowRight,
  FiCheckCircle, FiHeadphones, FiHeart
} from 'react-icons/fi'
import { FaWhatsapp, FaYoutube, FaTiktok } from 'react-icons/fa'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './ContactPage.css'

const ContactPage = () => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()

  const [formData, setFormData] = useState({
    name: '', email: '', phone: '', subject: '', message: ''
  })
  const [isSubmitted, setIsSubmitted] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [submitError, setSubmitError] = useState('')
  const [focusedField, setFocusedField] = useState(null)

  const handleChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({ ...prev, [name]: value }))
    setSubmitError('')
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setIsLoading(true)
    setSubmitError('')

    try {
      const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'
      const response = await fetch(`${API_BASE_URL}/contact`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
      })

      const result = await response.json()

      if (response.ok && result.success) {
        setIsSubmitted(true)
        setFormData({ name: '', email: '', phone: '', subject: '', message: '' })
      } else {
        setSubmitError(result.message || 'Failed to send message. Please try again.')
      }
    } catch (err) {
      console.error('Contact form error:', err)
      setSubmitError('Network error. Please check your connection and try again.')
    } finally {
      setIsLoading(false)
    }
  }

  const [heroTitle, setHeroTitle] = useState(getSetting('contact_hero_title', t('contact_hero_title')))
  const [heroDesc, setHeroDesc] = useState(getSetting('contact_hero_desc', t('contact_hero_desc')))
  const [missionTitle, setMissionTitle] = useState(getSetting('contact_urgent_title', t('urgent_help')))
  const [missionDesc, setMissionDesc] = useState(getSetting('contact_urgent_desc', t('contact_urgent_desc')))
  const [socialTitle, setSocialTitle] = useState(t('footer_social_title'))
  const [mapTitle, setMapTitle] = useState(t('footer_map_title'))
  const [mapDesc, setMapDesc] = useState(t('footer_map_desc'))

  const [location, setLocation] = useState(getSetting('contact_location', t('footer_location')))
  const [pinName, setPinName] = useState(getSetting('contact_map_pin_name', 'TiT Online Education'))

  const footerPhone = getSetting('footer_phone', '+94 114 477 488')
  const footerEmail = getSetting('footer_email', 'info@edulearn.lk')

  // Helper to extract src if full iframe tag is provided
  const extractMapSrc = (input) => {
    if (!input || typeof input !== 'string') return 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126743.58272!2d79.8!3d6.9!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae2593cf!2sColombo!5e0!3m2!1sen!2slk!4v1620000000000!5m2!1sen!2slk'
    if (input.includes('<iframe')) {
      const match = input.match(/src="([^"]+)"/)
      return match ? match[1] : input
    }
    return input
  }

  const mapEmbedLink = extractMapSrc(getSetting('contact_map_embed_link'))

  const supportHours = [
    { day: t('mon_fri'), time: getSetting('support_hours_mon_fri', '9:00 AM - 6:00 PM'), open: true },
    { day: t('sat'), time: getSetting('support_hours_sat', '9:00 AM - 2:00 PM'), open: true },
    { day: t('sun'), time: getSetting('support_hours_sun', t('closed') || 'Closed'), open: false }
  ]

  useEffect(() => {
    const defaultEn = {
      hero_title: 'Let\'s Start a Conversation',
      hero_desc: "Have questions about our courses? Want to enroll? We're here to help.",
      urgent_title: 'Need Urgent Help?',
      urgent_desc: 'Call our support line directly for immediate assistance.',
      social_title: 'Connect With Us',
      map_title: 'Our Location',
      map_desc: 'Find us on the map or visit our office directly.',
      location: 'Colombo, Sri Lanka',
      pin_name: 'TiT Online Education'
    }

    if (language !== 'en') {
      const translateAll = async () => {
        const valHeroT = getSetting('contact_hero_title', defaultEn.hero_title)
        if (valHeroT === defaultEn.hero_title) setHeroTitle(t('contact_hero_title'))
        else setHeroTitle(await translate(valHeroT))

        const valHeroD = getSetting('contact_hero_desc', defaultEn.hero_desc)
        if (valHeroD === defaultEn.hero_desc) setHeroDesc(t('contact_hero_desc'))
        else setHeroDesc(await translate(valHeroD))

        const valUrgentT = getSetting('contact_urgent_title', defaultEn.urgent_title)
        if (valUrgentT === defaultEn.urgent_title) setMissionTitle(t('urgent_help'))
        else setMissionTitle(await translate(valUrgentT))

        const valUrgentD = getSetting('contact_urgent_desc', defaultEn.urgent_desc)
        if (valUrgentD === defaultEn.urgent_desc) setMissionDesc(t('contact_urgent_desc'))
        else setMissionDesc(await translate(valUrgentD))

        const valLoc = getSetting('contact_location', defaultEn.location)
        if (valLoc === defaultEn.location) setLocation(t('footer_location'))
        else setLocation(await translate(valLoc))

        const valPin = getSetting('contact_map_pin_name', defaultEn.pin_name)
        if (valPin === defaultEn.pin_name) setPinName('TiT') // Keep TiT short or translate
        else setPinName(await translate(valPin))

        setSocialTitle(t('footer_social_title'))
        setMapTitle(t('footer_map_title'))
        setMapDesc(t('footer_map_desc'))
      }
      translateAll()
    } else {
      setHeroTitle(getSetting('contact_hero_title', t('contact_hero_title')))
      setHeroDesc(getSetting('contact_hero_desc', t('contact_hero_desc')))
      setMissionTitle(getSetting('contact_urgent_title', t('urgent_help')))
      setMissionDesc(getSetting('contact_urgent_desc', t('contact_urgent_desc')))
      setLocation(getSetting('contact_location', t('footer_location')))
      setPinName(getSetting('contact_map_pin_name', 'TiT Online Education'))
      setSocialTitle(t('footer_social_title'))
      setMapTitle(t('footer_map_title'))
      setMapDesc(t('footer_map_desc'))
    }
  }, [language, translate, getSetting, t])
  
  // Handle scrolling to hash anchor on mount or hash change
  useEffect(() => {
    if (window.location.hash === '#map-section') {
      const element = document.getElementById('map-section');
      if (element) {
        setTimeout(() => {
          element.scrollIntoView({ behavior: 'smooth' });
        }, 500); // Small delay to ensure page is rendered
      }
    }
  }, []);

  const socialLinks = [
    { icon: <FiFacebook />, name: 'Facebook', link: getSetting('social_facebook', '#') },
    { icon: <FiInstagram />, name: 'Instagram', link: getSetting('social_instagram', '#') },
    { icon: <FaWhatsapp />, name: 'WhatsApp', link: getSetting('social_whatsapp', '#') },
    { icon: <FaYoutube />, name: 'YouTube', link: getSetting('social_youtube', '#') },
    { icon: <FaTiktok />, name: 'TikTok', link: getSetting('social_tiktok', '#') }
  ].filter(social => social.link && social.link !== '#')

  return (
    <div className="cp">
      {/* ═══ HERO: Dark immersive split ═══ */}
      <section className="cp-hero">
        <div className="cp-hero-bg-effects">
          <div className="cp-glow cp-glow-1"></div>
          <div className="cp-glow cp-glow-2"></div>
          <div className="cp-grid-lines"></div>
        </div>

        <div className="container cp-hero-inner">
          <div className="cp-hero-left">
            <div className="cp-badge"><FiMessageCircle /> {t('contact_hero_badge')}</div>
            <h1 className="cp-title">
              {heroTitle}
            </h1>
            <p className="cp-desc">
              {heroDesc}
            </p>

            {/* Quick contact row */}
            <div className="cp-quick-row">
              <a href={`tel:${footerPhone.replace(/\s/g, '')}`} className="cp-quick-item">
                <div className="cp-quick-icon"><FiPhone /></div>
                <div>
                  <span className="cp-quick-label">{t('contact_call_label')}</span>
                  <span className="cp-quick-val">{footerPhone}</span>
                </div>
              </a>
              <a href={`mailto:${footerEmail}`} className="cp-quick-item">
                <div className="cp-quick-icon"><FiMail /></div>
                <div>
                  <span className="cp-quick-label">{t('contact_email_label')}</span>
                  <span className="cp-quick-val">{footerEmail}</span>
                </div>
              </a>
              <div className="cp-quick-item">
                <div className="cp-quick-icon"><FiMapPin /></div>
                <div>
                  <span className="cp-quick-label">{t('contact_visit_label')}</span>
                  <span className="cp-quick-val">{location}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Right side: visual illustration */}
          <div className="cp-hero-right">
            <div className="cp-hero-visual">
              <div className="cp-visual-card pc-vc-1">
                <FiHeadphones />
                <div>
                  <strong>{getSetting('contact_stat1_label', t('contact_support_24_7'))}</strong>
                  <span>{getSetting('contact_stat1_desc', t('contact_always_available'))}</span>
                </div>
              </div>
              <div className="cp-visual-card cp-vc-2">
                <FiHeart />
                <div>
                  <strong>{getSetting('stats_students', '10,000')} {t('students')}</strong>
                  <span>{t('happy_students')}</span>
                </div>
              </div>
              <div className="cp-visual-card cp-vc-3">
                <FiCheckCircle />
                <div>
                  <strong>{getSetting('stats_success_rate', '98%')} {t('satisfaction')}</strong>
                  <span>{t('success_rate')}</span>
                </div>
              </div>
              <div className="cp-visual-ring"></div>
              <div className="cp-visual-ring cp-ring-2"></div>
            </div>
          </div>
        </div>
      </section>

      {/* ═══ FORM + SIDEBAR ═══ */}
      <section className="cp-body">
        <div className="container">
          <div className="cp-body-grid">
            {/* ── FORM ── */}
            <div className="cp-form-card">
              <div className="cp-form-top">
                <h2><FiMessageSquare /> {t('contact_form_title')}</h2>
                <p>{t('contact_form_desc')}</p>
              </div>

              {isSubmitted ? (
                <div className="cp-done">
                  <div className="cp-done-circle"><FiCheckCircle /></div>
                  <h3>{t('message_sent') || 'Message Sent!'}</h3>
                  <p>{t('message_sent_desc') || "Thanks for reaching out. We'll get back to you soon."}</p>
                </div>
              ) : (
                <form onSubmit={handleSubmit} className="cp-form">
                  {submitError && <div className="error-message" style={{ color: '#ef4444', background: 'rgba(239,68,68,0.1)', padding: '0.75rem 1rem', borderRadius: '8px', marginBottom: '1rem', fontSize: '0.9rem' }}>{submitError}</div>}
                  <div className="cp-row">
                    <div className={`cp-input-group ${focusedField === 'name' ? 'active' : ''}`}>
                      <label><FiUser /> {t('label_name')}</label>
                      <input type="text" name="name" value={formData.name}
                        onChange={handleChange}
                        onFocus={() => setFocusedField('name')}
                        onBlur={() => setFocusedField(null)}
                        placeholder={t('name_placeholder') || "Enter your name"} required />
                    </div>
                    <div className={`cp-input-group ${focusedField === 'email' ? 'active' : ''}`}>
                      <label><FiMail /> {t('label_email')}</label>
                      <input type="email" name="email" value={formData.email}
                        onChange={handleChange}
                        onFocus={() => setFocusedField('email')}
                        onBlur={() => setFocusedField(null)}
                        placeholder={t('label_email_placeholder') || "john@example.com"} required />
                    </div>
                  </div>

                  <div className="cp-row">
                    <div className={`cp-input-group ${focusedField === 'phone' ? 'active' : ''}`}>
                      <label><FiPhone /> {t('label_phone')}</label>
                      <input type="tel" name="phone" value={formData.phone}
                        onChange={handleChange}
                        onFocus={() => setFocusedField('phone')}
                        onBlur={() => setFocusedField(null)}
                        placeholder={t('label_phone_placeholder') || "+94 XX XXX XXXX"} />
                    </div>
                    <div className={`cp-input-group ${focusedField === 'subject' ? 'active' : ''}`}>
                      <label><FiMessageSquare /> {t('label_subject')}</label>
                      <input type="text" name="subject" value={formData.subject}
                        onChange={handleChange}
                        onFocus={() => setFocusedField('subject')}
                        onBlur={() => setFocusedField(null)}
                        placeholder={t('subject_placeholder') || "How can we help?"} required />
                    </div>
                  </div>

                  <div className={`cp-input-group ${focusedField === 'message' ? 'active' : ''}`}>
                    <label><FiMessageCircle /> {t('label_message')}</label>
                    <textarea name="message" value={formData.message}
                      onChange={handleChange}
                      onFocus={() => setFocusedField('message')}
                      onBlur={() => setFocusedField(null)}
                      rows="5" placeholder={t('message_placeholder') || "Tell us about your inquiry..."} required />
                  </div>

                  <button type="submit" className="cp-send-btn" disabled={isLoading}>
                    <span>{isLoading ? (t('sending') || 'Sending...') : t('btn_send')}</span>
                    <FiSend />
                  </button>
                </form>
              )}
            </div>

            {/* ── SIDEBAR ── */}
            <aside className="cp-sidebar">
              {/* Hours */}
              <div className="cp-side-card">
                <h3><FiClock /> {t('footer_support_hours') || 'Support Hours'}</h3>
                <div className="cp-hours">
                  {supportHours.map((h, i) => (
                    <div key={i} className={`cp-hour ${!h.open ? 'closed' : ''}`}>
                      <span>{h.day}</span>
                      <span className="cp-hour-val">{h.time}</span>
                    </div>
                  ))}
                </div>
              </div>

              {/* Social */}
              <div className="cp-side-card">
                <h3><FiGlobe /> {socialTitle}</h3>
                <div className="cp-socials">
                  {socialLinks.map((s, i) => (
                    <a key={i} href={s.link} target="_blank" rel="noopener noreferrer" className="cp-soc-link">
                      {s.icon}
                      <span>{s.name}</span>
                      <FiArrowRight className="cp-soc-arrow" />
                    </a>
                  ))}
                </div>
              </div>

              {/* CTA */}
              {/* CTA */}
              <div className="cp-side-card cp-side-cta">
                <h3>{missionTitle}</h3>
                <p>{missionDesc}</p>
                <a href={`tel:${footerPhone.replace(/\s/g, '')}`} className="cp-call-btn">
                  <FiPhone /> {t('call_now')}
                </a>
              </div>
            </aside>
          </div>
        </div>
      </section>

      {/* ═══ MAP ═══ */}
      <section className="cp-map" id="map-section">
        <div className="container">
          <div className="cp-map-title">
            <h2><FiMapPin /> {mapTitle}</h2>
            <p>{mapDesc}</p>
          </div>
        </div>
        <div className="cp-map-frame">
          <div className="cp-map-pin">
            <FiMapPin />
            <div>
              <strong>{pinName}</strong>
              <span>{location}</span>
            </div>
          </div>
          <iframe
            src={mapEmbedLink}
            width="100%" height="480" style={{ border: 0 }}
            allowFullScreen="" loading="lazy"
            referrerPolicy="no-referrer-when-downgrade"
            title={mapTitle}
          ></iframe>
        </div>
      </section>
    </div>
  )
}

export default ContactPage
