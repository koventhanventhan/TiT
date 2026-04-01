import React, { useState, useEffect } from 'react'
import { FiAward, FiCheckCircle, FiStar, FiZap, FiTarget } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './WhyChooseUs.css'

const WhyChooseUs = () => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()

  const [whyTitle, setWhyTitle] = useState(getSetting('why_title', t('why_title')))
  const [whySubtitle, setWhySubtitle] = useState(getSetting('why_subtitle', t('why_subtitle')))
  const [reasons, setReasons] = useState([])

  useEffect(() => {
    const defaultEn = {
      title: 'Why Choose EduLearn?',
      subtitle: 'Empowering students with quality education and modern tools.',
      reasons_raw: '[]'
    }

    const why_reasons_raw = getSetting('why_reasons', defaultEn.reasons_raw)
    let baseReasons = []
    try {
      baseReasons = JSON.parse(why_reasons_raw)
      if (!Array.isArray(baseReasons) || baseReasons.length === 0) {
        baseReasons = [
          {
            icon: "https://cdn.lordicon.com/osuxyevn.json",
            title: 'Expert Instruction',
            description: 'Learn from highly qualified educators with years of experience.',
            localTitle: 'benefit_instruction',
            localDesc: 'benefit_instruction_desc',
            color: '#4f0bd9'
          },
          {
            icon: "https://cdn.lordicon.com/qhviklyi.json",
            title: 'Affordable Pricing',
            description: 'Premium education that fits your budget without compromising quality.',
            localTitle: 'benefit_pricing',
            localDesc: 'benefit_pricing_desc',
            color: '#10b981'
          },
          {
            icon: "https://cdn.lordicon.com/hrjifpbq.json",
            title: 'Lifetime Support',
            description: 'Access our support team and learning resources whenever you need them.',
            localTitle: 'benefit_support',
            localDesc: 'benefit_support_desc',
            color: '#8b5cf6'
          }
        ]
      }
    } catch (e) {
      console.error('Error parsing why_reasons', e)
    }

    if (language !== 'en') {
      const translateAll = async () => {
        const valTitle = getSetting('why_title', defaultEn.title)
        if (valTitle === defaultEn.title) setWhyTitle(t('why_title'))
        else setWhyTitle(await translate(valTitle))

        const valSub = getSetting('why_subtitle', defaultEn.subtitle)
        if (valSub === defaultEn.subtitle) setWhySubtitle(t('why_subtitle'))
        else setWhySubtitle(await translate(valSub))
        
        const translatedReasons = await Promise.all(baseReasons.map(async (r) => ({
          ...r,
          title: r.localTitle ? t(r.localTitle) : await translate(r.title),
          description: r.localDesc ? t(r.localDesc) : await translate(r.description)
        })))

        setReasons(translatedReasons)
      }
      translateAll()
    } else {
      setWhyTitle(getSetting('why_title', t('why_title')))
      setWhySubtitle(getSetting('why_subtitle', t('why_subtitle')))
      setReasons(baseReasons)
    }
  }, [language, translate, getSetting, t])

  return (
    <section className="premium-why section">
      <div className="container">
        <div className="pw-content-wrapper">
          <div className="pw-text-side">
            <span className="pw-badge">{t('why_badge')}</span>
            <h2 className="pw-title">{whyTitle}</h2>
            <p className="pw-description">{whySubtitle}</p>

            <ul className="pw-benefit-list">
              <li>
                <lord-icon
                  src="https://cdn.lordicon.com/yqzmiobz.json"
                  trigger="loop"
                  colors="primary:#4f0bd9"
                  style={{ width: '1.25rem', height: '1.25rem' }}
                /> {t('benefit_personalized')}
              </li>
              <li>
                <lord-icon
                  src="https://cdn.lordicon.com/yqzmiobz.json"
                  trigger="loop"
                  colors="primary:#4f0bd9"
                  style={{ width: '1.25rem', height: '1.25rem' }}
                /> {t('benefit_access')}
              </li>
              <li>
                <lord-icon
                  src="https://cdn.lordicon.com/yqzmiobz.json"
                  trigger="loop"
                  colors="primary:#4f0bd9"
                  style={{ width: '1.25rem', height: '1.25rem' }}
                /> {t('benefit_qa')}
              </li>
            </ul>
          </div>

          <div className="pw-grid-side">
            <div className="pw-reasons-grid">
              {reasons.map((reason, index) => (
                <div key={index} className="pw-reason-card" style={{ '--accent-color': reason.color }}>
                  <div className="pw-reason-icon-box">
                    {reason.icon && typeof reason.icon === 'string' ? (
                      <lord-icon
                        src={reason.icon}
                        trigger="loop"
                        colors="primary:#ffffff,secondary:#ffffff"
                        style={{ width: '3rem', height: '3rem' }}
                      />
                    ) : (
                      reason.icon
                    )}
                  </div>
                  <h3 className="pw-reason-title">{reason.title}</h3>
                  <p className="pw-reason-desc">{reason.description}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}

export default WhyChooseUs
