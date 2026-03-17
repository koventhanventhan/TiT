import React, { useState, useEffect } from 'react'
import { FiSettings, FiGlobe, FiPhone, FiShare2, FiSave, FiLayout, FiImage, FiMenu } from 'react-icons/fi'
import { getAdminSettings, updateAdminBranding, updateAdminSettings } from '../../services/dashboardService'
import { useSettings } from '../../context/SettingsContext'
import './AdminSettings.css'

export default function AdminSettings() {
    const { refreshSettings } = useSettings()
    const [settings, setSettings] = useState({})
    const [loading, setLoading] = useState(true)
    const [activeTab, setActiveTab] = useState('branding')
    const [logoFile, setLogoFile] = useState(null)
    const [logoPreview, setLogoPreview] = useState(null)
    const [removeLogo, setRemoveLogo] = useState(false)
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
        if (removeLogo) formData.append('remove_logo', '1')

        try {
            await updateAdminBranding(formData)
            alert('Branding updated successfully! Refresh to apply changes.')
            setRemoveLogo(false); // Reset removeLogo after successful save
            setLogoFile(null); // Clear file input
            setLogoPreview(null); // Clear preview
            refreshSettings();
            // If logo was removed, update settings state to reflect it
            if (removeLogo) {
                setSettings(prev => ({ ...prev, logo_url: null }));
            }
            // If a new logo was uploaded, the logo_url might be updated by the backend,
            // but for now, we just clear the preview if a new file was selected.
        } catch (error) {
            alert('Failed to update branding')
        }
    }

    const handleSettingsSave = async (updatedSettings) => {
        try {
            await updateAdminSettings(updatedSettings)
            setSettings({ ...settings, ...updatedSettings })
            refreshSettings()
            alert('Settings updated successfully!')
        } catch (error) {
            alert('Failed to update settings')
        }
    }

    const handleInputChange = (key, value) => {
        setSettings({ ...settings, [key]: value })
    }

    const handleLogoFileChange = (e) => {
        const file = e.target.files[0];
        setLogoFile(file);
        if (file) {
            setLogoPreview(URL.createObjectURL(file));
            setRemoveLogo(false); // If a new file is selected, we are not removing the logo
        } else {
            setLogoPreview(null);
        }
    };

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
                    <button className={activeTab === 'topbar' ? 'active' : ''} onClick={() => setActiveTab('topbar')}>
                        <FiLayout /> Topbar & Header
                    </button>
                    <button className={activeTab === 'general' ? 'active' : ''} onClick={() => setActiveTab('general')}>
                        <FiGlobe /> General Site
                    </button>
                    <button className={activeTab === 'content' ? 'active' : ''} onClick={() => setActiveTab('content')}>
                        <FiLayout /> Hero Section
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
                                        {(logoPreview || settings.logo_url) && !removeLogo ? (
                                            <div className="preview-container">
                                                <img src={logoPreview || settings.logo_url} alt="Logo Preview" />
                                                <button 
                                                    className="remove-logo-btn"
                                                    onClick={() => {
                                                        setRemoveLogo(true);
                                                        setLogoFile(null);
                                                        setLogoPreview(null);
                                                    }}
                                                    title="Remove Logo"
                                                >
                                                    &times;
                                                </button>
                                            </div>
                                        ) : (
                                            <div className="no-logo">No logo uploaded</div>
                                        )}
                                    </div>
                                        <input type="file" onChange={handleLogoFileChange} accept="image/*" />
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

                        {activeTab === 'topbar' && (
                            <div className="settings-group">
                                <h3>Topbar & Header Management</h3>
                                <p className="description">Control the visibility of topbar elements and customize navigation text.</p>
                                
                                <div className="settings-section mt-4">
                                    <h4>Visibility & Links</h4>
                                    <div className="toggle-grid">
                                        <div className="toggle-item">
                                            <label>Facebook</label>
                                            <div className="d-flex flex-column gap-2">
                                                <select 
                                                    value={settings.topbar_show_fb || 'yes'} 
                                                    onChange={(e) => handleInputChange('topbar_show_fb', e.target.value)}
                                                >
                                                    <option value="yes">On</option>
                                                    <option value="no">Off</option>
                                                </select>
                                                <input 
                                                    type="text" 
                                                    placeholder="Facebook Link"
                                                    value={settings.social_facebook || ''} 
                                                    onChange={(e) => handleInputChange('social_facebook', e.target.value)}
                                                />
                                            </div>
                                        </div>
                                        <div className="toggle-item">
                                            <label>Instagram</label>
                                            <div className="d-flex flex-column gap-2">
                                                <select 
                                                    value={settings.topbar_show_insta || 'yes'} 
                                                    onChange={(e) => handleInputChange('topbar_show_insta', e.target.value)}
                                                >
                                                    <option value="yes">On</option>
                                                    <option value="no">Off</option>
                                                </select>
                                                <input 
                                                    type="text" 
                                                    placeholder="Instagram Link"
                                                    value={settings.social_instagram || ''} 
                                                    onChange={(e) => handleInputChange('social_instagram', e.target.value)}
                                                />
                                            </div>
                                        </div>
                                        <div className="toggle-item">
                                            <label>YouTube</label>
                                            <div className="d-flex flex-column gap-2">
                                                <select 
                                                    value={settings.topbar_show_youtube || 'yes'} 
                                                    onChange={(e) => handleInputChange('topbar_show_youtube', e.target.value)}
                                                >
                                                    <option value="yes">On</option>
                                                    <option value="no">Off</option>
                                                </select>
                                                <input 
                                                    type="text" 
                                                    placeholder="YouTube Link"
                                                    value={settings.social_youtube || ''} 
                                                    onChange={(e) => handleInputChange('social_youtube', e.target.value)}
                                                />
                                            </div>
                                        </div>
                                        <div className="toggle-item">
                                            <label>Support Email</label>
                                            <div className="d-flex flex-column gap-2">
                                                <select 
                                                    value={settings.topbar_show_email || 'yes'} 
                                                    onChange={(e) => handleInputChange('topbar_show_email', e.target.value)}
                                                >
                                                    <option value="yes">On</option>
                                                    <option value="no">Off</option>
                                                </select>
                                                <input 
                                                    type="email" 
                                                    placeholder="Email Address"
                                                    value={settings.footer_email || ''} 
                                                    onChange={(e) => handleInputChange('footer_email', e.target.value)}
                                                />
                                            </div>
                                        </div>
                                        <div className="toggle-item">
                                            <label>Show Language</label>
                                            <select 
                                                value={settings.topbar_show_lang || 'yes'} 
                                                onChange={(e) => handleInputChange('topbar_show_lang', e.target.value)}
                                            >
                                                <option value="yes">On</option>
                                                <option value="no">Off</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div className="settings-section mt-6">
                                    <h4>Frontend Logo</h4>
                                    <div className="logo-upload-section">
                                        <div className="logo-preview-box">
                                            {(logoPreview || settings.logo_url) && !removeLogo ? (
                                                <div className="preview-container">
                                                    <img src={logoPreview || settings.logo_url} alt="Logo Preview" />
                                                    <button 
                                                        className="remove-logo-btn"
                                                        onClick={() => {
                                                            setRemoveLogo(true);
                                                            setLogoFile(null);
                                                            setLogoPreview(null);
                                                        }}
                                                        title="Remove Logo"
                                                    >
                                                        &times;
                                                    </button>
                                                </div>
                                            ) : (
                                                <div className="no-logo">No logo uploaded</div>
                                            )}
                                        </div>
                                        <input 
                                            type="file" 
                                            onChange={handleLogoFileChange} 
                                            accept="image/*" 
                                            className="mt-2"
                                        />
                                        <p className="description mt-2">Upload a logo to replace the default "TiT" text logo.</p>
                                    </div>
                                </div>

                                <div className="settings-section mt-6">
                                    <h4>Navigation Text</h4>
                                    <div className="input-grid">
                                        <div className="input-group">
                                            <label>Home</label>
                                            <input 
                                                type="text" 
                                                value={settings.nav_home || ''} 
                                                placeholder="Home"
                                                onChange={(e) => handleInputChange('nav_home', e.target.value)}
                                            />
                                        </div>
                                        <div className="input-group">
                                            <label>About</label>
                                            <input 
                                                type="text" 
                                                value={settings.nav_about || ''} 
                                                placeholder="About"
                                                onChange={(e) => handleInputChange('nav_about', e.target.value)}
                                            />
                                        </div>
                                        <div className="input-group">
                                            <label>Classes</label>
                                            <input 
                                                type="text" 
                                                value={settings.nav_classes || ''} 
                                                placeholder="Classes"
                                                onChange={(e) => handleInputChange('nav_classes', e.target.value)}
                                            />
                                        </div>
                                        <div className="input-group">
                                            <label>Learning Suite</label>
                                            <input 
                                                type="text" 
                                                value={settings.nav_learning_suite || ''} 
                                                placeholder="Learning Suite"
                                                onChange={(e) => handleInputChange('nav_learning_suite', e.target.value)}
                                            />
                                        </div>
                                        <div className="input-group">
                                            <label>Contact</label>
                                            <input 
                                                type="text" 
                                                value={settings.nav_contact || ''} 
                                                placeholder="Contact"
                                                onChange={(e) => handleInputChange('nav_contact', e.target.value)}
                                            />
                                        </div>
                                    </div>
                                </div>

                                <button className="save-btn mt-6" onClick={async () => {
                                    await handleSettingsSave({
                                        topbar_show_fb: settings.topbar_show_fb,
                                        topbar_show_insta: settings.topbar_show_insta,
                                        topbar_show_youtube: settings.topbar_show_youtube,
                                        topbar_show_email: settings.topbar_show_email,
                                        topbar_show_lang: settings.topbar_show_lang,
                                        remove_logo: removeLogo ? 1 : 0,
                                        social_facebook: settings.social_facebook,
                                        social_instagram: settings.social_instagram,
                                        social_youtube: settings.social_youtube,
                                        footer_email: settings.footer_email,
                                        nav_home: settings.nav_home,
                                        nav_about: settings.nav_about,
                                        nav_classes: settings.nav_classes,
                                        nav_learning_suite: settings.nav_learning_suite,
                                        nav_contact: settings.nav_contact
                                    });
                                    if (logoFile || removeLogo) {
                                        await handleBrandingSave();
                                    }
                                    setRemoveLogo(false);
                                }}>
                                    <FiSave /> Save Topbar & Logo Settings
                                </button>
                            </div>
                        )}

                        {activeTab === 'general' && (
                            <div className="settings-group">
                                <h3>Branding & Identity</h3>
                                <div className="input-row">
                                    <div className="input-group">
                                        <label>Platform Name</label>
                                        <input 
                                            type="text" 
                                            value={settings.site_name || ''} 
                                            onChange={(e) => handleInputChange('site_name', e.target.value)}
                                        />
                                    </div>
                                    <div className="input-group">
                                        <label>Tagline</label>
                                        <input 
                                            type="text" 
                                            value={settings.site_tagline || ''} 
                                            onChange={(e) => handleInputChange('site_tagline', e.target.value)}
                                        />
                                    </div>
                                </div>
                                <div className="input-group mt-4">
                                    <label>Site Meta Description</label>
                                    <textarea 
                                        rows="3" 
                                        value={settings.site_meta_desc || ''}
                                        onChange={(e) => handleInputChange('site_meta_desc', e.target.value)}
                                    ></textarea>
                                </div>
                                <button className="save-btn mt-6" onClick={() => handleSettingsSave({
                                    site_name: settings.site_name,
                                    site_tagline: settings.site_tagline,
                                    site_meta_desc: settings.site_meta_desc
                                })}>
                                    <FiSave /> Save General Settings
                                </button>
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
