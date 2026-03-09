import React, { useState, useEffect } from 'react'
import {
    FiSettings,
    FiGlobe,
    FiPhone,
    FiShare2,
    FiSave,
    FiLayout
} from 'react-icons/fi'
import { getAdminSettings, updateAdminBranding } from '../../services/dashboardService'
import { FiImage } from 'react-icons/fi'
import './AdminSettings.css'

export default function AdminSettings() {
    const [settings, setSettings] = useState({})
    const [loading, setLoading] = useState(true)
    const [activeTab, setActiveTab] = useState('branding')
    const [logoFile, setLogoFile] = useState(null)
    const [themeSettings, setThemeSettings] = useState({})

    useEffect(() => {
        async function loadSettings() {
            try {
                const data = await getAdminSettings()
                setSettings(data || {})
            } catch (error) {
                console.error('Error loading settings:', error)
            } finally {
                setLoading(false)
            }
        }
        loadSettings()
    }, [])

    const handleBrandingSave = async () => {
        const formData = new FormData()
        if (logoFile) formData.append('logo', logoFile)
        if (themeSettings.primary_color) formData.append('primary_color', themeSettings.primary_color)
        if (themeSettings.theme_mode) formData.append('theme_mode', themeSettings.theme_mode)

        try {
            await updateAdminBranding(formData)
            alert('Branding updated successfully! Refresh to apply changes.')
        } catch (error) {
            alert('Failed to update branding')
        }
    }

    if (loading) return <div className="loading-shimmer">Accessing system configuration...</div>

    return (
        <div className="admin-settings">
            <div className="page-header">
                <div className="header-info">
                    <h1>System Configuration</h1>
                    <p>Manage website content, branding, and global parameters.</p>
                </div>
                <button className="save-btn"><FiSave /> Save All Changes</button>
            </div>

            <div className="settings-layout">
                <aside className="tabs-nav">
                    <button className={activeTab === 'branding' ? 'active' : ''} onClick={() => setActiveTab('branding')}>
                        <FiImage /> Branding
                    </button>
                    <button className={activeTab === 'general' ? 'active' : ''} onClick={() => setActiveTab('general')}>
                        <FiGlobe /> General Site
                    </button>
                    <button className={activeTab === 'content' ? 'active' : ''} onClick={() => setActiveTab('content')}>
                        <FiLayout /> Page Content
                    </button>
                    <button className={activeTab === 'contact' ? 'active' : ''} onClick={() => setActiveTab('contact')}>
                        <FiPhone /> Contact Info
                    </button>
                    <button className={activeTab === 'social' ? 'active' : ''} onClick={() => setActiveTab('social')}>
                        <FiShare2 /> Social Links
                    </button>
                </aside>

                <div className="tab-content">
                    <div className="content-card">
                        {activeTab === 'branding' && (
                            <div className="settings-group">
                                <h3>Institute Branding</h3>
                                <p className="description">Customize how your institute appears to students and teachers.</p>

                                <div className="branding-selection">
                                    <div className="logo-upload-section">
                                        <label>Institute Logo</label>
                                        <div className="logo-preview-box">
                                            {settings.logo_url ? (
                                                <img src={settings.logo_url} alt="Logo" />
                                            ) : (
                                                <div className="logo-placeholder">No Logo Uploaded</div>
                                            )}
                                        </div>
                                        <input type="file" onChange={(e) => setLogoFile(e.target.files[0])} accept="image/*" />
                                    </div>

                                    <div className="theme-colors">
                                        <label>Primary Brand Color</label>
                                        <div className="color-picker-group">
                                            <input
                                                type="color"
                                                defaultValue={settings.theme_settings?.primary_color || '#1e40af'}
                                                onChange={(e) => setThemeSettings({ ...themeSettings, primary_color: e.target.value })}
                                            />
                                            <span>{themeSettings.primary_color || settings.theme_settings?.primary_color || '#1e40af'}</span>
                                        </div>
                                    </div>

                                    <div className="theme-mode">
                                        <label>Default Appearance</label>
                                        <select
                                            defaultValue={settings.theme_settings?.theme_mode || 'light'}
                                            onChange={(e) => setThemeSettings({ ...themeSettings, theme_mode: e.target.value })}
                                        >
                                            <option value="light">Always Light</option>
                                            <option value="dark">Always Dark</option>
                                        </select>
                                    </div>
                                </div>
                                <button className="save-btn mt-6" onClick={handleBrandingSave}>
                                    <FiSave /> Update Branding
                                </button>
                            </div>
                        )}

                        {activeTab === 'general' && (
                            <div className="settings-group">
                                <h3>Branding & Identity</h3>
                                <div className="input-row">
                                    <div className="input-group">
                                        <label>Platform Name</label>
                                        <input type="text" defaultValue={settings.site_name || 'Lenova LMS'} />
                                    </div>
                                    <div className="input-group">
                                        <label>Tagline</label>
                                        <input type="text" defaultValue={settings.site_tagline || 'Excellence in Online Learning'} />
                                    </div>
                                </div>
                                <div className="input-group mt-4">
                                    <label>Site Meta Description</label>
                                    <textarea rows="3" defaultValue={settings.site_meta_desc || 'The best online tuition platform.'}></textarea>
                                </div>
                            </div>
                        )}

                        {activeTab === 'content' && (
                            <div className="settings-group">
                                <h3>Hero Section Management</h3>
                                <div className="input-group">
                                    <label>Hero Heading</label>
                                    <input type="text" defaultValue={settings.hero_title || 'Master Your Future'} />
                                </div>
                                <div className="input-group mt-4">
                                    <label>Hero Subtext</label>
                                    <textarea rows="3" defaultValue={settings.hero_subtext || 'Join thousands of students in Sri Lanka.'}></textarea>
                                </div>
                            </div>
                        )}

                        {activeTab === 'contact' && (
                            <div className="settings-group">
                                <h3>Contact Access Points</h3>
                                <div className="input-row">
                                    <div className="input-group">
                                        <label>Support Email</label>
                                        <input type="email" defaultValue={settings.contact_email || 'support@lenova.lk'} />
                                    </div>
                                    <div className="input-group">
                                        <label>Primary Phone</label>
                                        <input type="text" defaultValue={settings.contact_phone || '+94 77 123 4567'} />
                                    </div>
                                </div>
                                <div className="input-group mt-4">
                                    <label>Physical Address</label>
                                    <input type="text" defaultValue={settings.contact_address || '123 Main St, Jaffna'} />
                                </div>
                            </div>
                        )}

                        {activeTab === 'social' && (
                            <div className="settings-group">
                                <h3>Social Media Connections</h3>
                                <div className="input-group mb-4">
                                    <label>Facebook Page URL</label>
                                    <input type="text" placeholder="https://facebook.com/..." />
                                </div>
                                <div className="input-group mb-4">
                                    <label>Instagram Handle</label>
                                    <input type="text" placeholder="https://instagram.com/..." />
                                </div>
                                <div className="input-group mb-4">
                                    <label>WhatsApp Business Number</label>
                                    <input type="text" placeholder="94771234567" />
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    )
}
