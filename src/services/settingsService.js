import axios from 'axios';
import { getAuthHeaders } from './apiClient';

const API_URL = import.meta.env.VITE_API_URL || '/api';

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
