import React, { createContext, useContext, useState, useEffect, useCallback } from 'react'
import { translations, languageCodes } from '../translations'
import { translateWithGemini } from '../services/translateService'

const LANGUAGE_STORAGE_KEY = 'site_language'
const TRANSLATION_CACHE_KEY = 'site_translation_cache'
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

  const [translationCache, setTranslationCache] = useState(() => {
    try {
      const stored = sessionStorage.getItem(TRANSLATION_CACHE_KEY)
      return stored ? JSON.parse(stored) : {}
    } catch (_) {
      return {}
    }
  })

  const updateCache = useCallback((lang, text, translated) => {
    setTranslationCache(prev => {
      const newCache = {
        ...prev,
        [lang]: {
          ...(prev[lang] || {}),
          [text]: translated
        }
      }
      try {
        sessionStorage.setItem(TRANSLATION_CACHE_KEY, JSON.stringify(newCache))
      } catch (_) {}
      return newCache
    })
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
      if (!text || typeof text !== 'string' || !text.trim()) return text
      if (targetLang === 'en') return text

      // Check cache first
      if (translationCache[targetLang] && translationCache[targetLang][text]) {
        return translationCache[targetLang][text]
      }

      try {
        const result = await translateWithGemini(text, targetLang)
        if (result && result !== text) {
          updateCache(targetLang, text, result)
          return result
        }
        return text
      } catch (_) {
        return text
      }
    },
    [language, translationCache, updateCache]
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
