import React, { useState, useEffect } from 'react'
import { FiX, FiEye, FiEyeOff } from 'react-icons/fi'
import { loginWithEmail, registerWithEmail, loginWithGoogle, loginWithFacebook } from '../services/authService'
import './AnimatedAuth.css'

const AnimatedAuth = ({ isOpen, onClose, defaultTab = 'login' }) => {
  const [isLogin, setIsLogin] = useState(defaultTab === 'login')
  const [showPassword, setShowPassword] = useState(false)
  const [showConfirmPassword, setShowConfirmPassword] = useState(false)
  const [loginData, setLoginData] = useState({ username: '', password: '' })
  const [signupData, setSignupData] = useState({
    username: '',
    email: '',
    password: '',
    confirmPassword: ''
  })
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')

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
    setSignupData({
      ...signupData,
      [e.target.name]: e.target.value
    })
    setError('')
  }

  const handleLoginSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setIsLoading(true)

    try {
      const result = await loginWithEmail(loginData.username, loginData.password)
      setIsLoading(false)
      alert(`Welcome back, ${result.user?.username || result.user?.email || 'User'}!`)
      onClose()
      window.location.reload()
    } catch (err) {
      setIsLoading(false)
      setError(err.message || 'Login failed. Please check your credentials.')
    }
  }

  const handleSignupSubmit = async (e) => {
    e.preventDefault()
    setError('')

    if (signupData.password !== signupData.confirmPassword) {
      setError('Passwords do not match!')
      return
    }

    if (signupData.password.length < 6) {
      setError('Password must be at least 6 characters long!')
      return
    }

    setIsLoading(true)

    try {
      const userData = {
        username: signupData.username,
        email: signupData.email,
        password: signupData.password
      }

      const result = await registerWithEmail(userData)
      setIsLoading(false)
      alert(`Registration successful! Welcome to EduLearn, ${result.user?.username || result.user?.email || 'User'}!`)
      onClose()
      window.location.reload()
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

  const handleFacebookLogin = async () => {
    setIsLoading(true)
    setError('')
    try {
      const result = await loginWithFacebook()
      setIsLoading(false)
      alert(`Welcome, ${result.user?.name || result.user?.username || 'User'}!`)
      onClose()
      window.location.reload()
    } catch (err) {
      setIsLoading(false)
      setError(err.message || 'Facebook login failed. Please try again.')
    }
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
                      <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                      <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                      <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                      <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Google
                  </button>
                  
                  <button type="button" className="facebook-auth-btn" onClick={handleFacebookLogin} disabled={isLoading}>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2">
                      <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Facebook
                  </button>
                </div>
              </form>
            ) : (
              <form onSubmit={handleSignupSubmit}>
                <h2>Register</h2>
                <div className="inputbox">
                  <input
                    type="text"
                    name="username"
                    value={signupData.username}
                    onChange={handleSignupChange}
                    required
                  />
                  <span>Username</span>
                  <i></i>
                </div>

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

                <div className="inputbox">
                  <input
                    type={showPassword ? 'text' : 'password'}
                    name="password"
                    value={signupData.password}
                    onChange={handleSignupChange}
                    required
                    minLength="6"
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

                <div className="inputbox">
                  <input
                    type={showConfirmPassword ? 'text' : 'password'}
                    name="confirmPassword"
                    value={signupData.confirmPassword}
                    onChange={handleSignupChange}
                    required
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

                <div className="links">
                  <span></span>
                  <a href="#" onClick={(e) => { e.preventDefault(); setIsLogin(true) }}>
                    Already have account?
                  </a>
                </div>
                <input type="submit" value={isLoading ? 'Registering...' : 'Register'} disabled={isLoading} />
                
                <div className="divider-auth">
                  <span>or</span>
                </div>
                
                <div className="social-auth-buttons">
                  <button type="button" className="google-auth-btn" onClick={handleGoogleLogin} disabled={isLoading}>
                    <svg width="20" height="20" viewBox="0 0 24 24">
                      <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                      <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                      <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                      <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Google
                  </button>
                  
                  <button type="button" className="facebook-auth-btn" onClick={handleFacebookLogin} disabled={isLoading}>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2">
                      <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Facebook
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

