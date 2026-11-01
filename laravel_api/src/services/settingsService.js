import axios from 'axios';
import { getAuthHeaders } from './apiClient';

import { API_BASE_URL } from './apiBase';
const API_URL = API_BASE_URL;

const settingsService = {
  /**
   * Fetch all site settings
   */
  getSettings: async () => {
    try {
      const response = await axios.get(`${API_URL}/settings`, {
        headers: getAuthHeaders()
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching settings:', error);
      return {};
    }
  }
};

export default settingsService;
