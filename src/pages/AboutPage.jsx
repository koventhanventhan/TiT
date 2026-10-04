import React, { useState, useEffect } from 'react'
import { FiCheckCircle, FiUsers, FiAward, FiBookOpen, FiTarget, FiTrendingUp, FiHeart, FiStar, FiUser, FiImage, FiX, FiChevronLeft, FiChevronRight } from 'react-icons/fi'
import { useLocation } from 'react-router-dom'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import { useAuthModal } from '../context/AuthModalContext'
import './AboutPage.css'

const AboutPage = () => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()
  const { openRegister } = useAuthModal()

  const [aboutTitle, setAboutTitle] = useState(getSetting('about_title', t('about_title')))
  const [aboutSubtitle, setAboutSubtitle] = useState(getSetting('about_subtitle', t('about_subtitle')))
  const [aboutDescription, setAboutDescription] = useState(getSetting('about_description', t('about_hero_desc')))
  const [missionTitle, setMissionTitle] = useState(getSetting('about_mission_title', t('section_mission')))
  const [missionText, setMissionText] = useState(getSetting('about_mission_text', t('about_mission_text'))) // Note: t('about_mission_text') might not be in translations.js, falling back to setting

  const about_mission_image = getSetting('about_mission_image', 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&h=600&fit=crop')
  
  const [features, setFeatures] = useState([])
  const [values, setValues] = useState([])
  const [successfulJourney, setSuccessfulJourney] = useState([])
  const [teachers, setTeachers] = useState([])

  const [activeTab, setActiveTab] = useState('journey')
  const [activeGalleryItems, setActiveGalleryItems] = useState(null)
  const [activeGalleryIndex, setActiveGalleryIndex] = useState(0)

  // CTA States
  const [ctaTitle, setCtaTitle] = useState(getSetting('about_cta_title', t('section_cta_title')))
  const [ctaDesc, setCtaDesc] = useState(getSetting('about_cta_desc', t('section_cta_desc')))
  const [ctaBtn1, setCtaBtn1] = useState(getSetting('about_cta_btn1', t('btn_register_now')))
  const [ctaBtn2, setCtaBtn2] = useState(getSetting('about_cta_btn2', t('btn_contact_us')))

  const location = useLocation()

  useEffect(() => {
    // Deep link to specific tab from URL query param
    const params = new URLSearchParams(location.search)
    const tabParam = params.get('tab')
    if (tabParam === 'images') {
      setActiveTab('images')
    }
  }, [location])

  useEffect(() => {
    const translateArray = async (arr, fields) => {
      if (!Array.isArray(arr)) return arr
      return await Promise.all(arr.map(async (item) => {
        const newItem = { ...item }
        for (const field of fields) {
          if (newItem[field]) {
            newItem[field] = await translate(newItem[field])
          }
        }
        return newItem
      }))
    }

    const parseAndTranslate = async () => {
      // Features
      let fRaw = []
      try {
        fRaw = JSON.parse(getSetting('about_features', '[]'))
        if (fRaw.length === 0) {
          fRaw = [
            { icon: 'FiBookOpen', title: t('feature_online_title'), description: t('feature_online_desc'), image: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&h=400&fit=crop' },
            { icon: 'FiUsers', title: t('feature_service_title'), description: t('feature_service_desc'), image: 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&h=400&fit=crop' },
            { icon: 'FiAward', title: t('feature_success_title'), description: t('feature_success_desc'), image: 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=600&h=400&fit=crop' },
            { icon: 'FiCheckCircle', title: t('feature_tutors_title'), description: t('feature_tutors_desc'), image: 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=600&h=400&fit=crop' }
          ]
        }
      } catch (e) { fRaw = [] }

      // Values
      let vRaw = []
      try {
        vRaw = JSON.parse(getSetting('about_values', '[]'))
        if (vRaw.length === 0) {
          vRaw = [
            { icon: 'FiHeart', title: t('value_student_title'), description: t('value_student_desc') },
            { icon: 'FiAward', title: t('value_excellence_title'), description: t('value_excellence_desc') },
            { icon: 'FiUsers', title: t('value_community_title'), description: t('value_community_desc') }
          ]
        }
      } catch (e) { vRaw = [] }

      // Journey
      let jRaw = []
      try {
        jRaw = JSON.parse(getSetting('about_journey', '[]'))
        if (jRaw.length === 0) {
          jRaw = [
            { year: '2024', achievement: '10,000+ Students Enrolled', description: 'Reached a milestone of over 10,000 active students across all grades' },
            { year: '2023', achievement: '95% Pass Rate', description: 'Achieved outstanding 95% pass rate in O/L and A/L examinations' },
            { year: '2022', achievement: '500+ Qualified Teachers', description: 'Expanded our team to over 500 experienced and qualified tutors' },
            { year: '2021', achievement: 'Award Winning Platform', description: 'Recognized as the Best Online Education Platform in Sri Lanka' }
          ]
        }
      } catch (e) { jRaw = [] }

      // Teachers
      let tRaw = []
      try {
        tRaw = JSON.parse(getSetting('about_teachers', '[]'))
        if (tRaw.length === 0) {
          tRaw = [
            { name: 'Dr. Kamal Perera', subject: 'Mathematics', qualification: 'Ph.D. in Mathematics, University of Colombo', experience: '15+ years', image: 'https://via.placeholder.com/150' },
            { name: 'Ms. Nimali Fernando', subject: 'Science', qualification: 'M.Sc. in Physics, University of Peradeniya', experience: '12+ years', image: 'https://via.placeholder.com/150' },
            { name: 'Mr. Ashan Silva', subject: 'English', qualification: 'M.A. in English Literature, University of Kelaniya', experience: '10+ years', image: 'https://via.placeholder.com/150' },
            { name: 'Dr. Priyanka Jayawardena', subject: 'Chemistry', qualification: 'Ph.D. in Chemistry, University of Moratuwa', experience: '18+ years', image: 'https://via.placeholder.com/150' }
          ]
        }
      } catch (e) { tRaw = [] }

      if (language !== 'en') {
        const title = await translate(getSetting('about_title', t('about_title')))
        const sub = await translate(getSetting('about_subtitle', t('about_subtitle')))
        const desc = await translate(getSetting('about_description', t('about_hero_desc')))
        const mTitle = await translate(getSetting('about_mission_title', t('section_mission')))
        let mText = getSetting('about_mission_text', '') || t('about_mission_text')
        mText = await translate(mText)
        
        const ctaTitle = await translate(getSetting('about_cta_title', t('section_cta_title')))
        const ctaDesc = await translate(getSetting('about_cta_desc', t('section_cta_desc')))
        const ctaBtn1 = await translate(getSetting('about_cta_btn1', t('btn_register_now')))
        const ctaBtn2 = await translate(getSetting('about_cta_btn2', t('btn_contact_us')))
        
        setAboutTitle(title)
        setAboutSubtitle(sub)
        setAboutDescription(desc)
        setMissionTitle(mTitle)
        setMissionText(mText)
        
        setCtaTitle(ctaTitle)
        setCtaDesc(ctaDesc)
        setCtaBtn1(ctaBtn1)
        setCtaBtn2(ctaBtn2)

        setFeatures(await translateArray(fRaw, ['title', 'description']))
        setValues(await translateArray(vRaw, ['title', 'description']))
        setSuccessfulJourney(await translateArray(jRaw, ['achievement', 'description']))
        setTeachers(await translateArray(tRaw, ['name', 'subject', 'qualification', 'experience']))
      } else {
        setAboutTitle(getSetting('about_title', t('about_title')))
        setAboutSubtitle(getSetting('about_subtitle', t('about_subtitle')))
        setAboutDescription(getSetting('about_description', t('about_hero_desc')))
        setMissionTitle(getSetting('about_mission_title', t('section_mission')))
        setMissionText(getSetting('about_mission_text', t('about_mission_text')))
        
        setCtaTitle(getSetting('about_cta_title', t('section_cta_title')))
        setCtaDesc(getSetting('about_cta_desc', t('section_cta_desc')))
        setCtaBtn1(getSetting('about_cta_btn1', t('btn_register_now')))
        setCtaBtn2(getSetting('about_cta_btn2', t('btn_contact_us')))

        setFeatures(fRaw)
        setValues(vRaw)
        setSuccessfulJourney(jRaw)
        setTeachers(tRaw)
      }
    }
    parseAndTranslate()
  }, [language, translate, getSetting, t])

  // Mapping icons after translation
  const iconMap = {
    FiBookOpen: <FiBookOpen />,
    FiUsers: <FiUsers />,
    FiAward: <FiAward />,
    FiCheckCircle: <FiCheckCircle />,
    FiHeart: <FiHeart />,
    FiStar: <FiStar />,
    FiTarget: <FiTarget />,
    FiTrendingUp: <FiTrendingUp />
  }

  const finalFeatures = features.map(f => ({
    ...f,
    icon: typeof f.icon === 'string' ? (iconMap[f.icon] || <FiCheckCircle />) : f.icon
  }))

  const finalValues = values.map(v => ({
    ...v,
    icon: typeof v.icon === 'string' ? (iconMap[v.icon] || <FiStar />) : v.icon
  }))

  // Stats Section
  const stats_years = getSetting('stats_years', '10+')
  const stats_students = getSetting('stats_students', '10K+')
  const stats_tutors = getSetting('stats_tutors', '200+')
  const stats_success = getSetting('stats_success_rate', '98%')

  const stats = [
    { number: stats_years, label: t('years_experience') || 'Years Experience', icon: <FiTrendingUp /> },
    { number: stats_students, label: t('students') || 'Happy Students', icon: <FiUsers /> },
    { number: stats_tutors, label: t('tutors') || 'Expert Tutors', icon: <FiAward /> },
    { number: stats_success, label: t('success_rate') || 'Success Rate', icon: <FiTarget /> }
  ]

  // Dynamic data from settings
  const galleryRaw = getSetting('about_gallery', '[]')

  // No-op - removed constant declarations that are now in state

  let galleryImages = []
  try {
    galleryImages = JSON.parse(galleryRaw)
    if (!Array.isArray(galleryImages)) galleryImages = []
  } catch (e) {
    galleryImages = []
  }

  // Helper function to generate gallery images (removed auto-duplication)
  const generateGalleryImages = (baseImages) => {
    return [...baseImages]
  }

  // Build image containers from dynamic gallery or use defaults
  const defaultImageContainers = [
    {
      name: t('gallery_online_classes'),
      images: [
        { image: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=400&h=300&fit=crop', title: 'Live Class 1' },
        { image: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&h=300&fit=crop', title: 'Live Class 2' },
        { image: 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400&h=300&fit=crop', title: 'Live Class 3' },
        { image: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=400&h=300&fit=crop', title: 'Live Class 4' }
      ]
    },
    {
      name: t('gallery_success_stories'),
      images: [
        { image: 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=400&h=300&fit=crop', title: 'Achievement 1' },
        { image: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=400&h=300&fit=crop', title: 'Achievement 2' }
      ]
    },
    {
      name: t('gallery_teacher_training'),
      images: [
        { image: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=400&h=300&fit=crop', title: 'Training 1' },
        { image: 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=400&h=300&fit=crop', title: 'Training 2' }
      ]
    },
    {
      name: t('gallery_award_ceremony'),
      images: [
        { image: 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400&h=300&fit=crop', title: 'Award 1' },
        { image: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=400&h=300&fit=crop', title: 'Award 2' }
      ]
    }
  ]

  // Group dynamic gallery images by category
  const groupImagesByCategory = (images) => {
    if (!images || images.length === 0) return []
    
    // Get unique categories from the images themselves
    const categories = [...new Set(images.map(img => img.category).filter(Boolean))]
    
    // If no categories found in images, fallback to default list
    const finalCategories = categories.length > 0 ? categories : [
      t('gallery_online_classes'),
      t('gallery_success_stories'),
      t('gallery_teacher_training'),
      t('gallery_award_ceremony')
    ]
    
    return finalCategories.map(cat => ({
      name: cat,
      images: images.filter(img => img.category === cat).map(img => ({ image: img.image, title: img.title }))
    })).filter(container => container.images.length > 0)
  }

  const dynamicContainers = groupImagesByCategory(galleryImages)
  const imageContainersData = dynamicContainers.length > 0 ? dynamicContainers : defaultImageContainers

  const imageContainers = imageContainersData.map(container => ({
    ...container,
    galleryImages: generateGalleryImages(container.images)
  }))

  const handleImageClick = (containerIndex, imageIndex) => {
    setActiveGalleryItems(imageContainers[containerIndex].galleryImages)
    setActiveGalleryIndex(imageIndex)
  }

  const handleNextImage = () => {
    if (activeGalleryItems) {
      setActiveGalleryIndex((prev) => (prev + 1) % activeGalleryItems.length)
    }
  }

  const handlePrevImage = () => {
    if (activeGalleryItems) {
      setActiveGalleryIndex((prev) => (prev - 1 + activeGalleryItems.length) % activeGalleryItems.length)
    }
  }

  const handleCloseGallery = () => {
    setActiveGalleryItems(null)
    setActiveGalleryIndex(0)
  }

  // Handle keyboard navigation
  useEffect(() => {
    if (!activeGalleryItems) return

    const handleKeyPress = (e) => {
      if (e.key === 'ArrowRight') {
        setActiveGalleryIndex((prev) => (prev + 1) % activeGalleryItems.length)
      }
      if (e.key === 'ArrowLeft') {
        setActiveGalleryIndex((prev) => (prev - 1 + activeGalleryItems.length) % activeGalleryItems.length)
      }
      if (e.key === 'Escape') {
        setActiveGalleryItems(null)
        setActiveGalleryIndex(0)
      }
    }
    window.addEventListener('keydown', handleKeyPress)
    return () => window.removeEventListener('keydown', handleKeyPress)
  }, [activeGalleryItems])

  return (
    <div className="about-page">
      {/* Hero Section */}
      <section className="about-hero">
        <div className="container">
          <div className="about-hero-content">
            <h1 className="about-hero-title">{aboutTitle}</h1>
            <p className="about-hero-subtitle">
              {aboutSubtitle}
            </p>
            <p className="about-hero-description">
              {aboutDescription}
            </p>
          </div>
        </div>
      </section>

      {/* Three Buttons Section */}
      <section className="about-buttons-section">
        <div className="container">
          <div className="about-buttons-wrapper">
            <button
              className={`about-tab-btn ${activeTab === 'journey' ? 'active' : ''}`}
              onClick={() => setActiveTab('journey')}
            >
              <FiStar />
              {t('tab_journey')}
            </button>
            <button
              className={`about-tab-btn ${activeTab === 'teachers' ? 'active' : ''}`}
              onClick={() => setActiveTab('teachers')}
            >
              <FiUser />
              {t('tab_teachers')}
            </button>
            <button
              className={`about-tab-btn ${activeTab === 'images' ? 'active' : ''}`}
              onClick={() => setActiveTab('images')}
            >
              <FiImage />
              {t('tab_gallery')}
            </button>
          </div>
        </div>
      </section>

      {/* Teachers Details Content */}
      {activeTab === 'teachers' && (
        <section className="about-teachers-section">
          <div className="container">
            <h3 className="tab-content-title">{t('tab_teachers')}</h3>
            <div className="teachers-grid-tab">
              {teachers.map((teacher, index) => (
                <div key={index} className="teacher-card-tab">
                  <div className="teacher-image-tab">
                    <img src={teacher.image} alt={teacher.name} />
                  </div>
                  <div className="teacher-info-tab">
                    <h4 className="teacher-name-tab">{teacher.name}</h4>
                    <p className="teacher-subject-tab">{teacher.subject}</p>
                    <p className="teacher-qualification-tab">{teacher.qualification}</p>
                    <p className="teacher-experience-tab">{t('experience_years')}{teacher.experience}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Our Images Content */}
      {activeTab === 'images' && (
        <section className="about-images-section">
          <div className="container">
            <h3 className="tab-content-title">{t('tab_gallery')}</h3>
            <div className="image-containers-wrapper">
              {imageContainers.map((container, containerIndex) => (
                <div key={containerIndex} className="image-container">
                  <h4 className="image-container-title">{container.name}</h4>
                  <div className="images-grid-container">
                    {container.images.map((img, imgIndex) => (
                      <div
                        key={imgIndex}
                        className="gallery-image-item"
                        onClick={() => handleImageClick(containerIndex, imgIndex)}
                      >
                        <div className="gallery-image-wrapper">
                          <img src={img.image} alt={img.title} />
                        </div>
                        <p className="gallery-image-title">{img.title}</p>
                      </div>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Show all sections only when Successful Journey is active */}
      {activeTab === 'journey' && (
        <>
          {/* Journey Milestones Section */}
          <section className="about-journey-milestones">
            <div className="container">
              <div className="journey-timeline">
                {successfulJourney.map((milestone, index) => (
                  <div key={index} className="journey-timeline-item">
                    <div className="journey-year">{milestone.year}</div>
                    <div className="journey-details">
                      <h3 className="journey-achievement">{milestone.achievement}</h3>
                      <p className="journey-description">{milestone.description}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </section>

          {/* Stats Section */}
          <section className="about-stats-section">
            <div className="container">
              <div className="stats-grid">
                {stats.map((stat, index) => (
                  <div key={index} className="stat-card">
                    <div className="stat-icon">{stat.icon}</div>
                    <div className="stat-number">{stat.number}</div>
                    <div className="stat-label">{stat.label}</div>
                  </div>
                ))}
              </div>
            </div>
          </section>

          {/* Features Section */}
          <section className="about-features-section">
            <div className="container">
              <div className="section-header">
                <h2 className="section-title">{t('section_different')}</h2>
                <p className="section-subtitle">
                  {t('section_different_sub')}
                </p>
              </div>

              <div className="features-list">
                {finalFeatures.map((feature, index) => (
                  <div key={index} className={`feature-item ${index % 2 === 0 ? 'left-image' : 'right-image'}`}>
                    <div className="feature-image-wrapper">
                      <img src={feature.image} alt={feature.title} className="feature-image" />
                      <div className="feature-overlay"></div>
                    </div>
                    <div className="feature-content">
                      <div className="feature-icon-wrapper">
                        <div className="feature-icon">{feature.icon}</div>
                      </div>
                      <h3 className="feature-title">{feature.title}</h3>
                      <p className="feature-description">{feature.description}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </section>

          {/* Values Section */}
          <section className="about-values-section">
            <div className="container">
              <div className="section-header">
                <h2 className="section-title">{t('section_values')}</h2>
                <p className="section-subtitle">
                  {t('section_values_sub')}
                </p>
              </div>

              <div className="values-grid">
                {finalValues.map((value, index) => (
                  <div key={index} className="value-card">
                    <div className="value-icon">{value.icon}</div>
                    <h3 className="value-title">{value.title}</h3>
                    <p className="value-description">{value.description}</p>
                  </div>
                ))}
              </div>
            </div>
          </section>

          {/* Mission Section */}
          <section className="about-mission-section">
            <div className="container">
              <div className="mission-content">
                <div className="mission-text">
                  <h2 className="mission-title">{missionTitle}</h2>
                  <p className="mission-description">
                    {missionText}
                  </p>
                </div>
                <div className="mission-image-wrapper">
                  <img
                    src={about_mission_image}
                    alt="Mission"
                    className="mission-image"
                  />
                </div>
              </div>
            </div>
          </section>

          {/* CTA Section */}
          <section className="about-cta-section">
            <div className="container">
              <div className="cta-content">
                <h2 className="cta-title">{ctaTitle}</h2>
                <p className="cta-description">
                  {ctaDesc}
                </p>
                <div className="cta-buttons">
                  <button onClick={openRegister} className="btn btn-primary btn-large">
                    {ctaBtn1}
                  </button>
                  <a href={getSetting('about_cta_btn2_link', '/contact')} className="btn btn-secondary btn-large">
                    {ctaBtn2}
                  </a>
                </div>
              </div>
            </div>
          </section>
        </>
      )}

      {/* Image Gallery Modal */}
      {activeGalleryItems && (
        <div className="image-gallery-modal" onClick={handleCloseGallery}>
          <div className="gallery-modal-content" onClick={(e) => e.stopPropagation()}>
            <button className="gallery-close-btn" onClick={handleCloseGallery}>
              <FiX />
            </button>
            <button className="gallery-nav-btn gallery-prev-btn" onClick={handlePrevImage}>
              <FiChevronLeft />
            </button>
            <button className="gallery-nav-btn gallery-next-btn" onClick={handleNextImage}>
              <FiChevronRight />
            </button>
            <div className="gallery-main-image">
              <img
                src={activeGalleryItems[activeGalleryIndex].image.replace('w=400&h=300', 'w=1200&h=800')}
                alt={activeGalleryItems[activeGalleryIndex].title}
              />
              <div className="gallery-image-info">
                <h4>{activeGalleryItems[activeGalleryIndex].title}</h4>
                <p>{activeGalleryIndex + 1} / {activeGalleryItems.length}</p>
              </div>
            </div>
            <div className="gallery-thumbnails">
              {activeGalleryItems.map((img, index) => (
                <div
                  key={index}
                  className={`gallery-thumbnail ${index === activeGalleryIndex ? 'active' : ''}`}
                  onClick={() => setActiveGalleryIndex(index)}
                >
                  <img src={img.image} alt={img.title} />
                </div>
              ))}
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default AboutPage

