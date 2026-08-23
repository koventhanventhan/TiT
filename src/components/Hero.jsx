import React, { useState, useEffect, useRef } from 'react'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import { FaCalendarAlt, FaGraduationCap, FaChalkboardTeacher, FaYoutube, FaArrowRight } from 'react-icons/fa'
import { useAuthModal } from '../context/AuthModalContext'
import './Hero.css'

const Hero = () => {
  const { getSetting } = useSettings()
  const { t, language, translate } = useLanguage()
  const { openRegister } = useAuthModal()

  const [yearsCount, setYearsCount] = useState(0)
  const [studentsCount, setStudentsCount] = useState(0)
  const [tutorsCount, setTutorsCount] = useState(0)
  const [hasAnimated, setHasAnimated] = useState(false)

  // Track settings in state to force re-animation or update if they change
  useEffect(() => {
    if (hasAnimated) {
      setYearsCount(parseInt(getSetting('stats_years', '0').toString().replace(/[^0-9]/g, '')) || 0)
      setStudentsCount(parseInt(getSetting('stats_students', '0').toString().replace(/[^0-9]/g, '')) || 0)
      setTutorsCount(parseInt(getSetting('stats_tutors', '0').toString().replace(/[^0-9]/g, '')) || 0)
    }
  }, [getSetting('stats_years'), getSetting('stats_students'), getSetting('stats_tutors')])

  const [heroTitle, setHeroTitle] = useState(getSetting('hero_title', t('hero_title')))
  const [heroTitleGradient, setHeroTitleGradient] = useState(getSetting('hero_title_gradient', t('hero_title_gradient')))
  const [heroDescription, setHeroDescription] = useState(getSetting('hero_description', t('hero_description')))

  // Hero Background & Slider Settings
  const heroBgMode = getSetting('hero_bg_mode', 'default')
  const heroBgImage = getSetting('hero_bg_image', '')
  const heroSliderImagesRaw = getSetting('hero_slider_images', '[]')
  const heroSliderInterval = (parseInt(getSetting('hero_slider_interval', '5')) || 5) * 1000
  const heroOverlayOpacity = parseFloat(getSetting('hero_overlay_opacity', '0.55'))
  const heroOverlayColor = getSetting('hero_overlay_color', '#0a0f1e')

  let heroSlides = []
  try {
    heroSlides = JSON.parse(heroSliderImagesRaw)
    if (!Array.isArray(heroSlides)) heroSlides = []
  } catch (e) {
    heroSlides = []
  }

  const [currentSlide, setCurrentSlide] = useState(0)

  useEffect(() => {
    if (heroBgMode === 'slider' && heroSlides.length > 1) {
      const timer = setInterval(() => {
        setCurrentSlide((prev) => (prev + 1) % heroSlides.length)
      }, heroSliderInterval)
      return () => clearInterval(timer)
    }
  }, [heroBgMode, heroSlides.length, heroSliderInterval])

  const statsRef = useRef(null)

  useEffect(() => {
    const defaultEn = {
      hero_title: 'Experience the Future of',
      hero_title_gradient: 'Quality Online Learning',
      hero_description: 'Top-notch online tutoring from qualified tutors at the comfort of your home. Join thousands of students achieving academic excellence with personalized learning.'
    }

    if (language !== 'en') {
      const translateHero = async () => {
        const currentTitle = getSetting('hero_title', defaultEn.hero_title)
        const currentGradient = getSetting('hero_title_gradient', defaultEn.hero_title_gradient)
        const currentDesc = getSetting('hero_description', defaultEn.hero_description)

        const [title, gradient, desc] = await Promise.all([
          currentTitle === defaultEn.hero_title ? Promise.resolve(t('hero_title')) : translate(currentTitle),
          currentGradient === defaultEn.hero_title_gradient ? Promise.resolve(t('hero_title_gradient')) : translate(currentGradient),
          currentDesc === defaultEn.hero_description ? Promise.resolve(t('hero_description')) : translate(currentDesc)
        ])

        setHeroTitle(title)
        setHeroTitleGradient(gradient)
        setHeroDescription(desc)
      }
      translateHero()
    } else {
      setHeroTitle(getSetting('hero_title', t('hero_title')))
      setHeroTitleGradient(getSetting('hero_title_gradient', t('hero_title_gradient')))
      setHeroDescription(getSetting('hero_description', t('hero_description')))
    }
  }, [language, getSetting, translate, t])

  const raw_years = getSetting('stats_years', '10')
  const raw_students = getSetting('stats_students', '10000')
  const raw_tutors = getSetting('stats_tutors', '200')

  const stats_years = parseInt(raw_years.toString().replace(/[^0-9]/g, '')) || 0
  const stats_students = parseInt(raw_students.toString().replace(/[^0-9]/g, '')) || 0
  const stats_tutors = parseInt(raw_tutors.toString().replace(/[^0-9]/g, '')) || 0

  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting && !hasAnimated) {
            setHasAnimated(true)
            animateCounters()
          }
        })
      },
      { threshold: 0.3 }
    )

    if (statsRef.current) {
      observer.observe(statsRef.current)
    }

    return () => {
      if (statsRef.current) {
        observer.unobserve(statsRef.current)
      }
    }
  }, [hasAnimated, stats_years, stats_students, stats_tutors])

  const animateCounters = () => {
    // Animate Years
    const yearsDuration = 2000
    const yearsSteps = 60
    const yearsIncrement = stats_years / yearsSteps
    let yearsCurrent = 0
    const yearsInterval = setInterval(() => {
      yearsCurrent += yearsIncrement
      if (yearsCurrent >= stats_years) {
        setYearsCount(stats_years)
        clearInterval(yearsInterval)
      } else {
        setYearsCount(Math.floor(yearsCurrent))
      }
    }, yearsDuration / yearsSteps)

    // Animate Students
    const studentsDuration = 2000
    const studentsSteps = 60
    const studentsIncrement = stats_students / studentsSteps
    let studentsCurrent = 0
    const studentsInterval = setInterval(() => {
      studentsCurrent += studentsIncrement
      if (studentsCurrent >= stats_students) {
        setStudentsCount(stats_students)
        clearInterval(studentsInterval)
      } else {
        setStudentsCount(Math.floor(studentsCurrent))
      }
    }, studentsDuration / studentsSteps)

    // Animate Tutors
    const tutorsDuration = 2000
    const tutorsSteps = 60
    const tutorsIncrement = stats_tutors / tutorsSteps
    let tutorsCurrent = 0
    const tutorsInterval = setInterval(() => {
      tutorsCurrent += tutorsIncrement
      if (tutorsCurrent >= stats_tutors) {
        setTutorsCount(stats_tutors)
        clearInterval(tutorsInterval)
      } else {
        setTutorsCount(Math.floor(tutorsCurrent))
      }
    }, tutorsDuration / tutorsSteps)
  }

  const formatStatValue = (value, count) => {
    // If original setting is a string with non-numeric chars (like "10K+", "+200"), 
    // we should try to preserve the formatting or just show the setting if it doesn't look like a simple number.
    const settingString = value.toString().trim()
    const isNumeric = !isNaN(parseFloat(settingString)) && isFinite(settingString)

    if (!isNumeric) return settingString

    // For numeric values, we use the animated count
    if (count >= 1000) {
      return (count / 1000).toFixed(0) + 'K'
    }
    return count.toString()
  }

  return (
    <section id="home" className={`hero ${heroBgMode !== 'default' ? 'has-custom-bg' : ''}`}>
      {/* Background Mode Rendering */}
      {heroBgMode === 'slider' && heroSlides.length > 0 ? (
        <div className="hero-slider-bg">
          {heroSlides.map((slide, index) => {
            const slideImg = typeof slide === 'string' ? slide : (slide.image || '')
            return (
              <div
                key={index}
                className={`hero-slide-item ${index === currentSlide ? 'active' : ''}`}
                style={{ backgroundImage: `url(${slideImg})` }}
              />
            )
          })}
          <div
            className="hero-bg-overlay"
            style={{
              backgroundColor: heroOverlayColor,
              opacity: heroOverlayOpacity
            }}
          />
          {heroSlides.length > 1 && (
            <div className="hero-slider-dots">
              {heroSlides.map((_, index) => (
                <button
                  key={index}
                  type="button"
                  className={`hero-dot ${index === currentSlide ? 'active' : ''}`}
                  onClick={() => setCurrentSlide(index)}
                  aria-label={`Go to slide ${index + 1}`}
                />
              ))}
            </div>
          )}
        </div>
      ) : heroBgMode === 'single' && heroBgImage ? (
        <div className="hero-single-bg-wrap">
          <div
            className="hero-single-bg"
            style={{ backgroundImage: `url(${heroBgImage})` }}
          />
          <div
            className="hero-bg-overlay"
            style={{
              backgroundColor: heroOverlayColor,
              opacity: heroOverlayOpacity
            }}
          />
        </div>
      ) : (
        <div className="hero-background"></div>
      )}

      <div className="container">
        <div className="hero-content">
          <div className="hero-text">
            <h1 className="hero-title">
              {heroTitle}{' '}
              <span className="gradient-text">{heroTitleGradient}</span>
            </h1>
            <p className="hero-description">
              {heroDescription}
            </p>
            <div className="hero-actions">
              <button onClick={openRegister} className="btn-register hero-btn">
                {t('hero_cta')}
                <FaArrowRight style={{ fontSize: '1rem', marginLeft: '0.5rem', color: '#ffffff' }} />
              </button>
              <a
                href="https://www.youtube.com/@titeducation2087"
                target="_blank"
                rel="noopener noreferrer"
                className="btn btn-youtube hero-btn"
              >
                <FaYoutube style={{ fontSize: '1.125rem', marginRight: '0.5rem', color: '#ffffff' }} />
                {t('hero_youtube')}
              </a>
            </div>
            <div className="hero-stats" ref={statsRef}>
              <div className="stat-item">
                <div className="stat-icon-wrapper">
                  <FaCalendarAlt style={{ fontSize: '1.5rem', color: '#4f0bd9' }} />
                </div>
                <div>
                  <div className="stat-number">
                    {formatStatValue(raw_years, yearsCount)}
                    {!isNaN(parseFloat(raw_years)) && <span className="stat-plus">+</span>}
                  </div>
                  <div className="stat-label">{t('years_experience')}</div>
                </div>
              </div>
              <div className="stat-item">
                <div className="stat-icon-wrapper">
                  <FaGraduationCap style={{ fontSize: '1.75rem', color: '#4f0bd9' }} />
                </div>
                <div>
                  <div className="stat-number">
                    {formatStatValue(raw_students, studentsCount)}
                    {!isNaN(parseFloat(raw_students)) && <span className="stat-plus">+</span>}
                  </div>
                  <div className="stat-label">{t('students')}</div>
                </div>
              </div>
              <div className="stat-item">
                <div className="stat-icon-wrapper">
                  <FaChalkboardTeacher style={{ fontSize: '1.5rem', color: '#4f0bd9' }} />
                </div>
                <div>
                  <div className="stat-number">
                    {formatStatValue(raw_tutors, tutorsCount)}
                    {!isNaN(parseFloat(raw_tutors)) && <span className="stat-plus">+</span>}
                  </div>
                  <div className="stat-label">{t('tutors')}</div>
                </div>
              </div>
            </div>
          </div>

          <div className="hero-visual-side">
            <img src="/Thesis.gif" alt="Hero Illustration" className="hero-mobile-img" />
            <lord-icon
              src="https://cdn.lordicon.com/jtihyjyw.json"
              trigger="loop"
              delay="2000"
              colors="primary:#4f0bd9,secondary:#1a103c"
              style={{ width: '100%', maxWidth: '31.25rem', height: '31.25rem' }}
            />
          </div>
        </div>
      </div>
    </section>
  )
}

export default Hero


