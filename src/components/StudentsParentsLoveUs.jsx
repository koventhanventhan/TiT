import React, { useState, useEffect } from 'react'
import { FiUsers, FiStar, FiTrendingUp, FiHeart, FiAward, FiMessageCircle } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './StudentsParentsLoveUs.css'

const StudentsParentsLoveUs = () => {
  const { getSetting } = useSettings();
  const { t, translate, language } = useLanguage()

  const [title, setTitle] = useState(getSetting('love_us_title', t('love_us_title')))
  const [subtitle, setSubtitle] = useState(getSetting('love_us_subtitle', t('love_us_subtitle')))
  const [testimonials, setTestimonials] = useState([])
  const [stats, setStats] = useState([])

  useEffect(() => {
    const defaultEn = {
      title: 'What Students & Parents Say',
      subtitle: 'Join thousands of satisfied learners achieving their goals.',
      testimonials_raw: '[]' // Using the default empty array logic
    }

    const testimonials_raw = getSetting('love_us_testimonials', defaultEn.testimonials_raw)
    let baseTestimonials = []
    try {
      baseTestimonials = JSON.parse(testimonials_raw)
      if (!Array.isArray(baseTestimonials) || baseTestimonials.length === 0) {
        baseTestimonials = [
          {
            name: 'Sarah Perera',
            role: 'Parent of Grade 10 Student',
            comment: 'The personalized attention and quality teaching have improved my daughter\'s grades significantly. Highly recommended!',
            rating: 5,
            image: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop'
          },
          {
            name: 'Kamal Fernando',
            role: 'A/L Student',
            comment: 'The best online tuition platform! The teachers are excellent and the classes are very interactive.',
            rating: 5,
            image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop'
          },
          {
            name: 'Nimali Silva',
            role: 'O/L Parent',
            comment: 'As a parent, I\'m very satisfied with the quality. The progress reports are excellent and consistent.',
            rating: 5,
            image: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop'
          }
        ]
      }
    } catch (e) {
      console.error('Error parsing love_us_testimonials', e)
    }

    const baseStats = [
      {
        icon: <FiUsers />,
        number: getSetting('love_us_stat1_number', '10,000+'),
        label: getSetting('love_us_stat1_label', t('happy_students')),
        enLabel: 'Happy Students'
      },
      {
        icon: <FiHeart />,
        number: getSetting('love_us_stat2_number', '98%'),
        label: getSetting('love_us_stat2_label', t('success_rate')),
        enLabel: 'Success Rate'
      },
      {
        icon: <FiStar />,
        number: getSetting('love_us_stat3_number', '4.9/5'),
        label: getSetting('love_us_stat3_label', t('review_rating')),
        enLabel: 'Review Rating'
      },
      {
        icon: <FiAward />,
        number: getSetting('love_us_stat4_number', '95%'),
        label: getSetting('love_us_stat4_label', t('pass_guarantee')),
        enLabel: 'Pass Guarantee'
      }
    ];

    if (language !== 'en') {
      const translateAll = async () => {
        // 1. Set titles and subtitles immediately (fast)
        const valTitle = getSetting('love_us_title', defaultEn.title)
        if (valTitle === defaultEn.title) setTitle(t('love_us_title'))
        else setTitle(await translate(valTitle))

        const valSub = getSetting('love_us_subtitle', defaultEn.subtitle)
        if (valSub === defaultEn.subtitle) setSubtitle(t('love_us_subtitle'))
        else setSubtitle(await translate(valSub))

        // 2. Set stats immediately (fast, labels are already in the array)
        const translatedStats = await Promise.all(baseStats.map(async (s) => ({
          ...s,
          label: t(s.enLabel.toLowerCase().replace(/\s+/g, '_')) || await translate(s.label)
        })))
        setStats(translatedStats)
        
        // 3. Translate testimonials in background (slow)
        const translatedTestimonials = await Promise.all(baseTestimonials.map(async (test) => {
          return {
            ...test,
            role: await translate(test.role),
            comment: await translate(test.comment)
          }
        }))
        setTestimonials(translatedTestimonials)
      }
      translateAll()
    } else {
      setTitle(getSetting('love_us_title', t('love_us_title')))
      setSubtitle(getSetting('love_us_subtitle', t('love_us_subtitle')))
      setTestimonials(baseTestimonials)
      setStats(baseStats)
    }
  }, [language, translate, getSetting, t])

  return (
    <section className="premium-reviews section">
      <div className="container">
        {/* Stats Row */}
        <div className="pr-stats-row">
          {stats.map((stat, index) => (
            <div key={index} className="pr-stat-item">
              <div className="pr-stat-icon-box">{stat.icon}</div>
              <div className="pr-stat-info">
                <h3>{stat.number}</h3>
                <p>{stat.label}</p>
              </div>
            </div>
          ))}
        </div>

        <div className="pr-header">
          <span className="pr-tag">{t('social_proof')}</span>
          <h2 className="pr-title">{title}</h2>
          <p className="pr-subtitle">
            {subtitle}
          </p>
        </div>

        <div className="pr-grid">
          {testimonials.map((t, index) => (
            <div key={index} className="pr-card">
              <div className="pr-quote-icon"><FiMessageCircle /></div>
              <div className="pr-card-rating">
                {[...Array(Math.max(0, parseInt(t.rating) || 0))].map((_, i) => (
                  <FiStar key={i} className="star-fill" />
                ))}
              </div>
              <p className="pr-comment">
                {t.comment && (t.comment.startsWith('"') && t.comment.endsWith('"')) 
                  ? t.comment 
                  : `"${t.comment}"`}
              </p>
              <div className="pr-user">
                <div className="pr-avatar">
                  <img src={t.image} alt={t.name} />
                </div>
                <div className="pr-details">
                  <h4>{t.name}</h4>
                  <span>{t.role}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

export default StudentsParentsLoveUs
