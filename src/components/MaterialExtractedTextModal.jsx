import React, { useState, useEffect } from 'react'
import { FiX, FiCheckCircle, FiAlertTriangle, FiSave, FiFileText } from 'react-icons/fi'
import { useLanguage } from '../context/LanguageContext'
import './MaterialExtractedTextModal.css'

const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'

export default function MaterialExtractedTextModal({ materialId, materialTitle, isOpen, onClose }) {
    const { t } = useLanguage()
    const [pages, setPages] = useState([])
    const [selectedPageNum, setSelectedPageNum] = useState(1)
    const [pageText, setPageText] = useState('')
    const [loading, setLoading] = useState(true)
    const [saving, setSaving] = useState(false)
    const [statusMessage, setStatusMessage] = useState(null)
    const [errorMessage, setErrorMessage] = useState(null)

    const MAX_CHARS = 50000

    useEffect(() => {
        if (!isOpen || !materialId) return

        let isMounted = true
        setLoading(true)
        setStatusMessage(null)
        setErrorMessage(null)

        const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token')

        fetch(`${API_BASE_URL}/materials/${materialId}/pages`, {
            headers: {
                'Accept': 'application/json',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {})
            }
        })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`)
                return res.json()
            })
            .then(data => {
                if (!isMounted) return
                const fetchedPages = data.pages || []
                setPages(fetchedPages)
                if (fetchedPages.length > 0) {
                    setSelectedPageNum(fetchedPages[0].page_number)
                    setPageText(fetchedPages[0].text || '')
                }
            })
            .catch(err => {
                if (!isMounted) return
                console.error('Failed to load material pages:', err)
                setErrorMessage('Failed to load material pages. ' + err.message)
            })
            .finally(() => {
                if (isMounted) setLoading(false)
            })

        return () => { isMounted = false }
    }, [isOpen, materialId])

    const selectedPage = pages.find(p => p.page_number === selectedPageNum) || null

    const handleSelectPage = (pageNum) => {
        setSelectedPageNum(pageNum)
        const target = pages.find(p => p.page_number === pageNum)
        setPageText(target ? (target.text || '') : '')
        setStatusMessage(null)
        setErrorMessage(null)
    }

    const handleSave = async () => {
        if (!selectedPage) return
        if (pageText.length > MAX_CHARS) {
            setErrorMessage(t('max_chars_limit', 'Max 50,000 characters per page'))
            return
        }

        setSaving(true)
        setStatusMessage(null)
        setErrorMessage(null)

        const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token')

        try {
            const res = await fetch(`${API_BASE_URL}/materials/${materialId}/pages/${selectedPage.page_number}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(token ? { 'Authorization': `Bearer ${token}` } : {})
                },
                body: JSON.stringify({ text: pageText })
            })

            const data = await res.json()
            if (!res.ok) {
                throw new Error(data.message || `HTTP ${res.status}`)
            }

            // Update local page state
            setPages(prev => prev.map(p => {
                if (p.page_number === selectedPage.page_number) {
                    return {
                        ...p,
                        text: pageText,
                        teacher_verified: true,
                        ocr_uncertain: false,
                        is_ocr: false,
                        last_edited_at: new Date().toISOString()
                    }
                }
                return p
            }))

            setStatusMessage(data.message || 'Page text updated and re-indexed successfully.')
        } catch (err) {
            console.error('Save failed:', err)
            setErrorMessage(err.message || 'Failed to save page text.')
        } finally {
            setSaving(false)
        }
    }

    if (!isOpen) return null

    return (
        <div className="text-review-modal-backdrop" onClick={onClose}>
            <div className="text-review-modal" onClick={e => e.stopPropagation()}>
                <div className="text-review-header">
                    <div className="text-review-title-group">
                        <FiFileText className="title-icon" />
                        <div>
                            <h3>{t('review_extracted_text', 'Review & Correct Extracted Text')}</h3>
                            <p className="subtitle">{materialTitle || `Material #${materialId}`}</p>
                        </div>
                    </div>
                    <button className="close-btn" onClick={onClose} aria-label="Close">
                        <FiX />
                    </button>
                </div>

                {loading ? (
                    <div className="text-review-loading">Loading extracted pages...</div>
                ) : pages.length === 0 ? (
                    <div className="text-review-empty">
                        <p>{t('no_pages_extracted', 'No extracted pages found for this material.')}</p>
                    </div>
                ) : (
                    <div className="text-review-body">
                        {/* Pages selector sidebar */}
                        <div className="pages-sidebar">
                            <h4>{t('page_label', 'Page')}</h4>
                            <div className="pages-nav-list">
                                {pages.map(p => (
                                    <button
                                        key={p.page_number}
                                        className={`page-nav-item ${p.page_number === selectedPageNum ? 'active' : ''} ${p.ocr_uncertain ? 'uncertain' : ''}`}
                                        onClick={() => handleSelectPage(p.page_number)}
                                    >
                                        <span className="page-num">{t('page_label', 'Page')} {p.page_number}</span>
                                        {p.teacher_verified ? (
                                            <span className="badge-verified" title={t('teacher_verified_badge', 'Verified by Teacher')}>
                                                <FiCheckCircle />
                                            </span>
                                        ) : p.ocr_uncertain ? (
                                            <span className="badge-uncertain" title={t('ocr_uncertain_badge', 'OCR Uncertain - Review Needed')}>
                                                <FiAlertTriangle />
                                            </span>
                                        ) : null}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Editor content area */}
                        <div className="editor-main">
                            <div className="editor-toolbar">
                                <div className="editor-page-meta">
                                    <span className="current-page-tag">{t('page_label', 'Page')} {selectedPageNum}</span>
                                    {selectedPage?.teacher_verified && (
                                        <span className="status-tag verified">
                                            <FiCheckCircle /> {t('teacher_verified_badge', 'Verified by Teacher')}
                                        </span>
                                    )}
                                    {selectedPage?.ocr_uncertain && (
                                        <span className="status-tag uncertain">
                                            <FiAlertTriangle /> {t('ocr_uncertain_badge', 'OCR Uncertain - Review Needed')}
                                        </span>
                                    )}
                                    {selectedPage?.last_edited_at && (
                                        <span className="audit-tag">
                                            {t('last_edited_by_label', 'Last edited at')}: {new Date(selectedPage.last_edited_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                        </span>
                                    )}
                                </div>

                                <div className="char-counter">
                                    <span className={pageText.length > MAX_CHARS ? 'char-over-limit' : ''}>
                                        {pageText.length.toLocaleString()} / {MAX_CHARS.toLocaleString()} {t('chars_count', 'characters')}
                                    </span>
                                </div>
                            </div>

                            <textarea
                                className="page-text-editor"
                                value={pageText}
                                onChange={e => setPageText(e.target.value)}
                                placeholder="Edit or verify extracted document text here..."
                                rows={14}
                            />

                            {errorMessage && (
                                <div className="text-review-alert error">
                                    <FiAlertTriangle /> {errorMessage}
                                </div>
                            )}

                            {statusMessage && (
                                <div className="text-review-alert success">
                                    <FiCheckCircle /> {statusMessage}
                                </div>
                            )}

                            <div className="editor-actions">
                                <span className="helper-note">{t('max_chars_limit', 'Max 50,000 characters per page')}</span>
                                <button
                                    className="btn-save-reindex"
                                    onClick={handleSave}
                                    disabled={saving || pageText.length > MAX_CHARS}
                                >
                                    <FiSave /> {saving ? t('saving_and_reindexing', 'Saving & Re-indexing...') : t('save_and_reindex', 'Save & Re-index')}
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    )
}
