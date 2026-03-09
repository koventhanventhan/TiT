import React, { useEffect, useState } from 'react'
import { FiDownload, FiCheckCircle, FiPlay, FiSmartphone } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './MobileApp.css'

const MobileApp = () => {
  const { getSetting } = useSettings();
  const [activeScreen, setActiveScreen] = useState(0);

  const screens = [
    { title: 'Interactive Classes', icon: '📚' },
    { title: 'Smart Study Tools', icon: '📖' },
    { title: 'Live Recordings', icon: '🎥' },
    { title: 'Detailed Progress', icon: '📊' }
  ];

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
                <span>99% Satisfaction</span>
              </div>
              <div className="pm-floating-card bottom">
                <FiPlay className="f-icon-play" />
                <span>Live Sessions</span>
              </div>
            </div>
          </div>

          {/* Text Side */}
          <div className="pm-text-side">
            <span className="pm-badge"><FiSmartphone /> Mobile Learning</span>
            <h2 className="pm-title">
              {getSetting('mobile_title', 'Learn Anytime, Anywhere')}
            </h2>
            <h3 className="pm-subtitle">EduLearn Mobile Application</h3>
            <p className="pm-description">
              {getSetting('mobile_description', 'Experience seamless education on the go. Access your courses, track progress, and join live sessions directly from your smartphone with our high-performance app.')}
            </p>

            <div className="pm-features">
              <div className="pm-f-item">
                <FiCheckCircle className="check-v" />
                <span>Seamless Offline Access</span>
              </div>
              <div className="pm-f-item">
                <FiCheckCircle className="check-v" />
                <span>Instant Push Notifications</span>
              </div>
              <div className="pm-f-item">
                <FiCheckCircle className="check-v" />
                <span>Secure Data Sync</span>
              </div>
            </div>

            <div className="pm-download-area">
              <a href={getSetting('mobile_app_store_link', '#')} className="pm-store-btn" target="_blank" rel="noopener noreferrer">
                <div className="store-icon">🍎</div>
                <div className="store-text">
                  <small>Download on the</small>
                  <strong>App Store</strong>
                </div>
              </a>
              <a href={getSetting('mobile_play_store_link', '#')} className="pm-store-btn" target="_blank" rel="noopener noreferrer">
                <div className="store-icon">🤖</div>
                <div className="store-text">
                  <small>Get it on</small>
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
