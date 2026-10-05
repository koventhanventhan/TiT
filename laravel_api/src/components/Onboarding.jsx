import React, { useState, useEffect } from 'react'
import { FiArrowRight, FiCheckCircle } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import { useAuthModal } from '../context/AuthModalContext'
import './Onboarding.css'

const Onboarding = () => {
  const { getSetting } = useSettings();
  const { t, translate, language } = useLanguage()
  const { openRegister } = useAuthModal()

  const [onboardingTitle, setOnboardingTitle] = useState(getSetting('onboarding_title', t('onboarding_title')))
  const [onboardingSubtitle, setOnboardingSubtitle] = useState(getSetting('onboarding_subtitle', t('onboarding_subtitle')))
  const [steps, setSteps] = useState([])

  useEffect(() => {
    const onboarding_steps_raw = getSetting('onboarding_steps', '[]')
    let baseSteps = []
    try {
      baseSteps = JSON.parse(onboarding_steps_raw)
      if (!Array.isArray(baseSteps) || baseSteps.length === 0) {
        baseSteps = [
          {
            id: '01',
            title: 'Easy Registration',
            description: 'Fill out our simple application form to get started. It only takes a few minutes!',
            icon: "https://cdn.lordicon.com/wjyqkiew.json",
            color: '#ffffff'
          },
          {
            id: '02',
            title: 'Free Consultation',
            description: 'Our academic advisors will reach out to understand your goals and recommend the best path.',
            icon: "https://cdn.lordicon.com/zpxybbhl.json",
            color: '#ffffff'
          },
          {
            id: '03',
            title: 'Start Learning',
            description: 'Complete your enrollment and unlock instant access to your classes and resources.',
            icon: "https://cdn.lordicon.com/dxjqoygy.json",
            color: '#ffffff'
          }
        ]
      }
    } catch (e) {
      console.error('Error parsing onboarding_steps', e)
    }

    const updateContent = async () => {
      const rawTitle = getSetting('onboarding_title', 'How It Works')
      const rawSubtitle = getSetting('onboarding_subtitle', 'Simple steps to start your education.')
      
      if (language !== 'en') {
        setOnboardingTitle(await translate(rawTitle))
        setOnboardingSubtitle(await translate(rawSubtitle))
        const translatedSteps = await Promise.all(baseSteps.map(async (s) => ({
          ...s,
          title: await translate(s.title),
          description: await translate(s.description)
        })))
        setSteps(translatedSteps)
      } else {
        setOnboardingTitle(rawTitle)
        setOnboardingSubtitle(rawSubtitle)
        setSteps(baseSteps)
      }
    }

    updateContent()
  }, [language, translate, getSetting])

  return (
    <section className="premium-onboarding section">
      <div className="container">
        <div className="po-header">
          <span className="po-badge">{t('onboarding_title')}</span>
          <h2 className="po-title">{onboardingTitle}</h2>
          <p className="po-subtitle">
            {onboardingSubtitle}
          </p>
        </div>

        <div className="po-steps-wrapper">
          <div className="po-timeline-line"></div>
          <div className="po-steps-grid">
            {steps.map((step, index) => (
              <div key={index} className="po-step-card" style={{ '--step-color': step.color }}>
                <div className="po-step-visual">
                  <div className="po-step-number">{step.id}</div>
                  <div className="po-step-icon-box">
                    {step.icon && typeof step.icon === 'string' ? (
                      <lord-icon
                        src={step.icon}
                        trigger="loop"
                        colors="primary:#ffffff,secondary:#ffffff"
                        style={{ width: '3.5rem', height: '3.5rem' }}
                      />
                    ) : (
                      step.icon
                    )}
                  </div>
                </div>

                <div className="po-step-content">
                  <h3 className="po-step-title">{step.title}</h3>
                  <p className="po-step-desc">{step.description}</p>
                </div>

                {index < steps.length - 1 && (
                  <div className="po-step-connector">
                    <lord-icon
                      src="https://cdn.lordicon.com/vduvxpxl.json"
                      trigger="loop"
                      colors="primary:#4f0bd9"
                      style={{ width: '2rem', height: '2rem' }}
                    />
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>

        <div className="po-footer">
          <button onClick={openRegister} className="po-cta-btn">{t('btn_register_now')}</button>
        </div>
      </div>
    </section>
  )
}

export default Onboarding
