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
    const rawTypes = getSetting('classes_types', '[]');
    let parsedTypes = [];
    try {
      parsedTypes = typeof rawTypes === 'string' ? JSON.parse(rawTypes) : rawTypes;
    } catch (e) {
      console.error("Failed to parse classes_types", e);
      parsedTypes = [];
    }

    const mapCards = (typesData) => {
      let data = typesData;
      if (!data || data.length === 0) {
        data = [
          {
            title: t('direct_class'),
            description: getSetting('classes_direct_description', 'High-impact face-to-face sessions in a dedicated learning environment.'),
            color: '#EB8153',
            stars: 5,
            image: '/venthan1.jpg',
            format: 'Physical',
            duration: 'Weekly',
            students: 'Group'
          },
          {
            title: t('online_class'),
            description: getSetting('classes_online_description', 'Interactive digital classrooms with full access to recordings and resources.'),
            color: '#667eea',
            stars: 5,
            image: '/venthan2.jpg',
            format: 'Online',
            duration: 'Flexible',
            students: 'Group/1-on-1'
          }
        ];
      }

      return data.map((item, index) => ({
        id: item.id || index,
        title: item.title,
        category: item.format || 'Course',
        description: item.description,
        accent: item.color || '#EB8153',
        duration: item.duration || 'Flexible',
        students: item.format?.toLowerCase().includes('online') ? 'Group/1-on-1' : 'Group',
        rating: (parseFloat(item.stars) || 5.0).toFixed(1),
        image: item.image,
        icon: item.title?.toLowerCase().includes('online') ? <FiGlobe /> : <FiBook />
      }));
    };

    const initialCards = mapCards(parsedTypes);
    setClassData(initialCards);

    if (language !== 'en' && (parsedTypes.length > 0 || initialCards.length > 0)) {
      const translateAll = async () => {
        setClassesTitle(await translate(getSetting('classes_title', t('classes_title'))))
        setClassesSubtitle(await translate(getSetting('classes_subtitle', t('classes_subtitle'))))
        
        const translatedCards = await Promise.all(initialCards.map(async (c) => ({
          ...c,
          title: await translate(c.title),
          category: await translate(c.category),
          description: await translate(c.description),
          duration: await translate(c.duration),
          students: await translate(c.students)
        })));

        setClassData(translatedCards)
      }
      translateAll()
    } else {
      setClassesTitle(getSetting('classes_title', t('classes_title')))
      setClassesSubtitle(getSetting('classes_subtitle', t('classes_subtitle')))
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
            <div 
              key={index} 
              className="pc-card"
              style={{ '--accent-color': course.accent }}
            >
              <div className="pc-card-media">
                <img 
                  src={course.image?.startsWith('http') ? course.image : (import.meta.env.VITE_API_URL?.replace('/api', '') || '') + '/' + course.image} 
                  alt={course.title} 
                  className="pc-card-img" 
                />
                <div className="pc-card-category" style={{ background: course.accent, boxShadow: `0 4px 12px ${course.accent}4d` }}>
                  {course.category}
                </div>
                <div className="pc-card-overlay">
                  <div className="pc-card-icon-overlay" style={{ color: course.accent }}>{course.icon}</div>
                </div>
              </div>

              <div className="pc-card-body">
                <div className="pc-card-meta">
                  <span className="pc-meta-item">
                    <FiClock style={{ color: course.accent }} /> {course.duration}
                  </span>
                  <span className="pc-meta-item">
                    <FiUsers style={{ color: course.accent }} /> {course.students}
                  </span>
                </div>

                <h3 className="pc-card-title">{course.title}</h3>
                <p className="pc-card-desc">{course.description}</p>

                <div className="pc-card-footer">
                  <div className="pc-rating">
                    <FiStar className="star-fill" />
                    <span>{course.rating}</span>
                  </div>
                   <Link to={`/classes`} className="pc-enroll-btn" style={{ color: course.accent }}>
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
