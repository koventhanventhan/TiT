import React, { useState, useEffect } from 'react'
import {
    FiSettings,
    FiGlobe,
    FiPhone,
    FiShare2,
    FiSave,
    FiLayout
} from 'react-icons/fi'
import { getAdminSettings } from '../../services/dashboardService'
import './AdminSettings.css'

export default function AdminSettings() {
    const [settings, setSettings] = useState({})
    const [loading, setLoading] = useState(true)
    const [activeTab, setActiveTab] = useState('general')

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
