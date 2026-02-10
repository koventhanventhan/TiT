import React from 'react'
import StudentRegistrationForm from '../components/StudentRegistrationForm'
import './RegisterPage.css'

const RegisterPage = () => {
  const handleClose = () => {
    // Don't navigate - stay on the current page
    // User can continue using the frontend
  }

  return (
    <div className="register-page">
      <StudentRegistrationForm 
        isOpen={true}
        onClose={handleClose}
      />
    </div>
  )
}

export default RegisterPage

