import React from 'react'
import { FiTrendingUp, FiDollarSign, FiHeadphones } from 'react-icons/fi'
import './WhyChooseUs.css'

const WhyChooseUs = () => {
  const reasons = [
    {
      icon: <FiTrendingUp />,
      title: 'Quality Learning',
      subtitle: 'Proven Results',
      description: 'We combine expert tutors with extensive knowledge, learning plans and engaging curriculum to ensure deep understanding and academic achievement.',
      gradient: 'gradient-1'
    },
    {
      icon: <FiDollarSign />,
      title: 'Value for Money',
      subtitle: 'Maximized ROI',
      description: 'Our classes prioritize affordability while delivering high-quality instruction and great results. This ensures you receive a maximized return on your investment in education.',
      gradient: 'gradient-2'
    },
    {
      icon: <FiHeadphones />,
      title: 'Professional Service',
      subtitle: 'Seamless Experience',
      description: 'We ensure a stress-free learning journey from initial enrollment to ongoing support, personalized guidance, and a dedicated team focused on your success.',
      gradient: 'gradient-3'
    }
  ]

  return (
    <section className="why-choose-us section">
      <div className="container">
        <div className="why-header">
          <h2 className="section-title">Why EduLearn?</h2>
          <p className="section-subtitle">
            Quality Assured Online Learning 👨🏻‍🎓
          </p>
        </div>

        <div className="reasons-grid">
          {reasons.map((reason, index) => (
            <div key={index} className={`reason-card ${reason.gradient}`}>
              <div className="reason-icon-wrapper">
                <div className="reason-icon">{reason.icon}</div>
              </div>
              <div className="reason-content">
                <h3 className="reason-title">{reason.title}</h3>
                <p className="reason-subtitle">{reason.subtitle}</p>
                <p className="reason-description">{reason.description}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

export default WhyChooseUs

