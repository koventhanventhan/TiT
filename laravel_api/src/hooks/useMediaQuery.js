import { useState, useEffect } from 'react';

/**
 * Shared Breakpoint Constants
 * Phone: max-width: 639px (NO rail, hamburger + drawer)
 * Tablet/Desktop: min-width: 640px (rail + hover/tap)
 */
export const PHONE_MEDIA_QUERY = '(max-width: 639px)';
export const TABLET_DESKTOP_MEDIA_QUERY = '(min-width: 640px)';

/**
 * SSR-safe media query hook with dynamic listener on screen resize / rotate.
 * @param {string} query
 * @returns {boolean}
 */
export function useMediaQuery(query) {
  const getMatches = (q) => {
    if (typeof window !== 'undefined' && typeof window.matchMedia === 'function') {
      return window.matchMedia(q).matches;
    }
    return false;
  };

  const [matches, setMatches] = useState(() => getMatches(query));

  useEffect(() => {
    if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') return;

    const mediaQueryList = window.matchMedia(query);
    const listener = (event) => setMatches(event.matches);

    setMatches(mediaQueryList.matches);

    if (mediaQueryList.addEventListener) {
      mediaQueryList.addEventListener('change', listener);
    } else {
      mediaQueryList.addListener(listener);
    }

    return () => {
      if (mediaQueryList.removeEventListener) {
        mediaQueryList.removeEventListener('change', listener);
      } else {
        mediaQueryList.removeListener(listener);
      }
    };
  }, [query]);

  return matches;
}

export default useMediaQuery;
