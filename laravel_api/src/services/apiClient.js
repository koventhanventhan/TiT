import axios from 'axios';

/**
 * Centralized API Client helper
 * Consistently attaches Authorization and X-Profile-Id headers
 * across all frontend API requests.
 */

export const getAuthHeaders = (isFormData = false) => {
  // Check which storage was used during login
  const hasLocalToken = !!localStorage.getItem('authToken');
  const storage = hasLocalToken ? localStorage : sessionStorage;
  
  const token = storage.getItem('authToken');
  const userStr = storage.getItem('user');
  const user = userStr ? JSON.parse(userStr) : null;

  const headers = {
    'Accept': 'application/json',
  };

  if (!isFormData) {
    headers['Content-Type'] = 'application/json';
  }

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  // Tenant is resolved from the Host header / domain. X-Institute-Id is no longer sent.

  if (user?.id) {
    headers['X-Profile-Id'] = user.id;
  }

  return headers;
};

export const getActiveStorage = () => {
  const hasLocalToken = !!localStorage.getItem('authToken');
  return hasLocalToken ? localStorage : sessionStorage;
};

/**
 * Check if a response status and payload indicate an expired or suspended institute.
 * Must only match 402 or 403 where code is strictly institute_expired or institute_suspended.
 * Any other 403 (e.g., context mismatch, role errors) returns false.
 */
export const isBlockedPayload = (status, data) => {
  if (status !== 402 && status !== 403) return false;
  return Boolean(
    data && (data.code === 'institute_expired' || data.code === 'institute_suspended')
  );
};

export const notifyInstituteBlocked = (data) => {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent('institute-status-blocked', { detail: data }));
  }
};

let interceptorsInstalled = false;

export const installApiInterceptors = () => {
  if (typeof window === 'undefined' || interceptorsInstalled) return;

  // 1. Axios response interceptor
  axios.interceptors.response.use(
    (response) => response,
    (error) => {
      const status = error?.response?.status;
      const data = error?.response?.data;
      if (isBlockedPayload(status, data)) {
        notifyInstituteBlocked(data);
      }
      return Promise.reject(error);
    }
  );

  // 2. Fetch interceptor
  if (window.fetch) {
    const originalFetch = window.fetch;
    window.fetch = async function (...args) {
      const response = await originalFetch.apply(this, args);
      if (response && (response.status === 402 || response.status === 403)) {
        try {
          const clone = response.clone();
          const data = await clone.json();
          if (isBlockedPayload(response.status, data)) {
            notifyInstituteBlocked(data);
          }
        } catch (_) {}
      }
      return response;
    };
  }

  interceptorsInstalled = true;
};

// Automatically install interceptors
installApiInterceptors();
