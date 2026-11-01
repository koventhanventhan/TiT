import { API_BASE_URL } from './apiBase';

import { getAuthHeaders } from './apiClient';

export const getLearningMaterials = async (params = {}) => {
  const query = new URLSearchParams(params).toString()
  const res = await fetch(`${API_BASE_URL}/learning-materials?${query}`, {
    headers: getAuthHeaders()
  })
  if (!res.ok) throw new Error('Failed to load learning materials')
  return res.json()
}

export default {
  getLearningMaterials
}
