import React from 'react'
import './Accreditations.css'

const Accreditations = () => {
  const accreditations = [
    { name: 'Microsoft', logo: '🏆' },
    { name: 'GitHub', logo: '💻' },
    { name: 'Innovate', logo: '💡' },
    { name: 'Spiral', logo: '🌀' },
    { name: 'SLASSCOM', logo: '🇱🇰' },
    { name: 'Hemas', logo: '🏢' },
    { name: 'ICTA', logo: '📱' }
  ]

  return (
    <section className="accreditations section">
      <div className="container">
        <div className="accreditations-header">
          <h2 className="section-title">Our Accreditations</h2>
          <p className="section-subtitle">
            Award-Winning Online Tuition awaits for Your Success 🎖️
          </p>
          <p className="accreditations-description">
            Hailed by educational leaders and market giants, EduLearn offers exceptional 
            online tuition service. Achieve your academic goals with our award-winning approach!
          </p>
        </div>

        <div className="accreditations-grid">
          {accreditations.map((accreditation, index) => (
            <div key={index} className="accreditation-card">
              <div className="accreditation-logo">{accreditation.logo}</div>
              <p className="accreditation-name">{accreditation.name}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

export default Accreditations

