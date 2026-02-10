import React, { useState, useEffect } from 'react'
import { FiCheckCircle, FiUsers, FiAward, FiBookOpen, FiTarget, FiTrendingUp, FiHeart, FiStar, FiUser, FiImage, FiX, FiChevronLeft, FiChevronRight } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './AboutPage.css'

const AboutPage = () => {
  const { getSetting } = useSettings()

  const about_title = getSetting('about_title', 'About TiT Online Education')
  const about_subtitle = getSetting('about_subtitle', "Sri Lanka's Premier Choice for Online Tuition 🎓")
  const about_description = getSetting('about_description', "Sri Lanka's trusted leader in online tuition. We ensure student success through personalized learning and comprehensive parental support. Invest in your child's successful learning journey with TiT Online Education.")
  const about_mission_title = getSetting('about_mission_title', 'Our Mission')
  const about_mission_text = getSetting('about_mission_text', 'To democratize quality education by making world-class online tuition accessible to every student in Sri Lanka. We believe that every child deserves the opportunity to excel academically, regardless of their location or background. Through innovative teaching methods, personalized learning paths, and dedicated support, we empower students to achieve their full potential and succeed in their academic journey.')

  const [activeTab, setActiveTab] = useState('journey')
  const [selectedImageGallery, setSelectedImageGallery] = useState(null)
  const [selectedImageIndex, setSelectedImageIndex] = useState(0)
  const features = [
    {
      icon: <FiBookOpen />,
      title: 'Top-notch Online Classes',
      description: 'Interactive live sessions with expert tutors designed to engage and inspire students.',
      image: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&h=400&fit=crop'
    },
    {
      icon: <FiUsers />,
      title: 'Professional Service & Standards',
      description: 'Dedicated support team for every student ensuring personalized attention and care.',
      image: 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&h=400&fit=crop'
    },
    {
      icon: <FiAward />,
      title: 'Guaranteed Academic Success',
      description: 'Proven track record of student achievements with measurable results and improvements.',
      image: 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=600&h=400&fit=crop'
    },
    {
      icon: <FiCheckCircle />,
      title: 'Qualified Professional Tutors',
      description: 'Experienced educators committed to your success with years of teaching expertise.',
      image: 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=600&h=400&fit=crop'
    }
  ]

  const stats = [
    { number: '10+', label: 'Years Experience', icon: <FiTrendingUp /> },
    { number: '10K+', label: 'Happy Students', icon: <FiUsers /> },
    { number: '200+', label: 'Expert Tutors', icon: <FiAward /> },
    { number: '98%', label: 'Success Rate', icon: <FiTarget /> }
  ]

  const values = [
    {
      icon: <FiHeart />,
      title: 'Student-Centered Approach',
      description: 'Every decision we make prioritizes student success and learning outcomes.'
    },
    {
      icon: <FiAward />,
      title: 'Excellence in Education',
      description: 'We maintain the highest standards in curriculum design and teaching methodology.'
    },
    {
      icon: <FiUsers />,
      title: 'Community Building',
      description: 'Fostering a supportive learning community where students thrive together.'
    }
  ]

  // Dynamic data from settings
  const journeyRaw = getSetting('about_journey', '[]')
  const teachersRaw = getSetting('about_teachers', '[]')
  const galleryRaw = getSetting('about_gallery', '[]')

  // Parse JSON with fallback to defaults
  let successfulJourney = []
  try {
    successfulJourney = JSON.parse(journeyRaw)
    if (!Array.isArray(successfulJourney) || successfulJourney.length === 0) {
      successfulJourney = [
        { year: '2024', achievement: '10,000+ Students Enrolled', description: 'Reached a milestone of over 10,000 active students across all grades' },
        { year: '2023', achievement: '95% Pass Rate', description: 'Achieved outstanding 95% pass rate in O/L and A/L examinations' },
        { year: '2022', achievement: '500+ Qualified Teachers', description: 'Expanded our team to over 500 experienced and qualified tutors' },
        { year: '2021', achievement: 'Award Winning Platform', description: 'Recognized as the Best Online Education Platform in Sri Lanka' }
      ]
    }
  } catch (e) {
    successfulJourney = [
      { year: '2024', achievement: '10,000+ Students Enrolled', description: 'Reached a milestone of over 10,000 active students across all grades' },
      { year: '2023', achievement: '95% Pass Rate', description: 'Achieved outstanding 95% pass rate in O/L and A/L examinations' },
      { year: '2022', achievement: '500+ Qualified Teachers', description: 'Expanded our team to over 500 experienced and qualified tutors' },
      { year: '2021', achievement: 'Award Winning Platform', description: 'Recognized as the Best Online Education Platform in Sri Lanka' }
    ]
  }

  let teachers = []
  try {
    teachers = JSON.parse(teachersRaw)
    if (!Array.isArray(teachers) || teachers.length === 0) {
      teachers = [
        { name: 'Dr. Kamal Perera', subject: 'Mathematics', qualification: 'Ph.D. in Mathematics, University of Colombo', experience: '15+ years', image: 'https://via.placeholder.com/150' },
        { name: 'Ms. Nimali Fernando', subject: 'Science', qualification: 'M.Sc. in Physics, University of Peradeniya', experience: '12+ years', image: 'https://via.placeholder.com/150' },
        { name: 'Mr. Ashan Silva', subject: 'English', qualification: 'M.A. in English Literature, University of Kelaniya', experience: '10+ years', image: 'https://via.placeholder.com/150' },
        { name: 'Dr. Priyanka Jayawardena', subject: 'Chemistry', qualification: 'Ph.D. in Chemistry, University of Moratuwa', experience: '18+ years', image: 'https://via.placeholder.com/150' }
      ]
    }
  } catch (e) {
    teachers = [
      { name: 'Dr. Kamal Perera', subject: 'Mathematics', qualification: 'Ph.D. in Mathematics, University of Colombo', experience: '15+ years', image: 'https://via.placeholder.com/150' },
      { name: 'Ms. Nimali Fernando', subject: 'Science', qualification: 'M.Sc. in Physics, University of Peradeniya', experience: '12+ years', image: 'https://via.placeholder.com/150' },
      { name: 'Mr. Ashan Silva', subject: 'English', qualification: 'M.A. in English Literature, University of Kelaniya', experience: '10+ years', image: 'https://via.placeholder.com/150' },
      { name: 'Dr. Priyanka Jayawardena', subject: 'Chemistry', qualification: 'Ph.D. in Chemistry, University of Moratuwa', experience: '18+ years', image: 'https://via.placeholder.com/150' }
    ]
  }

  let galleryImages = []
  try {
    galleryImages = JSON.parse(galleryRaw)
    if (!Array.isArray(galleryImages)) galleryImages = []
  } catch (e) {
    galleryImages = []
  }

  // Helper function to generate gallery images (minimum 10 images)
  const generateGalleryImages = (baseImages, containerName) => {
    let galleryImages = [...baseImages]

    // If we have less than 10 images, duplicate and modify to reach minimum 10
    while (galleryImages.length < 10) {
      const additionalImages = baseImages.map((img, index) => ({
        image: img.image.replace('w=400&h=300', 'w=800&h=600'),
        title: `${containerName} ${galleryImages.length + index + 1}`
      }))
      galleryImages = [...galleryImages, ...additionalImages]
    }

    // Return exactly 10 images (or more if base had more)
    return galleryImages.slice(0, Math.max(10, galleryImages.length))
  }

  // Build image containers from dynamic gallery or use defaults
  const defaultImageContainers = [
    {
      name: 'Online Class Sessions',
      images: [
        { image: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=400&h=300&fit=crop', title: 'Live Class 1' },
        { image: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&h=300&fit=crop', title: 'Live Class 2' },
        { image: 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400&h=300&fit=crop', title: 'Live Class 3' },
        { image: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=400&h=300&fit=crop', title: 'Live Class 4' }
      ]
    },
    {
      name: 'Student Success Stories',
      images: [
        { image: 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=400&h=300&fit=crop', title: 'Achievement 1' },
        { image: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=400&h=300&fit=crop', title: 'Achievement 2' }
      ]
    },
    {
      name: 'Teacher Training',
      images: [
        { image: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=400&h=300&fit=crop', title: 'Training 1' },
        { image: 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=400&h=300&fit=crop', title: 'Training 2' }
      ]
    },
    {
      name: 'Award Ceremony',
      images: [
        { image: 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400&h=300&fit=crop', title: 'Award 1' },
        { image: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=400&h=300&fit=crop', title: 'Award 2' }
      ]
    }
  ]

  // Group dynamic gallery images by category
  const groupImagesByCategory = (images) => {
    const categories = ['Online Class Sessions', 'Student Success Stories', 'Teacher Training', 'Award Ceremony']
    return categories.map(cat => ({
      name: cat,
      images: images.filter(img => img.category === cat).map(img => ({ image: img.image, title: img.title }))
    })).filter(container => container.images.length > 0)
  }

  const dynamicContainers = galleryImages.length > 0 ? groupImagesByCategory(galleryImages) : []
  const imageContainersData = dynamicContainers.length > 0 ? dynamicContainers : defaultImageContainers

  const imageContainers = imageContainersData.map(container => ({
    ...container,
    galleryImages: generateGalleryImages(container.images, container.name)
  }))

  const handleImageClick = (containerIndex, imageIndex) => {
    setSelectedImageGallery(imageContainers[containerIndex].galleryImages)
    setSelectedImageIndex(imageIndex)
  }

  const handleNextImage = () => {
    if (selectedImageGallery) {
      setSelectedImageIndex((prev) => (prev + 1) % selectedImageGallery.length)
    }
  }

  const handlePrevImage = () => {
    if (selectedImageGallery) {
      setSelectedImageIndex((prev) => (prev - 1 + selectedImageGallery.length) % selectedImageGallery.length)
    }
  }

  const handleCloseGallery = () => {
    setSelectedImageGallery(null)
    setSelectedImageIndex(0)
  }

  // Handle keyboard navigation
  useEffect(() => {
    if (!selectedImageGallery) return

    const handleKeyPress = (e) => {
      if (e.key === 'ArrowRight') {
        setSelectedImageIndex((prev) => (prev + 1) % selectedImageGallery.length)
      }
      if (e.key === 'ArrowLeft') {
        setSelectedImageIndex((prev) => (prev - 1 + selectedImageGallery.length) % selectedImageGallery.length)
      }
      if (e.key === 'Escape') {
        setSelectedImageGallery(null)
        setSelectedImageIndex(0)
      }
    }
    window.addEventListener('keydown', handleKeyPress)
    return () => window.removeEventListener('keydown', handleKeyPress)
  }, [selectedImageGallery])

  return (
    <div className="about-page">
      {/* Hero Section */}
      <section className="about-hero">
        <div className="container">
          <div className="about-hero-content">
            <h1 className="about-hero-title">{about_title}</h1>
            <p className="about-hero-subtitle">
              {about_subtitle}
            </p>
            <p className="about-hero-description">
              {about_description}
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
              Successful Journey
            </button>
            <button
              className={`about-tab-btn ${activeTab === 'teachers' ? 'active' : ''}`}
              onClick={() => setActiveTab('teachers')}
            >
              <FiUser />
              Teachers Details
            </button>
            <button
              className={`about-tab-btn ${activeTab === 'images' ? 'active' : ''}`}
              onClick={() => setActiveTab('images')}
            >
              <FiImage />
              Our Images
            </button>
          </div>
        </div>
      </section>

      {/* Teachers Details Content */}
      {activeTab === 'teachers' && (
        <section className="about-teachers-section">
          <div className="container">
            <h3 className="tab-content-title">Our Expert Teachers</h3>
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
                    <p className="teacher-experience-tab">Experience: {teacher.experience}</p>
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
            <h3 className="tab-content-title">Our Gallery</h3>
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
                <h2 className="section-title">What Makes Us Different</h2>
                <p className="section-subtitle">
                  Quality Assured Online Learning with Proven Results
                </p>
              </div>

              <div className="features-list">
                {features.map((feature, index) => (
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
                <h2 className="section-title">Our Core Values</h2>
                <p className="section-subtitle">
                  The Principles That Guide Everything We Do
                </p>
              </div>

              <div className="values-grid">
                {values.map((value, index) => (
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
                  <h2 className="mission-title">{about_mission_title}</h2>
                  <p className="mission-description">
                    {about_mission_text}
                  </p>
                </div>
                <div className="mission-image-wrapper">
                  <img
                    src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&h=600&fit=crop"
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
                <h2 className="cta-title">Ready to Start Your Learning Journey?</h2>
                <p className="cta-description">
                  Join thousands of students who are already achieving academic excellence with TiT Online Education.
                </p>
                <div className="cta-buttons">
                  <a href="/register" className="btn btn-primary btn-large">
                    Register Now
                  </a>
                  <a href="/contact" className="btn btn-secondary btn-large">
                    Contact Us
                  </a>
                </div>
              </div>
            </div>
          </section>
        </>
      )}

      {/* Image Gallery Modal */}
      {selectedImageGallery && (
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
                src={selectedImageGallery[selectedImageIndex].image.replace('w=400&h=300', 'w=1200&h=800')}
                alt={selectedImageGallery[selectedImageIndex].title}
              />
              <div className="gallery-image-info">
                <h4>{selectedImageGallery[selectedImageIndex].title}</h4>
                <p>{selectedImageIndex + 1} / {selectedImageGallery.length}</p>
              </div>
            </div>
            <div className="gallery-thumbnails">
              {selectedImageGallery.map((img, index) => (
                <div
                  key={index}
                  className={`gallery-thumbnail ${index === selectedImageIndex ? 'active' : ''}`}
                  onClick={() => setSelectedImageIndex(index)}
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

