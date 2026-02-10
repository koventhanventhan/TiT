import React, { useState } from 'react'
import { FiCheckCircle, FiUsers, FiAward, FiBookOpen, FiStar, FiUser, FiImage } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './About.css'

const About = () => {
  const { getSetting } = useSettings()

  const about_title = getSetting('about_title', 'About TiT Online Education')
  const about_subtitle = getSetting('about_subtitle', "Sri Lanka's Premier Choice for Online Tuition 🎓")
  const about_description = getSetting('about_description', "Sri Lanka's trusted leader in online tuition. We ensure student success through personalized learning and comprehensive parental support. Invest in your child's successful learning journey with TiT Online Education.")

  const [activeTab, setActiveTab] = useState('journey')

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

  const successfulJourney = [
    {
      year: '2024',
      achievement: '10,000+ Students Enrolled',
      description: 'Reached a milestone of over 10,000 active students across all grades'
    },
    {
      year: '2023',
      achievement: '95% Pass Rate',
      description: 'Achieved outstanding 95% pass rate in O/L and A/L examinations'
    },
    {
      year: '2022',
      achievement: '500+ Qualified Teachers',
      description: 'Expanded our team to over 500 experienced and qualified tutors'
    },
    {
      year: '2021',
      achievement: 'Award Winning Platform',
      description: 'Recognized as the Best Online Education Platform in Sri Lanka'
    }
  ]

  const teachers = [
    {
      name: 'Dr. Kamal Perera',
      subject: 'Mathematics',
      qualification: 'Ph.D. in Mathematics, University of Colombo',
      experience: '15+ years',
      image: 'https://via.placeholder.com/150'
    },
    {
      name: 'Ms. Nimali Fernando',
      subject: 'Science',
      qualification: 'M.Sc. in Physics, University of Peradeniya',
      experience: '12+ years',
      image: 'https://via.placeholder.com/150'
    },
    {
      name: 'Mr. Ashan Silva',
      subject: 'English',
      qualification: 'M.A. in English Literature, University of Kelaniya',
      experience: '10+ years',
      image: 'https://via.placeholder.com/150'
    },
    {
      name: 'Dr. Priyanka Jayawardena',
      subject: 'Chemistry',
      qualification: 'Ph.D. in Chemistry, University of Moratuwa',
      experience: '18+ years',
      image: 'https://via.placeholder.com/150'
    }
  ]

  const images = [
    {
      image: 'https://via.placeholder.com/400x300',
      title: 'Online Class Session',
      description: 'Interactive live classes with real-time student engagement'
    },
    {
      image: 'https://via.placeholder.com/400x300',
      title: 'Student Success Stories',
      description: 'Celebrating our students achievements and academic excellence'
    },
    {
      image: 'https://via.placeholder.com/400x300',
      title: 'Teacher Training',
      description: 'Continuous professional development for our teaching staff'
    },
    {
      image: 'https://via.placeholder.com/400x300',
      title: 'Award Ceremony',
      description: 'Recognizing outstanding students and their accomplishments'
    }
  ]

  return (
    <section id="about" className="about section">
      <div className="container">
        <div className="about-header">
          <h2 className="section-title">{about_title}</h2>
          <p className="section-subtitle">
            {about_subtitle}
          </p>
          <p className="about-description">
            {about_description}
          </p>

          {/* Three Buttons */}
          <div className="about-buttons">
            <button
              className={`about-btn ${activeTab === 'journey' ? 'active' : ''}`}
              onClick={() => setActiveTab('journey')}
            >
              <FiStar />
              Successful Journey
            </button>
            <button
              className={`about-btn ${activeTab === 'teachers' ? 'active' : ''}`}
              onClick={() => setActiveTab('teachers')}
            >
              <FiUser />
              Teachers Details
            </button>
            <button
              className={`about-btn ${activeTab === 'images' ? 'active' : ''}`}
              onClick={() => setActiveTab('images')}
            >
              <FiImage />
              Our Images
            </button>
          </div>

          {/* Content Sections */}
          <div className="about-content-section">
            {activeTab === 'journey' && (
              <div className="journey-content">
                <h3 className="content-title">Our Successful Journey</h3>
                <div className="journey-timeline">
                  {successfulJourney.map((item, index) => (
                    <div key={index} className="journey-item">
                      <div className="journey-year">{item.year}</div>
                      <div className="journey-details">
                        <h4 className="journey-achievement">{item.achievement}</h4>
                        <p className="journey-description">{item.description}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {activeTab === 'teachers' && (
              <div className="teachers-content">
                <h3 className="content-title">Our Expert Teachers</h3>
                <div className="teachers-grid">
                  {teachers.map((teacher, index) => (
                    <div key={index} className="teacher-card">
                      <div className="teacher-image">
                        <img src={teacher.image} alt={teacher.name} />
                      </div>
                      <div className="teacher-info">
                        <h4 className="teacher-name">{teacher.name}</h4>
                        <p className="teacher-subject">{teacher.subject}</p>
                        <p className="teacher-qualification">{teacher.qualification}</p>
                        <p className="teacher-experience">Experience: {teacher.experience}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {activeTab === 'images' && (
              <div className="images-content">
                <h3 className="content-title">Our Gallery</h3>
                <div className="images-grid">
                  {images.map((item, index) => (
                    <div key={index} className="image-card">
                      <div className="image-wrapper">
                        <img src={item.image} alt={item.title} />
                      </div>
                      <div className="image-info">
                        <h4 className="image-title">{item.title}</h4>
                        <p className="image-description">{item.description}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
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
          <a href="/register" className="btn btn-primary">
            Register Now
          </a>
        </div>
      </div>
    </section>
  )
}

export default About

