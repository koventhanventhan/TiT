import React from 'react'
import { useSettings } from '../context/SettingsContext'
import './WhyChooseUs.css'

const WhyChooseUs = () => {
  const { getSetting } = useSettings()

  const why_title = getSetting('why_title', 'Why Choose EduLearn?')
  const why_subtitle = getSetting('why_subtitle', 'Empowering students with quality education and modern tools.')

  const reasons = [
    {
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/osuxyevn.json"
          trigger="hover"
          colors="primary:#ffffff,secondary:#ffffff"
          style={{ width: '48px', height: '48px' }}
        />
      ),
      title: 'Expert Instruction',
      description: 'Learn from highly qualified educators who are passionate about teaching and student success.',
      color: '#4f0bd9'
    },
    {
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/qhviklyi.json"
          trigger="hover"
          colors="primary:#ffffff,secondary:#ffffff"
          style={{ width: '48px', height: '48px' }}
        />
      ),
      title: 'Affordable Pricing',
      description: 'Get premium quality education at prices that make sense, ensuring value for every student.',
      color: '#10b981'
    },
    {
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/srsrzquw.json"
          trigger="hover"
          colors="primary:#ffffff,secondary:#ffffff"
          style={{ width: '48px', height: '48px' }}
        />
      ),
      title: 'Lifetime Support',
      description: 'Our dedicated team is always here to guide you through your academic journey.',
      color: '#8b5cf6'
    }
  ]

  return (
    <section className="premium-why section">
      <div className="container">
        <div className="pw-content-wrapper">
          <div className="pw-text-side">
            <span className="pw-badge">Our Benefits</span>
            <h2 className="pw-title">{why_title}</h2>
            <p className="pw-description">{why_subtitle}</p>

            <ul className="pw-benefit-list">
              <li>
                <lord-icon
                  src="https://cdn.lordicon.com/yqzmiobz.json"
                  trigger="loop"
                  colors="primary:#4f0bd9"
                  style={{ width: '20px', height: '20px' }}
                /> Personalized Learning Paths
              </li>
              <li>
                <lord-icon
                  src="https://cdn.lordicon.com/yqzmiobz.json"
                  trigger="loop"
                  colors="primary:#4f0bd9"
                  style={{ width: '20px', height: '20px' }}
                /> 24/7 Access to Course Materials
              </li>
              <li>
                <lord-icon
                  src="https://cdn.lordicon.com/yqzmiobz.json"
                  trigger="loop"
                  colors="primary:#4f0bd9"
                  style={{ width: '20px', height: '20px' }}
                /> Interactive Live Q&A Sessions
              </li>
            </ul>
          </div>

          <div className="pw-grid-side">
            <div className="pw-reasons-grid">
              {reasons.map((reason, index) => (
                <div key={index} className="pw-reason-card" style={{ '--accent-color': reason.color }}>
                  <div className="pw-reason-icon-box">
                    {reason.icon}
                  </div>
                  <h3 className="pw-reason-title">{reason.title}</h3>
                  <p className="pw-reason-desc">{reason.description}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}

export default WhyChooseUs
