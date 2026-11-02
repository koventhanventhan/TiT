import React, { createContext, useContext, useState, useEffect } from 'react';
import settingsService from '../services/settingsService';

const SettingsContext = createContext();

export const useSettings = () => {
    const context = useContext(SettingsContext);
    if (!context) {
        throw new Error('useSettings must be used within a SettingsProvider');
    }
    return context;
};

const getInitialCachedSettings = () => {
    try {
        const raw = localStorage.getItem('cached_site_settings');
        if (raw) {
            const parsed = JSON.parse(raw);
            if (parsed && parsed.host === window.location.host && parsed.settings) {
                return parsed.settings;
            } else {
                localStorage.removeItem('cached_site_settings');
            }
        }
    } catch (_) {}
    return {};
};

export const SettingsProvider = ({ children }) => {
    const [settings, setSettings] = useState(getInitialCachedSettings);
    const [loading, setLoading] = useState(() => Object.keys(getInitialCachedSettings()).length === 0);
    const [error, setError] = useState(null);

    const applySiteBranding = (data) => {
        if (!data || typeof data !== 'object') return;
        const siteName = data.site_name || 'Online Education';
        document.title = siteName;

        const currentOrigin = window.location.origin;
        const currentPathname = window.location.pathname;
        const canonicalUrl = `${currentOrigin}${currentPathname}`;

        // 1. <link rel="canonical"> = location.origin + location.pathname
        let canonicalLink = document.querySelector("link[rel='canonical']");
        if (!canonicalLink) {
            canonicalLink = document.createElement('link');
            canonicalLink.rel = 'canonical';
            document.head.appendChild(canonicalLink);
        }
        canonicalLink.href = canonicalUrl;

        // Helper for meta tags
        const setMetaTag = (attrName, attrValue, content) => {
            if (!content) return;
            let el = document.querySelector(`meta[${attrName}='${attrValue}']`);
            if (!el) {
                el = document.createElement('meta');
                el.setAttribute(attrName, attrValue);
                document.head.appendChild(el);
            }
            el.setAttribute('content', content);
        };

        // 2. OpenGraph & Twitter tags
        setMetaTag('property', 'og:url', canonicalUrl);
        setMetaTag('property', 'og:title', siteName);
        if (data.site_description) {
            setMetaTag('property', 'og:description', data.site_description);
            setMetaTag('name', 'description', data.site_description);
        }

        // 3. og:image (institute logo from settings, absolute URL on the same host)
        const rawLogo = data.site_logo || data.site_favicon;
        let absoluteLogoUrl = '';
        if (rawLogo) {
            if (rawLogo.startsWith('http://') || rawLogo.startsWith('https://')) {
                try {
                    const parsed = new URL(rawLogo);
                    absoluteLogoUrl = parsed.href;
                } catch (_) {
                    absoluteLogoUrl = `${currentOrigin}/${rawLogo.replace(/^\/+/, '')}`;
                }
            } else {
                absoluteLogoUrl = `${currentOrigin}/${rawLogo.replace(/^\/+/, '')}`;
            }
            setMetaTag('property', 'og:image', absoluteLogoUrl);
        }

        // 4. JSON-LD structured data (EducationalOrganization with institute name/url/logo/contact)
        let jsonLdScript = document.querySelector("script[type='application/ld+json']#institute-json-ld")
            || document.querySelector("script[type='application/ld+json']");
        if (!jsonLdScript) {
            jsonLdScript = document.createElement('script');
            jsonLdScript.type = 'application/ld+json';
            jsonLdScript.id = 'institute-json-ld';
            document.head.appendChild(jsonLdScript);
        }
        const jsonLdData = {
            '@context': 'https://schema.org',
            '@type': 'EducationalOrganization',
            name: siteName,
            url: currentOrigin,
        };
        if (absoluteLogoUrl) {
            jsonLdData.logo = absoluteLogoUrl;
        }
        if (data.contact_email || data.contact_phone || data.contact_whatsapp) {
            jsonLdData.contactPoint = {
                '@type': 'ContactPoint',
                contactType: 'customer support',
                ...(data.contact_email ? { email: data.contact_email } : {}),
                ...(data.contact_phone ? { telephone: data.contact_phone } : {}),
            };
        }
        jsonLdScript.textContent = JSON.stringify(jsonLdData, null, 2);

        // 5. Favicon
        const faviconUrl = data.site_favicon || data.site_logo;
        if (faviconUrl) {
            let link = document.querySelector("link[rel~='icon']");
            if (!link) {
                link = document.createElement('link');
                link.rel = 'icon';
                document.head.appendChild(link);
            }
            link.href = faviconUrl;
        }

        // 6. Theme preset
        const enabledPresets = ['royal', 'ocean', 'emerald'];
        const preset = (data.theme_preset && enabledPresets.includes(data.theme_preset))
            ? data.theme_preset
            : 'royal';
        document.documentElement.dataset.themePreset = preset;
        try { localStorage.setItem('tit_theme_preset', preset); } catch (_) {}
    };

    const isConsoleRoute = () => {
        try {
            const p = window.location.pathname;
            return p.startsWith('/admin') ||
                   p.startsWith('/teacher') ||
                   p.startsWith('/student') ||
                   p.startsWith('/super-admin') ||
                   p.startsWith('/parent');
        } catch (_) {
            return false;
        }
    };

    const handleSettingsFailure = (err, attempt) => {
        // Ensure royal default is active on failure
        if (!document.documentElement.dataset.themePreset) {
            document.documentElement.dataset.themePreset = 'royal';
        }
        // Schedule background retry with exponential backoff
        if (attempt < 8) {
            const delay = Math.min(2000 * Math.pow(1.5, attempt), 30000);
            setTimeout(() => {
                fetchSettings(attempt + 1);
            }, delay);
        }
        // Only block authenticated consoles if no settings exist
        if (isConsoleRoute() && Object.keys(settings).length === 0) {
            setError(err.message || 'Failed to connect to the backend server.');
        }
    };

    const fetchSettings = async (attempt = 0) => {
        try {
            setError(null);
            const data = await settingsService.getSettings();
            if (data && typeof data === 'object' && Object.keys(data).length > 0) {
                applySiteBranding(data);
                setSettings(data);
                try {
                    localStorage.setItem('cached_site_settings', JSON.stringify({
                        host: window.location.host,
                        settings: data
                    }));
                } catch (_) {}
            } else {
                handleSettingsFailure(new Error('Settings response was empty or non-JSON'), attempt);
            }
        } catch (err) {
            console.error('Failed to fetch settings:', err);
            handleSettingsFailure(err, attempt);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        // Ensure royal default if attribute is absent
        if (!document.documentElement.dataset.themePreset) {
            document.documentElement.dataset.themePreset = 'royal';
        }
        // Apply cached settings immediately on mount
        const initial = getInitialCachedSettings();
        if (initial && Object.keys(initial).length > 0) {
            applySiteBranding(initial);
        } else {
            try {
                const cachedPreset = localStorage.getItem('tit_theme_preset');
                if (cachedPreset) document.documentElement.dataset.themePreset = cachedPreset;
            } catch (_) {}
        }
        fetchSettings(0);
    }, []);

    const refreshSettings = () => fetchSettings(0);

    // Helper to get a setting with a default value
    const getSetting = (key, defaultValue) => {
        return (settings[key] !== undefined && settings[key] !== null) ? settings[key] : defaultValue;
    };

    if (error && isConsoleRoute() && !Object.keys(settings).length) {
      return (
        <div style={{ 
          padding: '2.5rem', 
          textAlign: 'center', 
          fontFamily: 'Inter, sans-serif',
          background: '#f8fafc',
          minHeight: '100dvh',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center'
        }}>
          <div style={{ 
            background: 'white', 
            padding: '1.875rem', 
            borderRadius: '0.75rem', 
            boxShadow: '0 0.25rem 0.375rem -1.0px rgb(0 0 0 / 0.1)',
            maxWidth: '31.25rem'
          }}>
            <h2 style={{ color: '#ef4444', marginBottom: '1rem' }}>Connection Error</h2>
            <p style={{ color: '#475569', marginBottom: '1.5rem' }}>
              {error}
            </p>
            <div style={{ textAlign: 'left', background: '#f1f5f9', padding: '0.9375rem', borderRadius: '0.5rem', fontSize: '0.875rem', marginBottom: '1.25rem' }}>
              <strong>Tip:</strong> Ensure the backend server is running and accessible at <code>/api</code>.
            </div>
            <button 
              onClick={fetchSettings}
              style={{
                background: '#4f46e5',
                color: 'white',
                padding: '0.625rem 1.25rem',
                borderRadius: '0.375rem',
                border: 'none',
                cursor: 'pointer',
                fontWeight: '600'
              }}
            >
              Try Again
            </button>
          </div>
        </div>
      );
    }

    return (
        <SettingsContext.Provider value={{ settings, loading, getSetting, refreshSettings }}>
            {children}
        </SettingsContext.Provider>
    );
};
