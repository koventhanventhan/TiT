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

export const SettingsProvider = ({ children }) => {
    const [settings, setSettings] = useState({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const fetchSettings = async () => {
        try {
            setError(null);
            const data = await settingsService.getSettings();
            if (!data || Object.keys(data).length === 0) {
              // If we got an empty object but the request succeeded, it might still be a config issue
              if (import.meta.env.PROD && import.meta.env.VITE_API_URL?.includes('localhost')) {
                throw new Error('API URL is set to localhost in production. Please check Vercel environment variables.');
              }
            }
            setSettings(data);
        } catch (error) {
            console.error('Failed to fetch settings:', error);
            setError(error.message || 'Failed to connect to the backend server.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
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
              <strong>Tip for Admin:</strong> Ensure <code>VITE_API_URL</code> is correctly set in your Vercel/Production environment variables.
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
