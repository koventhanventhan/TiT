import React, { useState, useEffect, useRef } from 'react'
import {
    FiUpload,
    FiFolder,
    FiFileText,
    FiVideo,
    FiLink,
    FiDownload,
    FiSearch,
    FiGrid,
    FiList,
    FiX,
    FiExternalLink,
    FiTrash2,
    FiRefreshCw,
    FiAlertTriangle,
    FiCheckCircle,
    FiClock,
    FiHelpCircle
} from 'react-icons/fi'
import {
    getTeacherMaterials,
    uploadTeacherMaterial,
    deleteTeacherMaterial,
    retryTeacherMaterial
} from '../../services/dashboardService'
import { useToast } from '../../components/shared/ToastContext'
import { useLanguage } from '../../context/LanguageContext'

export default function TeacherMaterials() {
    const toast = useToast()
    const { t } = useLanguage()

    const [materials, setMaterials] = useState([])
    const [loading, setLoading] = useState(true)
    const [viewMode, setViewMode] = useState('grid')
    const [activeTab, setActiveTab] = useState('all')
    const [searchQuery, setSearchQuery] = useState('')

    // Upload Modal State
    const [isUploadModalOpen, setIsUploadModalOpen] = useState(false)
    const [uploadFormData, setUploadFormData] = useState({
        title: '',
        type: 'note',
        subject: '',
        grade: '',
        medium: 'english',
        url: ''
    })
    const [uploadFile, setUploadFile] = useState(null)
    const [uploading, setUploading] = useState(false)
    const [deletingId, setDeletingId] = useState(null)
    const [retryingId, setRetryingId] = useState(null)

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
        let icon = <FiFolder />
        switch ((type || '').toLowerCase()) {
            case 'video': icon = <FiVideo />; break;
            case 'note':
            case 'past_paper':
            case 'pdf':
            case 'document': icon = <FiFileText />; break;
            case 'link': icon = <FiLink />; break;
            default: icon = <FiFolder />; break;
        }
        return { icon, color: 'var(--t-primary)', bg: 'var(--t-primary-soft)' }
    }

    const tabs = [
        { id: 'all', label: 'All Files' },
        { id: 'note', label: 'Notes' },
        { id: 'past_paper', label: 'Past Papers' },
        { id: 'pdf', label: 'PDFs' },
        { id: 'document', label: 'Documents' },
        { id: 'video', label: 'Videos' },
        { id: 'link', label: 'Links' },
    ]

    const tabRefs = useRef({})

    useEffect(() => {
        if (tabRefs.current[activeTab]) {
            tabRefs.current[activeTab].scrollIntoView({ inline: 'center', behavior: 'smooth', block: 'nearest' })
        }
    }, [activeTab])

    const filteredMaterials = (materials || []).filter(m => {
        const matchesTab = activeTab === 'all' || (m.type && m.type.toLowerCase() === activeTab);
        const search = searchQuery.toLowerCase();
        const titleMatch = m.title ? m.title.toLowerCase().includes(search) : false;
        const subjMatch = m.subject ? m.subject.toLowerCase().includes(search) : false;
        const gradeMatch = m.grade ? m.grade.toLowerCase().includes(search) : false;
        const matchesSearch = !search || titleMatch || subjMatch || gradeMatch;
        return matchesTab && matchesSearch;
    });

    const isStudyType = (type) => {
        const tLower = (type || '').toLowerCase()
        return ['note', 'past_paper', 'paper', 'pdf', 'document'].includes(tLower)
    }

    const handleUpload = async (e) => {
        e.preventDefault()
        if (uploading) return

        if (!uploadFormData.title.trim()) {
            return toast.info('Title is required')
        }

        const isStudy = isStudyType(uploadFormData.type)
        if (isStudy) {
            if (!uploadFormData.grade.trim()) {
                return toast.info('Grade is required for study materials')
            }
            if (!uploadFormData.subject.trim()) {
                return toast.info('Subject is required for study materials')
            }
            if (!uploadFormData.medium) {
                return toast.info('Medium is required for study materials')
            }
        }

        setUploading(true)
        try {
            const formData = new FormData()
            formData.append('title', uploadFormData.title.trim())
            formData.append('type', uploadFormData.type)
            if (uploadFormData.subject) formData.append('subject', uploadFormData.subject.trim())
            if (uploadFormData.grade) formData.append('grade', uploadFormData.grade.trim())
            if (uploadFormData.medium) formData.append('medium', uploadFormData.medium)

            if (uploadFormData.type.toLowerCase() === 'link') {
                if (!uploadFormData.url.trim()) {
                    setUploading(false)
                    return toast.info('URL is required for link type')
                }
                formData.append('url', uploadFormData.url.trim())
            } else {
                if (!uploadFile) {
                    setUploading(false)
                    return toast.info('File is required')
                }
                formData.append('file', uploadFile)
            }

            const res = await uploadTeacherMaterial(formData)

            if (res && res.warning) {
                toast.warning(res.warning)
            } else {
                toast.success('Material uploaded successfully')
            }

            setIsUploadModalOpen(false)
            setUploadFormData({
                title: '',
                type: 'note',
                subject: '',
                grade: '',
                medium: 'english',
                url: ''
            })
            setUploadFile(null)
            load()
        } catch (err) {
            console.error(err)
            toast.error(err.message || 'Error uploading material')
        } finally {
            setUploading(false)
        }
    }

    const handleRetry = async (id) => {
        if (retryingId) return
        setRetryingId(id)
        try {
            await retryTeacherMaterial(id)
            toast.success('Re-indexing scheduled')
            setMaterials(prev => prev.map(m => m.id === id ? { ...m, rag_status: 'pending', rag_error: null } : m))
        } catch (err) {
            console.error(err)
            toast.error(err.message || 'Failed to retry indexing')
        } finally {
            setRetryingId(null)
        }
    }

    const handleDownload = (item) => {
        if (item.type?.toLowerCase() === 'link' && item.url) {
            window.open(item.url, '_blank')
        } else if (item.file_path) {
            if (item.file_path.startsWith('https://') || item.file_path.startsWith('http://')) {
                window.open(item.file_path, '_blank')
            } else {
                const baseUrl = import.meta.env.VITE_API_URL?.replace('/api', '') || ''
                window.open(`${baseUrl}/api/materials/download?path=${encodeURIComponent(item.file_path)}`, '_blank')
            }
        } else {
            toast.info('File not available')
        }
    }

    const handleDelete = async (item) => {
        if (deletingId) return
        if (!window.confirm(`Are you sure you want to delete "${item.title}"?`)) return

        setDeletingId(item.id)
        try {
            await deleteTeacherMaterial(item.id)
            toast.success('Deleted successfully')
            load()
        } catch (err) {
            console.error(err)
            toast.error(err.message || 'Failed to delete material')
        } finally {
            setDeletingId(null)
        }
    }

    /**
     * Renders plain translated status badge & info for teacher materials
     */
    const renderRagStatus = (item) => {
        const rawStatus = (item.rag_status || '').toLowerCase()
        const isRetrying = retryingId === item.id

        if (rawStatus === 'ready') {
            return (
                <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 8 }}>
                    <span style={{
                        display: 'inline-flex', alignItems: 'center', gap: 4,
                        padding: '3px 8px', borderRadius: 6, fontSize: 12, fontWeight: 600,
                        background: 'rgba(16, 185, 129, 0.15)', color: '#059669', border: '1px solid rgba(16, 185, 129, 0.3)'
                    }}>
                        <FiCheckCircle size={12} /> {t('mat_status_ready', 'Ready')}
                    </span>
                </div>
            )
        }

        if (rawStatus === 'processing' || rawStatus === 'pending') {
            return (
                <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 8 }}>
                    <span style={{
                        display: 'inline-flex', alignItems: 'center', gap: 4,
                        padding: '3px 8px', borderRadius: 6, fontSize: 12, fontWeight: 600,
                        background: 'rgba(59, 130, 246, 0.15)', color: '#2563eb', border: '1px solid rgba(59, 130, 246, 0.3)'
                    }}>
                        <FiClock size={12} /> {t('mat_status_processing', 'Processing')}
                    </span>
                </div>
            )
        }

        if (rawStatus === 'low_quality' || rawStatus === 'quality_pending') {
            return (
                <div style={{ marginTop: 8 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                        <span style={{
                            display: 'inline-flex', alignItems: 'center', gap: 4,
                            padding: '3px 8px', borderRadius: 6, fontSize: 12, fontWeight: 600,
                            background: 'rgba(245, 158, 11, 0.15)', color: '#d97706', border: '1px solid rgba(245, 158, 11, 0.3)'
                        }}>
                            <FiAlertTriangle size={12} /> {t('mat_status_low_quality', 'Needs clearer file')}
                        </span>
                        <button
                            type="button"
                            onClick={() => handleRetry(item.id)}
                            disabled={isRetrying}
                            title={t('mat_action_retry', 'Retry indexing')}
                            style={{
                                display: 'inline-flex', alignItems: 'center', gap: 4,
                                padding: '3px 8px', borderRadius: 6, fontSize: 11, fontWeight: 600,
                                background: 'var(--t-surface)', border: '1px solid var(--t-border)',
                                color: 'var(--t-text)', cursor: isRetrying ? 'not-allowed' : 'pointer',
                                opacity: isRetrying ? 0.6 : 1
                            }}
                        >
                            <FiRefreshCw size={11} className={isRetrying ? 'spin' : ''} />
                            {t('mat_action_retry', 'Retry')}
                        </button>
                    </div>
                    <div style={{ fontSize: 11, color: '#d97706', marginTop: 4, lineHeight: 1.3 }}>
                        {t('mat_status_low_quality_hint', 'The text could not be read well. Please upload a clearer PDF or typed text.')}
                    </div>
                </div>
            )
        }

        if (rawStatus === 'failed') {
            return (
                <div style={{ marginTop: 8 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                        <span style={{
                            display: 'inline-flex', alignItems: 'center', gap: 4,
                            padding: '3px 8px', borderRadius: 6, fontSize: 12, fontWeight: 600,
                            background: 'rgba(239, 68, 68, 0.15)', color: '#dc2626', border: '1px solid rgba(239, 68, 68, 0.3)'
                        }}>
                            <FiX size={12} /> {t('mat_status_failed', 'Failed')}
                        </span>
                        <button
                            type="button"
                            onClick={() => handleRetry(item.id)}
                            disabled={isRetrying}
                            title={t('mat_action_retry', 'Retry indexing')}
                            style={{
                                display: 'inline-flex', alignItems: 'center', gap: 4,
                                padding: '3px 8px', borderRadius: 6, fontSize: 11, fontWeight: 600,
                                background: 'var(--t-surface)', border: '1px solid var(--t-border)',
                                color: 'var(--t-text)', cursor: isRetrying ? 'not-allowed' : 'pointer',
                                opacity: isRetrying ? 0.6 : 1
                            }}
                        >
                            <FiRefreshCw size={11} className={isRetrying ? 'spin' : ''} />
                            {t('mat_action_retry', 'Retry')}
                        </button>
                    </div>
                    {item.rag_error && (
                        <div style={{ fontSize: 11, color: '#dc2626', marginTop: 4, lineHeight: 1.3 }}>
                            {item.rag_error}
                        </div>
                    )}
                </div>
            )
        }

        if (rawStatus === 'unsupported') {
            return (
                <div style={{ marginTop: 8 }}>
                    <span style={{
                        display: 'inline-flex', alignItems: 'center', gap: 4,
                        padding: '3px 8px', borderRadius: 6, fontSize: 12, fontWeight: 600,
                        background: 'rgba(239, 68, 68, 0.15)', color: '#dc2626', border: '1px solid rgba(239, 68, 68, 0.3)'
                    }}>
                        <FiAlertTriangle size={12} /> {t('mat_status_unsupported', 'Unsupported format')}
                    </span>
                    <div style={{ fontSize: 11, color: '#dc2626', marginTop: 4, lineHeight: 1.3 }}>
                        {item.rag_error || 'Legacy file format is unsupported, please upload PDF or DOCX.'}
                    </div>
                </div>
            )
        }

        // Skipped or not included (e.g. video / link / recordings)
        return (
            <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 8 }}>
                <span style={{
                    display: 'inline-flex', alignItems: 'center', gap: 4,
                    padding: '3px 8px', borderRadius: 6, fontSize: 12, fontWeight: 600,
                    background: 'rgba(148, 163, 184, 0.15)', color: '#64748b', border: '1px solid rgba(148, 163, 184, 0.3)'
                }}>
                    <FiHelpCircle size={12} /> {t('mat_status_not_included', 'Not included')}
                </span>
            </div>
        )
    }

    const inputStyle = {
        width: '100%', padding: '10px 14px', borderRadius: 8,
        border: '1px solid var(--t-border)', outline: 'none', fontSize: 14,
        background: 'var(--t-bg)', boxSizing: 'border-box', color: 'var(--t-text)'
    }

    return (
        <div style={{ paddingBottom: 88 }}>
            {/* Header Section */}
            <div className="teacher-page-header">
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: 'var(--t-text)', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>
                        Teaching Materials
                    </h1>
                    <p style={{ color: 'var(--t-text-3)', margin: 0, fontSize: 14 }}>
                        Manage and share resources with your students and study assistant
                    </p>
                </div>
                <button
                    className="t-btn"
                    id="teacher-upload-material-btn"
                    style={{
                        display: 'flex', alignItems: 'center', gap: 8, padding: '10px 20px',
                        minHeight: 44,
                        background: 'var(--t-primary)', color: '#fff',
                        border: 'none', borderRadius: 12, fontWeight: 600, fontSize: 14,
                        cursor: 'pointer', width: 'fit-content'
                    }}
                    onClick={() => setIsUploadModalOpen(true)}
                >
                    <FiUpload style={{ fontSize: 18 }} /> Upload Material
                </button>
            </div>

            {/* Toolbar */}
            <div className="materials-toolbar-card">
                <div
                    className="materials-tab-strip"
                    role="tablist"
                    aria-label="Teaching material categories"
                >
                    {tabs.map(tab => {
                        const isActive = activeTab === tab.id
                        return (
                            <button
                                key={tab.id}
                                ref={el => { tabRefs.current[tab.id] = el }}
                                role="tab"
                                aria-selected={isActive ? 'true' : 'false'}
                                className={`materials-tab-pill ${isActive ? 'active' : ''}`}
                                onClick={() => setActiveTab(tab.id)}
                            >
                                {tab.label}
                            </button>
                        )
                    })}
                </div>

                <div className="materials-controls-row">
                    <div className="materials-search-wrap">
                        <FiSearch className="materials-search-icon" />
                        <input
                            type="text"
                            className="materials-search-input"
                            placeholder="Search materials..."
                            value={searchQuery}
                            onChange={e => setSearchQuery(e.target.value)}
                        />
                    </div>
                    <div className="materials-view-toggle">
                        <button
                            type="button"
                            className={`t-icon-btn ${viewMode === 'grid' ? 'active' : ''}`}
                            onClick={() => setViewMode('grid')}
                            aria-label="Grid view"
                        >
                            <FiGrid size={18} />
                        </button>
                        <button
                            type="button"
                            className={`t-icon-btn ${viewMode === 'list' ? 'active' : ''}`}
                            onClick={() => setViewMode('list')}
                            aria-label="List view"
                        >
                            <FiList size={18} />
                        </button>
                    </div>
                </div>
            </div>

            <style>{`
                .materials-toolbar-card {
                    display: flex;
                    flex-direction: column;
                    gap: 16px;
                    margin-bottom: 24px;
                    background: var(--t-surface);
                    padding: 16px;
                    border-radius: 16px;
                    border: 1px solid var(--t-border);
                    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
                }
                @media (min-width: 1024px) {
                    .materials-toolbar-card {
                        flex-direction: row;
                        align-items: center;
                        justify-content: space-between;
                    }
                }
                .materials-tab-strip {
                    display: flex;
                    gap: 8px;
                    overflow-x: auto;
                    scroll-snap-type: x proximity;
                    -webkit-overflow-scrolling: touch;
                    scrollbar-width: none;
                    -ms-overflow-style: none;
                    mask-image: linear-gradient(to right, black calc(100% - 24px), transparent 100%);
                    -webkit-mask-image: linear-gradient(to right, black calc(100% - 24px), transparent 100%);
                    padding: 4px 0;
                    flex: 1 1 auto;
                    min-width: 0;
                    max-width: 100%;
                }
                .materials-tab-strip::-webkit-scrollbar {
                    display: none !important;
                    width: 0 !important;
                    height: 0 !important;
                }
                .materials-tab-pill {
                    flex: 0 0 auto;
                    min-height: 44px;
                    height: 44px;
                    padding: 0 16px;
                    border-radius: 999px;
                    white-space: nowrap;
                    font-size: 14px;
                    line-height: 1;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    box-sizing: border-box;
                    transition: background 0.2s, color 0.2s, border-color 0.2s;
                }
                .materials-tab-pill.active {
                    background: var(--t-primary-soft);
                    border: 1px solid var(--t-primary-border);
                    color: var(--t-primary);
                    font-weight: 600;
                }
                .materials-tab-pill:not(.active) {
                    background: transparent;
                    border: 1px solid transparent;
                    color: var(--t-text-muted, #64748b);
                    font-weight: 500;
                }
                .materials-controls-row {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    width: 100%;
                    box-sizing: border-box;
                }
                @media (min-width: 1024px) {
                    .materials-controls-row {
                        width: auto;
                        justify-content: flex-end;
                        flex: 0 0 auto;
                    }
                }
                .materials-search-wrap {
                    position: relative;
                    flex: 1 1 auto;
                    min-width: 0;
                    height: 44px;
                }
                @media (min-width: 1024px) {
                    .materials-search-wrap {
                        width: 260px;
                        flex: 0 0 260px;
                    }
                }
                .materials-search-icon {
                    position: absolute;
                    left: 14px;
                    top: 14px;
                    color: var(--t-text-3, #94a3b8);
                    pointer-events: none;
                }
                .materials-search-input {
                    width: 100%;
                    height: 44px;
                    min-height: 44px;
                    box-sizing: border-box;
                    padding: 10px 14px 10px 38px;
                    border-radius: 10px;
                    border: 1px solid var(--t-border);
                    background: var(--t-bg);
                    font-size: 14px;
                    outline: none;
                    color: var(--t-text);
                    transition: border-color 0.2s;
                }
                .materials-search-input:focus {
                    border-color: var(--t-primary);
                }
                .materials-view-toggle {
                    display: inline-flex;
                    align-items: center;
                    height: 44px;
                    min-height: 44px;
                    background: var(--t-bg);
                    border-radius: 10px;
                    padding: 2px;
                    border: 1px solid var(--t-border);
                    box-sizing: border-box;
                    flex-shrink: 0;
                    gap: 2px;
                }
                .materials-view-toggle button {
                    width: 40px;
                    height: 40px;
                    min-width: 40px;
                    min-height: 40px;
                    border: none;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    background: transparent;
                    color: var(--t-text-3, #94a3b8);
                    transition: background 0.15s, color 0.15s;
                    box-sizing: border-box;
                }
                .materials-view-toggle button.active {
                    background: var(--t-surface);
                    color: var(--t-primary);
                    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
                }
                .materials-empty-state {
                    background: var(--t-surface);
                    border-radius: 16px;
                    border: 1px dashed var(--t-border);
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                    box-sizing: border-box;
                    padding: 32px 16px;
                }
                @media (min-width: 640px) {
                    .materials-empty-state {
                        padding: 56px 24px;
                    }
                }
            `}</style>

            {/* Content Area */}
            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid var(--t-border)', borderTopColor: 'var(--t-primary)', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : filteredMaterials.length === 0 ? (
                <div className="materials-empty-state">
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: 'var(--t-primary-soft)', color: 'var(--t-primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>
                        <FiFolder />
                    </div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>No materials found</h3>
                    <p style={{ margin: 0, color: 'var(--t-text-3)', fontSize: 14 }}>Upload documents or add links for your students.</p>
                </div>
            ) : viewMode === 'grid' ? (
                /* Grid View */
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(280px, 100%), 1fr))', gap: 20 }}>
                    {filteredMaterials.map(item => {
                        const style = getIcon(item.type)
                        const d = item.created_at || item.date || new Date();
                        const isDeleting = deletingId === item.id;
                        return (
                            <div key={item.id} className="t-card" style={{
                                background: 'var(--t-surface)', borderRadius: 16, border: '1px solid var(--t-border)',
                                padding: 20, display: 'flex', flexDirection: 'column',
                                boxShadow: '0 2px 8px rgba(0,0,0,0.02)'
                            }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 14 }}>
                                    <div style={{ width: 48, height: 48, borderRadius: 12, background: style.bg, color: style.color, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 24 }}>
                                        {style.icon}
                                    </div>
                                    <span style={{ fontSize: 12, fontWeight: 600, color: 'var(--t-text-3)', textTransform: 'uppercase', background: 'var(--t-bg)', padding: '2px 8px', borderRadius: 4 }}>
                                        {item.type}
                                    </span>
                                </div>
                                <h3 style={{ margin: '0 0 6px 0', fontSize: 16, fontWeight: 700, color: 'var(--t-text)', lineHeight: 1.3, display: '-webkit-box', WebKitLineClamp: 2, WebKitBoxOrient: 'vertical', overflow: 'hidden' }}>
                                    {item.title}
                                </h3>
                                <p style={{ margin: '0 0 10px 0', color: 'var(--t-text-3)', fontSize: 13, fontWeight: 500 }}>
                                    {item.subject || 'All Subjects'} &bull; {item.grade || 'All Grades'} {item.medium ? `&bull; ${item.medium}` : ''}
                                </p>

                                {/* AI Search Status & Quality Feedback */}
                                {renderRagStatus(item)}

                                <div style={{ marginTop: 'auto', paddingTop: 16, borderTop: '1px solid var(--t-border)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                    <div style={{ fontSize: 12, color: 'var(--t-text-3)', fontWeight: 500 }}>
                                        {new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}
                                        {item.file_size ? <span style={{ margin: '0 6px' }}>&bull; {item.file_size}</span> : null}
                                    </div>
                                    <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                                        <button
                                            onClick={() => handleDownload(item)}
                                            className="t-icon-btn"
                                            aria-label={item.type?.toLowerCase() === 'link' ? 'Open link' : 'Download file'}
                                            style={{
                                                width: 44, height: 44, minWidth: 44, minHeight: 44, borderRadius: 8, background: 'var(--t-surface)', border: '1px solid var(--t-border)',
                                                display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--t-text-3)', cursor: 'pointer'
                                            }}
                                        >
                                            {item.type?.toLowerCase() === 'link' ? <FiExternalLink /> : <FiDownload />}
                                        </button>
                                        <button
                                            onClick={() => handleDelete(item)}
                                            disabled={isDeleting}
                                            className="t-icon-btn"
                                            aria-label="Delete material"
                                            style={{
                                                width: 44, height: 44, minWidth: 44, minHeight: 44, borderRadius: 8, background: 'var(--t-surface)', border: '1px solid var(--t-border)',
                                                display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--t-danger)', cursor: isDeleting ? 'not-allowed' : 'pointer',
                                                opacity: isDeleting ? 0.5 : 1
                                            }}
                                        >
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
                <div style={{ background: 'var(--t-surface)', borderRadius: 16, border: '1px solid var(--t-border)', overflow: 'hidden', boxShadow: '0 1px 3px rgba(0,0,0,0.02)' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                        <thead>
                            <tr style={{ borderBottom: '1px solid var(--t-border)', background: 'var(--t-bg)' }}>
                                <th style={{ padding: '16px 20px', fontSize: 13, fontWeight: 700, color: 'var(--t-text-2)' }}>Material</th>
                                <th style={{ padding: '16px 20px', fontSize: 13, fontWeight: 700, color: 'var(--t-text-2)' }}>Type</th>
                                <th style={{ padding: '16px 20px', fontSize: 13, fontWeight: 700, color: 'var(--t-text-2)' }}>Grade & Subject</th>
                                <th style={{ padding: '16px 20px', fontSize: 13, fontWeight: 700, color: 'var(--t-text-2)' }}>AI Search Status</th>
                                <th style={{ padding: '16px 20px', fontSize: 13, fontWeight: 700, color: 'var(--t-text-2)' }}>Date</th>
                                <th style={{ padding: '16px 20px', fontSize: 13, fontWeight: 700, color: 'var(--t-text-2)', textAlign: 'right' }}>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredMaterials.map(item => {
                                const style = getIcon(item.type)
                                const d = item.created_at || item.date || new Date();
                                const isDeleting = deletingId === item.id;
                                return (
                                    <tr key={item.id} style={{ borderBottom: '1px solid var(--t-border)' }}>
                                        <td style={{ padding: '16px 20px' }}>
                                            <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                                <div style={{ width: 36, height: 36, borderRadius: 8, background: style.bg, color: style.color, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 18 }}>
                                                    {style.icon}
                                                </div>
                                                <div>
                                                    <div style={{ fontWeight: 600, color: 'var(--t-text)', fontSize: 14 }}>{item.title}</div>
                                                    <div style={{ fontSize: 12, color: 'var(--t-text-3)' }}>{item.file_size || item.size || ''}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style={{ padding: '16px 20px', fontSize: 13, color: 'var(--t-text-2)', textTransform: 'capitalize' }}>
                                            {item.type}
                                        </td>
                                        <td style={{ padding: '16px 20px', fontSize: 13, color: 'var(--t-text-2)' }}>
                                            {item.grade || 'All'} - {item.subject || 'All'} {item.medium ? `(${item.medium})` : ''}
                                        </td>
                                        <td style={{ padding: '16px 20px' }}>
                                            {renderRagStatus(item)}
                                        </td>
                                        <td style={{ padding: '16px 20px', fontSize: 13, color: 'var(--t-text-3)' }}>
                                            {new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}
                                        </td>
                                        <td style={{ padding: '16px 20px', textAlign: 'right' }}>
                                            <div style={{ display: 'inline-flex', gap: 8, alignItems: 'center' }}>
                                                <button
                                                    onClick={() => handleDownload(item)}
                                                    className="t-icon-btn"
                                                    aria-label={item.type?.toLowerCase() === 'link' ? 'Open link' : 'Download file'}
                                                    style={{
                                                        width: 44, height: 44, minWidth: 44, minHeight: 44, borderRadius: 8, background: 'var(--t-surface)', border: '1px solid var(--t-border)', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', color: 'var(--t-text-3)', cursor: 'pointer'
                                                    }}
                                                >
                                                    {item.type?.toLowerCase() === 'link' ? <FiExternalLink /> : <FiDownload />}
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(item)}
                                                    disabled={isDeleting}
                                                    className="t-icon-btn"
                                                    aria-label="Delete material"
                                                    style={{
                                                        width: 44, height: 44, minWidth: 44, minHeight: 44, borderRadius: 8, background: 'var(--t-surface)', border: '1px solid var(--t-border)', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', color: 'var(--t-danger)', cursor: isDeleting ? 'not-allowed' : 'pointer',
                                                        opacity: isDeleting ? 0.5 : 1
                                                    }}
                                                >
                                                    <FiTrash2 />
                                                </button>
                                            </div>
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
                <div className="teacher-modal-overlay">
                    <div className="teacher-modal-dialog" style={{ maxWidth: 520 }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '20px 24px', borderBottom: '1px solid var(--t-border)', background: 'var(--t-surface)' }}>
                            <h2 style={{ margin: 0, fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>Upload Material</h2>
                            <button
                                onClick={() => setIsUploadModalOpen(false)}
                                className="t-icon-btn"
                                aria-label="Close upload modal"
                                style={{
                                    width: 44, height: 44, minWidth: 44, minHeight: 44, background: 'none', border: 'none', color: 'var(--t-text-3)', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center'
                                }}
                            >
                                <FiX size={20} />
                            </button>
                        </div>

                        <div className="teacher-modal-body" style={{ padding: '20px 24px' }}>
                            <form onSubmit={handleUpload}>
                                <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                                    <div>
                                        <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 6 }}>
                                            Title <span style={{ color: 'var(--t-danger)' }}>*</span>
                                        </label>
                                        <input
                                            type="text"
                                            required
                                            style={inputStyle}
                                            value={uploadFormData.title}
                                            onChange={e => setUploadFormData({ ...uploadFormData, title: e.target.value })}
                                            placeholder="e.g., Chapter 1 Physics Notes"
                                        />
                                    </div>

                                    <div style={{ display: 'flex', gap: 16, flexWrap: 'wrap' }}>
                                        <div style={{ flex: '1 1 200px' }}>
                                            <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 6 }}>
                                                Type <span style={{ color: 'var(--t-danger)' }}>*</span>
                                            </label>
                                            <select
                                                style={inputStyle}
                                                value={uploadFormData.type}
                                                onChange={e => setUploadFormData({ ...uploadFormData, type: e.target.value })}
                                            >
                                                <option value="note">Study Note</option>
                                                <option value="past_paper">Past Paper</option>
                                                <option value="pdf">PDF Document</option>
                                                <option value="document">Other Document (Word/PPT)</option>
                                                <option value="video">Video</option>
                                                <option value="link">Web Link</option>
                                            </select>
                                        </div>

                                        <div style={{ flex: '1 1 200px' }}>
                                            <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 6 }}>
                                                Grade {isStudyType(uploadFormData.type) && <span style={{ color: 'var(--t-danger)' }}>*</span>}
                                            </label>
                                            <input
                                                type="text"
                                                required={isStudyType(uploadFormData.type)}
                                                style={inputStyle}
                                                value={uploadFormData.grade}
                                                onChange={e => setUploadFormData({ ...uploadFormData, grade: e.target.value })}
                                                placeholder="e.g., Grade 10, 10, grade-10"
                                            />
                                            <small style={{ display: 'block', color: 'var(--t-text-3)', fontSize: 11, marginTop: 4 }}>
                                                {t('mat_grade_hint', 'Grade controls which students can see the file and AI search.')}
                                            </small>
                                        </div>
                                    </div>

                                    <div style={{ display: 'flex', gap: 16, flexWrap: 'wrap' }}>
                                        <div style={{ flex: '1 1 200px' }}>
                                            <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 6 }}>
                                                Subject {isStudyType(uploadFormData.type) && <span style={{ color: 'var(--t-danger)' }}>*</span>}
                                            </label>
                                            <input
                                                type="text"
                                                required={isStudyType(uploadFormData.type)}
                                                style={inputStyle}
                                                value={uploadFormData.subject}
                                                onChange={e => setUploadFormData({ ...uploadFormData, subject: e.target.value })}
                                                placeholder="e.g., Physics, Mathematics"
                                            />
                                        </div>

                                        <div style={{ flex: '1 1 200px' }}>
                                            <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 6 }}>
                                                Medium {isStudyType(uploadFormData.type) && <span style={{ color: 'var(--t-danger)' }}>*</span>}
                                            </label>
                                            <select
                                                style={inputStyle}
                                                value={uploadFormData.medium}
                                                onChange={e => setUploadFormData({ ...uploadFormData, medium: e.target.value })}
                                            >
                                                <option value="tamil">Tamil</option>
                                                <option value="english">English</option>
                                                <option value="sinhala">Sinhala</option>
                                                <option value="all">All Mediums</option>
                                            </select>
                                        </div>
                                    </div>

                                    {uploadFormData.type.toLowerCase() === 'link' ? (
                                        <div>
                                            <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 6 }}>
                                                URL <span style={{ color: 'var(--t-danger)' }}>*</span>
                                            </label>
                                            <input
                                                type="url"
                                                required
                                                style={inputStyle}
                                                value={uploadFormData.url}
                                                onChange={e => setUploadFormData({ ...uploadFormData, url: e.target.value })}
                                                placeholder="https://..."
                                            />
                                        </div>
                                    ) : (
                                        <div>
                                            <label style={{ display: 'block', fontSize: 13, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 6 }}>
                                                File <span style={{ color: 'var(--t-danger)' }}>*</span>
                                            </label>
                                            <input
                                                type="file"
                                                required
                                                onChange={e => setUploadFile(e.target.files[0])}
                                                style={{ ...inputStyle, padding: 8 }}
                                                accept=".pdf,.docx,.pptx,.jpg,.jpeg,.png,.webp,.mp4"
                                            />
                                            <small style={{ display: 'block', color: 'var(--t-text-3)', fontSize: 11, marginTop: 4 }}>
                                                Supported: PDF, DOCX, PPTX, JPG, PNG. Note: legacy .doc and .ppt are unsupported.
                                            </small>
                                        </div>
                                    )}

                                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 12, marginTop: 12 }}>
                                        <button
                                            type="button"
                                            className="t-btn"
                                            onClick={() => setIsUploadModalOpen(false)}
                                            style={{
                                                padding: '10px 18px', borderRadius: 8, border: '1px solid var(--t-border)',
                                                background: 'var(--t-surface)', color: 'var(--t-text-2)', cursor: 'pointer',
                                                minHeight: 44, fontWeight: 600
                                            }}
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            type="submit"
                                            className="t-btn"
                                            disabled={uploading}
                                            style={{
                                                padding: '10px 24px', borderRadius: 8, border: 'none',
                                                background: 'var(--t-primary)', color: '#fff', cursor: uploading ? 'not-allowed' : 'pointer',
                                                minHeight: 44, fontWeight: 600, opacity: uploading ? 0.7 : 1,
                                                display: 'flex', alignItems: 'center', gap: 8
                                            }}
                                        >
                                            {uploading && <div style={{ width: 16, height: 16, border: '2px solid #fff', borderTopColor: 'transparent', borderRadius: '50%', animation: 'spin 0.6s linear infinite' }} />}
                                            {uploading ? 'Uploading...' : 'Upload'}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </div>
    )
}
