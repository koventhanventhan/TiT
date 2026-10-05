import React, { createContext, useContext, useState, useCallback } from 'react'

const AuthModalContext = createContext(null)

export function useAuthModal() {
  const ctx = useContext(AuthModalContext)
  if (!ctx) throw new Error('useAuthModal must be used within AuthModalProvider')
  return ctx
}

export function AuthModalProvider({ children }) {
  const [isAuthOpen, setIsAuthOpen] = useState(false)
  const [authTab, setAuthTab] = useState('login')

  const openLogin = useCallback(() => {
    setAuthTab('login')
    setIsAuthOpen(true)
  }, [])

  const openRegister = useCallback(() => {
    setAuthTab('register')
    setIsAuthOpen(true)
  }, [])

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
