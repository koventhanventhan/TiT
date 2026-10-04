import React, { useState, useEffect } from 'react'
import { FiUpload, FiFolder, FiFile, FiVideo, FiLink } from 'react-icons/fi'
import { getTeacherMaterials } from '../../services/dashboardService'
import './TeacherSections.css'

export default function TeacherMaterials() {
    const [materials, setMaterials] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                const data = await getTeacherMaterials()
                setMaterials(data)
            } catch (e) {
                console.error(e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    const getIcon = (type) => {
        switch (type.toLowerCase()) {
            case 'video': return <FiVideo />
            case 'pdf': return <FiFile />
            case 'link': return <FiLink />
            default: return <FiFolder />
        }
    }

    return (
        <div className="teacher-section">
            <div className="section-top">
                <h2>Teaching Materials</h2>
                <button className="btn-primary"><FiUpload /> Upload Material</button>
            </div>

            {loading ? (
                <div className="loading-shimmer">Loading library...</div>
            ) : (
                <div className="materials-list">
                    <div className="material-categories">
                        <div className="category active">All Files</div>
                        <div className="category">Documents</div>
                        <div className="category">Videos</div>
                        <div className="category">Links</div>
                    </div>

                    <div className="materials-grid">
                        {materials.map(item => (
                            <div key={item.id} className="material-item-card">
                                <div className="item-icon-wrapper">{getIcon(item.type)}</div>
                                <div className="item-info">
                                    <h4>{item.title}</h4>
                                    <p>{item.subject} • {item.grade}</p>
                                </div>
                                <div className="item-actions">
                                    <button className="btn-action">Download</button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    )
}
