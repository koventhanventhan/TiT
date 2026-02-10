import React from 'react'
import { FiUserPlus, FiMessageCircle, FiCreditCard } from 'react-icons/fi'
import './Onboarding.css'

const Onboarding = () => {
  const steps = [
    {
      number: '01',
      icon: <FiUserPlus />,
      title: 'Register',
      description: 'Ready to get started? Click "Register" to enroll and apply. Make sure to enter your details correctly!'
    },
    {
      number: '02',
      icon: <FiMessageCircle />,
      title: 'Consultation',
      description: 'A dedicated student Consultation will contact you to discuss your application and answer any questions you may have.'
    },
    {
      number: '03',
      icon: <FiCreditCard />,
      title: 'Pay & Learn',
      description: 'Kick start your journey to success with EduLearn immediately following your initial subscription payment. Begin a transformative learning experience tailored to your aspirations.'
    }
  ]

  return (
    <section className="onboarding section">
      <div className="container">
        <div className="onboarding-header">
          <h2 className="section-title">Onboarding Process</h2>
          <p className="section-subtitle">
            Follow our simple steps to join EduLearn Online Tuition 📚
          </p>
        </div>

        <div className="steps-container">
          {steps.map((step, index) => (
            <div key={index} className="step-card">
              <div className="step-number">{step.number}</div>
              <div className="step-icon">{step.icon}</div>
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

