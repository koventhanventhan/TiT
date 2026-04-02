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
  const [benefits, setBenefits] = useState([])

  useEffect(() => {
    const parseJSON = (key, fallback) => {
      try {
        const val = getSetting(key, '[]');
        const parsed = typeof val === 'string' ? JSON.parse(val) : val;
        return parsed.length > 0 ? parsed : fallback;
      } catch (e) { return fallback; }
    }

    const defaultReasons = [
      {
        icon: "https://cdn.lordicon.com/osuxyevn.json",
        title: t('benefit_instruction'),
        description: t('benefit_instruction_desc'),
        color: '#4f0bd9'
      },
      {
        icon: "https://cdn.lordicon.com/qhviklyi.json",
        title: t('benefit_pricing'),
        description: t('benefit_pricing_desc'),
        color: '#10b981'
      },
      {
        icon: "https://cdn.lordicon.com/hrjifpbq.json",
        title: t('benefit_support'),
        description: t('benefit_support_desc'),
        color: '#8b5cf6'
      }
    ];

    const defaultBenefits = [
      { text: t('benefit_personalized') },
      { text: t('benefit_access') },
      { text: t('benefit_qa') }
    ];

    const rawReasons = parseJSON('why_reasons', defaultReasons);
    const rawBenefits = parseJSON('why_benefits', defaultBenefits);

    if (language !== 'en') {
      const translateAll = async () => {
        setWhyTitle(await translate(getSetting('why_title', t('why_title'))))
        setWhySubtitle(await translate(getSetting('why_subtitle', t('why_subtitle'))))
        
        const translatedReasons = await Promise.all(rawReasons.map(async (r) => ({
          ...r,
          title: await translate(r.title),
          description: await translate(r.description)
        })))

        const translatedBenefits = await Promise.all(rawBenefits.map(async (b) => ({
          ...b,
          text: await translate(b.text)
        })))

        setReasons(translatedReasons)
        setBenefits(translatedBenefits)
      }
      translateAll()
    } else {
      setWhyTitle(getSetting('why_title', t('why_title')))
      setWhySubtitle(getSetting('why_subtitle', t('why_subtitle')))
      setReasons(rawReasons)
      setBenefits(rawBenefits)
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
              {benefits.map((benefit, idx) => (
                <li key={idx}>
                  <lord-icon
                    src="https://cdn.lordicon.com/yqzmiobz.json"
                    trigger="loop"
                    colors="primary:#4f0bd9"
                    style={{ width: '1.25rem', height: '1.25rem' }}
                  /> {benefit.text}
                </li>
              ))}
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
