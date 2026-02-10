import React from 'react'
import { FiSmartphone, FiDownload } from 'react-icons/fi'
import './MobileApp.css'

const MobileApp = () => {
  return (
    <section className="mobile-app section">
      <div className="container">
        <div className="mobile-app-content">
          <div className="mobile-app-text">
            <h2 className="section-title mobile-title">
              Learn Anytime, Anywhere..!
            </h2>
            <h3 className="mobile-subtitle">EduLearn Mobile App</h3>
            <p className="mobile-description">
              Take your learning on the go with the EduLearn Mobile App, available on both 
              iOS and Android. Access live classes, class recordings, exams, and progress 
              updates seamlessly from your mobile device. Stay connected, stay updated, and 
              unlock a world of learning at your fingertips—anytime, anywhere!
            </p>
            <div className="mobile-badges">
              <div className="badge">
                <FiDownload />
                <span>Download on App Store</span>
              </div>
              <div className="badge">
                <FiDownload />
                <span>Get it on Google Play</span>
              </div>
            </div>
          </div>
          <div className="mobile-app-visual">
            <div className="phone-mockup">
              <div className="phone-screen">
                <div className="app-interface">
                  <div className="app-header">
                    <div className="app-icon">📚</div>
                    <h4>EduLearn</h4>
                  </div>
                  <div className="app-content">
                    <div className="app-card"></div>
                    <div className="app-card"></div>
                    <div className="app-card"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}

export default MobileApp

