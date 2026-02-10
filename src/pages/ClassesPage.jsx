import React from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { FiBook, FiGlobe, FiTarget, FiArrowRight, FiCheck, FiUsers, FiClock, FiAward } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './ClassesPage.css'

const ClassesPage = () => {
  const { getSetting } = useSettings()
  const [searchParams] = useSearchParams()
  const type = searchParams.get('type')

  const allClassTypes = [
    {
      id: 1,
      icon: <FiBook />,
      title: 'Direct Class',
      description: getSetting('classes_direct_description', 'Comprehensive face-to-face learning experience with expert tutors in a physical classroom setting.'),
      gradient: 'gradient-1',
      features: getSetting('classes_direct_features', "Small group sessions\nDirect teacher interaction\nPhysical learning materials\nIn-person assessments\nFocus and discipline").split('\n').filter(f => f.trim()),
      duration: getSetting('classes_direct_duration', 'Flexible schedules'),
      students: getSetting('classes_direct_format', 'Small Groups'),
      price: getSetting('classes_direct_price', 'Affordable rates')
    },
    {
      id: 2,
      icon: <FiGlobe />,
      title: 'Online Class',
      description: getSetting('classes_online_description', 'Convenient live interactive sessions accessible from anywhere with high-quality digital resources.'),
      gradient: 'gradient-2',
      features: getSetting('classes_online_features', "Interactive live classes\nRecorded lesson access\nDigital study materials\nOnline quizzes/exams\nFlexible learning from home").split('\n').filter(f => f.trim()),
      duration: getSetting('classes_online_duration', 'Flexible schedules'),
      students: getSetting('classes_online_format', 'Group & One-on-One'),
      price: getSetting('classes_online_price', 'Competitive pricing')
    }
  ]

  const classTypes = type
    ? allClassTypes.filter(ct => ct.title.toLowerCase().startsWith(type.toLowerCase()))
    : allClassTypes;

  const subjects = {
    'Direct Class': getSetting('classes_direct_subjects', 'Mathematics, Science, English, Sinhala, Tamil, History, Geography, Commerce, ICT, Art').split(',').map(s => s.trim()),
    'Online Class': getSetting('classes_online_subjects', 'Mathematics, Physics, Chemistry, Biology, English, Business Studies, Economics, Accounting, ICT, Computer Science').split(',').map(s => s.trim())
  }

  return (
    <div className="classes-page">
      <div className="classes-page-hero">
        <div className="container">
          <h1 className="classes-page-title">Our Classes</h1>
          <p className="classes-page-subtitle">
            Explore & Enroll - Online Tuition for all subjects. Grade 1 to Advanced Level.
            Group or one-on-one? We got you!
          </p>
        </div>
      </div>

      <div className="container">
        <div className="classes-page-content">
          {classTypes.map((classType, index) => (
            <div key={classType.id} className={`class-detail-card ${classType.gradient}`}>
              <div className="class-header">
                <div className="class-icon-large">
                  {classType.icon}
                </div>
                <div className="class-header-content">
                  <h2 className="class-title-large">{classType.title}</h2>
                  <p className="class-description-large">{classType.description}</p>
                </div>
              </div>

              <div className="class-details-grid">
                <div className="class-info-section">
                  <h3 className="section-heading">
                    <FiCheck />
                    What's Included
                  </h3>
                  <ul className="features-list">
                    {classType.features.map((feature, idx) => (
                      <li key={idx}>
                        <FiCheck />
                        {feature}
                      </li>
                    ))}
                  </ul>
                </div>

                <div className="class-info-section">
                  <h3 className="section-heading">
                    <FiBook />
                    Available Subjects
                  </h3>
                  <div className="subjects-grid">
                    {subjects[classType.title]?.map((subject, idx) => (
                      <span key={idx} className="subject-badge">
                        {subject}
                      </span>
                    ))}
                  </div>
                </div>

                <div className="class-info-section">
                  <h3 className="section-heading">
                    <FiUsers />
                    Class Details
                  </h3>
                  <div className="class-details-list">
                    <div className="detail-item">
                      <FiClock />
                      <div>
                        <strong>Duration:</strong>
                        <span>{classType.duration}</span>
                      </div>
                    </div>
                    <div className="detail-item">
                      <FiUsers />
                      <div>
                        <strong>Format:</strong>
                        <span>{classType.students}</span>
                      </div>
                    </div>
                    <div className="detail-item">
                      <FiAward />
                      <div>
                        <strong>Pricing:</strong>
                        <span>{classType.price}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="class-cta">
                <Link to="/contact" className="btn-enroll">
                  Enroll Now
                  <FiArrowRight />
                </Link>
                <a href="#register" className="btn-learn-more">
                  Learn More
                </a>
              </div>
            </div>
          ))}
        </div>

        <div className="classes-cta-section">
          <h2>Ready to Start Learning?</h2>
          <p>Choose your curriculum and start your journey to academic success today!</p>
          <div className="cta-buttons">
            <Link to="/contact" className="btn-primary-large">
              Get Started
              <FiArrowRight />
            </Link>
            <a href="/register" className="btn-secondary-large">
              Register Now
            </a>
          </div>
        </div>
      </div>
    </div>
  )
}

export default ClassesPage

