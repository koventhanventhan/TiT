import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const settingsService = {
  /**
   * Fetch all site settings
   */
  getSettings: async () => {
    try {
      const response = await axios.get(`${API_URL}/settings`);
      return response.data;
    } catch (error) {
      console.error('Error fetching settings:', error);
      return {};
    }
  }
};

export default settingsService;
