import React from 'react'
import { FiTrendingUp, FiDollarSign, FiHeadphones } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './WhyChooseUs.css'

const WhyChooseUs = () => {
  const { getSetting } = useSettings()

  const why_title = getSetting('why_title', 'Why EduLearn?')
  const why_subtitle = getSetting('why_subtitle', 'Quality Assured Online Learning 👨🏻‍🎓')

  const rawReasons = getSetting('why_reasons', '[]');
  let dynamicReasons = [];
  try {
    dynamicReasons = JSON.parse(rawReasons);
  } catch (e) {
    dynamicReasons = [];
  }

  const defaultIcons = [<FiTrendingUp />, <FiDollarSign />, <FiHeadphones />];

  const reasons = dynamicReasons.length > 0 ? dynamicReasons.map((reason, index) => ({
    ...reason,
    icon: defaultIcons[index] || <FiTrendingUp />
  })) : [
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
          <h2 className="section-title">{why_title}</h2>
          <p className="section-subtitle">
            {why_subtitle}
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


