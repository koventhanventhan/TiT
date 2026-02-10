import React, { useState, useEffect, useRef } from 'react'
import { FiArrowRight } from 'react-icons/fi'
import { FaYoutube } from 'react-icons/fa'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './Hero.css'

const Hero = () => {
  const { getSetting } = useSettings()
  const { t, language } = useLanguage()

  const [yearsCount, setYearsCount] = useState(0)
  const [studentsCount, setStudentsCount] = useState(0)
  const [tutorsCount, setTutorsCount] = useState(0)
  const [hasAnimated, setHasAnimated] = useState(false)
  const statsRef = useRef(null)

  const hero_title = language === 'en' ? getSetting('hero_title', t('hero_title')) : t('hero_title')
  const hero_title_gradient = language === 'en' ? getSetting('hero_title_gradient', t('hero_title_gradient')) : t('hero_title_gradient')
  const hero_description = language === 'en' ? getSetting('hero_description', t('hero_description')) : t('hero_description')

  const stats_years = parseInt(getSetting('stats_years', 10))
  const stats_students = parseInt(getSetting('stats_students', 10000))
  const stats_tutors = parseInt(getSetting('stats_tutors', 200))

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

  const formatStudents = (num) => {
    if (num >= 1000) {
      return (num / 1000).toFixed(0) + 'K'
    }
    return num.toString()
  }

  return (
    <section id="home" className="hero">
      <div className="hero-background">
      </div>
      <div className="container">
        <div className="hero-content">
          <div className="hero-text">
            <h1 className="hero-title">
              {hero_title}
              <span className="gradient-text">{hero_title_gradient}</span>
            </h1>
            <p className="hero-description">
              {hero_description}
            </p>
            <div className="hero-actions">
              <a href="/register" className="btn btn-primary hero-btn">
                {t('hero_cta')}
                <FiArrowRight className="btn-icon" />
              </a>
              <a
                href="https://www.youtube.com/@titeducation2087"
                target="_blank"
                rel="noopener noreferrer"
                className="btn btn-youtube hero-btn"
              >
                <FaYoutube className="btn-icon youtube-icon" />
                TiT youtube
              </a>
            </div>
            <div className="hero-stats" ref={statsRef}>
              <div className="stat-item">
                <div className="stat-number">{yearsCount}+</div>
                <div className="stat-label">{t('years_experience')}</div>
              </div>
              <div className="stat-item">
                <div className="stat-number">{formatStudents(studentsCount)}+</div>
                <div className="stat-label">{t('students')}</div>
              </div>
              <div className="stat-item">
                <div className="stat-number">{tutorsCount}+</div>
                <div className="stat-label">{t('tutors')}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}

export default Hero


