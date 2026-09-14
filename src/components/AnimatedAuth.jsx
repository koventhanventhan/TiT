import React, { useState, useEffect } from 'react'
import { FiX, FiEye, FiEyeOff } from 'react-icons/fi'
import { loginWithEmail, registerWithEmail, loginWithGoogle, forgotPassword, verifyMergeOtp } from '../services/authService'
import StudentRegistrationForm from './StudentRegistrationForm'
import { useLanguage } from '../context/LanguageContext'
import './AnimatedAuth.css'
import { useToast } from '../components/shared/ToastContext';


const AnimatedAuth = ({ isOpen, onClose, defaultTab = 'login' }) => {
  const toast = useToast();

  const { t } = useLanguage()
  const [isLogin, setIsLogin] = useState(defaultTab === 'login')
  const [showPassword, setShowPassword] = useState(false)
  const [showConfirmPassword, setShowConfirmPassword] = useState(false)
  const [loginData, setLoginData] = useState({ username: '', password: '' })
  const [signupData, setSignupData] = useState({
    email: '',
    password: '',
    confirmPassword: '',
    role: 'user', // Default to student registration
  })
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')
  const [showStudentForm, setShowStudentForm] = useState(false)
  const [isForgotPassword, setIsForgotPassword] = useState(false)
  const [forgotEmail, setForgotEmail] = useState('')
  const [successMessage, setSuccessMessage] = useState('')
  const [rememberMe, setRememberMe] = useState(true)

  // Merge Flow State
  const [isMergeFlow, setIsMergeFlow] = useState(false)
  const [mergeToken, setMergeToken] = useState(null)
  const [maskedContact, setMaskedContact] = useState('')
  const [mergeOtp, setMergeOtp] = useState('')

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
      const result = await loginWithEmail(loginData.username, loginData.password, rememberMe)

      // Log the result for debugging
      console.log('Login result:', result)
      console.log('User role:', result?.user?.role)
      console.log('Is admin redirect?', result?._isAdminRedirect)

      // Check if user is admin or super_admin FIRST - before any alerts or other logic
      if (result && result.user && result.token) {
        const userRole = result.user.role
        const isAdmin = userRole && String(userRole).toLowerCase() === 'admin'
        const isSuperAdmin = userRole && String(userRole).toLowerCase() === 'super_admin'

        if (isSuperAdmin) {
          console.log('✅ Super Admin detected - redirecting to super admin dashboard...')
          console.log('   User:', result.user.email)
          console.log('   Role:', result.user.role)
          setIsLoading(false)
          onClose()
          window.location.href = '/super-admin/dashboard'
          return
        }

        if (isAdmin) {
          console.log('✅ Admin detected - redirecting to dashboard immediately...')
          console.log('   User:', result.user.email)
          console.log('   Role:', result.user.role)
          console.log('   Token:', result.token ? 'Present' : 'Missing')
          setIsLoading(false)
          onClose()
          // Redirect immediately - NO ALERT, NO DELAY
          const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'
          const BASE_URL = API_BASE_URL.replace('/api', '') || window.location.origin
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
        toast.info(`Welcome back, ${result?.user?.username || result?.user?.email || 'User'}!`)
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
      if (errorMessage.includes('Unable to connect') || errorMessage.includes('Cannot connect') || errorMessage.includes('Failed to fetch') || errorMessage.includes('NetworkError')) {
        setError('Unable to connect to server. Please check your internet connection or try again later')
      } else if (errorMessage.includes('credentials') || errorMessage.includes('incorrect')) {
        // Keep the original error message for credential errors
        setError(errorMessage)
      }
    }
  }

  const handleSignupSubmit = async (e) => {
    e.preventDefault()
    setError('')

    // Email validation
    if (!signupData.email) {
      setError('Email is required')
      return
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(signupData.email)) {
      setError('Please enter a valid email address')
      return
    }

    // Password strength validation
    if (signupData.password.length < 8) {
      setError('Password must be at least 8 characters long!')
      return
    }

    if (!/[A-Z]/.test(signupData.password)) {
      setError('Password must contain at least 1 uppercase letter (A-Z)')
      return
    }

    if (!/[a-z]/.test(signupData.password)) {
      setError('Password must contain at least 1 lowercase letter (a-z)')
      return
    }

    if (!/[0-9]/.test(signupData.password)) {
      setError('Password must contain at least 1 number (0-9)')
      return
    }

    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(signupData.password)) {
      setError('Password must contain at least 1 special character (!@#$%^&*...)')
      return
    }

    // Confirm password match
    if (signupData.password !== signupData.confirmPassword) {
      setError('Passwords do not match!')
      return
    }


    setIsLoading(true)

    try {
      let userData = {
        email: signupData.email,
        password: signupData.password,
        role: 'user', // Registering as a student
      }

      const result = await registerWithEmail(userData)
      setIsLoading(false)

      // Show success message
      toast.success(`Registration successful! Welcome, ${result.user?.username || result.user?.email || 'Student'}!`)

      // After successful admin registration, show student entry form
      setShowStudentForm(true)
      onClose() // Close the admin registration form
    } catch (err) {
      setIsLoading(false)
      if (err.isMergeFlow) {
        setIsMergeFlow(true)
        setMergeToken(err.mergeToken)
        setMaskedContact(err.maskedContact)
        return
      }
      setError(err.message || 'Registration failed. Please try again.')
    }
  }

  const handleMergeOtpSubmit = async (e) => {
    e.preventDefault()
    if (!mergeOtp) {
      setError('Please enter the OTP.')
      return
    }

    setIsLoading(true)
    setError('')

    try {
      const data = await verifyMergeOtp(mergeToken, mergeOtp)
      setIsLoading(false)
      
      if (data.is_deferred) {
        setShowStudentForm(true)
        setIsMergeFlow(false)
        onClose() 
      } else {
        window.location.reload()
      }
    } catch (err) {
      setIsLoading(false)
      setError(err.message || 'OTP Verification failed.')
    }
  }

  const handleGoogleLogin = async () => {
    setIsLoading(true)
    setError('')
    try {
      const result = await loginWithGoogle()
      setIsLoading(false)
      
      // If it's a new student with pending registration, show the details form
      if (result.user?.role === 'user' && result.user?.registration_status === 'pending') {
        console.log('✨ New Google student! Showing registration form for details...')
        setShowStudentForm(true)
        return
      }

      onClose()
      // Redirection is handled inside loginWithGoogle in authService.js
    } catch (err) {
      setError(err.message || 'Login failed')
      setIsLoading(false)
    }
  }

  const handleForgotSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setSuccessMessage('')
    setIsLoading(true)

    try {
      const response = await forgotPassword(forgotEmail)
      setSuccessMessage(response.message || 'Reset link sent!')
    } catch (err) {
      setError(err.message || 'Failed to send reset link')
    } finally {
      setIsLoading(false)
    }
  }


  // Show student registration form if needed
  if (showStudentForm) {
    return (
      <StudentRegistrationForm
        isOpen={true}
        onClose={() => setShowStudentForm(false)}
        mergeToken={mergeToken}
      />
    )
  }

  if (!isOpen) return null

  return (
    <div className="animated-auth-overlay" onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}>
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
            {t('auth_signin')}
          </button>
          <button
            className={`auth-tab-btn ${!isLogin ? 'active' : ''}`}
            onClick={() => {
              setIsLogin(false)
              setError('')
            }}
          >
            {t('auth_register')}
          </button>
        </div>

        {error && (
          <div className="auth-error-message">
            {error}
          </div>
        )}

        <div className={`animated-box ${isLogin ? '' : 'register-mode'}`}>
          <div className="animated-form">
            {isMergeFlow ? (
              <form onSubmit={handleMergeOtpSubmit}>
                <h2>Verify Account</h2>
                
                <p style={{ color: '#aaa', fontSize: '14px', marginBottom: '15px', textAlign: 'center', lineHeight: '1.5' }}>
                  An account with this contact already exists.<br/>
                  We've sent a 6-digit code to <strong>{maskedContact}</strong> to verify you're the parent.
                </p>

                <div className="inputbox">
                  <input
                    type="text"
                    value={mergeOtp}
                    onChange={(e) => setMergeOtp(e.target.value)}
                    placeholder=" "
                    required
                    maxLength="6"
                    pattern="\d{6}"
                  />
                  <span>6-Digit Code</span>
                  <i></i>
                </div>

                <div className="links">
                  <span></span>
                  <a href="#" onClick={(e) => { e.preventDefault(); setIsMergeFlow(false); setError(''); }}>
                    Cancel
                  </a>
                </div>

                <input type="submit" value={isLoading ? t('loading') : 'Verify'} disabled={isLoading} />
              </form>
            ) : isForgotPassword ? (
              <form onSubmit={handleForgotSubmit}>
                <h2>{t('auth_forgot')}</h2>

                {successMessage ? (
                  <div className="auth-success-message" style={{ 
                    background: 'rgba(34, 197, 94, 0.1)', 
                    border: '1.0px solid rgba(34, 197, 94, 0.3)', 
                    color: '#86efac', 
                    padding: '0.75rem 1rem', 
                    borderRadius: '0.5rem', 
                    marginBottom: '1.25rem', 
                    fontSize: '0.875rem', 
                    textAlign: 'center' 
                  }}>
                    {successMessage}
                  </div>
                ) : null}

                <div className="inputbox">
                  <input
                    type="email"
                    name="email"
                    value={forgotEmail}
                    onChange={(e) => setForgotEmail(e.target.value)}
                    placeholder=" "
                    required
                  />
                  <span>{t('auth_email')}</span>
                  <i></i>
                </div>

                <div className="links">
                  <span></span>
                  <a href="#" onClick={(e) => { e.preventDefault(); setIsForgotPassword(false); setError(''); setSuccessMessage(''); }}>{t('nav_login')}</a>
                </div>

                <input type="submit" value={isLoading ? t('loading') : t('auth_send_reset')} disabled={isLoading} />
              </form>
            ) : isLogin ? (
              <form onSubmit={handleLoginSubmit}>
                <h2>{t('auth_signin')}</h2>
                <div className="inputbox">
                  <input
                    type="text"
                    name="username"
                    value={loginData.username}
                    onChange={handleLoginChange}
                    placeholder=" "
                    required
                  />
                  <span>{t('auth_username')}</span>
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
                  <span>{t('auth_password')}</span>
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
                  <a href="#" onClick={(e) => { e.preventDefault(); setIsForgotPassword(true); setError(''); setSuccessMessage(''); }}>{t('auth_forgot')}</a>
                  <a href="#" onClick={(e) => { e.preventDefault(); setIsLogin(false) }}>
                    {t('auth_signup_link')}
                  </a>
                </div>

                <div className="remember-me-container">
                  <label className="remember-me-label">
                    <input
                      type="checkbox"
                      checked={rememberMe}
                      onChange={(e) => setRememberMe(e.target.checked)}
                    />
                    <span>{t('auth_remember_me')}</span>
                  </label>
                </div>

                <input type="submit" value={isLoading ? t('loading') : t('nav_login')} disabled={isLoading} />

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
                <h2>{t('auth_student_reg')}</h2>


                {/* Admin Registration Form */}

                {/* Email */}
                <div className="inputbox">
                  <input
                    type="email"
                    name="email"
                    value={signupData.email}
                    onChange={handleSignupChange}
                    placeholder=" "
                    required
                  />
                  <span>{t('auth_email')}</span>
                  <i></i>
                </div>

                {/* Password */}
                <div className="inputbox">
                  <input
                    type={showPassword ? 'text' : 'password'}
                    name="password"
                    value={signupData.password}
                    onChange={handleSignupChange}
                    placeholder=" "
                    required
                    minLength="8"
                  />
                  <span>{t('auth_password')}</span>
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
                    placeholder=" "
                    required
                    minLength="8"
                  />
                  <span>{t('auth_confirm_password')}</span>
                  <i></i>
                  <button
                    type="button"
                    className="password-toggle"
                    onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                  >
                    {showConfirmPassword ? <FiEyeOff /> : <FiEye />}
                  </button>
                </div>

                <div className="links">
                  <span></span>
                  <a href="#" onClick={(e) => { e.preventDefault(); setIsLogin(true) }}>
                    {t('auth_already_account')}
                  </a>
                </div>

                {/* Submit Button */}
                <input
                  type="submit"
                  value={isLoading ? t('loading') : t('auth_create_account')}
                  disabled={isLoading}
                  className="register-submit-input"
                />

                <div className="divider-auth">
                  <span>{t('auth_or')}</span>
                </div>

                <div className="social-auth-buttons">
                  <button type="button" className="google-auth-btn" onClick={handleGoogleLogin} disabled={isLoading}>
                    <svg width="20" height="20" viewBox="0 0 24 24">
                      <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                      <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                      <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                      <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                    </svg>
                    {t('auth_google')}
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

