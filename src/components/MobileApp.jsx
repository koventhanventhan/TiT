import React from 'react'
import { FiSmartphone, FiDownload } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './MobileApp.css'

const MobileApp = () => {
  const { getSetting } = useSettings();

  return (
    <section className="mobile-app section">
      <div className="container">
        <div className="mobile-app-content">
          <div className="mobile-app-text">
            <h2 className="section-title mobile-title">
              {getSetting('mobile_title', 'Learn Anytime, Anywhere..! ')}
            </h2>
            <h3 className="mobile-subtitle">{getSetting('mobile_subtitle', 'EduLearn Mobile App')}</h3>
            <p className="mobile-description">
              {getSetting('mobile_description', 'Take your learning on the go with the EduLearn Mobile App, available on both iOS and Android. Access live classes, class recordings, exams, and progress updates seamlessly from your mobile device. Stay connected, stay updated, and unlock a world of learning at your fingertips—anytime, anywhere!')}
            </p>
            <div className="mobile-badges">
              <a href={getSetting('mobile_app_store_link', '#')} className="badge" target="_blank" rel="noopener noreferrer">
                <FiDownload />
                <span>Download on App Store</span>
              </a>
              <a href={getSetting('mobile_play_store_link', '#')} className="badge" target="_blank" rel="noopener noreferrer">
                <FiDownload />
                <span>Get it on Google Play</span>
              </a>
            </div>
          </div>
          <div className="mobile-app-visual">
            <div className="mobile-gallery-container">
              <div className="phone-mockup-gallery">
                <div className="phone-frame-gallery">
                  <div className="phone-notch-gallery"></div>
                  <div className="phone-screen-gallery">
                    <div className="gallery-slider">
                      <div className="gallery-screen active" data-index="0">
                        <div className="screen-content screen-1">
                          <div className="screen-header">
                            <div className="status-bar">
                              <span>9:41</span>
                              <div className="status-dots">
                                <span></span><span></span><span></span>
                              </div>
                            </div>
                            <div className="app-header-content">
                              <div className="app-icon-screen">📚</div>
                              <h4>EduLearn</h4>
                            </div>
                          </div>
                          <div className="screen-body">
                            <div className="gallery-item featured"></div>
                            <div className="gallery-grid">
                              <div className="gallery-item"></div>
                              <div className="gallery-item"></div>
                              <div className="gallery-item"></div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="gallery-screen" data-index="1">
                        <div className="screen-content screen-2">
                          <div className="screen-header">
                            <div className="status-bar">
                              <span>9:41</span>
                              <div className="status-dots">
                                <span></span><span></span><span></span>
                              </div>
                            </div>
                            <div className="app-header-content">
                              <div className="app-icon-screen">📖</div>
                              <h4>Classes</h4>
                            </div>
                          </div>
                          <div className="screen-body">
                            <div className="class-list">
                              <div className="class-item"></div>
                              <div className="class-item"></div>
                              <div className="class-item"></div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="gallery-screen" data-index="2">
                        <div className="screen-content screen-3">
                          <div className="screen-header">
                            <div className="status-bar">
                              <span>9:41</span>
                              <div className="status-dots">
                                <span></span><span></span><span></span>
                              </div>
                            </div>
                            <div className="app-header-content">
                              <div className="app-icon-screen">🎥</div>
                              <h4>Recordings</h4>
                            </div>
                          </div>
                          <div className="screen-body">
                            <div className="recording-list">
                              <div className="recording-item"></div>
                              <div className="recording-item"></div>
                              <div className="recording-item"></div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="gallery-screen" data-index="3">
                        <div className="screen-content screen-4">
                          <div className="screen-header">
                            <div className="status-bar">
                              <span>9:41</span>
                              <div className="status-dots">
                                <span></span><span></span><span></span>
                              </div>
                            </div>
                            <div className="app-header-content">
                              <div className="app-icon-screen">📊</div>
                              <h4>Progress</h4>
                            </div>
                          </div>
                          <div className="screen-body">
                            <div className="progress-chart">
                              <div className="chart-bar"></div>
                              <div className="chart-bar"></div>
                              <div className="chart-bar"></div>
                              <div className="chart-bar"></div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="phone-home-indicator-gallery"></div>
                </div>
              </div>
              <div className="gallery-dots">
                <span className="dot active"></span>
                <span className="dot"></span>
                <span className="dot"></span>
                <span className="dot"></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}

export default MobileApp

