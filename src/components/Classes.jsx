import React from 'react'
import { Link } from 'react-router-dom'
import { FiArrowRight, FiBook, FiGlobe, FiMapPin, FiMonitor, FiUsers, FiStar, FiClock } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './Classes.css'

const Classes = () => {
  const { getSetting } = useSettings()

  const classes_title = getSetting('classes_title', 'Featured Programs')
  const classes_subtitle = getSetting('classes_subtitle', 'Choose the best learning path for your future success with our premium education modules.')

  const classData = [
    {
      title: 'Direct Physical Class',
      category: 'Physical',
      description: getSetting('classes_direct_description', 'High-impact face-to-face sessions in a dedicated learning environment.'),
      accent: 'direct',
      duration: 'Weekly',
      students: 'Group',
      rating: '4.9',
      price: 'Affordable',
      image: '/venthan1.jpg',
      icon: <FiBook />
    },
    {
      title: 'Online Live Class',
      category: 'Online',
      description: getSetting('classes_online_description', 'Interactive digital classrooms with full access to recordings and resources.'),
      accent: 'online',
      duration: 'Flexible',
      students: 'Group/1-on-1',
      rating: '5.0',
      price: 'Premium',
      image: '/venthan2.jpg',
      icon: <FiGlobe />
    }
  ]

  return (
    <section id="classes" className="premium-classes section">
      <div className="container">
        <div className="pc-header">
          <span className="pc-tag">Our Expertise</span>
          <h2 className="pc-title">{classes_title}</h2>
          <p className="pc-subtitle">{classes_subtitle}</p>
        </div>

        <div className="pc-grid">
          {classData.map((course, index) => (
            <div key={index} className={`pc-card pc-card-${course.accent}`}>
              <div className="pc-card-media">
                <img src={course.image} alt={course.title} className="pc-card-img" />
                <div className="pc-card-category">{course.category}</div>
                <div className="pc-card-overlay">
                  <div className="pc-card-icon-overlay">{course.icon}</div>
                </div>
              </div>

              <div className="pc-card-body">
                <div className="pc-card-meta">
                  <span className="pc-meta-item">
                    <FiClock /> {course.duration}
                  </span>
                  <span className="pc-meta-item">
                    <FiUsers /> {course.students}
                  </span>
                </div>

                <h3 className="pc-card-title">{course.title}</h3>
                <p className="pc-card-desc">{course.description}</p>

                <div className="pc-card-footer">
                  <div className="pc-rating">
                    <FiStar className="star-fill" />
                    <span>{course.rating}</span>
                  </div>
                  <Link to={`/classes?type=${course.accent}`} className="pc-enroll-btn">
                    Details <FiArrowRight />
                  </Link>
                </div>
              </div>
            </div>
          ))}
        </div>

        <div className="pc-view-all">
          <Link to="/classes" className="pc-outline-btn">
            View All Programs
          </Link>
        </div>
      </div>
    </section>
  )
}

export default Classes
