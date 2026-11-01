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
