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
  const [screens, setScreens] = useState([])
  const [features, setFeatures] = useState([])
  const [cardSatisfaction, setCardSatisfaction] = useState(getSetting('mobile_card_satisfaction', t('mobile_satisfaction')))
  const [cardSessions, setCardSessions] = useState(getSetting('mobile_card_sessions', t('mobile_live_sessions')))
  const [appStoreShow, setAppStoreShow] = useState(getSetting('mobile_app_store_show', 'on'))
  const [playStoreShow, setPlayStoreShow] = useState(getSetting('mobile_play_store_show', 'on'))

  useEffect(() => {
    const parseJSON = (key, fallback) => {
      try {
        const val = getSetting(key, '[]');
        const parsed = typeof val === 'string' ? JSON.parse(val) : val;
        return parsed.length > 0 ? parsed : fallback;
      } catch (e) { return fallback; }
    }

    const defaultScreens = [
      { title: t('app_screen1'), icon: '📚' },
      { title: t('app_screen2'), icon: '📖' },
      { title: t('app_screen3'), icon: '🎥' },
      { title: t('app_screen4'), icon: '📊' }
    ];

    const defaultFeatures = [
      { text: t('app_feature1') },
      { text: t('app_feature2') },
      { text: t('app_feature3') }
    ];

    const rawScreens = parseJSON('mobile_screens', defaultScreens);
    const rawFeatures = parseJSON('mobile_features', defaultFeatures);

    if (language !== 'en') {
      const translateAll = async () => {
        setMobileTitle(await translate(getSetting('mobile_title', t('mobile_title'))))
        setMobileSubtitle(await translate(getSetting('mobile_subtitle', t('mobile_subtitle'))))
        setMobileDescription(await translate(getSetting('mobile_description', t('mobile_description'))))
        setCardSatisfaction(await translate(getSetting('mobile_card_satisfaction', t('mobile_satisfaction'))))
        setCardSessions(await translate(getSetting('mobile_card_sessions', t('mobile_live_sessions'))))
        setAppStoreShow(getSetting('mobile_app_store_show', 'on'))
        setPlayStoreShow(getSetting('mobile_play_store_show', 'on'))
        
        const translatedScreens = await Promise.all(rawScreens.map(async (s) => ({
          ...s,
          title: await translate(s.title)
        })))

        const translatedFeatures = await Promise.all(rawFeatures.map(async (f) => ({
          ...f,
          text: await translate(f.text)
        })))

        setScreens(translatedScreens)
        setFeatures(translatedFeatures)
      }
      translateAll()
    } else {
      setMobileTitle(getSetting('mobile_title', t('mobile_title')))
      setMobileSubtitle(getSetting('mobile_subtitle', t('mobile_subtitle')))
      setMobileDescription(getSetting('mobile_description', t('mobile_description')))
      setCardSatisfaction(getSetting('mobile_card_satisfaction', t('mobile_satisfaction')))
      setCardSessions(getSetting('mobile_card_sessions', t('mobile_live_sessions')))
      setAppStoreShow(getSetting('mobile_app_store_show', 'on'))
      setPlayStoreShow(getSetting('mobile_play_store_show', 'on'))
      setScreens(rawScreens)
      setFeatures(rawFeatures)
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
                <span>{cardSatisfaction}</span>
              </div>
              <div className="pm-floating-card bottom">
                <FiPlay className="f-icon-play" />
                <span>{cardSessions}</span>
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
              {appStoreShow === 'on' && (
                <a href={getSetting('mobile_app_store_link', '#')} className="pm-store-btn" target="_blank" rel="noopener noreferrer">
                  <div className="store-icon">🍎</div>
                  <div className="store-text">
                    <small>{t('app_store_small')}</small>
                    <strong>App Store</strong>
                  </div>
                </a>
              )}
              {playStoreShow === 'on' && (
                <a href={getSetting('mobile_play_store_link', '#')} className="pm-store-btn" target="_blank" rel="noopener noreferrer">
                  <div className="store-icon">🤖</div>
                  <div className="store-text">
                    <small>{t('play_store_small')}</small>
                    <strong>Google Play</strong>
                  </div>
                </a>
              )}
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}

export default MobileApp
