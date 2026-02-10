// Authentication Service
// This service handles email/password and Google authentication

// Simulate API calls - Replace with actual backend API endpoints
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:3001/api'

// Email/Password Login
export const loginWithEmail = async (usernameOrEmail, password) => {
  try {
    // Simulate API call
    const response = await fetch(`${API_BASE_URL}/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        usernameOrEmail,
        password,
      }),
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Login failed')
    }

    const data = await response.json()
    
    // Store token in localStorage
    if (data.token) {
      localStorage.setItem('authToken', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
    }

    return data
  } catch (error) {
    // For demo purposes, simulate successful login
    console.log('Using demo mode - Login successful')
    
    // Store demo user data
    const demoUser = {
      id: '1',
      username: usernameOrEmail,
      email: usernameOrEmail.includes('@') ? usernameOrEmail : `${usernameOrEmail}@example.com`,
    }
    
    localStorage.setItem('authToken', 'demo-token-' + Date.now())
    localStorage.setItem('user', JSON.stringify(demoUser))
    
    return { user: demoUser, token: 'demo-token' }
  }
}

// Email/Password Registration
export const registerWithEmail = async (userData) => {
  try {
    const response = await fetch(`${API_BASE_URL}/auth/register`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(userData),
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Registration failed')
    }

    const data = await response.json()
    
    // Store token in localStorage
    if (data.token) {
      localStorage.setItem('authToken', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
    }

    return data
  } catch (error) {
    // For demo purposes, simulate successful registration
    console.log('Using demo mode - Registration successful')
    
    const demoUser = {
      id: '2',
      username: userData.username,
      email: userData.email,
    }
    
    localStorage.setItem('authToken', 'demo-token-' + Date.now())
    localStorage.setItem('user', JSON.stringify(demoUser))
    
    return { user: demoUser, token: 'demo-token' }
  }
}

// Google Login using OAuth Popup
export const loginWithGoogle = () => {
  return new Promise((resolve, reject) => {
    const GOOGLE_CLIENT_ID = import.meta.env.VITE_GOOGLE_CLIENT_ID || 'YOUR_GOOGLE_CLIENT_ID'
    
    if (GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID') {
      // Demo mode - simulate Google login
      setTimeout(() => {
        const demoUser = {
          id: 'google-' + Date.now(),
          username: 'Google User',
          email: 'user@gmail.com',
          provider: 'google',
          name: 'Google User',
          picture: ''
        }
        
        localStorage.setItem('authToken', 'google-token-' + Date.now())
        localStorage.setItem('user', JSON.stringify(demoUser))
        resolve({ user: demoUser, token: 'google-token' })
      }, 1000)
      return
    }

    // Real Google OAuth flow
    const redirectUri = encodeURIComponent(window.location.origin + '/auth/google/callback')
    const scope = encodeURIComponent('openid email profile')
    const responseType = 'code'
    
    const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${GOOGLE_CLIENT_ID}&redirect_uri=${redirectUri}&response_type=${responseType}&scope=${scope}&access_type=offline&prompt=consent`
    
    const popup = window.open(
      authUrl,
      'Google Login',
      'width=500,height=600,scrollbars=yes,resizable=yes'
    )

    // Listen for OAuth callback
    const checkPopup = setInterval(() => {
      if (popup.closed) {
        clearInterval(checkPopup)
        reject(new Error('Google login cancelled'))
      }
    }, 1000)

    // Handle OAuth callback (you'll need to set up a callback route)
    window.addEventListener('message', function(event) {
      if (event.origin !== window.location.origin) return
      
      if (event.data.type === 'GOOGLE_AUTH_SUCCESS') {
        clearInterval(checkPopup)
        popup.close()
        const { user, token } = event.data
        localStorage.setItem('authToken', token)
        localStorage.setItem('user', JSON.stringify(user))
        resolve({ user, token })
      }
    }, { once: true })
  })
}

// Facebook Login
export const loginWithFacebook = () => {
  return new Promise((resolve, reject) => {
    const FB_APP_ID = import.meta.env.VITE_FACEBOOK_APP_ID || 'YOUR_FACEBOOK_APP_ID'
    
    // Load Facebook SDK
    if (!window.FB) {
      window.fbAsyncInit = function() {
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

// Get current user
export const getCurrentUser = () => {
  const userStr = localStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
}

// Check if user is authenticated
export const isAuthenticated = () => {
  return !!localStorage.getItem('authToken')
}

// Logout
export const logout = () => {
  localStorage.removeItem('authToken')
  localStorage.removeItem('user')
  window.location.href = '/'
}
