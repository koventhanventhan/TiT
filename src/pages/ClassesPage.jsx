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
    const rawTypes = getSetting('classes_types', '[]');
    let parsedTypes = [];
    try {
      parsedTypes = typeof rawTypes === 'string' ? JSON.parse(rawTypes) : rawTypes;
    } catch (e) {
      console.error("Failed to parse classes_types", e);
      parsedTypes = [];
    }

    const mapTypes = (typesData) => {
      let data = typesData;
      if (!data || data.length === 0) {
        // Fallback to defaults if empty
        data = [
          {
            title: t('direct_class'),
            description: getSetting('classes_direct_description', 'Comprehensive face-to-face learning experience.'),
            duration: getSetting('classes_direct_duration', 'Flexible'),
            price: getSetting('classes_direct_price', 'Affordable'),
            format: getSetting('classes_direct_format', 'Small Groups'),
            features: getSetting('classes_direct_features', "Small groups\nExpert tutors"),
            subjects: getSetting('classes_direct_subjects', 'Math, Science'),
            color: '#EB8153',
            stars: 5
          },
          {
            title: t('online_class'),
            description: getSetting('classes_online_description', 'Convenient live interactive sessions.'),
            duration: getSetting('classes_online_duration', 'Flexible'),
            price: getSetting('classes_online_price', 'Competitive'),
            format: getSetting('classes_online_format', 'One-on-One'),
            features: getSetting('classes_online_features', "Live classes\nRecordings"),
            subjects: getSetting('classes_online_subjects', 'Physics, Chemistry'),
            color: '#667eea',
            stars: 5
          }
        ];
      }
      return data.map((item, index) => {
        const title = item.title || 'Class Type';
        const description = item.description || '';
        const features = (item.features || '').split('\n').filter(f => f.trim());
        const subjects = (item.subjects || '').split(',').map(s => s.trim()).filter(s => s);
        
        return {
          id: item.id || `type-${index}`,
          title: title,
          tagline: item.format || 'Training Program',
          description: description,
          accent: item.color || '#EB8153',
          color: item.color || '#EB8153',
          image: item.image || '',
          stars: parseInt(item.stars) || 5,
          features: features,
          duration: item.duration || 'Flexible',
          students: item.format || 'Group Sessions',
          price: item.price || 'Contact for Pricing',
          subjects: subjects,
          highlights: [
            { icon: item.title?.toLowerCase().includes('online') ? <FiMonitor /> : <FiMapPin />, label: item.format || 'Flexible' },
            { icon: <FiUsers />, label: item.title?.toLowerCase().includes('one-on-one') ? 'Personalized' : 'Group Learning' },
            { icon: <FiStar />, label: `${item.stars || 5} Star Rating` }
          ]
        };
      });
    };

    const initialTypes = mapTypes(parsedTypes);
    const initialSubjects = {};
    initialTypes.forEach(t => {
      initialSubjects[t.title] = t.subjects;
    });

    const initialFiltered = type 
      ? initialTypes.filter(ct => ct.title.toLowerCase().includes(type.toLowerCase()))
      : initialTypes;
    
    setClassTypes(initialFiltered);
    setSubjectsObj(initialSubjects);

    if (language !== 'en' && parsedTypes.length > 0) {
      const translateAll = async () => {
        setClpTitle(await translate(getSetting('classes_title', t('classes_title'))))
        setClpSubtitle(await translate(getSetting('classes_subtitle', t('classes_subtitle'))))
        setClpBadge(await translate(getSetting('classes_badge', t('our_classes'))))

        const translatedTypes = await Promise.all(initialTypes.map(async (ct) => ({
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
        for (const [key, list] of Object.entries(initialSubjects)) {
          const translatedKey = await translate(key)
          translatedSubjects[translatedKey] = await Promise.all(list.map(s => translate(s)))
        }

        const filtered = type 
          ? translatedTypes.filter(ct => ct.title.toLowerCase().includes(type.toLowerCase()))
          : translatedTypes;

        setClassTypes(filtered)
        setSubjectsObj(translatedSubjects)
      }
      translateAll()
    } else {
      setClpTitle(getSetting('classes_title', t('classes_title')))
      setClpSubtitle(getSetting('classes_subtitle', t('classes_subtitle')))
      setClpBadge(getSetting('classes_badge', t('our_classes')))
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
              <div key={ct.id} className="clp-card" style={{ '--accent-color': ct.color }}>
                {/* Card Header */}
                <div className="clp-card-head">
                  <div 
                    className="clp-card-head-bg" 
                    style={{ 
                      background: ct.image 
                        ? `linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.9)), url(${ct.image.startsWith('http') ? ct.image : (import.meta.env.VITE_API_URL?.replace('/api', '') || '') + '/' + ct.image})`
                        : `linear-gradient(135deg, ${ct.color} 0%, ${ct.color}dd 100%)`,
                      backgroundSize: 'cover',
                      backgroundPosition: 'center'
                    }}
                  ></div>
                  <div className="clp-card-head-inner">
                    <div className="clp-card-icon" style={{ borderColor: `${ct.color}44`, color: ct.color }}>
                      {ct.image ? <img src={ct.image.startsWith('http') ? ct.image : (import.meta.env.VITE_API_URL?.replace('/api', '') || '') + '/' + ct.image} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: 'inherit' }} /> : <FiStar />}
                    </div>
                    <div>
                      <span className="clp-card-tag" style={{ color: `${ct.color}aa` }}>{ct.tagline}</span>
                      <h2 className="clp-card-title">{ct.title}</h2>
                    </div>
                  </div>
                  <p className="clp-card-desc">{ct.description}</p>
                  {/* Highlight pills */}
                  <div className="clp-highlights">
                    {ct.highlights.map((h, i) => (
                      <span key={i} className="clp-highlight" style={{ borderColor: `${ct.color}33`, color: `${ct.color}cc` }}>
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
                      <h3><FiCheck style={{ color: ct.color }} /> {t('whats_included')}</h3>
                      <ul className="clp-features">
                        {ct.features.map((f, i) => (
                          <li key={i}><FiCheck style={{ background: `${ct.color}11`, color: ct.color }} /> {f}</li>
                        ))}
                      </ul>
                    </div>

                    {/* Subjects */}
                    <div className="clp-sec">
                      <h3><FiBook style={{ color: ct.color }} /> {t('available_subjects')}</h3>
                      <div className="clp-badges">
                        {subjectsObj[ct.title]?.map((s, i) => (
                          <span key={i} className="clp-badge" style={{ '--hover-bg': ct.color }}>{s}</span>
                        ))}
                      </div>
                    </div>

                    {/* Details */}
                    <div className="clp-sec">
                      <h3><FiUsers style={{ color: ct.color }} /> {t('class_details')}</h3>
                      <div className="clp-details">
                        <div className="clp-detail">
                          <FiClock style={{ color: ct.color }} />
                          <div>
                            <strong>{t('duration')}</strong>
                            <span>{ct.duration}</span>
                          </div>
                        </div>
                        <div className="clp-detail">
                          <FiUsers style={{ color: ct.color }} />
                          <div>
                            <strong>{t('format')}</strong>
                            <span>{ct.students}</span>
                          </div>
                        </div>
                        <div className="clp-detail">
                          <FiAward style={{ color: ct.color }} />
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
                    <a href="#register" className="clp-btn-enroll" style={{ background: `linear-gradient(135deg, ${ct.color} 0%, ${ct.color}dd 100%)`, boxShadow: `0 12px 36px ${ct.color}44` }}>
                      {t('btn_enroll_now')} <FiArrowRight />
                    </a>
                    <a href="#register" className="clp-btn-outline" style={{ borderColor: ct.color, color: ct.color }}>
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
