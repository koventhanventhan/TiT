import React, { useState, useEffect, useRef } from 'react'
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
    const [isOverflowing, setIsOverflowing] = useState(false)

    const tabStripRef = useRef(null)
    const tabRefs = useRef({})

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
        const chipStyle = { color: 'var(--t-primary)', bg: 'var(--t-primary-soft)' }
        switch (t) {
            case 'video':
            case 'recording':
                return { icon: <FiVideo />, ...chipStyle }
            case 'pdf':
                return { icon: <FiFileText />, ...chipStyle }
            case 'document':
                return { icon: <FiFileText />, ...chipStyle }
            case 'link':
                return { icon: <FiLink />, ...chipStyle }
            default:
                return { icon: <FiFolder />, ...chipStyle }
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

    useEffect(() => {
        const el = tabStripRef.current
        if (!el) return
        const check = () => {
            setIsOverflowing(el.scrollWidth > el.clientWidth + 2)
        }
        check()
        window.addEventListener('resize', check)
        return () => window.removeEventListener('resize', check)
    }, [tabs.length])

    useEffect(() => {
        const activeBtn = tabRefs.current[activeTab]
        if (activeBtn && typeof activeBtn.scrollIntoView === 'function') {
            activeBtn.scrollIntoView({ inline: 'center', behavior: 'smooth', block: 'nearest' })
        }
    }, [activeTab])

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
        <div style={{ paddingBottom: 80 }}>
            {/* Header */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24 }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: 'var(--t-text)', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Study Materials</h1>
                    <p style={{ color: 'var(--t-text-muted)', margin: 0, fontSize: 14 }}>Access your class notes, videos, and resources</p>
                </div>
            </div>

            {/* Toolbar */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24,
                background: '#fff', padding: 16, borderRadius: 16,
                border: '1px solid var(--t-border)', boxShadow: 'var(--t-shadow-sm)'
            }}>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 16, alignItems: 'center', justifyContent: 'space-between' }}>
                    <div
                        ref={tabStripRef}
                        className="materials-tab-strip"
                        role="tablist"
                        aria-label="Material categories"
                        style={{
                            display: 'flex',
                            gap: 8,
                            overflowX: 'auto',
                            scrollSnapType: 'x proximity',
                            WebkitOverflowScrolling: 'touch',
                            scrollbarWidth: 'none',
                            msOverflowStyle: 'none',
                            maskImage: isOverflowing ? 'linear-gradient(to right, black calc(100% - 28px), transparent 100%)' : 'none',
                            WebkitMaskImage: isOverflowing ? 'linear-gradient(to right, black calc(100% - 28px), transparent 100%)' : 'none',
                            padding: '4px 2px',
                            width: '100%',
                            maxWidth: 580,
                            boxSizing: 'border-box'
                        }}
                    >
                        {tabs.map(tab => {
                            const isActive = activeTab === tab.id
                            return (
                                <button
                                    key={tab.id}
                                    ref={el => { tabRefs.current[tab.id] = el }}
                                    role="tab"
                                    aria-selected={isActive}
                                    onClick={() => setActiveTab(tab.id)}
                                    style={{
                                        flex: isOverflowing ? '0 0 auto' : '1 1 0px',
                                        minHeight: 44,
                                        height: 44,
                                        padding: '0 16px',
                                        borderRadius: 999,
                                        fontSize: 14,
                                        fontWeight: 600,
                                        lineHeight: 1,
                                        whiteSpace: 'nowrap',
                                        cursor: 'pointer',
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        boxSizing: 'border-box',
                                        border: isActive
                                            ? '1px solid var(--t-primary-border, rgba(79, 11, 217, 0.3))'
                                            : '1px solid transparent',
                                        background: isActive
                                            ? 'var(--t-primary-soft, rgba(79, 11, 217, 0.08))'
                                            : 'transparent',
                                        color: isActive
                                            ? 'var(--t-primary, #4f0bd9)'
                                            : 'var(--t-text-muted)',
                                        transition: 'background-color 0.15s, border-color 0.15s, color 0.15s'
                                    }}
                                >
                                    {tab.label}
                                </button>
                            )
                        })}
                    </div>

                    <div style={{ position: 'relative', width: '100%', maxWidth: 280, flex: '1 1 200px' }}>
                        <FiSearch style={{ position: 'absolute', left: 12, top: 14, color: 'var(--t-text-muted)', pointerEvents: 'none' }} />
                        <input
                            type="text"
                            placeholder="Search materials..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            style={{
                                width: '100%', minHeight: 44, height: 44, boxSizing: 'border-box',
                                padding: '10px 12px 10px 36px', borderRadius: 10,
                                border: '1px solid var(--t-border)', background: 'var(--t-bg)', fontSize: 14,
                                outline: 'none', transition: 'border-color 0.2s', color: 'var(--t-text)'
                            }}
                        />
                    </div>
                </div>
            </div>

            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid var(--t-border)', borderTopColor: 'var(--t-primary)', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : error ? (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid var(--t-danger)', padding: '40px 20px', textAlign: 'center' }}>
                    <FiAlertCircle size={36} style={{ color: 'var(--t-danger)', marginBottom: 12 }} />
                    <h3 style={{ margin: '0 0 8px', fontSize: 18, fontWeight: 700, color: 'var(--t-danger)' }}>Failed to load materials</h3>
                    <p style={{ margin: '0 0 20px', color: 'var(--t-text-muted)', fontSize: 14 }}>{error}</p>
                    <button
                        onClick={load}
                        style={{
                            minHeight: 44, padding: '10px 20px', borderRadius: 10, background: 'var(--t-primary)',
                            color: '#fff', border: 'none', fontWeight: 600, cursor: 'pointer',
                            display: 'inline-flex', alignItems: 'center', gap: 8
                        }}
                    >
                        <FiRefreshCw /> Retry
                    </button>
                </div>
            ) : filteredMaterials.length === 0 ? (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px dashed var(--t-border)', padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center' }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: 'var(--t-bg)', color: 'var(--t-text-muted)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}><FiFolder /></div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>No materials found</h3>
                    <p style={{ margin: 0, color: 'var(--t-text-muted)', fontSize: 14 }}>
                        {searchQuery ? 'No materials match your search criteria.' : 'Try selecting a different category or check back later.'}
                    </p>
                </div>
            ) : (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(260px, 100%), 1fr))', gap: 20 }}>
                    {filteredMaterials.map(item => {
                        const style = getIcon(item.type)
                        return (
                            <div key={item.id} className="t-card" style={{
                                background: '#fff', borderRadius: 16, border: '1px solid var(--t-border)',
                                padding: 20, display: 'flex', flexDirection: 'column',
                                boxShadow: 'var(--t-shadow-sm)', cursor: 'pointer'
                            }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 16 }}>
                                    <div style={{ width: 48, height: 48, borderRadius: 12, background: style.bg, color: style.color, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 24 }}>
                                        {style.icon}
                                    </div>
                                    <div style={{ fontSize: 11, fontWeight: 700, color: 'var(--t-text-muted)', background: 'var(--t-bg)', padding: '4px 8px', borderRadius: 6, textTransform: 'uppercase' }}>
                                        {item.type || 'File'}
                                    </div>
                                </div>
                                <h3 style={{ margin: '0 0 6px 0', fontSize: 16, fontWeight: 700, color: 'var(--t-text)', lineHeight: 1.3, display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                                    {item.title}
                                </h3>
                                <p style={{ margin: '0 0 4px 0', color: 'var(--t-text-muted)', fontSize: 13, fontWeight: 500 }}>
                                    {item.group || item.subject || 'General Material'}
                                </p>
                                <p style={{ margin: '0 0 16px 0', color: 'var(--t-text-muted)', fontSize: 12 }}>
                                    Grade: {item.grade || 'All'}
                                </p>
                                <div style={{ marginTop: 'auto', paddingTop: 16, borderTop: '1px solid var(--t-border)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                    <div style={{ fontSize: 12, color: 'var(--t-text-muted)', fontWeight: 500 }}>
                                        {new Date(item.created_at || item.date || Date.now()).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}
                                        {item.file_size && item.file_size !== '--' && <><span style={{ margin: '0 6px' }}>•</span>{item.file_size}</>}
                                    </div>
                                    <button onClick={(e) => { e.stopPropagation(); handleDownload(item); }}
                                        title={item.type?.toLowerCase() === 'recording' ? 'Watch recording' : undefined}
                                        aria-label="Download material"
                                        style={{
                                        width: 44, height: 44, borderRadius: 8, background: 'var(--t-bg)', border: '1px solid var(--t-border)',
                                        display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--t-text-muted)', cursor: 'pointer', transition: 'opacity 0.2s, transform 0.1s'
                                    }}>
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
