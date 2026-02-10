import React, { useState } from 'react'
import { FiMail, FiPhone, FiMapPin, FiSend, FiMessageCircle, FiUser, FiMessageSquare, FiClock, FiGlobe, FiFacebook, FiTwitter, FiInstagram, FiLinkedin } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './ContactPage.css'

const ContactPage = () => {
  const { getSetting } = useSettings()
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: ''
  })

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    })
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    console.log('Form submitted:', formData)
    alert('Thank you for your message! We will get back to you soon.')
    setFormData({ name: '', email: '', phone: '', subject: '', message: '' })
  }

  const contactHeroTitle = getSetting('contact_hero_title', 'Contact Us')
  const contactHeroSubtitle = getSetting('contact_hero_subtitle', "Get in touch with us. We're here to help you with any questions or inquiries.")

  const contactDetails = [
    {
      icon: <FiPhone />,
      title: 'Phone',
      content: getSetting('footer_phone', '+94 114 477 488'),
      link: `tel:${getSetting('footer_phone', '+94114477488').replace(/\s/g, '')}`,
      description: 'Call us for immediate assistance',
      color: 'gradient-1'
    },
    {
      icon: <FiMail />,
      title: 'Email',
      content: getSetting('footer_email', 'info@edulearn.lk'),
      link: `mailto:${getSetting('footer_email', 'info@edulearn.lk')}`,
      description: 'Send us an email anytime',
      color: 'gradient-2'
    },
    {
      icon: <FiMapPin />,
      title: 'Address',
      content: getSetting('contact_location', 'Colombo, Sri Lanka'),
      link: '#',
      description: 'Visit our office',
      color: 'gradient-3'
    },
    {
      icon: <FiGlobe />,
      title: 'Website',
      content: getSetting('contact_website', 'www.edulearn.lk'),
      link: `https://${getSetting('contact_website', 'www.edulearn.lk')}`,
      description: 'Explore our platform',
      color: 'gradient-1'
    }
  ]

  const supportHours = [
    { day: 'Weekdays', time: getSetting('contact_hours_weekdays', '9:00 AM - 6:00 PM') },
    { day: 'Saturday', time: getSetting('contact_hours_saturday', '9:00 AM - 2:00 PM') },
    { day: 'Sunday', time: getSetting('contact_hours_sunday', 'Closed') }
  ]

  const socialLinks = [
    { icon: <FiFacebook />, name: 'Facebook', link: getSetting('social_facebook', '#'), color: '#1877F2' },
    { icon: <FiTwitter />, name: 'Twitter', link: getSetting('social_twitter', '#'), color: '#1DA1F2' },
    { icon: <FiInstagram />, name: 'Instagram', link: getSetting('social_instagram', '#'), color: '#E4405F' },
    { icon: <FiLinkedin />, name: 'LinkedIn', link: getSetting('social_linkedin', '#'), color: '#0077B5' }
  ]

  return (
    <div className="contact-page">
      <div className="contact-page-hero">
        <div className="container">
          <h1 className="contact-page-title">{contactHeroTitle}</h1>
          <p className="contact-page-subtitle">
            {contactHeroSubtitle}
          </p>
        </div>
      </div>

      <div className="container">
        <div className="contact-page-content">
          {/* Contact Details Cards */}
          <section className="contact-details-section">
            <h2 className="section-heading">Contact Information</h2>
            <div className="contact-details-grid">
              {contactDetails.map((detail, index) => (
                <div key={index} className={`contact-detail-card ${detail.color}`}>
                  <div className="detail-icon-wrapper">
                    <div className="detail-icon">{detail.icon}</div>
                  </div>
                  <h3 className="detail-title">{detail.title}</h3>
                  <a href={detail.link} className="detail-content">{detail.content}</a>
                  <p className="detail-description">{detail.description}</p>
                </div>
              ))}
            </div>
          </section>

          {/* Support Hours & Social Media */}
          <div className="info-grid">
            <div className="support-hours-card">
              <h3 className="card-title">
                <FiClock />
                Support Hours
              </h3>
              <div className="hours-list">
                {supportHours.map((hour, index) => (
                  <div key={index} className="hour-item">
                    <span className="hour-day">{hour.day}</span>
                    <span className="hour-time">{hour.time}</span>
                  </div>
                ))}
              </div>
            </div>

            <div className="social-media-card">
              <h3 className="card-title">
                <FiGlobe />
                Follow Us
              </h3>
              <div className="social-links-grid">
                {socialLinks.map((social, index) => (
                  <a
                    key={index}
                    href={social.link}
                    className="social-link"
                    style={{ '--social-color': social.color }}
                  >
                    {social.icon}
                    <span>{social.name}</span>
                  </a>
                ))}
              </div>
            </div>
          </div>

          {/* Contact Form */}
          <section className="contact-form-section">
            <div className="form-container">
              <h2 className="section-heading">
                <FiMessageSquare />
                Send Us a Message
              </h2>
              <p className="form-description">
                Fill out the form below and we'll get back to you as soon as possible.
              </p>

              <form onSubmit={handleSubmit} className="contact-form">
                <div className="form-row">
                  <div className="form-group">
                    <label htmlFor="name" className="form-label">
                      <FiUser />
                      Full Name
                    </label>
                    <input
                      type="text"
                      id="name"
                      name="name"
                      value={formData.name}
                      onChange={handleChange}
                      className="form-input"
                      placeholder="Enter your full name"
                      required
                    />
                  </div>

                  <div className="form-group">
                    <label htmlFor="email" className="form-label">
                      <FiMail />
                      Email Address
                    </label>
                    <input
                      type="email"
                      id="email"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      className="form-input"
                      placeholder="your.email@example.com"
                      required
                    />
                  </div>
                </div>

                <div className="form-row">
                  <div className="form-group">
                    <label htmlFor="phone" className="form-label">
                      <FiPhone />
                      Phone Number
                    </label>
                    <input
                      type="tel"
                      id="phone"
                      name="phone"
                      value={formData.phone}
                      onChange={handleChange}
                      className="form-input"
                      placeholder="+94 XX XXX XXXX"
                    />
                  </div>

                  <div className="form-group">
                    <label htmlFor="subject" className="form-label">
                      <FiMessageSquare />
                      Subject
                    </label>
                    <input
                      type="text"
                      id="subject"
                      name="subject"
                      value={formData.subject}
                      onChange={handleChange}
                      className="form-input"
                      placeholder="What's this about?"
                      required
                    />
                  </div>
                </div>

                <div className="form-group">
                  <label htmlFor="message" className="form-label">
                    <FiMessageCircle />
                    Your Message
                  </label>
                  <textarea
                    id="message"
                    name="message"
                    value={formData.message}
                    onChange={handleChange}
                    className="form-textarea"
                    rows="6"
                    placeholder="Tell us more about your inquiry..."
                    required
                  ></textarea>
                </div>

                <button type="submit" className="submit-button">
                  <span>Send Message</span>
                  <FiSend />
                </button>
              </form>
            </div>
          </section>
        </div>
      </div>
    </div>
  )
}

export default ContactPage

