import React, { useState, useEffect } from 'react'
import {
    FiPlus,
    FiFile,
    FiVideo,
    FiDownload,
    FiTrash2,
    FiSearch,
    FiGrid,
    FiBookOpen,
    FiExternalLink
} from 'react-icons/fi'
import { getAdminMaterials } from '../../services/dashboardService'
import { getAuthHeaders } from '../../services/apiClient'
import LearningSuiteUploadModal from '../../components/shared/LearningSuiteUploadModal'
import { deleteFileFromFirebase } from '../../services/firebaseStorageService'
import './AdminMaterials.css'
import { useToast } from '../../components/shared/ToastContext';


const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'

export default function AdminMaterials() {
  const toast = useToast();

    const [materials, setMaterials] = useState([])
    const [loading, setLoading] = useState(true)
    const [searchTerm, setSearchTerm] = useState('')
    const [selectedType, setSelectedType] = useState('all')
    const [isUploadModalOpen, setIsUploadModalOpen] = useState(false)

    const loadMaterials = async () => {
        setLoading(true)
        try {
            const data = await getAdminMaterials()
            setMaterials(data.data || data || [])
        } catch (error) {
            console.error('Error loading materials:', error)
        } finally {
            setLoading(false)
        }
    }

    useEffect(() => {
        loadMaterials()
    }, [])

    const handleDelete = async (item) => {
        if (!window.confirm(`Are you sure you want to delete "${item.title}"?`)) return

        try {
            // Delete from database
            const res = await fetch(`${API_BASE_URL}/admin/materials/${item.id}`, {
                method: 'DELETE',
                headers: getAuthHeaders(),
                credentials: 'include',
            })

            if (res.ok) {
                // Also clean up from Firebase Storage if it's a Firebase URL
                if (item.file_path && item.file_path.includes('firebasestorage.googleapis.com')) {
                    await deleteFileFromFirebase(item.file_path)
                }
                setMaterials((prev) => prev.filter((m) => m.id !== item.id))
            } else {
                toast.error('Failed to delete material.')
            }
        } catch (error) {
            console.error('Error deleting material:', error)
            toast.error('An error occurred while deleting.')
        }
    }

    const filteredMaterials = materials.filter((item) => {
        const matchesSearch = item.title?.toLowerCase().includes(searchTerm.toLowerCase()) ||
            item.subject?.toLowerCase().includes(searchTerm.toLowerCase()) ||
            item.grade?.toLowerCase().includes(searchTerm.toLowerCase())
        
        const matchesType = selectedType === 'all' || item.type === selectedType
        return matchesSearch && matchesType
    })

    const getFileUrl = (item) => {
        const target = item.url || item.file_path
        if (!target) return '#'
        if (target.startsWith('http')) return target
        const backendBase = API_BASE_URL.replace('/api', '')
        return `${backendBase}/${target.startsWith('/') ? target.slice(1) : target}`
    }

    return (
        <div className="admin-materials">
            <div className="page-header">
                <div className="header-info">
                    <h1>Global Materials Hub</h1>
                    <p>Upload and manage Study Notes, Past Papers, and Video Recordings using Firebase Storage.</p>
                </div>
                <button
                    className="add-btn"
                    onClick={() => setIsUploadModalOpen(true)}
                >
                    <FiPlus /> Upload to Firebase Storage
                </button>
            </div>

            {/* Filter & Search Bar */}
            <div className="materials-filter-bar" style={{
                display: 'flex',
                gap: '1rem',
                marginBottom: '1.5rem',
                flexWrap: 'wrap'
            }}>
                <div style={{
                    position: 'relative',
                    flex: 1,
                    minWidth: '250px'
                }}>
                    <FiSearch style={{
                        position: 'absolute',
                        left: '1rem',
                        top: '50%',
                        transform: 'translateY(-50%)',
                        color: '#94a3b8'
                    }} />
                    <input
                        type="text"
                        placeholder="Search notes, past papers, recordings..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        style={{
                            width: '100%',
                            padding: '0.65rem 1rem 0.65rem 2.5rem',
                            borderRadius: '0.6rem',
                            background: 'rgba(255, 255, 255, 0.05)',
                            border: '1px solid rgba(255, 255, 255, 0.12)',
                            color: '#f8fafc',
                            outline: 'none'
                        }}
                    />
                </div>

                <select
                    value={selectedType}
                    onChange={(e) => setSelectedType(e.target.value)}
                    style={{
                        padding: '0.65rem 1rem',
                        borderRadius: '0.6rem',
                        background: '#0f172a',
                        border: '1px solid rgba(255, 255, 255, 0.12)',
                        color: '#f8fafc',
                        outline: 'none'
                    }}
                >
                    <option value="all">All Material Types</option>
                    <option value="note">Study Notes (PDF)</option>
                    <option value="paper">Past Papers (PDF)</option>
                    <option value="recording">Class Recordings (Video)</option>
                </select>
            </div>

            {loading ? (
                <div className="loading-shimmer">Loading materials from database...</div>
            ) : filteredMaterials.length === 0 ? (
                <div style={{
                    textAlign: 'center',
                    padding: '3rem 1rem',
                    background: 'rgba(255, 255, 255, 0.02)',
                    borderRadius: '1rem',
                    border: '1px dashed rgba(255, 255, 255, 0.1)'
                }}>
                    <FiBookOpen style={{ fontSize: '2.5rem', color: '#ff8533', marginBottom: '0.75rem' }} />
                    <h3 style={{ color: '#f8fafc', marginBottom: '0.25rem' }}>No materials found</h3>
                    <p style={{ color: '#94a3b8', fontSize: '0.875rem', marginBottom: '1.25rem' }}>
                        Click below to upload your first note, past paper, or video to Firebase Storage.
                    </p>
                    <button
                        className="add-btn"
                        onClick={() => setIsUploadModalOpen(true)}
                    >
                        <FiPlus /> Upload New Resource
                    </button>
                </div>
            ) : (
                <div className="materials-grid">
                    {filteredMaterials.map((item) => (
                        <div key={item.id} className="material-card">
                            <div className="material-icon">
                                {item.type === 'recording' ? <FiVideo /> : <FiFile />}
                            </div>
                            <div className="material-content">
                                <div className="m-header">
                                    <span className="type-tag">{item.type ? item.type.replace('_', ' ') : 'PDF'}</span>
                                    <span className="grade-tag">{item.grade ? item.grade.replace('-', ' ').toUpperCase() : 'ALL'}</span>
                                </div>
                                <h3>{item.title}</h3>
                                {item.subject && <p style={{ fontSize: '0.8rem', color: '#94a3b8', margin: '0.2rem 0' }}>{item.subject}</p>}
                                <div className="m-footer">
                                    <span>{item.file_size || 'Cloud File'}</span>
                                    <div className="m-actions">
                                        <button
                                            className="del-btn"
                                            onClick={() => handleDelete(item)}
                                            title="Delete Material"
                                        >
                                            <FiTrash2 />
                                        </button>
                                        <a
                                            className="dl-btn"
                                            href={getFileUrl(item)}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            title="Open / Download"
                                            style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center' }}
                                        >
                                            <FiExternalLink />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Firebase Upload Modal */}
            <LearningSuiteUploadModal
                isOpen={isUploadModalOpen}
                onClose={() => setIsUploadModalOpen(false)}
                onSuccess={loadMaterials}
            />
        </div>
    )
}
