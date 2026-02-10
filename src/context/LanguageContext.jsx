import React, { createContext, useContext, useState, useEffect, useCallback } from 'react'
import { translations, languageCodes } from '../translations'
import { translateWithGemini } from '../services/translateService'

const LANGUAGE_STORAGE_KEY = 'site_language'
const DEFAULT_LANG = 'en'

const LanguageContext = createContext(null)

export function useLanguage() {
  const ctx = useContext(LanguageContext)
  if (!ctx) throw new Error('useLanguage must be used within LanguageProvider')
  return ctx
}

export function LanguageProvider({ children }) {
  const [language, setLanguageState] = useState(() => {
    try {
      const stored = localStorage.getItem(LANGUAGE_STORAGE_KEY)
      if (stored && (stored === 'en' || stored === 'ta' || stored === 'si')) return stored
    } catch (_) {}
    return DEFAULT_LANG
  })

  const setLanguage = useCallback((lang) => {
    if (lang !== 'en' && lang !== 'ta' && lang !== 'si') return
    setLanguageState(lang)
    try {
      localStorage.setItem(LANGUAGE_STORAGE_KEY, lang)
    } catch (_) {}
  }, [])

  const t = useCallback(
    (key, fallback = '') => {
      const dict = translations[language]
      if (dict && dict[key] !== undefined) return dict[key]
      const enDict = translations.en
      return (enDict && enDict[key]) || fallback || key
    },
    [language]
  )

  const translate = useCallback(
    async (text, targetLang = language) => {
      if (targetLang === 'en' || !text?.trim()) return text
      try {
        const result = await translateWithGemini(text, targetLang)
        return result || text
      } catch (_) {
        return text
      }
    },
    [language]
  )

  useEffect(() => {
    document.documentElement.lang = language === 'ta' ? 'ta' : language === 'si' ? 'si' : 'en'
  }, [language])

  return (
    <LanguageContext.Provider
      value={{
        language,
        setLanguage,
        t,
        translate,
        languageCodes,
      }}
    >
      {children}
    </LanguageContext.Provider>
  )
}
