import React, { createContext, useContext, useState, useCallback, useEffect } from 'react'
import { useNavigate, useLocation } from 'react-router-dom'

const AuthModalContext = createContext(null)

export function useAuthModal() {
  const ctx = useContext(AuthModalContext)
  if (!ctx) throw new Error('useAuthModal must be used within AuthModalProvider')
  return ctx
}

export function AuthModalProvider({ children }) {
  const [isAuthOpen, setIsAuthOpen] = useState(false)
  const [authTab, setAuthTab] = useState('login')

  const navigate = useNavigate()
  const location = useLocation()

  // Ensure modal is closed when navigating between routes
  useEffect(() => {
    setIsAuthOpen(false)
  }, [location.pathname])

  const openLogin = useCallback(() => {
    setAuthTab('login')
    setIsAuthOpen(true)
  }, [])

  const openRegister = useCallback(() => {
    setIsAuthOpen(false)
    navigate('/register')
  }, [navigate])

  const closeAuth = useCallback(() => {
    setIsAuthOpen(false)
  }, [])

  return (
    <AuthModalContext.Provider
      value={{
        isAuthOpen,
        authTab,
        openLogin,
        openRegister,
        closeAuth,
        setAuthTab
      }}
    >
      {children}
    </AuthModalContext.Provider>
  )
}
