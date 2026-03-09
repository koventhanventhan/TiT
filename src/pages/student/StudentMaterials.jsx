import React, { useState, useEffect } from 'react'
import { FiFolder, FiFile, FiVideo, FiLink, FiDownload } from 'react-icons/fi'
import { getStudentMaterials } from '../../services/dashboardService'
import './StudentSections.css'

export default function StudentMaterials() {
    const [materials, setMaterials] = useState([])
    const [loading, setLoading] = useState(true)
    const [activeCategory, setActiveCategory] = useState('all')

    useEffect(() => {
        async function load() {
            try {
                const data = await getStudentMaterials()
                setMaterials(Array.isArray(data) ? data : data.data || [])
            } catch (e) {
                console.error('Error loading materials:', e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    const getIcon = (type) => {
        switch ((type || '').toLowerCase()) {
            case 'video': return <FiVideo />
            case 'pdf': case 'document': return <FiFile />
            case 'link': return <FiLink />
            default: return <FiFolder />
        }
    }

    const categories = ['all', ...new Set(materials.map(m => (m.type || 'other').toLowerCase()))]
    const filtered = activeCategory === 'all' ? materials : materials.filter(m => (m.type || '').toLowerCase() === activeCategory)

    return (
        <div className="student-section">
            <div className="section-top">
                <h2>Learning Materials</h2>
            </div>

            {loading ? (
                <div className="loading-shimmer">Loading materials...</div>
            ) : (
                <div>
                    <div className="material-categories">
                        {categories.map(cat => (
                            <button
                                key={cat}
                                className={`category ${activeCategory === cat ? 'active' : ''}`}
                                onClick={() => setActiveCategory(cat)}
                            >
                                {cat.charAt(0).toUpperCase() + cat.slice(1)}
                            </button>
                        ))}
                    </div>

                    {filtered.length === 0 ? (
                        <div className="empty-state">
                            <FiFolder />
                            <p>No materials found in this category.</p>
                        </div>
                    ) : (
                        <div className="materials-grid">
                            {filtered.map(item => (
                                <div key={item.id} className="material-item-card">
                                    <div className="item-icon-wrapper">{getIcon(item.type)}</div>
                                    <div className="item-info">
                                        <h4>{item.title}</h4>
                                        <p>{item.subject || 'General'} {item.grade ? `• ${item.grade}` : ''}</p>
                                    </div>
                                    <button className="btn-view" title="Download">
                                        <FiDownload />
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    )
}
