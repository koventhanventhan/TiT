// Authentication Service
// This service handles email/password and Google authentication

// Laravel API endpoints
const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'

// Extract base URL (without /api) for non-API endpoints like CSRF cookie and admin redirects
export const BASE_URL = API_BASE_URL.replace('/api', '') || window.location.origin

// Debug: Log the API URL being used
if (import.meta.env.PROD && (API_BASE_URL.includes('localhost') || API_BASE_URL.includes('127.0.0.1'))) {
  console.warn('⚠️ PROD WARNING: API_BASE_URL is pointing to localhost. This will fail on Vercel.');
}
console.log('🔧 API Base URL:', API_BASE_URL)
console.log('🔧 Context:', import.meta.env.MODE)

import { getAuthHeaders, getActiveStorage } from './apiClient';

// Helper function to format Laravel validation errors
const formatLaravelErrors = (error) => {
  // Check if this is a Laravel validation error response
  if (error.errors && typeof error.errors === 'object') {
    const errorMessages = []

    // Iterate through each field's errors
    for (const [field, messages] of Object.entries(error.errors)) {
      if (Array.isArray(messages) && messages.length > 0) {
        // Format field name (convert snake_case to Title Case)
        const fieldName = field
          .split('_')
          .map(word => word.charAt(0).toUpperCase() + word.slice(1))
          .join(' ')

        // Add each error message for this field
        messages.forEach(msg => {
          errorMessages.push(`${fieldName}: ${msg}`)
        })
      }
    }

    // Return formatted error message
    if (errorMessages.length > 0) {
      return errorMessages.join(' ')
    }
  }

  // Fallback to original message or error property
  return error.message || error.error || 'An error occurred'
}

// Email/Password Login
export const loginWithEmail = async (usernameOrEmail, password, remember = false) => {
  try {
    // First, get CSRF cookie from Sanctum (required for stateful requests)
    try {
      console.log('🍪 Getting CSRF cookie...')
      await fetch(`${BASE_URL}/sanctum/csrf-cookie`, {
        method: 'GET',
        credentials: 'include',
      })
      console.log('✅ CSRF cookie obtained')
    } catch (csrfError) {
      console.warn('⚠️ Could not get CSRF cookie, continuing anyway:', csrfError)
    }

    console.log('🔐 Attempting login to:', `${API_BASE_URL}/auth/login`)
    console.log('📝 Login credentials:', { usernameOrEmail, passwordLength: password?.length || 0 })

    let response
    try {
      response = await fetch(`${API_BASE_URL}/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          usernameOrEmail,
          password,
          remember,
        }),
      })
    } catch (fetchError) {
      console.error('❌ Network error during login:', {
        error: fetchError,
        message: fetchError.message,
        name: fetchError.name,
        stack: fetchError.stack,
        apiUrl: `${API_BASE_URL}/auth/login`
      })

      // More specific error messages - only for actual network failures
      if (fetchError.name === 'TypeError' &&
        (fetchError.message.includes('Failed to fetch') ||
          fetchError.message.includes('NetworkError') ||
          fetchError.message.includes('Network request failed'))) {
        throw new Error('Unable to connect to server. Please check your internet connection or try again later')
      }
      // Re-throw other errors as-is
      throw fetchError
    }

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Login failed' }))
      console.error('❌ Login failed:', {
        status: response.status,
        statusText: response.statusText,
        error: error
      })

      // Handle 500 server errors
      if (response.status === 500) {
        // Try to extract a more specific error message from the response
        const errorMsg = error.message || error.error || 'Server error occurred. Please check the console for details.'
        // Check if it's a database error
        if (errorMsg.includes('SQLSTATE') || errorMsg.includes('column') || errorMsg.includes('table')) {
          throw new Error('Database error: Please ensure all migrations have been run. Check console for details.')
        }
        throw new Error(errorMsg)
      }

      // Format Laravel validation errors
      const errorMessage = formatLaravelErrors(error)
      throw new Error(errorMessage || `Login failed (${response.status})`)
    }

    const data = await response.json()
    console.log('✅ Login successful!', {
      user: data.user,
      hasToken: !!data.token,
      role: data.user?.role
    })

    // Store token in localStorage or sessionStorage
    if (data.token) {
      const storage = remember ? localStorage : sessionStorage
      storage.setItem('authToken', data.token)
      storage.setItem('user', JSON.stringify(data.user))
      
      if (data.profiles && data.profiles.length > 0) {
        storage.setItem('availableProfiles', JSON.stringify(data.profiles))
      }
      
      console.log(`💾 Token and user data saved to ${remember ? 'localStorage' : 'sessionStorage'}`)

      // Check if user is admin and redirect to admin dashboard
      const userRole = data.user?.role
      // Case-insensitive role check for robustness
      const isAdmin = userRole && String(userRole).toLowerCase() === 'admin'

      console.log('🔍 Role check:', {
        role: userRole,
        roleType: typeof userRole,
        roleString: String(userRole),
        isAdmin: isAdmin,
        hasUser: !!data.user,
        hasToken: !!data.token
      })

      if (data.user && data.token && isAdmin) {
        console.log('👑 ADMIN DETECTED! Redirecting to admin dashboard immediately...')
        console.log('   User:', data.user.email)
        console.log('   Role:', data.user.role)
        console.log('   Token:', data.token ? 'Present (' + data.token.substring(0, 20) + '...)' : 'Missing')

        // Set a flag to prevent component from showing alert
        data._isAdminRedirect = true

        // Direct redirect with token - IMMEDIATE, NO DELAY
        // The token will be validated on the server side and session will be created
        const redirectUrl = `${BASE_URL}/admin/login?token=${encodeURIComponent(data.token)}`
        console.log('🚀 REDIRECTING TO:', redirectUrl)

        // Immediate redirect - no setTimeout delay
        window.location.href = redirectUrl

        return data
      } else if (data.user && data.token) {
        const isStudent = userRole && String(userRole).toLowerCase() === 'user'
        const isTeacher = userRole && String(userRole).toLowerCase() === 'teacher'
        
        if (isStudent) {
          if (data.profiles && data.profiles.length > 1) {
             window.location.href = '/select-profile'
          } else {
             window.location.href = '/student/dashboard'
          }
          return data
        }
        if (isTeacher) {
          window.location.href = '/teacher/dashboard'
          return data
        }
      }
    }

    return data
  } catch (error) {
    console.error('❌ Login error:', {
      message: error.message,
      stack: error.stack,
      name: error.name,
      apiUrl: API_BASE_URL
    })

    // Only modify error message for actual network failures
    // Preserve specific error messages (credentials, validation, etc.)
    const isNetworkError = error.name === 'TypeError' &&
      (error.message.includes('Failed to fetch') ||
        error.message.includes('NetworkError') ||
        error.message.includes('Network request failed'))

    const isSpecificError = error.message.includes('Unable to connect') ||
      error.message.includes('Cannot connect') ||
      error.message.includes('credentials') ||
      error.message.includes('incorrect') ||
      error.message.includes('Login failed') ||
      error.message.includes('validation')

    if (isNetworkError && !isSpecificError) {
      throw new Error('Unable to connect to server. Please check your internet connection or try again later')
    }

    // Re-throw the error (either original or already modified)
    throw error
  }
}

// Student registration step 1 (creates user with pending_payment)
export const registerStep1 = async (userData) => {
  try {
    await fetch(`${BASE_URL}/sanctum/csrf-cookie`, { method: 'GET', credentials: 'include' }).catch(() => { })
    const response = await fetch(`${API_BASE_URL}/register/step1`, {
      method: 'POST',
      headers: getAuthHeaders(),
      credentials: 'include',
      body: JSON.stringify(userData),
    })
    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Registration failed' }))
      
      // Handle the existing_account_found special case gracefully
      if (response.status === 409 && error.status === 'existing_account_found') {
        return error; // Return it so the component can handle the OTP UI transition
      }

      throw new Error(formatLaravelErrors(error) || `Registration failed (${response.status})`)
    }
    const data = await response.json()
    if (data.token) {
      localStorage.setItem('authToken', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
    }
    return data
  } catch (error) {
    if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
      throw new Error('Unable to connect to server. Please check your internet connection or try again later')
    }
    throw error
  }
}

// Student registration step 2 (payment: offline or online)
export const registerStep2 = async (paymentMethod, amount = null, profileId = null) => {
  const token = localStorage.getItem('authToken')
  if (!token) throw new Error('Not authenticated')
  const headers = getAuthHeaders()
  if (profileId) {
    headers['X-Profile-Id'] = profileId
  }
  const response = await fetch(`${API_BASE_URL}/register/step2`, {
    method: 'POST',
    headers,
    credentials: 'include',
    body: JSON.stringify({ payment_method: paymentMethod, amount: amount || undefined }),
  })
  if (!response.ok) {
    const error = await response.json().catch(() => ({}))
    throw new Error(error.message || 'Payment step failed')
  }
  const data = await response.json()

  // Update local user data if present
  if (data.user || data.registration_status) {
    const currentLocalUser = JSON.parse(localStorage.getItem('user') || '{}');
    const updatedUser = {
      ...currentLocalUser,
      ...(data.user || {}),
      registration_status: data.registration_status || data.user?.registration_status
    };
    localStorage.setItem('user', JSON.stringify(updatedUser));
  }

  return data
}

// Send OTP to merge account (sibling registration)
export const sendMergeOtp = async (mergeToken) => {
  try {
    const response = await fetch(`${API_BASE_URL}/register/send-merge-otp`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({ merge_token: mergeToken }),
    })
    
    if (!response.ok) {
      const error = await response.json().catch(() => ({}))
      throw new Error(error.message || 'Failed to send OTP')
    }
    
    return await response.json()
  } catch (error) {
    throw error
  }
}

// Verify OTP to merge account
export const verifyMergeOtp = async (mergeToken, otp, registrationData) => {
  try {
    const response = await fetch(`${API_BASE_URL}/register/verify-merge-otp`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({ merge_token: mergeToken, otp, ...registrationData }),
    })
    
    if (!response.ok) {
      const error = await response.json().catch(() => ({}))
      throw new Error(error.message || 'OTP Verification failed')
    }
    
    const data = await response.json()
    
    // On success, store token and user to log them in
    if (data.token) {
      localStorage.setItem('authToken', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
      if (data.profiles && data.profiles.length > 0) {
        localStorage.setItem('availableProfiles', JSON.stringify(data.profiles))
      }
    }
    
    return data
  } catch (error) {
    throw error
  }
}

// Report payment success after gateway (e.g. Razorpay)
export const registerPaymentSuccess = async (orderId, paymentId = null, profileId = null) => {
  const token = localStorage.getItem('authToken')
  if (!token) throw new Error('Not authenticated')
  const headers = getAuthHeaders()
  if (profileId) {
    headers['X-Profile-Id'] = profileId
  }
  const response = await fetch(`${API_BASE_URL}/register/payment-success`, {
    method: 'POST',
    headers,
    credentials: 'include',
    body: JSON.stringify({ order_id: orderId, payment_id: paymentId }),
  })
  if (!response.ok) {
    const error = await response.json().catch(() => ({}))
    throw new Error(error.message || 'Payment confirmation failed')
  }
  const data = await response.json()

  // Update local user data if present
  if (data.user || data.registration_status) {
    const currentLocalUser = JSON.parse(localStorage.getItem('user') || '{}');
    const updatedUser = {
      ...currentLocalUser,
      ...(data.user || {}),
      registration_status: data.registration_status || data.user?.registration_status
    };
    localStorage.setItem('user', JSON.stringify(updatedUser));
  }

  return data
}

// Send OTP for email verification
export const sendVerificationOtp = async (email) => {
  try {
    const response = await fetch(`${API_BASE_URL}/auth/send-verification-otp`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({ email }),
    })
    
    if (!response.ok) {
      const error = await response.json().catch(() => ({}))
      const errorMessage = formatLaravelErrors(error) || error.message || 'Failed to send verification code'
      throw new Error(errorMessage)
    }
    
    return await response.json()
  } catch (error) {
    throw error
  }
}

// Verify OTP for email verification
export const verifyEmailOtp = async (email, otp) => {
  try {
    const response = await fetch(`${API_BASE_URL}/auth/verify-email-otp`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({ email, otp }),
    })
    
    if (!response.ok) {
      const error = await response.json().catch(() => ({}))
      const errorMessage = formatLaravelErrors(error) || error.message || 'OTP Verification failed'
      throw new Error(errorMessage)
    }
    
    return await response.json()
  } catch (error) {
    throw error
  }
}

// Email/Password Registration
export const registerWithEmail = async (userData) => {
  try {
    // Get CSRF cookie first for stateful requests
    try {
      console.log('🍪 Getting CSRF cookie for registration...')
      await fetch(`${BASE_URL}/sanctum/csrf-cookie`, {
        method: 'GET',
        credentials: 'include',
      })
      console.log('✅ CSRF cookie obtained')
    } catch (csrfError) {
      console.warn('⚠️ Could not get CSRF cookie, continuing anyway:', csrfError)
    }

    console.log('📝 Attempting registration to:', `${API_BASE_URL}/auth/register`)
    console.log('📋 Registration data:', {
      email: userData.email,
      role: userData.role,
      hasPassword: !!userData.password
    })

    let response
    try {
      response = await fetch(`${API_BASE_URL}/auth/register`, {
        method: 'POST',
        headers: getAuthHeaders(),
        credentials: 'include',
        body: JSON.stringify(userData),
      })
    } catch (fetchError) {
      console.error('❌ Network error during registration:', {
        error: fetchError,
        message: fetchError.message,
        name: fetchError.name,
        stack: fetchError.stack,
        apiUrl: `${API_BASE_URL}/auth/register`
      })

      // More specific error messages - only for actual network failures
      if (fetchError.name === 'TypeError' &&
        (fetchError.message.includes('Failed to fetch') ||
          fetchError.message.includes('NetworkError') ||
          fetchError.message.includes('Network request failed'))) {
        throw new Error('Unable to connect to server. Please check your internet connection or try again later')
      }
      // Re-throw other errors as-is
      throw fetchError
    }

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Registration failed' }))
      console.error('❌ Registration failed:', {
        status: response.status,
        statusText: response.statusText,
      })

      // Handle 409 Existing Account Found (Merge Flow)
      if (response.status === 409 && error.status === 'existing_account_found') {
        const mergeError = new Error(error.message || 'An account with this contact already exists.')
        mergeError.isMergeFlow = true
        mergeError.mergeToken = error.merge_token
        mergeError.maskedContact = error.masked_contact
        throw mergeError
      }

      // Handle 500 server errors
      if (response.status === 500) {
        // Try to extract a more specific error message from the response
        const errorMsg = error.message || error.error || 'Server error occurred. Please check the console for details.'
        // Check if it's a database error
        if (errorMsg.includes('SQLSTATE') || errorMsg.includes('column') || errorMsg.includes('table')) {
          throw new Error('Database error: Please ensure all migrations have been run. Check console for details.')
        }
        throw new Error(errorMsg)
      }

      // Format Laravel validation errors
      const errorMessage = formatLaravelErrors(error)
      throw new Error(errorMessage || `Registration failed (${response.status})`)
    }

    const data = await response.json()
    console.log('✅ Registration successful!', {
      user: data.user,
      hasToken: !!data.token,
      role: data.user?.role
    })

    // Store token in localStorage
    if (data.token) {
      localStorage.setItem('authToken', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
      console.log('💾 Token and user data saved to localStorage')
    }

    return data
  } catch (error) {
    console.error('❌ Registration error:', {
      message: error.message,
      stack: error.stack,
      name: error.name,
      apiUrl: API_BASE_URL
    })

    // Only modify error message for actual network failures
    // Preserve specific error messages (validation, duplicate email, etc.)
    const isNetworkError = error.name === 'TypeError' &&
      (error.message.includes('Failed to fetch') ||
        error.message.includes('NetworkError') ||
        error.message.includes('Network request failed'))

    const isSpecificError = error.message.includes('Unable to connect') ||
      error.message.includes('Cannot connect') ||
      error.message.includes('Registration failed') ||
      error.message.includes('validation') ||
      error.message.includes('already exists') ||
      error.message.includes('duplicate')

    if (isNetworkError && !isSpecificError) {
      throw new Error('Unable to connect to server. Please check your internet connection or try again later')
    }

    // Re-throw the error (either original or already modified)
    throw error
  }
}

// Google Login using OAuth Popup
export const loginWithGoogle = () => {
  return new Promise((resolve, reject) => {
    // Real Google OAuth flow - through backend
    const authUrl = `${API_BASE_URL}/auth/google/redirect`

    const popup = window.open(
      authUrl,
      'Google Login',
      'width=500,height=600,scrollbars=yes,resizable=yes'
    )

    if (!popup) {
      reject(new Error('Popup blocked! Please allow popups for this site.'))
      return
    }

    // Listen for OAuth callback
    const checkPopup = setInterval(() => {
      if (popup.closed) {
        clearInterval(checkPopup)
        // Wait a bit to see if 'message' event arrives before rejecting
        setTimeout(() => {
          if (!localStorage.getItem('authToken')) {
            reject(new Error('Google login cancelled'))
          }
        }, 500)
      }
    }, 1000)

    // Handle OAuth callback from the backend popup
    const handleMessage = (event) => {
      // Security: Validate origin if possible, but '*' is used in backend for simplicity in local dev
      // if (event.origin !== BASE_URL) return; 

      if (event.data && (event.data.token || event.data.error)) {
        clearInterval(checkPopup)

        if (event.data.error) {
          reject(new Error(event.data.message || 'Google Auth failed'))
        } else {
          const { user, token } = event.data
          localStorage.setItem('authToken', token)
          localStorage.setItem('user', JSON.stringify(user))

          // Role-based redirect logic (same as loginWithEmail)
          const userRole = user?.role
          const isAdmin = userRole && String(userRole).toLowerCase() === 'admin'
          const isSuperAdmin = userRole && String(userRole).toLowerCase() === 'super_admin'
          const isStudent = userRole && String(userRole).toLowerCase() === 'user'
          const isTeacher = userRole && String(userRole).toLowerCase() === 'teacher'

          // If student is pending, DON'T redirect here, let component handle common registration form
          if (isStudent && user.registration_status === 'pending') {
            console.log('📝 New Google student detected, staying on page for details...')
            resolve({ user, token })
            return
          }

          if (isSuperAdmin) {
            window.location.href = '/super-admin/dashboard'
          } else if (isAdmin) {
            window.location.href = `${BASE_URL}/admin/login?token=${encodeURIComponent(token)}`
          } else if (isStudent) {
            window.location.href = '/student/dashboard'
          } else if (isTeacher) {
            window.location.href = '/teacher/dashboard'
          }

          resolve({ user, token })
        }

        window.removeEventListener('message', handleMessage)
      }
    }

    window.addEventListener('message', handleMessage)
  })
}

// Facebook Login
export const loginWithFacebook = () => {
  return new Promise((resolve, reject) => {
    const FB_APP_ID = import.meta.env.VITE_FACEBOOK_APP_ID || 'YOUR_FACEBOOK_APP_ID'

    // Load Facebook SDK
    if (!window.FB) {
      window.fbAsyncInit = function () {
        window.FB.init({
          appId: FB_APP_ID,
          cookie: true,
          xfbml: true,
          version: 'v18.0'
        })
        performFacebookLogin(FB_APP_ID, resolve, reject)
      }

      const script = document.createElement('script')
      script.src = 'https://connect.facebook.net/en_US/sdk.js'
      script.async = true
      script.defer = true
      script.crossOrigin = 'anonymous'
      script.onerror = () => reject(new Error('Failed to load Facebook SDK'))
      document.head.appendChild(script)
    } else {
      performFacebookLogin(FB_APP_ID, resolve, reject)
    }
  })
}

const performFacebookLogin = (appId, resolve, reject) => {
  if (appId === 'YOUR_FACEBOOK_APP_ID') {
    // Demo mode - simulate Facebook login
    setTimeout(() => {
      const demoUser = {
        id: 'facebook-' + Date.now(),
        username: 'Facebook User',
        email: 'user@facebook.com',
        provider: 'facebook',
        name: 'Facebook User',
        picture: ''
      }

      localStorage.setItem('authToken', 'facebook-token-' + Date.now())
      localStorage.setItem('user', JSON.stringify(demoUser))
      resolve({ user: demoUser, token: 'facebook-token' })
    }, 1000)
    return
  }

  // Real Facebook OAuth flow
  window.FB.login((response) => {
    if (response.authResponse) {
      // Get user info
      window.FB.api('/me', { fields: 'id,name,email,picture' }, (userInfo) => {
        if (userInfo.error) {
          reject(new Error(userInfo.error.message || 'Failed to get user info'))
          return
        }

        const user = {
          id: userInfo.id,
          email: userInfo.email || `${userInfo.id}@facebook.com`,
          username: userInfo.name || `user_${userInfo.id}`,
          name: userInfo.name,
          picture: userInfo.picture?.data?.url || '',
          provider: 'facebook'
        }

        localStorage.setItem('authToken', response.authResponse.accessToken)
        localStorage.setItem('user', JSON.stringify(user))
        resolve({ user, token: response.authResponse.accessToken })
      })
    } else {
      reject(new Error('Facebook login cancelled or failed'))
    }
  }, { scope: 'email,public_profile' })
}

// Get current user from API
export const getCurrentUser = async () => {
  const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken')
  if (!token) return null

  try {
    const response = await fetch(`${API_BASE_URL}/auth/user`, {
      method: 'GET',
      headers: getAuthHeaders(),
      credentials: 'include',
    })

    if (response.ok) {
      const data = await response.json()
      // Persist fresh user data to storage
      if (data.user) {
        localStorage.setItem('user', JSON.stringify(data.user))
        console.log('🔄 Local user data synchronized with API')
      }
      return data.user
    }
  } catch (error) {
    console.error('Get user error:', error)
  }

  // Fallback to storage
  const userStr = localStorage.getItem('user') || sessionStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
}

// Check if user is authenticated
export const isAuthenticated = () => {
  return !!(localStorage.getItem('authToken') || sessionStorage.getItem('authToken'))
}

// Logout
export const logout = async () => {
  const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken')

  if (token) {
    try {
      await fetch(`${API_BASE_URL}/auth/logout`, {
        method: 'POST',
        headers: getAuthHeaders(),
        credentials: 'include',
      })
    } catch (error) {
      console.error('Logout error:', error)
    }
  }

  localStorage.removeItem('authToken')
  localStorage.removeItem('user')
  sessionStorage.removeItem('authToken')
  sessionStorage.removeItem('user')
  window.location.href = '/'
}

// Forgot Password
export const forgotPassword = async (email) => {
  try {
    const response = await fetch(`${API_BASE_URL}/auth/forgot-password`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ email }),
    })

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Failed to send reset link' }))
      throw new Error(error.message || 'Failed to send reset link')
    }

    return await response.json()
  } catch (error) {
    console.error('Forgot password error:', error)
    throw error
  }
}
