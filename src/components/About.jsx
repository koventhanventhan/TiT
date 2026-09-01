import React, { useState, useEffect } from 'react'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import { useNavigate } from 'react-router-dom'
import { useAuthModal } from '../context/AuthModalContext'
import './About.css'

const About = () => {
  const { getSetting } = useSettings()
  const { t, language, translate } = useLanguage()
  const navigate = useNavigate()

  const [aboutTitle, setAboutTitle] = useState('')
  const [aboutSubtitle, setAboutSubtitle] = useState('')
  const [aboutHeroDesc, setAboutHeroDesc] = useState('')

  useEffect(() => {
    const defaultEn = {
      about_title: 'About TiT Online Education',
      about_subtitle: "Sri Lanka's Premier Choice for Online Tuition 🎓",
      about_hero_desc: "Sri Lanka's trusted leader in online tuition. We ensure student success through personalized learning and comprehensive parental support."
    }

    if (language !== 'en') {
      const translateAbout = async () => {
        const currentTitle = getSetting('about_title', defaultEn.about_title)
        const currentSubtitle = getSetting('about_subtitle', defaultEn.about_subtitle)
        const currentDesc = getSetting('about_hero_desc', defaultEn.about_hero_desc)

        const [title, subtitle, desc] = await Promise.all([
          currentTitle === defaultEn.about_title ? Promise.resolve(t('about_title')) : translate(currentTitle),
          currentSubtitle === defaultEn.about_subtitle ? Promise.resolve(t('about_subtitle')) : translate(currentSubtitle),
          currentDesc === defaultEn.about_hero_desc ? Promise.resolve(t('about_hero_desc')) : translate(currentDesc)
        ])

        setAboutTitle(title)
        setAboutSubtitle(subtitle)
        setAboutHeroDesc(desc)
      }
      translateAbout()
    } else {
      setAboutTitle(getSetting('about_title', t('about_title')))
      setAboutSubtitle(getSetting('about_subtitle', t('about_subtitle')))
      setAboutHeroDesc(getSetting('about_hero_desc', t('about_hero_desc')))
    }
  }, [language, getSetting, t, translate])

  const [activeTab, setActiveTab] = useState('journey')

  const features = [
    {
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/jtihyjyw.json"
          trigger="hover"
          colors="primary:#4f0bd9,secondary:#1a103c"
          style={{ width: '2.5rem', height: '2.5rem' }}
        />
      ),
      title: 'Top-notch Online Classes',
      description: 'Interactive live sessions with expert tutors'
    },
    {
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/dxjqoygy.json"
          trigger="hover"
          colors="primary:#4f0bd9,secondary:#1a103c"
          style={{ width: '2.5rem', height: '2.5rem' }}
        />
      ),
      title: 'Professional Service & Standards',
      description: 'Dedicated support team for every student'
    },
    {
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/fpisjznf.json"
          trigger="hover"
          colors="primary:#4f0bd9,secondary:#1a103c"
          style={{ width: '2.5rem', height: '2.5rem' }}
        />
      ),
      title: 'Guaranteed Academic Success',
      description: 'Proven track record of student achievements'
    },
    {
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/yqzmiobz.json"
          trigger="hover"
          colors="primary:#4f0bd9,secondary:#1a103c"
          style={{ width: '2.5rem', height: '2.5rem' }}
        />
      ),
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
      <div className="container" data-aos="fade-up">
        <div className="about-header">
          <h2 className="section-title">{aboutTitle}</h2>
          <p className="section-subtitle">
            {aboutSubtitle}
          </p>
        </div>

        <div className="about-hero">
          <div className="about-hero-content">
            <p className="hero-desc">
              {aboutHeroDesc}
            </p>
          </div>
        </div>

        {/* Three Buttons */}
        <div className="about-buttons">
          <button
            className={`about-btn ${activeTab === 'journey' ? 'active' : ''}`}
            onClick={() => setActiveTab('journey')}
          >
            <lord-icon
              src="https://cdn.lordicon.com/igiiqzue.json"
              trigger="hover"
              colors={activeTab === 'journey' ? "primary:#ffffff" : "primary:#4f0bd9"}
              style={{ width: '1.25rem', height: '1.25rem' }}
            />
            {t('about_tab_journey')}
          </button>
          <button
            className={`about-btn ${activeTab === 'teachers' ? 'active' : ''}`}
            onClick={() => setActiveTab('teachers')}
          >
            <lord-icon
              src="https://cdn.lordicon.com/dxjqoygy.json"
              trigger="hover"
              colors={activeTab === 'teachers' ? "primary:#ffffff" : "primary:#4f0bd9"}
              style={{ width: '1.25rem', height: '1.25rem' }}
            />
            {t('about_tab_teachers')}
          </button>
          <button
            className={`about-btn ${activeTab === 'images' ? 'active' : ''}`}
            onClick={() => setActiveTab('images')}
          >
            <lord-icon
              src="https://cdn.lordicon.com/fgpmetxx.json"
              trigger="hover"
              colors={activeTab === 'images' ? "primary:#ffffff" : "primary:#4f0bd9"}
              style={{ width: '1.25rem', height: '1.25rem' }}
            />
            {t('about_tab_images')}
          </button>
        </div>

        {/* Content Sections */}
        <div className="about-content-section">
          {activeTab === 'journey' && (
            <div className="journey-content">
              <h3 className="content-title">{t('about_journey_title')}</h3>
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
              <h3 className="content-title">{t('about_teachers_title')}</h3>
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
                      <p className="teacher-experience">{t('label_experience') || 'Experience'}: {teacher.experience}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {activeTab === 'images' && (
            <div className="images-content">
              <h3 className="content-title">{t('about_gallery_title')}</h3>
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
          <button onClick={() => navigate('/register')} className="btn btn-primary">
            {t('btn_register_now')}
          </button>
        </div>
      </div>
    </section>
  )
}

export default About
