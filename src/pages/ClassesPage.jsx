import React from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import {
  FiBook, FiGlobe, FiArrowRight, FiCheck, FiUsers, FiClock,
  FiAward, FiMonitor, FiMapPin, FiStar, FiPlay, FiFileText
} from 'react-icons/fi'
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
      tagline: 'Face-to-Face Learning',
      description: getSetting('classes_direct_description', 'Comprehensive face-to-face learning experience with expert tutors in a physical classroom setting.'),
      accent: 'direct',
      features: getSetting('classes_direct_features', "Small group sessions\nDirect teacher interaction\nPhysical learning materials\nIn-person assessments\nFocus and discipline").split('\n').filter(f => f.trim()),
      duration: getSetting('classes_direct_duration', 'Flexible schedules'),
      students: getSetting('classes_direct_format', 'Small Groups'),
      price: getSetting('classes_direct_price', 'Affordable rates'),
      highlights: [
        { icon: <FiMapPin />, label: 'In-Person' },
        { icon: <FiUsers />, label: 'Small Groups' },
        { icon: <FiStar />, label: 'Expert Tutors' }
      ]
    },
    {
      id: 2,
      icon: <FiGlobe />,
      title: 'Online Class',
      tagline: 'Learn From Anywhere',
      description: getSetting('classes_online_description', 'Convenient live interactive sessions accessible from anywhere with high-quality digital resources.'),
      accent: 'online',
      features: getSetting('classes_online_features', "Interactive live classes\nRecorded lesson access\nDigital study materials\nOnline quizzes/exams\nFlexible learning from home").split('\n').filter(f => f.trim()),
      duration: getSetting('classes_online_duration', 'Flexible schedules'),
      students: getSetting('classes_online_format', 'Group & One-on-One'),
      price: getSetting('classes_online_price', 'Competitive pricing'),
      highlights: [
        { icon: <FiMonitor />, label: 'Live Sessions' },
        { icon: <FiPlay />, label: 'Recordings' },
        { icon: <FiFileText />, label: 'Resources' }
      ]
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
    <div className="clp">
      {/* ═══ HERO ═══ */}
      <section className="clp-hero">
        <div className="clp-hero-bg">
          <div className="clp-orb clp-orb-1"></div>
          <div className="clp-orb clp-orb-2"></div>
          <div className="clp-hero-grid"></div>
        </div>
        <div className="container">
          <div className="clp-hero-content">
            <span className="clp-hero-badge"><FiBook /> Our Classes</span>
            <h1 className="clp-hero-title">
              Explore & <span>Enroll</span>
            </h1>
            <p className="clp-hero-sub">
              Online Tuition for all subjects. Grade 1 to Advanced Level. Group or one-on-one? We got you!
            </p>
          </div>
        </div>
      </section>

      {/* ═══ CLASS CARDS ═══ */}
      <section className="clp-content">
        <div className="container">
          <div className="clp-cards">
            {classTypes.map((ct) => (
              <div key={ct.id} className={`clp-card clp-card-${ct.accent}`}>
                {/* Card Header */}
                <div className="clp-card-head">
                  <div className="clp-card-head-bg"></div>
                  <div className="clp-card-head-inner">
                    <div className="clp-card-icon">{ct.icon}</div>
                    <div>
                      <span className="clp-card-tag">{ct.tagline}</span>
                      <h2 className="clp-card-title">{ct.title}</h2>
                    </div>
                  </div>
                  <p className="clp-card-desc">{ct.description}</p>
                  {/* Highlight pills */}
                  <div className="clp-highlights">
                    {ct.highlights.map((h, i) => (
                      <span key={i} className="clp-highlight">
                        {h.icon} {h.label}
                      </span>
                    ))}
                  </div>
                </div>

                {/* Card Body */}
                <div className="clp-card-body">
                  <div className="clp-sections">
                    {/* Features */}
                    <div className="clp-sec">
                      <h3><FiCheck /> What's Included</h3>
                      <ul className="clp-features">
                        {ct.features.map((f, i) => (
                          <li key={i}><FiCheck /> {f}</li>
                        ))}
                      </ul>
                    </div>

                    {/* Subjects */}
                    <div className="clp-sec">
                      <h3><FiBook /> Available Subjects</h3>
                      <div className="clp-badges">
                        {subjects[ct.title]?.map((s, i) => (
                          <span key={i} className="clp-badge">{s}</span>
                        ))}
                      </div>
                    </div>

                    {/* Details */}
                    <div className="clp-sec">
                      <h3><FiUsers /> Class Details</h3>
                      <div className="clp-details">
                        <div className="clp-detail">
                          <FiClock />
                          <div>
                            <strong>Duration</strong>
                            <span>{ct.duration}</span>
                          </div>
                        </div>
                        <div className="clp-detail">
                          <FiUsers />
                          <div>
                            <strong>Format</strong>
                            <span>{ct.students}</span>
                          </div>
                        </div>
                        <div className="clp-detail">
                          <FiAward />
                          <div>
                            <strong>Pricing</strong>
                            <span>{ct.price}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* CTA */}
                  <div className="clp-card-cta">
                    <Link to="/contact" className="clp-btn-enroll">
                      Enroll Now <FiArrowRight />
                    </Link>
                    <a href="#register" className="clp-btn-outline">
                      Learn More
                    </a>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Bottom CTA */}
          <div className="clp-bottom-cta">
            <div className="clp-bottom-cta-bg"></div>
            <h2>Ready to Start Learning?</h2>
            <p>Choose your curriculum and start your journey to academic success today!</p>
            <div className="clp-bottom-btns">
              <Link to="/contact" className="clp-btn-primary">
                Get Started <FiArrowRight />
              </Link>
              <a href="/register" className="clp-btn-secondary">
                Register Now
              </a>
            </div>
          </div>
        </div>
      </section>
    </div>
  )
}

export default ClassesPage
