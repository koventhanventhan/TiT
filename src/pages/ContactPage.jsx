import React, { useState } from 'react'
import {
  FiMail, FiPhone, FiMapPin, FiSend, FiMessageCircle,
  FiUser, FiMessageSquare, FiClock, FiGlobe,
  FiFacebook, FiInstagram, FiArrowRight,
  FiCheckCircle, FiHeadphones, FiHeart
} from 'react-icons/fi'
import { FaWhatsapp, FaYoutube, FaTiktok } from 'react-icons/fa'
import { useSettings } from '../context/SettingsContext'
import './ContactPage.css'

const ContactPage = () => {
  const { getSetting } = useSettings()
  const [formData, setFormData] = useState({
    name: '', email: '', phone: '', subject: '', message: ''
  })
  const [isSubmitted, setIsSubmitted] = useState(false)
  const [focusedField, setFocusedField] = useState(null)

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value })
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    setIsSubmitted(true)
    setTimeout(() => {
      setIsSubmitted(false)
      setFormData({ name: '', email: '', phone: '', subject: '', message: '' })
    }, 3000)
  }

  const footerPhone = getSetting('footer_phone', '+94 114 477 488')
  const footerEmail = getSetting('footer_email', 'info@edulearn.lk')
  const contactLocation = getSetting('contact_location', 'Colombo, Sri Lanka')

  const supportHours = [
    { day: 'Monday - Friday', time: getSetting('contact_hours_weekdays', '9:00 AM - 6:00 PM'), open: true },
    { day: 'Saturday', time: getSetting('contact_hours_saturday', '9:00 AM - 2:00 PM'), open: true },
    { day: 'Sunday', time: getSetting('contact_hours_sunday', 'Closed'), open: false }
  ]

  const socialLinks = [
    { icon: <FiFacebook />, name: 'Facebook', link: getSetting('social_facebook', '#') },
    { icon: <FiInstagram />, name: 'Instagram', link: getSetting('social_instagram', '#') },
    { icon: <FaWhatsapp />, name: 'WhatsApp', link: getSetting('social_whatsapp', '#') },
    { icon: <FaYoutube />, name: 'YouTube', link: getSetting('social_youtube', '#') },
    { icon: <FaTiktok />, name: 'TikTok', link: getSetting('social_tiktok', '#') }
  ]

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
            <div className="cp-badge"><FiMessageCircle /> Contact Us</div>
            <h1 className="cp-title">
              Let's Start a <span>Conversation</span>
            </h1>
            <p className="cp-desc">
              Have questions about our courses? Want to enroll? We're here to help.
              Reach out to us through any channel below.
            </p>

            {/* Quick contact row */}
            <div className="cp-quick-row">
              <a href={`tel:${footerPhone.replace(/\s/g, '')}`} className="cp-quick-item">
                <div className="cp-quick-icon"><FiPhone /></div>
                <div>
                  <span className="cp-quick-label">Call Us</span>
                  <span className="cp-quick-val">{footerPhone}</span>
                </div>
              </a>
              <a href={`mailto:${footerEmail}`} className="cp-quick-item">
                <div className="cp-quick-icon"><FiMail /></div>
                <div>
                  <span className="cp-quick-label">Email Us</span>
                  <span className="cp-quick-val">{footerEmail}</span>
                </div>
              </a>
              <div className="cp-quick-item">
                <div className="cp-quick-icon"><FiMapPin /></div>
                <div>
                  <span className="cp-quick-label">Visit Us</span>
                  <span className="cp-quick-val">{contactLocation}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Right side: visual illustration */}
          <div className="cp-hero-right">
            <div className="cp-hero-visual">
              <div className="cp-visual-card cp-vc-1">
                <FiHeadphones />
                <div>
                  <strong>24/7 Support</strong>
                  <span>Always available</span>
                </div>
              </div>
              <div className="cp-visual-card cp-vc-2">
                <FiHeart />
                <div>
                  <strong>10K+ Students</strong>
                  <span>Trust us</span>
                </div>
              </div>
              <div className="cp-visual-card cp-vc-3">
                <FiCheckCircle />
                <div>
                  <strong>98% Satisfaction</strong>
                  <span>Rate</span>
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
                <h2><FiMessageSquare /> Send Us a Message</h2>
                <p>Fill out the form below and we'll respond within 24 hours.</p>
              </div>

              {isSubmitted ? (
                <div className="cp-done">
                  <div className="cp-done-circle"><FiCheckCircle /></div>
                  <h3>Message Sent!</h3>
                  <p>Thanks for reaching out. We'll get back to you soon.</p>
                </div>
              ) : (
                <form onSubmit={handleSubmit} className="cp-form">
                  <div className="cp-row">
                    <div className={`cp-input-group ${focusedField === 'name' ? 'active' : ''}`}>
                      <label><FiUser /> Full Name</label>
                      <input type="text" name="name" value={formData.name}
                        onChange={handleChange}
                        onFocus={() => setFocusedField('name')}
                        onBlur={() => setFocusedField(null)}
                        placeholder="Enter your name" required />
                    </div>
                    <div className={`cp-input-group ${focusedField === 'email' ? 'active' : ''}`}>
                      <label><FiMail /> Email</label>
                      <input type="email" name="email" value={formData.email}
                        onChange={handleChange}
                        onFocus={() => setFocusedField('email')}
                        onBlur={() => setFocusedField(null)}
                        placeholder="john@example.com" required />
                    </div>
                  </div>

                  <div className="cp-row">
                    <div className={`cp-input-group ${focusedField === 'phone' ? 'active' : ''}`}>
                      <label><FiPhone /> Phone</label>
                      <input type="tel" name="phone" value={formData.phone}
                        onChange={handleChange}
                        onFocus={() => setFocusedField('phone')}
                        onBlur={() => setFocusedField(null)}
                        placeholder="+94 XX XXX XXXX" />
                    </div>
                    <div className={`cp-input-group ${focusedField === 'subject' ? 'active' : ''}`}>
                      <label><FiMessageSquare /> Subject</label>
                      <input type="text" name="subject" value={formData.subject}
                        onChange={handleChange}
                        onFocus={() => setFocusedField('subject')}
                        onBlur={() => setFocusedField(null)}
                        placeholder="How can we help?" required />
                    </div>
                  </div>

                  <div className={`cp-input-group ${focusedField === 'message' ? 'active' : ''}`}>
                    <label><FiMessageCircle /> Message</label>
                    <textarea name="message" value={formData.message}
                      onChange={handleChange}
                      onFocus={() => setFocusedField('message')}
                      onBlur={() => setFocusedField(null)}
                      rows="5" placeholder="Tell us about your inquiry..." required />
                  </div>

                  <button type="submit" className="cp-send-btn">
                    <span>Send Message</span>
                    <FiSend />
                  </button>
                </form>
              )}
            </div>

            {/* ── SIDEBAR ── */}
            <aside className="cp-sidebar">
              {/* Hours */}
              <div className="cp-side-card">
                <h3><FiClock /> Support Hours</h3>
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
                <h3><FiGlobe /> Connect With Us</h3>
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
              <div className="cp-side-card cp-side-cta">
                <h3>Need Urgent Help?</h3>
                <p>Call our support line directly for immediate assistance.</p>
                <a href={`tel:${footerPhone.replace(/\s/g, '')}`} className="cp-call-btn">
                  <FiPhone /> Call Now
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
            <h2><FiMapPin /> Our Location</h2>
            <p>Find us on the map or visit our office directly.</p>
          </div>
        </div>
        <div className="cp-map-frame">
          <div className="cp-map-pin">
            <FiMapPin />
            <div>
              <strong>TiT Education</strong>
              <span>{contactLocation}</span>
            </div>
          </div>
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126743.63219517708!2d79.7861!3d6.9271!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae253d10f7a7003%3A0x320b2e4d32d3838d!2sColombo%2C%20Sri%20Lanka!5e0!3m2!1sen!2slk!4v1708000000000!5m2!1sen!2slk"
            width="100%" height="480" style={{ border: 0 }}
            allowFullScreen="" loading="lazy"
            referrerPolicy="no-referrer-when-downgrade"
            title="Our Location"
          ></iframe>
        </div>
      </section>
    </div>
  )
}

export default ContactPage
