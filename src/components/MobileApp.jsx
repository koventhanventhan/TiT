import React, { useState, useEffect } from 'react'
import { FiSmartphone, FiDownload, FiCheck, FiCheckCircle, FiPlay } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './MobileApp.css'

const MobileApp = () => {
  const { getSetting } = useSettings();
  const { t, translate, language } = useLanguage()

  const [mobileTitle, setMobileTitle] = useState(getSetting('mobile_title', t('mobile_title')))
  const [mobileSubtitle, setMobileSubtitle] = useState(getSetting('mobile_subtitle', t('mobile_subtitle')))
  const [mobileDescription, setMobileDescription] = useState(getSetting('mobile_description', t('mobile_description')))
  const [screens, setScreens] = useState([
    { title: t('app_screen1'), icon: '📚' },
    { title: t('app_screen2'), icon: '📖' },
    { title: t('app_screen3'), icon: '🎥' },
    { title: t('app_screen4'), icon: '📊' }
  ])
  const [features, setFeatures] = useState([])

  useEffect(() => {
    if (language !== 'en') {
      const translateAll = async () => {
        const title = await translate(getSetting('mobile_title', t('mobile_title')))
        const sub = await translate(getSetting('mobile_subtitle', t('mobile_subtitle')))
        const desc = await translate(getSetting('mobile_description', t('mobile_description')))
        
        const translatedScreens = await Promise.all(screens.map(async (s) => ({
          ...s,
          title: await translate(s.title)
        })))

        let fRaw = []
        try {
          fRaw = JSON.parse(getSetting('mobile_features', '[]'))
          if (fRaw.length === 0) {
            fRaw = [
              { text: t('app_feature1') },
              { text: t('app_feature2') },
              { text: t('app_feature3') }
            ]
          }
        } catch (e) { fRaw = [] }

        const translatedFeatures = await Promise.all(fRaw.map(async (f) => ({
          ...f,
          text: await translate(f.text)
        })))

        setMobileTitle(title)
        setMobileSubtitle(sub)
        setMobileDescription(desc)
        setScreens(translatedScreens)
        setFeatures(translatedFeatures)
      }
      translateAll()
    } else {
      setMobileTitle(getSetting('mobile_title', t('mobile_title')))
      setMobileSubtitle(getSetting('mobile_subtitle', t('mobile_subtitle')))
      setMobileDescription(getSetting('mobile_description', t('mobile_description')))
      setScreens([
        { title: t('app_screen1'), icon: '📚' },
        { title: t('app_screen2'), icon: '📖' },
        { title: t('app_screen3'), icon: '🎥' },
        { title: t('app_screen4'), icon: '📊' }
      ])
      
      let fRaw = []
      try {
        fRaw = JSON.parse(getSetting('mobile_features', '[]'))
        if (fRaw.length === 0) {
          fRaw = [
            { text: t('app_feature1') },
            { text: t('app_feature2') },
            { text: t('app_feature3') }
          ]
        }
      } catch (e) { fRaw = [] }
      setFeatures(fRaw)
    }
  }, [language, translate, getSetting, t])

  const [activeScreen, setActiveScreen] = useState(0);

  useEffect(() => {
    const timer = setInterval(() => {
      setActiveScreen((prev) => (prev + 1) % screens.length);
    }, 4000);
    return () => clearInterval(timer);
  }, [screens.length]);

  return (
    <section className="premium-mobile section">
      <div className="container">
        <div className="pm-wrapper">
          {/* Visual Side */}
          <div className="pm-visual-side">
            <div className="pm-circle-glow"></div>
            <div className="pm-phone-container">
              <div className="phone-mockup">
                <div className="phone-bezel">
                  <div className="phone-speaker"></div>
                  <div className="phone-screen">
                    <div className="screen-carousel" style={{ transform: `translateX(-${activeScreen * 100}%)` }}>
                      {screens.map((s, i) => (
                        <div key={i} className="screen-page">
                          <div className="screen-header">
                            <div className="s-time">9:41</div>
                            <div className="s-icons">📶 🔋</div>
                          </div>
                          <div className="screen-body">
                            <div className="s-app-icon">{s.icon}</div>
                            <h4 className="s-title">{s.title}</h4>
                            <div className="s-dummy-content">
                              <div className="s-line"></div>
                              <div className="s-line short"></div>
                              <div className="s-rect"></div>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                  <div className="phone-home-line"></div>
                </div>
              </div>

              {/* Floating elements */}
              <div className="pm-floating-card top">
                <FiCheckCircle className="f-icon" />
                <span>{t('mobile_satisfaction')}</span>
              </div>
              <div className="pm-floating-card bottom">
                <FiPlay className="f-icon-play" />
                <span>{t('mobile_live_sessions')}</span>
              </div>
            </div>
          </div>

          {/* Text Side */}
          <div className="pm-text-side">
            <span className="pm-badge"><FiSmartphone /> {t('mobile_badge')}</span>
            <h2 className="pm-title">
              {mobileTitle}
            </h2>
            <h3 className="pm-subtitle">{mobileSubtitle}</h3>
            <p className="pm-description">
              {mobileDescription}
            </p>

            <div className="pm-features">
              {features.map((f, i) => (
                <div key={i} className="pm-f-item">
                  <FiCheckCircle className="check-v" />
                  <span>{f.text}</span>
                </div>
              ))}
            </div>

            <div className="pm-download-area">
              <a href={getSetting('mobile_app_store_link', '#')} className="pm-store-btn" target="_blank" rel="noopener noreferrer">
                <div className="store-icon">🍎</div>
                <div className="store-text">
                  <small>{t('app_store_small')}</small>
                  <strong>App Store</strong>
                </div>
              </a>
              <a href={getSetting('mobile_play_store_link', '#')} className="pm-store-btn" target="_blank" rel="noopener noreferrer">
                <div className="store-icon">🤖</div>
                <div className="store-text">
                  <small>{t('play_store_small')}</small>
                  <strong>Google Play</strong>
                </div>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}

export default MobileApp
