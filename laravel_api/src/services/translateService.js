import { API_BASE_URL } from './apiBase';
/**
 * Translation service: uses static translations first;
 * optional Gemini API for on-demand translation of dynamic content.
 */


import { getAuthHeaders } from './apiClient';

/**
 * Call backend Gemini translate API for dynamic text.
 * @param {string} text - Text to translate
 * @param {string} targetLang - 'ta' or 'si'
 * @returns {Promise<string>} Translated text
 */
export async function translateWithGemini(text, targetLang) {
  if (!text || typeof text !== 'string') return text
  if (targetLang === 'en') return text
  try {
    const res = await fetch(`${API_BASE_URL}/translate`, {
      method: 'POST',
      headers: getAuthHeaders(),
      credentials: 'include',
      body: JSON.stringify({ text: text.trim(), target_lang: targetLang }),
    })
    if (!res.ok) return text
    const data = await res.json()
    return data.translated_text || text
  } catch (_) {
    return text
  }
}
