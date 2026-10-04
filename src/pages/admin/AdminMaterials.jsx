import React, { useState, useEffect } from 'react'
import {
    FiPlus,
    FiFile,
    FiVideo,
    FiDownload,
    FiTrash2,
    FiSearch,
    FiGrid
} from 'react-icons/fi'
import { getAdminMaterials } from '../../services/dashboardService'
import './AdminMaterials.css'

export default function AdminMaterials() {
    const [materials, setMaterials] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function loadMaterials() {
            try {
                const data = await getAdminMaterials()
                setMaterials(data.data || [])
            } catch (error) {
                console.error('Error loading materials:', error)
            } finally {
                setLoading(false)
            }
        }
        loadMaterials()
    }, [])

    if (loading) return <div className="loading-shimmer">Scanning material database...</div>

    return (
        <div className="admin-materials">
            <div className="page-header">
                <div className="header-info">
                    <h1>Global Materials Hub</h1>
                    <p>Upload and manage study guides, past papers, and video recordings.</p>
                </div>
                <button className="add-btn"><FiPlus /> Upload New Resource</button>
            </div>

            <div className="materials-grid">
                {materials.map((item) => (
                    <div key={item.id} className="material-card">
                        <div className="material-icon">
                            {item.type === 'recording' ? <FiVideo /> : <FiFile />}
                        </div>
                        <div className="material-content">
                            <div className="m-header">
                                <span className="type-tag">{item.type.replace('_', ' ')}</span>
                                <span className="grade-tag">{item.grade}</span>
                            </div>
                            <h3>{item.title}</h3>
                            <div className="m-footer">
                                <span>{item.file_size || 'N/A'}</span>
                                <div className="m-actions">
                                    <button className="del-btn"><FiTrash2 /></button>
                                    <button className="dl-btn"><FiDownload /></button>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    )
}
