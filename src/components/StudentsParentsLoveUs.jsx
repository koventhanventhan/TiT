import React from 'react'
import { FiHeart, FiStar, FiUsers, FiAward, FiMessageCircle } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './StudentsParentsLoveUs.css'

const StudentsParentsLoveUs = () => {
  const { getSetting } = useSettings();

  const testimonials = [
    {
      name: 'Sarah Perera',
      role: 'Parent of Grade 10 Student',
      comment: 'The personalized attention and quality teaching have improved my daughter\'s grades significantly. Highly recommended!',
      rating: 5,
      image: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop'
    },
    {
      name: 'Kamal Fernando',
      role: 'A/L Student',
      comment: 'The best online tuition platform! The teachers are excellent and the classes are very interactive.',
      rating: 5,
      image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop'
    },
    {
      name: 'Nimali Silva',
      role: 'O/L Parent',
      comment: 'As a parent, I\'m very satisfied with the quality. The progress reports are excellent and consistent.',
      rating: 5,
      image: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop'
    }
  ];

  const stats = [
    {
      icon: <FiUsers />,
      number: getSetting('love_us_stat1_number', '10,000+'),
      label: 'Happy Students'
    },
    {
      icon: <FiHeart />,
      number: getSetting('love_us_stat2_number', '98%'),
      label: 'Success Rate'
    },
    {
      icon: <FiStar />,
      number: getSetting('love_us_stat3_number', '4.9/5'),
      label: 'Review Rating'
    },
    {
      icon: <FiAward />,
      number: getSetting('love_us_stat4_number', '95%'),
      label: 'Pass Guarantee'
    }
  ];

  return (
    <section className="premium-reviews section">
      <div className="container">
        {/* Stats Row */}
        <div className="pr-stats-row">
          {stats.map((stat, index) => (
            <div key={index} className="pr-stat-item">
              <div className="pr-stat-icon-box">{stat.icon}</div>
              <div className="pr-stat-info">
                <h3>{stat.number}</h3>
                <p>{stat.label}</p>
              </div>
            </div>
          ))}
        </div>

        <div className="pr-header">
          <span className="pr-tag">Social Proof</span>
          <h2 className="pr-title">{getSetting('love_us_title', 'What Our Community Says')}</h2>
          <p className="pr-subtitle">
            Join thousands of satisfied students and parents who have transformed their
            academic journey with our premium education platform.
          </p>
        </div>

        <div className="pr-grid">
          {testimonials.map((t, index) => (
            <div key={index} className="pr-card">
              <div className="pr-quote-icon"><FiMessageCircle /></div>
              <div className="pr-card-rating">
                {[...Array(t.rating)].map((_, i) => (
                  <FiStar key={i} className="star-fill" />
                ))}
              </div>
              <p className="pr-comment">"{t.comment}"</p>
              <div className="pr-user">
                <div className="pr-avatar">
                  <img src={t.image} alt={t.name} />
                </div>
                <div className="pr-details">
                  <h4>{t.name}</h4>
                  <span>{t.role}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

export default StudentsParentsLoveUs
