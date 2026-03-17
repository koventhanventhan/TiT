import React, { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { FiArrowRight, FiBook, FiGlobe, FiMapPin, FiMonitor, FiUsers, FiStar, FiClock } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './Classes.css'

const Classes = () => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()

  const [classesTitle, setClassesTitle] = useState(getSetting('classes_title', t('classes_title')))
  const [classesSubtitle, setClassesSubtitle] = useState(getSetting('classes_subtitle', t('classes_subtitle')))
  const [classData, setClassData] = useState([])

  useEffect(() => {
    const baseData = [
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

    const defaultEn = {
      classes_title: 'Our Classes',
      classes_subtitle: 'Find the perfect class for your academic goals.'
    }

    if (language !== 'en') {
      const translateAll = async () => {
        const currentTitle = getSetting('classes_title', defaultEn.classes_title)
        const currentSub = getSetting('classes_subtitle', defaultEn.classes_subtitle)
        
        if (currentTitle === defaultEn.classes_title) setClassesTitle(t('classes_title'))
        else setClassesTitle(await translate(currentTitle))

        if (currentSub === defaultEn.classes_subtitle) setClassesSubtitle(t('classes_subtitle'))
        else setClassesSubtitle(await translate(currentSub))
        
        const translatedData = baseData.map((c, idx) => {
          const directKey = idx === 0 ? 'classes_direct_title' : 'classes_online_title'
          const descKey = idx === 0 ? 'classes_direct_description' : 'classes_online_description'
          return {
            ...c,
            title: t(directKey),
            description: t(descKey),
            category: t(c.accent === 'direct' ? 'filter_direct' : 'filter_online'),
            duration: language === 'ta' ? 'வாராந்திர' : (language === 'si' ? 'සතිපතා' : 'Weekly'),
            students: language === 'ta' ? 'குழு' : (language === 'si' ? 'කණ්ඩායම' : 'Group')
          }
        })

        setClassData(translatedData)
      }
      translateAll()
    } else {
      setClassesTitle(getSetting('classes_title', t('classes_title')))
      setClassesSubtitle(getSetting('classes_subtitle', t('classes_subtitle')))
      setClassData(baseData)
    }
  }, [language, translate, getSetting, t])

  return (
    <section id="classes" className="premium-classes section">
      <div className="container">
        <div className="pc-header">
          <span className="pc-tag">{t('our_expertise')}</span>
          <h2 className="pc-title">{classesTitle}</h2>
          <p className="pc-subtitle">{classesSubtitle}</p>
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
                    {t('details') || 'Details'} <FiArrowRight />
                  </Link>
                </div>
              </div>
            </div>
          ))}
        </div>

        <div className="pc-view-all">
          <Link to="/classes" className="pc-outline-btn">
            {t('view_all_programs') || 'View All Programs'}
          </Link>
        </div>
      </div>
    </section>
  )
}

export default Classes
