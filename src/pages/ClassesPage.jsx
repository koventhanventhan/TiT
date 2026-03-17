import React from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import {
  FiBook, FiGlobe, FiArrowRight, FiCheck, FiUsers, FiClock,
  FiAward, FiMonitor, FiMapPin, FiStar, FiPlay, FiFileText
} from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import { useState, useEffect } from 'react'
import './ClassesPage.css'

const ClassesPage = () => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()
  const [searchParams] = useSearchParams()
  const type = searchParams.get('type')

  const [clpTitle, setClpTitle] = useState(getSetting('classes_title', t('classes_title')))
  const [clpSubtitle, setClpSubtitle] = useState(getSetting('classes_subtitle', t('classes_subtitle')))
  const [clpBadge, setClpBadge] = useState(getSetting('classes_badge', t('our_classes')))
  const [classTypes, setClassTypes] = useState([])
  const [subjectsObj, setSubjectsObj] = useState({})

  useEffect(() => {
    const allClassTypes = [
      {
        id: 1,
        icon: <FiBook />,
        title: t('direct_class'),
        tagline: t('face_to_face'),
        description: getSetting('classes_direct_description', t('classes_direct_description')),
        accent: 'direct',
        features: getSetting('classes_direct_features', t('classes_direct_features')).split('\n').filter(f => f.trim()),
        duration: getSetting('classes_direct_duration', t('detail_flexible_schedules')),
        students: getSetting('classes_direct_format', t('detail_small_groups')),
        price: getSetting('classes_direct_price', t('detail_affordable_rates')),
        highlights: [
          { icon: <FiMapPin />, label: t('in_person') },
          { icon: <FiUsers />, label: t('small_groups') },
          { icon: <FiStar />, label: t('expert_academic_tutors') }
        ]
      },
      {
        id: 2,
        icon: <FiGlobe />,
        title: t('online_class'),
        tagline: t('learn_from_anywhere'),
        description: getSetting('classes_online_description', t('classes_online_description')),
        accent: 'online',
        features: getSetting('classes_online_features', t('classes_online_features')).split('\n').filter(f => f.trim()),
        duration: getSetting('classes_online_duration', t('detail_flexible_schedules')),
        students: getSetting('classes_online_format', t('detail_group_one_on_one')),
        price: getSetting('classes_online_price', t('detail_competitive_pricing')),
        highlights: [
          { icon: <FiMonitor />, label: t('live_sessions') },
          { icon: <FiPlay />, label: t('lesson_recordings') },
          { icon: <FiFileText />, label: t('digital_resources') }
        ]
      }
    ]

    const subjectsRaw = {
      [t('direct_class')]: getSetting('classes_direct_subjects', `${t('subject_mathematics')}, ${t('subject_science')}, ${t('subject_english')}, ${t('subject_sinhala')}, ${t('subject_tamil')}, ${t('subject_history')}, ${t('subject_geography')}, ${t('subject_commerce')}, ${t('subject_ict')}, ${t('subject_art')}`).split(',').map(s => s.trim()),
      [t('online_class')]: getSetting('classes_online_subjects', `${t('subject_mathematics')}, ${t('subject_physics')}, ${t('subject_chemistry')}, ${t('subject_biology')}, ${t('subject_english')}, ${t('subject_business_studies')}, ${t('subject_economics')}, ${t('subject_accounting')}, ${t('subject_ict')}, ${t('subject_computer_science')}`).split(',').map(s => s.trim())
    }

    // Set initial state for immediate rendering (will be updated by translation)
    const initialFiltered = type 
      ? allClassTypes.filter(ct => ct.accent === type)
      : allClassTypes;
    setClassTypes(initialFiltered)
    setSubjectsObj(subjectsRaw)

    if (language !== 'en') {
      const translateAll = async () => {
        setClpTitle(await translate(getSetting('classes_title', t('classes_title'))))
        setClpSubtitle(await translate(getSetting('classes_subtitle', t('classes_subtitle'))))
        setClpBadge(await translate(getSetting('classes_badge', t('our_classes'))))

        const translatedTypes = await Promise.all(allClassTypes.map(async (ct) => ({
          ...ct,
          title: await translate(ct.title),
          tagline: await translate(ct.tagline),
          description: await translate(ct.description),
          features: await Promise.all(ct.features.map(f => translate(f))),
          duration: await translate(ct.duration),
          students: await translate(ct.students),
          price: await translate(ct.price),
          highlights: await Promise.all(ct.highlights.map(async h => ({ ...h, label: await translate(h.label) })))
        })))

        const translatedSubjects = {}
        for (const [key, list] of Object.entries(subjectsRaw)) {
          const translatedKey = await translate(key)
          translatedSubjects[translatedKey] = await Promise.all(list.map(s => translate(s)))
        }

        const filtered = type 
          ? translatedTypes.filter(ct => ct.accent === type)
          : translatedTypes;

        setClassTypes(filtered)
        setSubjectsObj(translatedSubjects)
      }
      translateAll()
    } else {
      setClpTitle(getSetting('classes_title', t('classes_title')))
      setClpSubtitle(getSetting('classes_subtitle', t('classes_subtitle')))
      setClpBadge(getSetting('classes_badge', t('our_classes')))
      
      const filtered = type 
        ? allClassTypes.filter(ct => ct.accent === type)
        : allClassTypes;

      setClassTypes(filtered)
      setSubjectsObj(subjectsRaw)
    }
  }, [language, translate, getSetting, t, type])

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
            <span className="clp-hero-badge"><FiBook /> {clpBadge}</span>
            <h1 className="clp-hero-title">
              {clpTitle}
            </h1>
            <p className="clp-hero-sub">
              {clpSubtitle}
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
                      <h3><FiCheck /> {t('whats_included')}</h3>
                      <ul className="clp-features">
                        {ct.features.map((f, i) => (
                          <li key={i}><FiCheck /> {f}</li>
                        ))}
                      </ul>
                    </div>

                    {/* Subjects */}
                    <div className="clp-sec">
                      <h3><FiBook /> {t('available_subjects')}</h3>
                      <div className="clp-badges">
                        {subjectsObj[ct.title]?.map((s, i) => (
                          <span key={i} className="clp-badge">{s}</span>
                        ))}
                      </div>
                    </div>

                    {/* Details */}
                    <div className="clp-sec">
                      <h3><FiUsers /> {t('class_details')}</h3>
                      <div className="clp-details">
                        <div className="clp-detail">
                          <FiClock />
                          <div>
                            <strong>{t('duration')}</strong>
                            <span>{ct.duration}</span>
                          </div>
                        </div>
                        <div className="clp-detail">
                          <FiUsers />
                          <div>
                            <strong>{t('format')}</strong>
                            <span>{ct.students}</span>
                          </div>
                        </div>
                        <div className="clp-detail">
                          <FiAward />
                          <div>
                            <strong>{t('pricing')}</strong>
                            <span>{ct.price}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* CTA */}
                  <div className="clp-card-cta">
                    <a href="#register" className="clp-btn-enroll">
                      {t('btn_enroll_now')} <FiArrowRight />
                    </a>
                    <a href="#register" className="clp-btn-outline">
                      {t('learn_more')}
                    </a>
                  </div>
                </div>
              </div>
            ))}
          </div>


        </div>
      </section>
    </div>
  )
}

export default ClassesPage
