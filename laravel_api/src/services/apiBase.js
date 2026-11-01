/**
 * Dynamic Same-Origin API Base and Storage URL Resolver
 * Resolves API calls to the same origin (/api) across all tenant domains.
 * Allows VITE_API_URL only as an optional local dev override.
 */
export const API_BASE_URL = (import.meta.env.VITE_API_URL && import.meta.env.DEV)
  ? import.meta.env.VITE_API_URL
  : '/api';

/**
 * Returns the base URL for the backend without trailing /api,
 * e.g. '' (same-origin) or 'http://127.0.0.1:8000' in dev.
 */
export const BACKEND_BASE_URL = API_BASE_URL.replace(/\/api\/?$/, '');

/**
 * Resolves storage/uploaded asset URLs relative to the current host.
 * If url starts with 'http://' or 'https://', returns it directly.
 * Otherwise returns relative root path or dev backend path.
 */
export function resolveAssetUrl(path) {
  if (!path) return '';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  const cleanPath = path.startsWith('/') ? path : `/${path}`;
  return BACKEND_BASE_URL ? `${BACKEND_BASE_URL}${cleanPath}` : cleanPath;
}

export default API_BASE_URL;

