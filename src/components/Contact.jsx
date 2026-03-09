import React, { useState } from 'react'
import { useSettings } from '../context/SettingsContext'
import './Contact.css'

const Contact = () => {
  const { getSetting } = useSettings()

  const footer_email = getSetting('footer_email', 'info@edulearn.lk')
  const footer_phone = getSetting('footer_phone', '+94 114 477 488')
  const contact_location = getSetting('contact_location', 'Colombo, Sri Lanka')

  // Contact Hero settings
  const contact_hero_title = getSetting('contact_hero_title', 'Get In Touch')
  const contact_hero_subtitle = getSetting('contact_hero_subtitle', "Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.")

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
    // Handle form submission here
    console.log('Form submitted:', formData)
    alert('Thank you for your message! We will get back to you soon.')
    setFormData({ name: '', email: '', phone: '', subject: '', message: '' })
  }

  const contactInfo = [
    {
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/tftaqpbe.json"
          trigger="hover"
          colors="primary:#4f0bd9,secondary:#1a103c"
          style={{ width: '40px', height: '40px' }}
        />
      ),
      title: 'Phone',
      content: footer_phone,
      link: `tel:${footer_phone.replace(/\s/g, '')}`,
      color: 'gradient-1'
    },
    {
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/ebjjbeag.json"
          trigger="hover"
          colors="primary:#4f0bd9,secondary:#1a103c"
          style={{ width: '40px', height: '40px' }}
        />
      ),
      title: 'Email',
      content: footer_email,
      link: `mailto:${footer_email}`,
      color: 'gradient-2'
    },
    {
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/surdgmqi.json"
          trigger="hover"
          colors="primary:#4f0bd9,secondary:#1a103c"
          style={{ width: '40px', height: '40px' }}
        />
      ),
      title: 'Location',
      content: contact_location,
      link: '#',
      color: 'gradient-3'
    }
  ]

  return (
    <section id="contact" className="contact section">
      <div className="container">
        <div className="contact-header">
          <h2 className="section-title">{contact_hero_title}</h2>
          <p className="section-subtitle">
            {contact_hero_subtitle}
          </p>
        </div>

        <div className="contact-content">
          <div className="contact-info-section">
            <div className="contact-info-cards">
              {contactInfo.map((info, index) => (
                <div key={index} className={`contact-info-card ${info.color}`}>
                  <div className="contact-icon-wrapper">
                    <div className="contact-icon">{info.icon}</div>
                  </div>
                  <h3 className="contact-info-title">{info.title}</h3>
                  <a href={info.link} className="contact-info-link">
                    {info.content}
                  </a>
                </div>
              ))}
            </div>

            <div className="contact-hours">
              <h3 className="hours-title">
                <lord-icon
                  src="https://cdn.lordicon.com/fdxqxpql.json"
                  trigger="hover"
                  colors="primary:#4f0bd9"
                  style={{ width: '24px', height: '24px', marginRight: '8px' }}
                />
                Support Hours
              </h3>
              <div className="hours-list">
                <div className="hours-item">
                  <span className="hours-day">Monday - Friday</span>
                  <span className="hours-time">9:00 AM - 6:00 PM</span>
                </div>
                <div className="hours-item">
                  <span className="hours-day">Saturday</span>
                  <span className="hours-time">9:00 AM - 2:00 PM</span>
                </div>
                <div className="hours-item">
                  <span className="hours-day">Sunday</span>
                  <span className="hours-time">Closed</span>
                </div>
              </div>
            </div>
          </div>

          <div className="contact-form-section">
            <div className="form-wrapper">
              <h3 className="form-title">
                <lord-icon
                  src="https://cdn.lordicon.com/fdxqxpql.json"
                  trigger="hover"
                  colors="primary:#4f0bd9"
                  style={{ width: '24px', height: '24px', marginRight: '8px' }}
                />
                Send Us a Message
              </h3>
              <form onSubmit={handleSubmit} className="contact-form">
                <div className="form-group">
                  <label htmlFor="name" className="form-label">
                    <lord-icon
                      src="https://cdn.lordicon.com/dxjqoygy.json"
                      trigger="focus"
                      colors="primary:#4f0bd9"
                      style={{ width: '20px', height: '20px', marginRight: '8px' }}
                    />
                    Your Name
                  </label>
                  <input
                    type="text"
                    id="name"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    className="form-input"
                    placeholder="Enter your name"
                    required
                  />
                </div>

                <div className="form-row">
                  <div className="form-group">
                    <label htmlFor="email" className="form-label">
                      <lord-icon
                        src="https://cdn.lordicon.com/ebjjbeag.json"
                        trigger="focus"
                        colors="primary:#4f0bd9"
                        style={{ width: '20px', height: '20px', marginRight: '8px' }}
                      />
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

                  <div className="form-group">
                    <label htmlFor="phone" className="form-label">
                      <lord-icon
                        src="https://cdn.lordicon.com/tftaqpbe.json"
                        trigger="focus"
                        colors="primary:#4f0bd9"
                        style={{ width: '20px', height: '20px', marginRight: '8px' }}
                      />
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
                </div>

                <div className="form-group">
                  <label htmlFor="subject" className="form-label">
                    <lord-icon
                      src="https://cdn.lordicon.com/fdxqxpql.json"
                      trigger="focus"
                      colors="primary:#4f0bd9"
                      style={{ width: '20px', height: '20px', marginRight: '8px' }}
                    />
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

                <div className="form-group">
                  <label htmlFor="message" className="form-label">
                    <lord-icon
                      src="https://cdn.lordicon.com/fdxqxpql.json"
                      trigger="focus"
                      colors="primary:#4f0bd9"
                      style={{ width: '20px', height: '20px', marginRight: '8px' }}
                    />
                    Your Message
                  </label>
                  <textarea
                    id="message"
                    name="message"
                    value={formData.message}
                    onChange={handleChange}
                    className="form-textarea"
                    rows="5"
                    placeholder="Tell us more about your inquiry..."
                    required
                  ></textarea>
                </div>

                <button type="submit" className="form-submit-btn">
                  <span>Send Message</span>
                  <lord-icon
                    src="https://cdn.lordicon.com/aymdfhbt.json"
                    trigger="hover"
                    colors="primary:#ffffff"
                    style={{ width: '20px', height: '20px', marginLeft: '8px' }}
                  />
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}

export default Contact

