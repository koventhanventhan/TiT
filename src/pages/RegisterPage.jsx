import React from 'react'
import StudentRegistrationForm from '../components/StudentRegistrationForm'
import './RegisterPage.css'

const RegisterPage = () => {
  return (
    <div className="register-page">
      <StudentRegistrationForm
        isOpen={true}
      />
    </div>
  )
}

export default RegisterPage

