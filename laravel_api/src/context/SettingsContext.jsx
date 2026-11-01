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
        if (data.site_name) {
            document.title = data.site_name;
        }
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
        if (data.theme_preset) {
            document.documentElement.dataset.themePreset = data.theme_preset;
            try { localStorage.setItem('tit_theme_preset', data.theme_preset); } catch (_) {}
        }
    };

    const fetchSettings = async () => {
        try {
            setError(null);
            const data = await settingsService.getSettings();
            if (data && typeof data === 'object') {
                applySiteBranding(data);
                setSettings(data);
                try {
                    localStorage.setItem('cached_site_settings', JSON.stringify({
                        host: window.location.host,
                        settings: data
                    }));
                } catch (_) {}
            }
        } catch (err) {
            console.error('Failed to fetch settings:', err);
            // Only show blocking error if we don't even have cached settings
            if (Object.keys(settings).length === 0) {
                setError(err.message || 'Failed to connect to the backend server.');
            }
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
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
        fetchSettings();
    }, []);

    const refreshSettings = () => fetchSettings();

    // Helper to get a setting with a default value
    const getSetting = (key, defaultValue) => {
        return (settings[key] !== undefined && settings[key] !== null) ? settings[key] : defaultValue;
    };

    if (error && !Object.keys(settings).length) {
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
