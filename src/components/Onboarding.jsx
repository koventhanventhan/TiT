import React from 'react'
import { useSettings } from '../context/SettingsContext'
import './Onboarding.css'

const Onboarding = () => {
  const { getSetting } = useSettings();

  const steps = [
    {
      id: '01',
      title: 'Easy Registration',
      description: 'Fill out our simple application form to get started. It only takes a few minutes!',
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/dxjqoygy.json"
          trigger="hover"
          stroke="bold"
          colors="primary:#ffffff,secondary:#ffffff"
          style={{ width: '56px', height: '56px' }}
        />
      ),
      color: '#ffffff'
    },
    {
      id: '02',
      title: 'Free Consultation',
      description: 'Our academic advisors will reach out to understand your goals and recommend the best path.',
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/fdxqxpql.json"
          trigger="hover"
          stroke="bold"
          colors="primary:#ffffff,secondary:#ffffff"
          style={{ width: '56px', height: '56px' }}
        />
      ),
      color: '#ffffff'
    },
    {
      id: '03',
      title: 'Start Learning',
      description: 'Complete your enrollment and unlock instant access to your classes and resources.',
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/jtihyjyw.json"
          trigger="hover"
          stroke="bold"
          colors="primary:#ffffff,secondary:#ffffff"
          style={{ width: '56px', height: '56px' }}
        />
      ),
      color: '#ffffff'
    }
  ];

  return (
    <section className="premium-onboarding section">
      <div className="container">
        <div className="po-header">
          <span className="po-badge">How It Works</span>
          <h2 className="po-title">{getSetting('onboarding_title', 'Your Journey to Excellence')}</h2>
          <p className="po-subtitle">
            {getSetting('onboarding_subtitle', 'Joining EduLearn is simple, fast, and transparent. Follow these steps to begin.')}
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
                    {step.icon}
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
                      style={{ width: '32px', height: '32px' }}
                    />
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>

        <div className="po-footer">
          <a href="/register" className="po-cta-btn">Register Today</a>
        </div>
      </div>
    </section>
  )
}

export default Onboarding
