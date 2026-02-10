import React from 'react'
import { FiCheckCircle, FiUsers, FiAward, FiBookOpen } from 'react-icons/fi'
import './About.css'

const About = () => {
  const features = [
    {
      icon: <FiBookOpen />,
      title: 'Top-notch Online Classes',
      description: 'Interactive live sessions with expert tutors'
    },
    {
      icon: <FiUsers />,
      title: 'Professional Service & Standards',
      description: 'Dedicated support team for every student'
    },
    {
      icon: <FiAward />,
      title: 'Guaranteed Academic Success',
      description: 'Proven track record of student achievements'
    },
    {
      icon: <FiCheckCircle />,
      title: 'Qualified Professional Tutors',
      description: 'Experienced educators committed to your success'
    }
  ]

  return (
    <section id="about" className="about section">
      <div className="container">
        <div className="about-header">
          <h2 className="section-title">About EduLearn</h2>
          <p className="section-subtitle">
            Sri Lanka's Premier Choice for Online Tuition 🎓
          </p>
          <p className="about-description">
            Sri Lanka's trusted leader in online tuition. We ensure student success through 
            personalized learning and comprehensive parental support. Invest in your child's 
            successful learning journey with EduLearn.
          </p>
        </div>

        <div className="features-grid">
          {features.map((feature, index) => (
            <div key={index} className="feature-card">
              <div className="feature-icon">{feature.icon}</div>
              <h3 className="feature-title">{feature.title}</h3>
              <p className="feature-description">{feature.description}</p>
            </div>
          ))}
        </div>

        <div className="about-cta">
          <a href="#register" className="btn btn-primary">
            Register Now
          </a>
        </div>
      </div>
    </section>
  )
}

export default About

