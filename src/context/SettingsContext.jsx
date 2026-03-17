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

    const fetchSettings = async () => {
        try {
            const data = await settingsService.getSettings();
            setSettings(data);
        } catch (error) {
            console.error('Failed to fetch settings:', error);
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
        return settings[key] !== undefined ? settings[key] : defaultValue;
    };

    return (
        <SettingsContext.Provider value={{ settings, loading, getSetting, refreshSettings }}>
            {children}
        </SettingsContext.Provider>
    );
};
