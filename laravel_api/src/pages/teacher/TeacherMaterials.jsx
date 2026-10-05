import React, { useState, useEffect } from 'react'
import { FiUpload, FiFolder, FiFileText, FiVideo, FiLink, FiDownload, FiSearch, FiGrid, FiList, FiX, FiExternalLink, FiTrash2 } from 'react-icons/fi'
import { getTeacherMaterials, uploadTeacherMaterial, deleteTeacherMaterial } from '../../services/dashboardService'
import { useToast } from '../../components/shared/ToastContext';


export default function TeacherMaterials() {
  const toast = useToast();

    const [materials, setMaterials] = useState([])
    const [loading, setLoading] = useState(true)
    const [viewMode, setViewMode] = useState('grid')
    const [activeTab, setActiveTab] = useState('all')
    const [searchQuery, setSearchQuery] = useState('')
    
    // Upload Modal State
    const [isUploadModalOpen, setIsUploadModalOpen] = useState(false)
    const [uploadFormData, setUploadFormData] = useState({ title: '', type: 'pdf', subject: '', grade: '', url: '' })
    const [uploadFile, setUploadFile] = useState(null)
    const [uploading, setUploading] = useState(false)

    const load = async () => {
        setLoading(true)
        try {
            const data = await getTeacherMaterials()
            setMaterials(Array.isArray(data) ? data : [])
        } catch (e) {
            console.error(e)
            setMaterials([])
        } finally {
            setLoading(false)
        }
    }

    useEffect(() => {
        load()
    }, [])

    const getIcon = (type) => {
        switch ((type || '').toLowerCase()) {
            case 'video': return { icon: <FiVideo />, color: '#ec4899', bg: '#fdf2f8' }
            case 'pdf': return { icon: <FiFileText />, color: '#ef4444', bg: '#fef2f2' }
            case 'document': return { icon: <FiFileText />, color: '#3b82f6', bg: '#eff6ff' }
            case 'link': return { icon: <FiLink />, color: '#10b981', bg: '#ecfdf5' }
            default: return { icon: <FiFolder />, color: '#6366f1', bg: '#eef2ff' }
        }
    }

    const tabs = [
        { id: 'all', label: 'All Files' },
        { id: 'pdf', label: 'PDFs' },
        { id: 'video', label: 'Videos' },
        { id: 'link', label: 'Links' },
        { id: 'document', label: 'Documents' }
    ]

    const filteredMaterials = (materials || []).filter(m => {
        const matchesTab = activeTab === 'all' || (m.type && m.type.toLowerCase() === activeTab);
        const search = searchQuery.toLowerCase();
        const titleMatch = m.title ? m.title.toLowerCase().includes(search) : false;
        const subjMatch = m.subject ? m.subject.toLowerCase().includes(search) : false;
        const gradeMatch = m.grade ? m.grade.toLowerCase().includes(search) : false;
        const matchesSearch = !search || titleMatch || subjMatch || gradeMatch;
        return matchesTab && matchesSearch;
    });

    const handleUpload = async (e) => {
        e.preventDefault()
        if (!uploadFormData.title) return toast.info('Title is required')
        
        setUploading(true)
        try {
            const formData = new FormData()
            formData.append('title', uploadFormData.title)
            formData.append('type', uploadFormData.type)
            if (uploadFormData.subject) formData.append('subject', uploadFormData.subject)
            if (uploadFormData.grade) formData.append('grade', uploadFormData.grade)
            
            if (uploadFormData.type.toLowerCase() === 'link') {
                if (!uploadFormData.url) {
                    setUploading(false)
                    return toast.info('URL is required for link type')
                }
                formData.append('url', uploadFormData.url)
            } else {
                if (!uploadFile) {
                    setUploading(false)
                    return toast.info('File is required')
                }
                formData.append('file', uploadFile)
            }

            await uploadTeacherMaterial(formData)
            setIsUploadModalOpen(false)
            setUploadFormData({ title: '', type: 'pdf', subject: '', grade: '', url: '' })
            setUploadFile(null)
            load()
        } catch (err) {
            console.error(err)
            toast.error(err.message || 'Error uploading material')
        } finally {
            setUploading(false)
        }
    }

    const handleDownload = (item) => {
        if (item.type.toLowerCase() === 'link' && item.url) {
            window.open(item.url, '_blank')
        } else if (item.file_path) {
            const baseUrl = import.meta.env.VITE_API_URL?.replace('/api', '') || ''
            window.open(`${baseUrl}/api/materials/download?path=${encodeURIComponent(item.file_path)}`, '_blank')
        } else {
            toast.info('File not available')
        }
    }

    const handleDelete = async (item) => {
        if (!window.confirm(`Are you sure you want to delete "${item.title}"?`)) return;
        
        try {
            await deleteTeacherMaterial(item.id);
            toast.success('Deleted successfully');
            load();
        } catch (err) {
            console.error(err);
            toast.error(err.message || 'Failed to delete material');
        }
    }

    // Modal Helper
    const inputStyle = {
        width: '100%', padding: '10px 14px', borderRadius: 8,
        border: '1px solid #e2e8f0', outline: 'none', fontSize: 14,
        background: '#f8fafc', boxSizing: 'border-box'
    }

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24,
                '@media (minWidth: 640px)': { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }
            }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: '#1e293b', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Teaching Materials</h1>
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>Manage and share resources with your students</p>
                </div>
                <button style={{
                    display: 'flex', alignItems: 'center', gap: 8, padding: '10px 20px',
                    background: 'linear-gradient(135deg, #7c3aed, #a855f7)', color: '#fff',
                    border: 'none', borderRadius: 12, fontWeight: 600, fontSize: 14,
                    cursor: 'pointer', boxShadow: '0 4px 12px rgba(124,58,237,0.3)',
                    transition: 'all 0.2s', width: 'fit-content'
                }}
                onClick={() => setIsUploadModalOpen(true)}
                onMouseEnter={e => e.currentTarget.style.transform = 'translateY(-2px)'}
                onMouseLeave={e => e.currentTarget.style.transform = 'translateY(0)'}
                >
                    <FiUpload style={{ fontSize: 18 }} /> Upload Material
                </button>
            </div>

            {/* Toolbar */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24,
                background: '#fff', padding: 16, borderRadius: 16,
                border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.02)'
            }}>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 16, alignItems: 'center', justifyContent: 'space-between' }}>
                    <div style={{ display: 'flex', gap: 8, overflowX: 'auto', paddingBottom: 4 }}>
                        {tabs.map(tab => (
                            <button key={tab.id} onClick={() => setActiveTab(tab.id)} style={{
                                padding: '8px 16px', borderRadius: 20, fontSize: 13, fontWeight: 600, cursor: 'pointer',
                                border: activeTab === tab.id ? '1px solid #7c3aed' : '1px solid transparent',
                                background: activeTab === tab.id ? '#f5f3ff' : 'transparent',
                                color: activeTab === tab.id ? '#7c3aed' : '#64748b',
                                transition: 'all 0.2s', whiteSpace: 'nowrap'
                            }}
                            onMouseEnter={e => { if (activeTab !== tab.id) e.currentTarget.style.background = '#f8fafc' }}
                            onMouseLeave={e => { if (activeTab !== tab.id) e.currentTarget.style.background = 'transparent' }}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>

                    <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
                        <div style={{ position: 'relative' }}>
                            <FiSearch style={{ position: 'absolute', left: 12, top: 10, color: '#94a3b8' }} />
                            <input type="text" placeholder="Search files..." value={searchQuery} onChange={e => setSearchQuery(e.target.value)} style={{
                                width: 200, padding: '8px 12px 8px 36px', borderRadius: 8,
                                border: '1px solid #e2e8f0', background: '#f8fafc', fontSize: 13,
                                outline: 'none', transition: 'border-color 0.2s', boxSizing: 'border-box'
                            }} onFocus={e => e.target.style.borderColor = '#7c3aed'} onBlur={e => e.target.style.borderColor = '#e2e8f0'} />
                        </div>
                        <div style={{ display: 'flex', background: '#f1f5f9', borderRadius: 8, padding: 4 }}>
                            <button onClick={() => setViewMode('grid')} style={{
                                padding: 6, borderRadius: 6, border: 'none', cursor: 'pointer',
                                background: viewMode === 'grid' ? '#fff' : 'transparent',
                                color: viewMode === 'grid' ? '#334155' : '#94a3b8',
                                boxShadow: viewMode === 'grid' ? '0 1px 2px rgba(0,0,0,0.1)' : 'none'
                            }}><FiGrid /></button>
                            <button onClick={() => setViewMode('list')} style={{
                                padding: 6, borderRadius: 6, border: 'none', cursor: 'pointer',
                                background: viewMode === 'list' ? '#fff' : 'transparent',
                                color: viewMode === 'list' ? '#334155' : '#94a3b8',
                                boxShadow: viewMode === 'list' ? '0 1px 2px rgba(0,0,0,0.1)' : 'none'
                            }}><FiList /></button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Content Area */}
            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#7c3aed', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : filteredMaterials.length === 0 ? (
                <div style={{
                    background: '#fff', borderRadius: 16, border: '1px dashed #cbd5e1',
                    padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center'
                }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: '#f1f5f9', color: '#94a3b8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>
                        <FiFolder />
                    </div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#334155' }}>No materials found</h3>
                    <p style={{ margin: '0 0 20px 0', color: '#64748b', fontSize: 14 }}>You haven't uploaded any {activeTab !== 'all' ? activeTab : ''} materials yet.</p>
                    <button style={{
                        padding: '10px 20px', background: '#7c3aed', color: '#fff', border: 'none',
                        borderRadius: 10, fontWeight: 600, fontSize: 14, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8
                    }} onClick={() => setIsUploadModalOpen(true)}>
                        <FiUpload /> Upload Material
                    </button>
                </div>
            ) : viewMode === 'grid' ? (
                /* Grid View */
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(260px, 1fr))', gap: 20 }}>
                    {filteredMaterials.map(item => {
                        const style = getIcon(item.type)
                        const d = item.created_at || item.date || new Date();
                        return (
                            <div key={item.id} style={{
                                background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0',
                                padding: 20, display: 'flex', flexDirection: 'column',
                                boxShadow: '0 2px 8px rgba(0,0,0,0.02)', transition: 'all 0.2s', cursor: 'pointer'
                            }}
                            onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-4px)'; e.currentTarget.style.boxShadow = '0 12px 24px rgba(0,0,0,0.06)'; e.currentTarget.style.borderColor = '#cbd5e1' }}
                            onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,0.02)'; e.currentTarget.style.borderColor = '#e2e8f0' }}
                            >
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 16 }}>
                                    <div style={{ width: 48, height: 48, borderRadius: 12, background: style.bg, color: style.color, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 24 }}>
                                        {style.icon}
                                    </div>
                                </div>
                                <h3 style={{ margin: '0 0 6px 0', fontSize: 16, fontWeight: 700, color: '#1e293b', lineHeight: 1.3, display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                                    {item.title}
                                </h3>
                                <p style={{ margin: '0 0 16px 0', color: '#64748b', fontSize: 13, fontWeight: 500 }}>
                                    {item.subject || 'All Subjects'} • {item.grade || 'All Grades'}
                                </p>
                                <div style={{ marginTop: 'auto', paddingTop: 16, borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                    <div style={{ fontSize: 12, color: '#94a3b8', fontWeight: 500 }}>
                                        {new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}
                                        {item.size ? <span style={{ margin: '0 6px' }}>• {item.size}</span> : null}
                                    </div>
                                    <div style={{ display: 'flex', gap: 8 }}>
                                        <button onClick={(e) => { e.stopPropagation(); handleDownload(item); }} style={{
                                            width: 32, height: 32, borderRadius: 8, background: '#f8fafc', border: '1px solid #e2e8f0',
                                            display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#64748b', cursor: 'pointer', transition: 'all 0.2s'
                                        }} onMouseEnter={e => { e.currentTarget.style.background = '#7c3aed'; e.currentTarget.style.color = '#fff'; e.currentTarget.style.borderColor = '#7c3aed' }} onMouseLeave={e => { e.currentTarget.style.background = '#f8fafc'; e.currentTarget.style.color = '#64748b'; e.currentTarget.style.borderColor = '#e2e8f0' }}>
                                            {item.type?.toLowerCase() === 'link' ? <FiExternalLink /> : <FiDownload />}
                                        </button>
                                        <button onClick={(e) => { e.stopPropagation(); handleDelete(item); }} style={{
                                            width: 32, height: 32, borderRadius: 8, background: '#f8fafc', border: '1px solid #e2e8f0',
                                            display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#ef4444', cursor: 'pointer', transition: 'all 0.2s'
                                        }} onMouseEnter={e => { e.currentTarget.style.background = '#ef4444'; e.currentTarget.style.color = '#fff'; e.currentTarget.style.borderColor = '#ef4444' }} onMouseLeave={e => { e.currentTarget.style.background = '#f8fafc'; e.currentTarget.style.color = '#ef4444'; e.currentTarget.style.borderColor = '#e2e8f0' }}>
                                            <FiTrash2 />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        )
                    })}
                </div>
            ) : (
                /* List View */
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0', overflow: 'hidden', boxShadow: '0 1px 3px rgba(0,0,0,0.02)' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                        <thead>
                            <tr style={{ background: '#f8fafc', borderBottom: '1px solid #e2e8f0' }}>
                                <th style={{ padding: '14px 20px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Name</th>
                                <th style={{ padding: '14px 20px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Subject</th>
                                <th style={{ padding: '14px 20px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Date Modified</th>
                                <th style={{ padding: '14px 20px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px', textAlign: 'right' }}>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredMaterials.map(item => {
                                const style = getIcon(item.type)
                                const d = item.created_at || item.date || new Date();
                                return (
                                    <tr key={item.id} style={{ borderBottom: '1px solid #f1f5f9', transition: 'background 0.2s', cursor: 'pointer' }}
                                    onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'} onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                                    >
                                        <td style={{ padding: '16px 20px' }}>
                                            <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                                <div style={{ width: 40, height: 40, borderRadius: 10, background: style.bg, color: style.color, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20 }}>
                                                    {style.icon}
                                                </div>
                                                <div>
                                                    <div style={{ fontSize: 15, fontWeight: 600, color: '#1e293b', marginBottom: 2 }}>{item.title}</div>
                                                    <div style={{ fontSize: 12, color: '#94a3b8', fontWeight: 500, textTransform: 'uppercase' }}>{item.type}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style={{ padding: '16px 20px' }}>
                                            <div style={{ fontSize: 14, fontWeight: 500, color: '#334155', marginBottom: 2 }}>{item.subject || '-'}</div>
                                            <div style={{ fontSize: 12, color: '#94a3b8' }}>{item.grade || '-'}</div>
                                        </td>
                                        <td style={{ padding: '16px 20px', fontSize: 14, color: '#475569', fontWeight: 500 }}>
                                            {new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}
                                        </td>
                                        <td style={{ padding: '16px 20px', textAlign: 'right' }}>
                                            <button onClick={(e) => { e.stopPropagation(); handleDownload(item); }} style={{
                                                width: 32, height: 32, borderRadius: 8, background: '#fff', border: '1px solid #e2e8f0', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', color: '#64748b', cursor: 'pointer', transition: 'all 0.2s', marginRight: 8
                                            }} onMouseEnter={e => { e.currentTarget.style.background = '#7c3aed'; e.currentTarget.style.color = '#fff'; e.currentTarget.style.borderColor = '#7c3aed' }} onMouseLeave={e => { e.currentTarget.style.background = '#fff'; e.currentTarget.style.color = '#64748b'; e.currentTarget.style.borderColor = '#e2e8f0' }}>
                                                {item.type?.toLowerCase() === 'link' ? <FiExternalLink /> : <FiDownload />}
                                            </button>
                                            <button onClick={(e) => { e.stopPropagation(); handleDelete(item); }} style={{
                                                width: 32, height: 32, borderRadius: 8, background: '#fff', border: '1px solid #e2e8f0', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', color: '#ef4444', cursor: 'pointer', transition: 'all 0.2s'
                                            }} onMouseEnter={e => { e.currentTarget.style.background = '#ef4444'; e.currentTarget.style.color = '#fff'; e.currentTarget.style.borderColor = '#ef4444' }} onMouseLeave={e => { e.currentTarget.style.background = '#fff'; e.currentTarget.style.color = '#ef4444'; e.currentTarget.style.borderColor = '#e2e8f0' }}>
                                                <FiTrash2 />
                                            </button>
                                        </td>
                                    </tr>
                                )
                            })}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Upload Modal */}
            {isUploadModalOpen && (
                <div style={{
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(15,23,42,0.6)',
                    backdropFilter: 'blur(4px)', zIndex: 100, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20
                }}>
                    <div style={{
                        background: '#fff', borderRadius: 20, width: '100%', maxWidth: 500,
                        boxShadow: '0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04)',
                        overflow: 'hidden' // for rounded corners with header
                    }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '20px 24px', borderBottom: '1px solid #e2e8f0', background: '#f8fafc' }}>
                            <h2 style={{ margin: 0, fontSize: 18, fontWeight: 700, color: '#1e293b' }}>Upload Material</h2>
                            <button onClick={() => setIsUploadModalOpen(false)} style={{ background: 'none', border: 'none', color: '#64748b', cursor: 'pointer', padding: 4 }}><FiX size={20} /></button>
                        </div>
                        
                        <form onSubmit={handleUpload} style={{ padding: 24 }}>
                            <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 6 }}>Title*</label>
                                    <input type="text" required style={inputStyle} value={uploadFormData.title} onChange={e => setUploadFormData({...uploadFormData, title: e.target.value})} placeholder="e.g., Chapter 1 Physics Notes" />
                                </div>
                                
                                <div style={{ display: 'flex', gap: 16 }}>
                                    <div style={{ flex: 1 }}>
                                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 6 }}>Type</label>
                                        <select style={inputStyle} value={uploadFormData.type} onChange={e => setUploadFormData({...uploadFormData, type: e.target.value})}>
                                            <option value="pdf">PDF Document</option>
                                            <option value="document">Other Document (Word/PPT)</option>
                                            <option value="video">Video</option>
                                            <option value="link">Web Link</option>
                                        </select>
                                    </div>
                                    <div style={{ flex: 1 }}>
                                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 6 }}>Subject (Optional)</label>
                                        <input type="text" style={inputStyle} value={uploadFormData.subject} onChange={e => setUploadFormData({...uploadFormData, subject: e.target.value})} placeholder="e.g., Mathematics" />
                                    </div>
                                </div>

                                <div>
                                    <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 6 }}>Grade/Class (Optional)</label>
                                    <input type="text" style={inputStyle} value={uploadFormData.grade} onChange={e => setUploadFormData({...uploadFormData, grade: e.target.value})} placeholder="e.g., A/L 2026 or Group A" />
                                </div>

                                {uploadFormData.type === 'link' ? (
                                    <div>
                                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 6 }}>URL Link*</label>
                                        <input type="url" required style={inputStyle} value={uploadFormData.url} onChange={e => setUploadFormData({...uploadFormData, url: e.target.value})} placeholder="https://..." />
                                    </div>
                                ) : (
                                    <div>
                                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: '#475569', marginBottom: 6 }}>File*</label>
                                        <input type="file" required style={inputStyle} onChange={e => setUploadFile(e.target.files[0])} />
                                    </div>
                                )}
                            </div>

                            <div style={{ marginTop: 32, display: 'flex', justifyContent: 'flex-end', gap: 12 }}>
                                <button type="button" onClick={() => setIsUploadModalOpen(false)} style={{
                                    padding: '10px 16px', borderRadius: 10, background: '#fff', border: '1px solid #e2e8f0', color: '#64748b', fontWeight: 600, cursor: 'pointer', fontSize: 14
                                }}>Cancel</button>
                                <button type="submit" disabled={uploading} style={{
                                    padding: '10px 20px', borderRadius: 10, background: '#7c3aed', border: 'none', color: '#fff', fontWeight: 600, cursor: uploading ? 'not-allowed' : 'pointer', fontSize: 14, display: 'flex', alignItems: 'center', gap: 8, opacity: uploading ? 0.7 : 1
                                }}>
                                    {uploading ? 'Uploading...' : 'Upload Material'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    )
}
