import React from 'react'
import { Link } from 'react-router-dom'
import { FiBook, FiGlobe, FiTarget, FiArrowRight, FiCheck, FiUsers, FiClock, FiAward } from 'react-icons/fi'
import './ClassesPage.css'

const ClassesPage = () => {
  const classTypes = [
    {
      id: 1,
      icon: <FiBook />,
      title: 'Sri Lankan National Syllabus',
      description: 'Comprehensive coverage of the national curriculum from Grade 1 to Advanced Level',
      gradient: 'gradient-1',
      features: [
        'Grade 1 to Advanced Level coverage',
        'All subjects included',
        'Past paper practice',
        'Exam preparation support',
        'Qualified local tutors'
      ],
      duration: 'Flexible schedules',
      students: 'Group & One-on-One',
      price: 'Affordable rates'
    },
    {
      id: 2,
      icon: <FiGlobe />,
      title: 'Cambridge / EDEXCEL Syllabus',
      description: 'International curriculum support for Cambridge and EDEXCEL qualifications',
      gradient: 'gradient-2',
      features: [
        'IGCSE & A-Level support',
        'Cambridge curriculum',
        'EDEXCEL curriculum',
        'International exam prep',
        'Experienced international tutors'
      ],
      duration: 'Flexible schedules',
      students: 'Group & One-on-One',
      price: 'Competitive pricing'
    },
    {
      id: 3,
      icon: <FiTarget />,
      title: 'Essential Skill Development',
      description: 'Build critical thinking, problem-solving, and communication skills',
      gradient: 'gradient-3',
      features: [
        'Critical thinking skills',
        'Problem-solving techniques',
        'Communication skills',
        'Leadership development',
        'Career readiness'
      ],
      duration: 'Flexible schedules',
      students: 'Group & One-on-One',
      price: 'Affordable rates'
    }
  ]

  const subjects = {
    'Sri Lankan National Syllabus': [
      'Mathematics', 'Science', 'English', 'Sinhala', 'Tamil', 
      'History', 'Geography', 'Commerce', 'ICT', 'Art'
    ],
    'Cambridge / EDEXCEL Syllabus': [
      'Mathematics', 'Physics', 'Chemistry', 'Biology', 'English',
      'Business Studies', 'Economics', 'Accounting', 'ICT', 'Computer Science'
    ],
    'Essential Skill Development': [
      'Critical Thinking', 'Problem Solving', 'Communication', 
      'Leadership', 'Time Management', 'Study Skills'
    ]
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
            <a href="#register" className="btn-secondary-large">
              Register Now
            </a>
          </div>
        </div>
      </div>
    </div>
  )
}

export default ClassesPage

