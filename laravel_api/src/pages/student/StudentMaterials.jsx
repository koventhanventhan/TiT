import React, { useState, useEffect } from 'react'
import {
    FiFolder, FiFileText, FiVideo, FiLink, FiDownload,
    FiSearch, FiExternalLink, FiRefreshCw, FiAlertCircle
} from 'react-icons/fi'
import { getStudentMaterials } from '../../services/dashboardService'
import { useToast } from '../../components/shared/ToastContext'

export default function StudentMaterials() {
    const toast = useToast()

    const [materials, setMaterials] = useState([])
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState(null)
    const [activeTab, setActiveTab] = useState('all')
    const [searchQuery, setSearchQuery] = useState('')

    const load = async () => {
        setLoading(true)
        setError(null)
        try {
            const data = await getStudentMaterials()
            setMaterials(Array.isArray(data) ? data : data?.data || [])
        } catch (e) {
            console.error('Failed to load materials:', e)
            setError(e.message || 'Failed to load study materials')
        } finally {
            setLoading(false)
        }
    }

    useEffect(() => {
        load()
    }, [])

    const getIcon = (type) => {
        const t = (type || '').toLowerCase()
        switch (t) {
            case 'video':
            case 'recording':
                return { icon: <FiVideo />, color: '#ec4899', bg: '#fdf2f8' }
            case 'pdf':
                return { icon: <FiFileText />, color: '#ef4444', bg: '#fef2f2' }
            case 'document':
                return { icon: <FiFileText />, color: '#3b82f6', bg: '#eff6ff' }
            case 'link':
                return { icon: <FiLink />, color: '#10b981', bg: '#ecfdf5' }
            default:
                return { icon: <FiFolder />, color: '#6366f1', bg: '#eef2ff' }
        }
    }

    const handleDownload = (item) => {
        const itemType = (item.type || '').toLowerCase()
        if (['link', 'recording'].includes(itemType) && item.url) {
            window.open(item.url, '_blank', 'noopener')
        } else if (item.file_path) {
            if (item.file_path.startsWith('http://') || item.file_path.startsWith('https://')) {
                window.open(item.file_path, '_blank', 'noopener')
            } else {
                const baseUrl = import.meta.env.VITE_API_URL?.replace('/api', '') || ''
                window.open(`${baseUrl}/api/materials/download?path=${encodeURIComponent(item.file_path)}`, '_blank')
            }
        } else {
            toast.info('File not available')
        }
    }

    const tabs = [
        { id: 'all', label: 'All Files' },
        { id: 'pdf', label: 'PDFs' },
        { id: 'video', label: 'Videos' },
        { id: 'recording', label: 'Recordings' },
        { id: 'document', label: 'Documents' }
    ]

    const q = searchQuery.trim().toLowerCase()
    const filteredMaterials = (materials || []).filter(m => {
        const itemType = (m.type || '').toLowerCase()
        const matchesTab = activeTab === 'all' || itemType === activeTab
        if (!matchesTab) return false
        if (!q) return true

        const title = (m.title || '').toLowerCase()
        const subject = (m.subject || m.group || '').toLowerCase()
        const grade = (m.grade || '').toLowerCase()
        return title.includes(q) || subject.includes(q) || grade.includes(q)
    })

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24, '@media (minWidth: 640px)': { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' } }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: '#1e293b', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Study Materials</h1>
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>Access your class notes, videos, and resources</p>
                </div>
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
                                border: activeTab === tab.id ? '1px solid #4f0bd9' : '1px solid transparent',
                                background: activeTab === tab.id ? '#f3e8ff' : 'transparent',
                                color: activeTab === tab.id ? '#4f0bd9' : '#64748b',
                                transition: 'all 0.2s', whiteSpace: 'nowrap'
                            }}
                            onMouseEnter={e => { if (activeTab !== tab.id) e.currentTarget.style.background = '#f8fafc' }}
                            onMouseLeave={e => { if (activeTab !== tab.id) e.currentTarget.style.background = 'transparent' }}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>

                    <div style={{ position: 'relative' }}>
                        <FiSearch style={{ position: 'absolute', left: 12, top: 10, color: '#94a3b8' }} />
                        <input
                            type="text"
                            placeholder="Search materials..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            style={{
                                width: 220, padding: '8px 12px 8px 36px', borderRadius: 8,
                                border: '1px solid #e2e8f0', background: '#f8fafc', fontSize: 13,
                                outline: 'none', transition: 'border-color 0.2s'
                            }}
                            onFocus={e => e.target.style.borderColor = '#4f0bd9'}
                            onBlur={e => e.target.style.borderColor = '#e2e8f0'}
                        />
                    </div>
                </div>
            </div>

            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#4f0bd9', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : error ? (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid #fee2e2', padding: '40px 20px', textAlign: 'center' }}>
                    <FiAlertCircle size={36} style={{ color: '#ef4444', marginBottom: 12 }} />
                    <h3 style={{ margin: '0 0 8px', fontSize: 18, fontWeight: 700, color: '#991b1b' }}>Failed to load materials</h3>
                    <p style={{ margin: '0 0 20px', color: '#64748b', fontSize: 14 }}>{error}</p>
                    <button
                        onClick={load}
                        style={{
                            padding: '10px 20px', borderRadius: 10, background: '#4f0bd9',
                            color: '#fff', border: 'none', fontWeight: 600, cursor: 'pointer',
                            display: 'inline-flex', alignItems: 'center', gap: 8
                        }}
                    >
                        <FiRefreshCw /> Retry
                    </button>
                </div>
            ) : filteredMaterials.length === 0 ? (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px dashed #cbd5e1', padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center' }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: '#f1f5f9', color: '#94a3b8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}><FiFolder /></div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#334155' }}>No materials found</h3>
                    <p style={{ margin: 0, color: '#64748b', fontSize: 14 }}>
                        {searchQuery ? 'No materials match your search criteria.' : 'Try selecting a different category or check back later.'}
                    </p>
                </div>
            ) : (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(260px, 100%), 1fr))', gap: 20 }}>
                    {filteredMaterials.map(item => {
                        const style = getIcon(item.type)
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
                                    <div style={{ fontSize: 11, fontWeight: 700, color: '#64748b', background: '#f1f5f9', padding: '4px 8px', borderRadius: 6, textTransform: 'uppercase' }}>
                                        {item.type || 'File'}
                                    </div>
                                </div>
                                <h3 style={{ margin: '0 0 6px 0', fontSize: 16, fontWeight: 700, color: '#1e293b', lineHeight: 1.3, display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                                    {item.title}
                                </h3>
                                <p style={{ margin: '0 0 4px 0', color: '#64748b', fontSize: 13, fontWeight: 500 }}>
                                    {item.group || item.subject || 'General Material'}
                                </p>
                                <p style={{ margin: '0 0 16px 0', color: '#94a3b8', fontSize: 12 }}>
                                    Grade: {item.grade || 'All'}
                                </p>
                                <div style={{ marginTop: 'auto', paddingTop: 16, borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                    <div style={{ fontSize: 12, color: '#94a3b8', fontWeight: 500 }}>
                                        {new Date(item.created_at || item.date || Date.now()).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}
                                        {item.file_size && item.file_size !== '--' && <><span style={{ margin: '0 6px' }}>•</span>{item.file_size}</>}
                                    </div>
                                    <button onClick={(e) => { e.stopPropagation(); handleDownload(item); }}
                                        title={item.type?.toLowerCase() === 'recording' ? 'Watch recording' : undefined}
                                        style={{
                                        width: 32, height: 32, borderRadius: 8, background: '#f8fafc', border: '1px solid #e2e8f0',
                                        display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#64748b', cursor: 'pointer', transition: 'all 0.2s'
                                    }} onMouseEnter={e => { e.currentTarget.style.background = '#4f0bd9'; e.currentTarget.style.color = '#fff'; e.currentTarget.style.borderColor = '#4f0bd9' }} onMouseLeave={e => { e.currentTarget.style.background = '#f8fafc'; e.currentTarget.style.color = '#64748b'; e.currentTarget.style.borderColor = '#e2e8f0' }}>
                                        {['link', 'recording'].includes(item.type?.toLowerCase()) ? <FiExternalLink /> : <FiDownload />}
                                    </button>
                                </div>
                            </div>
                        )
                    })}
                </div>
            )}
        </div>
    )
}
