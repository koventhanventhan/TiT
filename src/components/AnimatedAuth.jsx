import React, { useState, useEffect } from 'react'
import { useNavigate, useLocation } from 'react-router-dom'
import { FiX, FiEye, FiEyeOff } from 'react-icons/fi'
import { loginWithEmail, registerWithEmail, loginWithGoogle, forgotPassword } from '../services/authService'
import { useLanguage } from '../context/LanguageContext'
import './AnimatedAuth.css'
import { useToast } from '../components/shared/ToastContext'

const AnimatedAuth = ({ isOpen, onClose, defaultTab = 'login' }) => {
  const toast = useToast()
  const navigate = useNavigate()
  const location = useLocation()

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
  const [isForgotPassword, setIsForgotPassword] = useState(false)
  const [forgotEmail, setForgotEmail] = useState('')
  const [successMessage, setSuccessMessage] = useState('')
  const [rememberMe, setRememberMe] = useState(true)

  // Reset modal state on open, close, or route changes
  useEffect(() => {
    if (isOpen) {
      setIsLogin(defaultTab === 'login')
      setError('')
      setSuccessMessage('')
      setIsForgotPassword(false)
    } else {
      setIsForgotPassword(false)
      setError('')
      setSuccessMessage('')
    }
  }, [isOpen, defaultTab, location.pathname])

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

      console.log('Login result:', result)
      console.log('User role:', result?.user?.role)
      console.log('Is admin redirect?', result?._isAdminRedirect)

      if (result && result.user && result.token) {
        const userRole = result.user.role
        const isAdmin = userRole && String(userRole).toLowerCase() === 'admin'
        const isSuperAdmin = userRole && String(userRole).toLowerCase() === 'super_admin'

        if (isSuperAdmin) {
          console.log('✅ Super Admin detected - redirecting to super admin dashboard...')
          setIsLoading(false)
          onClose()
          window.location.href = '/super-admin/dashboard'
          return
        }

        if (isAdmin) {
          console.log('✅ Admin detected - redirecting to dashboard immediately...')
          setIsLoading(false)
          onClose()
          const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'
          const BASE_URL = API_BASE_URL.replace('/api', '') || window.location.origin
          const redirectUrl = `${BASE_URL}/admin/login?token=${encodeURIComponent(result.token)}`
          window.location.href = redirectUrl
          return
        }
      }

      if (result && result._isAdminRedirect) {
        console.log('✅ Admin redirect in progress (from authService)...')
        setIsLoading(false)
        onClose()
        return
      }

      const userRole = result?.user?.role ? String(result.user.role).toLowerCase() : 'user'
      console.log('ℹ️ User role detected:', userRole)

      setIsLoading(false)
      onClose()

      if (userRole === 'teacher') {
        console.log('🎓 Teacher detected - redirecting to /teacher/dashboard')
        window.location.href = '/teacher/dashboard'
      } else if (userRole === 'user') {
        console.log('📚 Student detected - redirecting to /student/dashboard')
        window.location.href = '/student/dashboard'
      } else {
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

      if (errorMessage.includes('Unable to connect') || errorMessage.includes('Cannot connect') || errorMessage.includes('Failed to fetch') || errorMessage.includes('NetworkError')) {
        setError('Unable to connect to server. Please check your internet connection or try again later')
      } else if (errorMessage.includes('credentials') || errorMessage.includes('incorrect')) {
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

      toast.success(`Registration successful! Welcome, ${result.user?.username || result.user?.email || 'Student'}!`)

      // Close the modal and navigate to /register to complete student details
      onClose()
      navigate('/register')
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
      
      // If it's a new student with pending registration, redirect to registration form
      if (result.user?.role === 'user' && result.user?.registration_status === 'pending') {
        console.log('✨ New Google student! Redirecting to registration form for details...')
        onClose()
        navigate('/register')
        return
      }

      onClose()
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
            {isForgotPassword ? (
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

                <div className="existing-parent-notice" style={{
                  background: 'rgba(59, 130, 246, 0.1)',
                  border: '1px solid rgba(59, 130, 246, 0.3)',
                  padding: '12px 15px',
                  borderRadius: '8px',
                  marginBottom: '20px',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '5px'
                }}>
                  <strong style={{ color: '#3b82f6', fontSize: '0.9rem' }}>Already have a Parent Account?</strong>
                  <span style={{ fontSize: '0.8rem', color: '#64748b' }}>If you want to add another child, please <a href="#" onClick={(e) => { e.preventDefault(); setIsLogin(true); setError(''); }} style={{ color: '#3b82f6', fontWeight: 'bold', textDecoration: 'underline' }}>Login here</a> instead of creating a new account.</span>
                </div>

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
