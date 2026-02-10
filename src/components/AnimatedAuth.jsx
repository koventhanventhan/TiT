import React, { useState, useEffect } from 'react'
import { FiX, FiEye, FiEyeOff } from 'react-icons/fi'
import { loginWithEmail, registerWithEmail, loginWithGoogle } from '../services/authService'
import StudentRegistrationForm from './StudentRegistrationForm'
import './AnimatedAuth.css'

const AnimatedAuth = ({ isOpen, onClose, defaultTab = 'login' }) => {
  const [isLogin, setIsLogin] = useState(defaultTab === 'login')
  const [showPassword, setShowPassword] = useState(false)
  const [showConfirmPassword, setShowConfirmPassword] = useState(false)
  const [loginData, setLoginData] = useState({ username: '', password: '' })
  const [signupData, setSignupData] = useState({
    email: '',
    password: '',
    confirmPassword: '',
    role: 'admin', // Admin registration only
    firstName: '',
    lastName: '',
    phoneNumber: ''
  })
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')
  const [showStudentForm, setShowStudentForm] = useState(false)

  useEffect(() => {
    if (isOpen) {
      setIsLogin(defaultTab === 'login')
      setError('')
    }
  }, [isOpen, defaultTab])

  const handleLoginChange = (e) => {
    setLoginData({
      ...loginData,
      [e.target.name]: e.target.value
    })
    setError('')
  }

  const handleSignupChange = (e) => {
    const { name, value, type, checked } = e.target

    if (type === 'checkbox') {
      // Handle other checkboxes if needed in the future
      setSignupData({
        ...signupData,
        [name]: checked
      })
    } else {
      setSignupData({
        ...signupData,
        [name]: value
      })
    }
    setError('')
  }

  const handleLoginSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setIsLoading(true)

    try {
      const result = await loginWithEmail(loginData.username, loginData.password, true)

      // Log the result for debugging
      console.log('Login result:', result)
      console.log('User role:', result?.user?.role)
      console.log('Is admin redirect?', result?._isAdminRedirect)

      // Check if user is admin FIRST - before any alerts or other logic
      if (result && result.user && result.token) {
        const userRole = result.user.role
        const isAdmin = userRole && String(userRole).toLowerCase() === 'admin'

        if (isAdmin) {
          console.log('✅ Admin detected - redirecting to dashboard immediately...')
          console.log('   User:', result.user.email)
          console.log('   Role:', result.user.role)
          console.log('   Token:', result.token ? 'Present' : 'Missing')
          setIsLoading(false)
          onClose()
          // Redirect immediately - NO ALERT, NO DELAY
          const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
          const BASE_URL = API_BASE_URL.replace('/api', '') || 'http://localhost:8000'
          const redirectUrl = `${BASE_URL}/admin/login?token=${encodeURIComponent(result.token)}`
          console.log('🚀 ADMIN REDIRECT TO:', redirectUrl)
          // Immediate redirect - no setTimeout delay
          window.location.href = redirectUrl
          return
        }
      }

      // Check if this is an admin redirect (redirect already happened in authService)
      if (result && result._isAdminRedirect) {
        console.log('✅ Admin redirect in progress (from authService)...')
        setIsLoading(false)
        onClose()
        // Redirect already happened, just return
        return
      }

      // Check role and redirect accordingly
      const userRole = result?.user?.role ? String(result.user.role).toLowerCase() : 'user'
      console.log('ℹ️ User role detected:', userRole)

      setIsLoading(false)
      onClose()

      if (userRole === 'teacher') {
        // Teacher - redirect to teacher dashboard
        console.log('🎓 Teacher detected - redirecting to /teacher/dashboard')
        window.location.href = '/teacher/dashboard'
      } else if (userRole === 'user') {
        // Student - redirect to student dashboard
        console.log('📚 Student detected - redirecting to /student/dashboard')
        window.location.href = '/student/dashboard'
      } else {
        // Unknown role - just reload
        console.log('❓ Unknown role - reloading page')
        alert(`Welcome back, ${result?.user?.username || result?.user?.email || 'User'}!`)
        window.location.reload()
      }
    } catch (err) {
      setIsLoading(false)
      const errorMessage = err.message || 'Login failed. Please check your credentials.'
      setError(errorMessage)
      console.error('❌ Login error in component:', {
        message: err.message,
        stack: err.stack,
        name: err.name,
        fullError: err
      })

      // Show more helpful error messages
      if (errorMessage.includes('Cannot connect') || errorMessage.includes('Failed to fetch') || errorMessage.includes('NetworkError')) {
        setError('Cannot connect to server. Please ensure Laravel server is running: cd backend && php artisan serve')
      } else if (errorMessage.includes('credentials') || errorMessage.includes('incorrect')) {
        // Keep the original error message for credential errors
        setError(errorMessage)
      }
    }
  }

  const handleSignupSubmit = async (e) => {
    e.preventDefault()
    setError('')

    // Common validation
    if (signupData.password !== signupData.confirmPassword) {
      setError('Passwords do not match!')
      return
    }

    if (signupData.password.length < 8) {
      setError('Password must be at least 8 characters long!')
      return
    }

    if (!signupData.email) {
      setError('Email is required')
      return
    }

    // Admin validation
    if (!signupData.firstName || signupData.firstName.length < 2) {
      setError('First name must be at least 2 characters')
      return
    }

    if (!signupData.lastName || signupData.lastName.length < 2) {
      setError('Last name must be at least 2 characters')
      return
    }

    if (!signupData.phoneNumber) {
      setError('Phone number is required')
      return
    }


    setIsLoading(true)

    try {
      let userData = {
        email: signupData.email,
        password: signupData.password,
        role: 'admin', // Admin registration only
        first_name: signupData.firstName,
        last_name: signupData.lastName,
        phone_number: signupData.phoneNumber
      }

      const result = await registerWithEmail(userData)
      setIsLoading(false)

      // Show success message
      alert(`Registration successful! Admin account created. Welcome, ${result.user?.username || result.user?.email || 'Admin'}!`)

      // After successful admin registration, show student entry form
      setShowStudentForm(true)
      onClose() // Close the admin registration form
    } catch (err) {
      setIsLoading(false)
      setError(err.message || 'Registration failed. Please try again.')
    }
  }

  const handleGoogleLogin = async () => {
    setIsLoading(true)
    setError('')
    try {
      const result = await loginWithGoogle()
      setIsLoading(false)
      alert(`Welcome, ${result.user?.name || result.user?.username || 'User'}!`)
      onClose()
      window.location.reload()
    } catch (err) {
      setIsLoading(false)
      setError(err.message || 'Google login failed. Please try again.')
    }
  }


  // Show student registration form if needed
  if (showStudentForm) {
    return (
      <StudentRegistrationForm
        isOpen={true}
        onClose={() => setShowStudentForm(false)}
      />
    )
  }

  if (!isOpen) return null

  return (
    <div className="animated-auth-overlay" onClick={onClose}>
      <div className="animated-auth-wrapper" onClick={(e) => e.stopPropagation()}>
        <button className="animated-auth-close" onClick={onClose}>
          <FiX />
        </button>

        <div className="auth-tabs-container">
          <button
            className={`auth-tab-btn ${isLogin ? 'active' : ''}`}
            onClick={() => {
              setIsLogin(true)
              setError('')
            }}
          >
            Sign in
          </button>
          <button
            className={`auth-tab-btn ${!isLogin ? 'active' : ''}`}
            onClick={() => {
              setIsLogin(false)
              setError('')
            }}
          >
            Register
          </button>
        </div>

        {error && (
          <div className="auth-error-message">
            {error}
          </div>
        )}

        <div className={`animated-box ${isLogin ? '' : 'register-mode'}`}>
          <div className="animated-form">
            {isLogin ? (
              <form onSubmit={handleLoginSubmit}>
                <h2>Sign in</h2>
                <div className="inputbox">
                  <input
                    type="text"
                    name="username"
                    value={loginData.username}
                    onChange={handleLoginChange}
                    placeholder=" "
                    required
                  />
                  <span>Username</span>
                  <i></i>
                </div>

                <div className="inputbox">
                  <input
                    type={showPassword ? 'text' : 'password'}
                    name="password"
                    value={loginData.password}
                    onChange={handleLoginChange}
                    placeholder=" "
                    required
                  />
                  <span>Password</span>
                  <i></i>
                  <button
                    type="button"
                    className="password-toggle"
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    {showPassword ? <FiEyeOff /> : <FiEye />}
                  </button>
                </div>

                <div className="links">
                  <a href="#forgot">Forgot Password</a>
                  <a href="#" onClick={(e) => { e.preventDefault(); setIsLogin(false) }}>
                    Signup
                  </a>
                </div>
                <input type="submit" value={isLoading ? 'Logging in...' : 'Login'} disabled={isLoading} />

                <div className="divider-auth">
                  <span>or</span>
                </div>

                <div className="social-auth-buttons">
                  <button type="button" className="google-auth-btn" onClick={handleGoogleLogin} disabled={isLoading}>
                    <svg width="20" height="20" viewBox="0 0 24 24">
                      <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                      <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                      <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                      <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                    </svg>
                    Google
                  </button>
                </div>
              </form>
            ) : (
              <form onSubmit={handleSignupSubmit}>
                <h2>Register</h2>


                {/* Admin Registration Form */}
                <>
                  {/* First Name */}
                  <div className="inputbox">
                    <input
                      type="text"
                      name="firstName"
                      value={signupData.firstName}
                      onChange={handleSignupChange}
                      required
                    />
                    <span>First Name</span>
                    <i></i>
                  </div>

                  {/* Last Name */}
                  <div className="inputbox">
                    <input
                      type="text"
                      name="lastName"
                      value={signupData.lastName}
                      onChange={handleSignupChange}
                      required
                    />
                    <span>Last Name</span>
                    <i></i>
                  </div>

                  {/* Phone Number */}
                  <div className="inputbox">
                    <input
                      type="tel"
                      name="phoneNumber"
                      value={signupData.phoneNumber}
                      onChange={handleSignupChange}
                      required
                      placeholder="+94XXXXXXXXX"
                    />
                    <span>Phone Number</span>
                    <i></i>
                  </div>

                  {/* Email */}
                  <div className="inputbox">
                    <input
                      type="email"
                      name="email"
                      value={signupData.email}
                      onChange={handleSignupChange}
                      required
                    />
                    <span>Email</span>
                    <i></i>
                  </div>

                  {/* Password */}
                  <div className="inputbox">
                    <input
                      type={showPassword ? 'text' : 'password'}
                      name="password"
                      value={signupData.password}
                      onChange={handleSignupChange}
                      required
                      minLength="8"
                    />
                    <span>Password</span>
                    <i></i>
                    <button
                      type="button"
                      className="password-toggle"
                      onClick={() => setShowPassword(!showPassword)}
                    >
                      {showPassword ? <FiEyeOff /> : <FiEye />}
                    </button>
                  </div>

                  {/* Confirm Password */}
                  <div className="inputbox">
                    <input
                      type={showConfirmPassword ? 'text' : 'password'}
                      name="confirmPassword"
                      value={signupData.confirmPassword}
                      onChange={handleSignupChange}
                      required
                      minLength="8"
                    />
                    <span>Confirm Password</span>
                    <i></i>
                    <button
                      type="button"
                      className="password-toggle"
                      onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                    >
                      {showConfirmPassword ? <FiEyeOff /> : <FiEye />}
                    </button>
                  </div>
                </>

                <div className="links">
                  <span></span>
                  <a href="#" onClick={(e) => { e.preventDefault(); setIsLogin(true) }}>
                    Already have account?
                  </a>
                </div>

                {/* Submit Button */}
                <input
                  type="submit"
                  value={isLoading ? 'Registering...' : 'Create Account'}
                  disabled={isLoading}
                  className="register-submit-input"
                />

                <div className="divider-auth">
                  <span>or</span>
                </div>

                <div className="social-auth-buttons">
                  <button type="button" className="google-auth-btn" onClick={handleGoogleLogin} disabled={isLoading}>
                    <svg width="20" height="20" viewBox="0 0 24 24">
                      <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                      <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                      <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                      <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                    </svg>
                    Google
                  </button>
                </div>
              </form>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}

export default AnimatedAuth

