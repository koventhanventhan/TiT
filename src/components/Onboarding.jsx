import React from 'react'
import { FiUserPlus, FiMessageCircle, FiCreditCard } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './Onboarding.css'

const Onboarding = () => {
  const { getSetting } = useSettings();

  const defaultSteps = [
    {
      number: '01',
      title: 'Register',
      description: 'Ready to get started? Click "Register" to enroll and apply. Make sure to enter your details correctly!'
    },
    {
      number: '02',
      title: 'Consultation',
      description: 'A dedicated student Consultation will contact you to discuss your application and answer any questions you may have.'
    },
    {
      number: '03',
      title: 'Pay & Learn',
      description: 'Kick start your journey to success with EduLearn immediately following your initial subscription payment. Begin a transformative learning experience tailored to your aspirations.'
    }
  ]

  let steps = defaultSteps;
  const stepsJson = getSetting('onboarding_steps');
  if (stepsJson) {
    try {
      const parsed = JSON.parse(stepsJson);
      if (Array.isArray(parsed) && parsed.length > 0) {
        steps = parsed;
      }
    } catch (e) {
      console.error('Failed to parse onboarding_steps JSON', e);
    }
  }

  // Icons mapping for steps (we'll cycle through these or use a default)
  const icons = [<FiUserPlus />, <FiMessageCircle />, <FiCreditCard />];

  return (
    <section className="onboarding section">
      <div className="container">
        <div className="onboarding-header">
          <h2 className="section-title">{getSetting('onboarding_title', 'Onboarding Process')}</h2>
          <p className="section-subtitle">
            {getSetting('onboarding_subtitle', 'Follow our simple steps to join EduLearn Online Tuition 📚')}
          </p>
        </div>

        <div className="steps-container">
          {steps.map((step, index) => (
            <div key={index} className="step-card">
              <div className="step-number">{step.number}</div>
              <div className="step-icon">{icons[index % icons.length]}</div>
              <h3 className="step-title">{step.title}</h3>
              <p className="step-description">{step.description}</p>
              {index < steps.length - 1 && <div className="step-connector"></div>}
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

export default Onboarding

