import React, { useState, useEffect } from 'react'
import {
    FiCpu,
    FiCheckCircle,
    FiAlertTriangle,
    FiXCircle,
    FiRefreshCw,
    FiHelpCircle,
    FiPhone,
    FiMail,
    FiMapPin,
    FiClock,
    FiShield,
    FiToggleLeft,
    FiToggleRight,
    FiThumbsDown,
    FiMessageSquare,
    FiFileText,
    FiLayers,
    FiPlus,
    FiTrash2,
    FiEdit2,
    FiSave,
    FiExternalLink
} from 'react-icons/fi'
import './SuperAdminRag.css'

const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'

export default function SuperAdminRag() {
    const [activeTab, setActiveTab] = useState('telemetry')
    const [statusData, setStatusData] = useState(null)
    const [feedbackData, setFeedbackData] = useState(null)
    const [contactSettings, setContactSettings] = useState({
        phone: '',
        whatsapp: '',
        email: '',
        address: '',
        hours: ''
    })
    const [faqs, setFaqs] = useState([])
    const [loading, setLoading] = useState(true)
    const [refreshing, setRefreshing] = useState(false)
    const [actionMsg, setActionMsg] = useState(null)
    const [errorMsg, setErrorMsg] = useState(null)

    // FAQ Form Modal / Editing state
    const [editingFaq, setEditingFaq] = useState(null)
    const [isFaqModalOpen, setIsFaqModalOpen] = useState(false)
    const [faqForm, setFaqForm] = useState({
        question: '',
        answer: '',
        language: 'en',
        sort_order: 0,
        is_active: true
    })

    const token = localStorage.getItem('token')
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...(token ? { 'Authorization': `Bearer ${token}` } : {})
    }

    const showToast = (msg, isErr = false) => {
        if (isErr) {
            setErrorMsg(msg)
            setActionMsg(null)
        } else {
            setActionMsg(msg)
            setErrorMsg(null)
        }
        setTimeout(() => {
            setActionMsg(null)
            setErrorMsg(null)
        }, 5000)
    }

    // Load All Data
    const loadAll = async () => {
        setRefreshing(true)
        try {
            const [statusRes, feedbackRes, contactRes, faqsRes] = await Promise.all([
                fetch(`${API_BASE_URL}/admin/rag/status`, { headers }),
                fetch(`${API_BASE_URL}/admin/rag/feedback-loop`, { headers }),
                fetch(`${API_BASE_URL}/admin/rag/contact-settings`, { headers }),
                fetch(`${API_BASE_URL}/admin/faqs`, { headers })
            ])

            if (statusRes.ok) {
                const s = await statusRes.json()
                setStatusData(s)
            }
            if (feedbackRes.ok) {
                const f = await feedbackRes.json()
                setFeedbackData(f)
            }
            if (contactRes.ok) {
                const c = await contactRes.json()
                setContactSettings({
                    phone: c.phone || '',
                    whatsapp: c.whatsapp || '',
                    email: c.email || '',
                    address: c.address || '',
                    hours: c.hours || ''
                })
            }
            if (faqsRes.ok) {
                const fq = await faqsRes.json()
                setFaqs(fq.data || [])
            }
        } catch (err) {
            console.error('Failed to load RAG admin data:', err)
            showToast('Failed to load operational data. Please check connection.', true)
        } finally {
            setLoading(false)
            setRefreshing(false)
        }
    }

    useEffect(() => {
        loadAll()
    }, [])

    // Reindex Material
    const handleReindex = async (materialId, title) => {
        try {
            const res = await fetch(`${API_BASE_URL}/admin/rag/materials/${materialId}/reindex`, {
                method: 'POST',
                headers
            })
            const data = await res.json()
            if (res.ok) {
                showToast(`Material #${materialId} (${title}) reindex queued!`)
                loadAll()
            } else {
                showToast(data.message || 'Failed to reindex material', true)
            }
        } catch (err) {
            showToast('Failed to reindex material.', true)
        }
    }

    // Toggle Assistant Switch
    const handleToggleSwitch = async (key, currentValue) => {
        try {
            const payload = { [key]: !currentValue }
            const res = await fetch(`${API_BASE_URL}/admin/rag/toggle-assistant`, {
                method: 'POST',
                headers,
                body: JSON.stringify(payload)
            })
            const data = await res.json()
            if (res.ok) {
                showToast(`Switch updated: ${key} is now ${!currentValue ? 'ENABLED' : 'DISABLED'}`)
                loadAll()
            } else {
                showToast(data.message || 'Failed to toggle switch.', true)
            }
        } catch (err) {
            showToast('Failed to toggle switch.', true)
        }
    }

    // Save Contact Settings
    const handleSaveContactSettings = async (e) => {
        e.preventDefault()
        try {
            const res = await fetch(`${API_BASE_URL}/admin/rag/contact-settings`, {
                method: 'POST',
                headers,
                body: JSON.stringify(contactSettings)
            })
            const data = await res.json()
            if (res.ok) {
                showToast('Assistant public contact settings updated successfully!')
            } else {
                showToast(data.message || 'Failed to update contact settings.', true)
            }
        } catch (err) {
            showToast('Network error updating contact settings.', true)
        }
    }

    // FAQ Handlers
    const handleOpenFaqModal = (faq = null) => {
        if (faq) {
            setEditingFaq(faq)
            setFaqForm({
                question: faq.question,
                answer: faq.answer,
                language: faq.language || 'en',
                sort_order: faq.sort_order || 0,
                is_active: faq.is_active ?? true
            })
        } else {
            setEditingFaq(null)
            setFaqForm({
                question: '',
                answer: '',
                language: 'en',
                sort_order: 0,
                is_active: true
            })
        }
        setIsFaqModalOpen(true)
    }

    const handleSaveFaq = async (e) => {
        e.preventDefault()
        try {
            const url = editingFaq
                ? `${API_BASE_URL}/admin/faqs/${editingFaq.id}`
                : `${API_BASE_URL}/admin/faqs`
            const method = editingFaq ? 'PUT' : 'POST'

            const res = await fetch(url, {
                method,
                headers,
                body: JSON.stringify(faqForm)
            })
            const data = await res.json()
            if (res.ok) {
                showToast(editingFaq ? 'FAQ updated successfully!' : 'FAQ created successfully!')
                setIsFaqModalOpen(false)
                loadAll()
            } else {
                showToast(data.message || 'Failed to save FAQ.', true)
            }
        } catch (err) {
            showToast('Failed to save FAQ.', true)
        }
    }

    const handleDeleteFaq = async (faqId) => {
        if (!window.confirm('Are you sure you want to delete this FAQ item?')) return
        try {
            const res = await fetch(`${API_BASE_URL}/admin/faqs/${faqId}`, {
                method: 'DELETE',
                headers
            })
            if (res.ok) {
                showToast('FAQ deleted successfully.')
                loadAll()
            } else {
                showToast('Failed to delete FAQ.', true)
            }
        } catch (err) {
            showToast('Failed to delete FAQ.', true)
        }
    }

    if (loading) {
        return (
            <div className="super-rag-page loading">
                <div className="rag-loader-spinner"></div>
                <p>Loading AI Assistant Operations Dashboard...</p>
            </div>
        )
    }

    const matStatus = statusData?.materials_status || {}
    const rates = statusData?.rates || {}
    const switches = statusData?.switches || {}

    return (
        <div className="super-rag-page">
            {/* Page Header */}
            <header className="rag-page-header">
                <div>
                    <h2><FiCpu className="icon-pulse" /> AI Assistant (RAG) Operations</h2>
                    <p className="subtitle">Real-time status, knowledge pipeline, safeguarding reviews, and quality telemetry.</p>
                </div>
                <div className="header-actions">
                    <button
                        className="btn-refresh"
                        onClick={loadAll}
                        disabled={refreshing}
                        title="Refresh data"
                    >
                        <FiRefreshCw className={refreshing ? 'spinning' : ''} />
                        <span>Refresh Telemetry</span>
                    </button>
                </div>
            </header>

            {/* Notification Banner */}
            {actionMsg && (
                <div className="rag-alert-banner success">
                    <FiCheckCircle /> <span>{actionMsg}</span>
                </div>
            )}
            {errorMsg && (
                <div className="rag-alert-banner error">
                    <FiAlertTriangle /> <span>{errorMsg}</span>
                </div>
            )}

            {/* Navigation Tabs */}
            <nav className="rag-tabs">
                <button
                    className={`tab-btn ${activeTab === 'telemetry' ? 'active' : ''}`}
                    onClick={() => setActiveTab('telemetry')}
                >
                    <FiLayers /> Status & Telemetry
                </button>
                <button
                    className={`tab-btn ${activeTab === 'materials' ? 'active' : ''}`}
                    onClick={() => setActiveTab('materials')}
                >
                    <FiFileText /> Materials & Reindexing ({statusData?.failed_materials?.length || 0} failed)
                </button>
                <button
                    className={`tab-btn ${activeTab === 'distress' ? 'active' : ''}`}
                    onClick={() => setActiveTab('distress')}
                >
                    <FiShield /> Distress Review ({statusData?.flagged_queries?.length || 0})
                </button>
                <button
                    className={`tab-btn ${activeTab === 'feedback' ? 'active' : ''}`}
                    onClick={() => setActiveTab('feedback')}
                >
                    <FiThumbsDown /> Feedback Loop & Missing Notes
                </button>
                <button
                    className={`tab-btn ${activeTab === 'faqs' ? 'active' : ''}`}
                    onClick={() => setActiveTab('faqs')}
                >
                    <FiHelpCircle /> FAQ Management ({faqs.length})
                </button>
                <button
                    className={`tab-btn ${activeTab === 'contact' ? 'active' : ''}`}
                    onClick={() => setActiveTab('contact')}
                >
                    <FiPhone /> Public Contact Settings
                </button>
            </nav>

            {/* TAB 1: STATUS & TELEMETRY */}
            {activeTab === 'telemetry' && (
                <div className="tab-pane telemetry-pane">
                    {/* Switch Controllers */}
                    <div className="switches-card">
                        <div className="switch-item">
                            <div>
                                <h4>Student Study Assistant (RAG)</h4>
                                <p>Enables AI answers from teacher notes for eligible enrolled students.</p>
                            </div>
                            <button
                                className={`switch-toggle-btn ${switches.rag_enabled ? 'enabled' : 'disabled'}`}
                                onClick={() => handleToggleSwitch('rag_enabled', switches.rag_enabled)}
                            >
                                {switches.rag_enabled ? <FiToggleRight /> : <FiToggleLeft />}
                                <span>{switches.rag_enabled ? 'ACTIVE' : 'DISABLED'}</span>
                            </button>
                        </div>
                        <div className="switch-divider"></div>
                        <div className="switch-item">
                            <div>
                                <h4>Website Public Assistant</h4>
                                <p>Enables public widget for visitors to query timetable, subjects, and institute info.</p>
                            </div>
                            <button
                                className={`switch-toggle-btn ${switches.public_assistant_enabled ? 'enabled' : 'disabled'}`}
                                onClick={() => handleToggleSwitch('public_assistant_enabled', switches.public_assistant_enabled)}
                            >
                                {switches.public_assistant_enabled ? <FiToggleRight /> : <FiToggleLeft />}
                                <span>{switches.public_assistant_enabled ? 'ACTIVE' : 'DISABLED'}</span>
                            </button>
                        </div>
                    </div>

                    {/* Material Ingestion Status Cards */}
                    <h3 className="section-title">Knowledge Ingestion Status</h3>
                    <div className="stats-grid">
                        <div className="stat-card border-green">
                            <div>
                                <span className="label">Ready (Indexed)</span>
                                <h3>{matStatus.ready ?? 0}</h3>
                            </div>
                            <div className="card-badge bg-green"><FiCheckCircle /> Ready</div>
                        </div>
                        <div className="stat-card border-amber">
                            <div>
                                <span className="label">OCR Uncertainty Warnings</span>
                                <h3>{matStatus.ocr_uncertain_warnings ?? 0}</h3>
                            </div>
                            <div className="card-badge bg-amber"><FiAlertTriangle /> Needs Review</div>
                        </div>
                        <div className="stat-card border-blue">
                            <div>
                                <span className="label">Processing / Pending</span>
                                <h3>{(matStatus.processing ?? 0) + (matStatus.pending ?? 0)}</h3>
                            </div>
                            <div className="card-badge bg-blue"><FiRefreshCw /> In Ingestion</div>
                        </div>
                        <div className="stat-card border-rose">
                            <div>
                                <span className="label">Failed Ingestion</span>
                                <h3>{matStatus.failed ?? 0}</h3>
                            </div>
                            <div className="card-badge bg-rose"><FiXCircle /> Failed</div>
                        </div>
                        <div className="stat-card border-slate">
                            <div>
                                <span className="label">Unsupported File Types</span>
                                <h3>{matStatus.unsupported ?? 0}</h3>
                            </div>
                            <div className="card-badge bg-slate"><FiFileText /> Unsupported</div>
                        </div>
                    </div>

                    {/* Operational Query Telemetry */}
                    <h3 className="section-title">Query Telemetry & Quality Rates</h3>
                    <div className="stats-grid">
                        <div className="stat-card">
                            <div>
                                <span className="label">Total Queries (14d)</span>
                                <h3>{rates.total_queries ?? 0}</h3>
                            </div>
                        </div>
                        <div className="stat-card">
                            <div>
                                <span className="label">No-Notes Found Rate</span>
                                <h3 className={rates.no_notes_found_rate_percent > 30 ? 'text-amber' : ''}>
                                    {rates.no_notes_found_rate_percent ?? 0}%
                                </h3>
                                <span className="subtext">{rates.no_notes_found_count ?? 0} queries</span>
                            </div>
                        </div>
                        <div className="stat-card">
                            <div>
                                <span className="label">Thumbs-Down Rate</span>
                                <h3 className={rates.thumbs_down_rate_percent > 15 ? 'text-rose' : ''}>
                                    {rates.thumbs_down_rate_percent ?? 0}%
                                </h3>
                                <span className="subtext">{rates.thumbs_down_count ?? 0} down / {rates.rated_queries_count ?? 0} rated</span>
                            </div>
                        </div>
                        <div className="stat-card">
                            <div>
                                <span className="label">Blocked by Safety</span>
                                <h3 className="text-rose">{rates.safety_block_count ?? 0}</h3>
                                <span className="subtext">Guardrail blocked</span>
                            </div>
                        </div>
                    </div>

                    {/* Queries per Day Chart / Table */}
                    <h3 className="section-title">Daily Query Volume (Past 7 Days)</h3>
                    <div className="table-card">
                        <table className="rag-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Total Queries</th>
                                    <th>Answered</th>
                                    <th>No Notes Found</th>
                                    <th>Distress Flagged</th>
                                    <th>Errors</th>
                                </tr>
                            </thead>
                            <tbody>
                                {(statusData?.daily_queries || []).map((day) => (
                                    <tr key={day.date}>
                                        <td className="font-semibold">{day.date}</td>
                                        <td><span className="pill pill-primary">{day.total}</span></td>
                                        <td><span className="pill pill-success">{day.answered}</span></td>
                                        <td>
                                            <span className={`pill ${day.no_notes_found > 0 ? 'pill-warning' : 'pill-slate'}`}>
                                                {day.no_notes_found}
                                            </span>
                                        </td>
                                        <td>
                                            <span className={`pill ${day.flagged > 0 ? 'pill-danger' : 'pill-slate'}`}>
                                                {day.flagged}
                                            </span>
                                        </td>
                                        <td>{day.error}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* TAB 2: MATERIALS & REINDEXING */}
            {activeTab === 'materials' && (
                <div className="tab-pane materials-pane">
                    <h3 className="section-title">Failed Learning Materials</h3>
                    <p className="section-desc">These items encountered errors during parsing, OCR, or embedding generation. Re-indexing will run the ingestion job again.</p>

                    {statusData?.failed_materials?.length === 0 ? (
                        <div className="empty-state-card">
                            <FiCheckCircle className="text-green text-3xl" />
                            <h4>No Failed Materials!</h4>
                            <p>All learning materials have been indexed or are pending processing.</p>
                        </div>
                    ) : (
                        <div className="table-card">
                            <table className="rag-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Subject / Grade</th>
                                        <th>Failure Reason</th>
                                        <th>Last Attempt</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(statusData?.failed_materials || []).map((m) => (
                                        <tr key={m.id}>
                                            <td>#{m.id}</td>
                                            <td className="font-semibold">{m.title}</td>
                                            <td>{m.subject || '—'} / {m.grade || '—'}</td>
                                            <td className="error-reason-cell">
                                                <code className="error-badge">{m.rag_error || 'Unknown ingestion error'}</code>
                                            </td>
                                            <td>{new Date(m.updated_at).toLocaleString()}</td>
                                            <td>
                                                <button
                                                    className="btn-table-action reindex-btn"
                                                    onClick={() => handleReindex(m.id, m.title)}
                                                >
                                                    <FiRefreshCw /> Reindex
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}

            {/* TAB 3: DISTRESS REVIEW LIST */}
            {activeTab === 'distress' && (
                <div className="tab-pane distress-pane">
                    <div className="safeguard-header-box">
                        <FiShield className="safeguard-icon" />
                        <div>
                            <h4>Safeguarding & Distress Queries (Restricted Access)</h4>
                            <p>Confidential records where students mentioned self-harm, emotional crisis, or personal distress. Warm support responses with local helplines (1333 / 1926) were delivered immediately.</p>
                        </div>
                    </div>

                    {statusData?.flagged_queries?.length === 0 ? (
                        <div className="empty-state-card">
                            <FiCheckCircle className="text-green text-3xl" />
                            <h4>No Distress Alerts Logged</h4>
                            <p>No queries have triggered the student crisis keyword guardian.</p>
                        </div>
                    ) : (
                        <div className="table-card">
                            <table className="rag-table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Student</th>
                                        <th>Contact</th>
                                        <th>Trigger / Reason</th>
                                        <th>Student Query</th>
                                        <th>System Response</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(statusData?.flagged_queries || []).map((q) => (
                                        <tr key={q.id}>
                                            <td className="text-nowrap">{new Date(q.created_at).toLocaleString()}</td>
                                            <td className="font-semibold">{q.user?.name || `Student #${q.user_id}`}</td>
                                            <td>
                                                <div className="contact-meta">
                                                    {q.user?.phone_number && <span><FiPhone /> {q.user.phone_number}</span>}
                                                    {q.user?.email && <span><FiMail /> {q.user.email}</span>}
                                                </div>
                                            </td>
                                            <td><span className="pill pill-danger">{q.flag_reason || 'distress'}</span></td>
                                            <td className="student-query-text">{q.question}</td>
                                            <td className="system-reply-text">{q.answer}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}

            {/* TAB 4: FEEDBACK LOOP (MISSING NOTES) */}
            {activeTab === 'feedback' && (
                <div className="tab-pane feedback-pane">
                    <div className="feedback-intro-box">
                        <div>
                            <h4>Teacher Feedback Loop: Missing Subject Notes & Low-Quality Answers</h4>
                            <p>Questions where no notes were found or where students gave thumbs-down feedback. All records are <strong>strictly redacted with zero student personal data</strong> so teachers can see exactly which syllabus topics require uploaded notes.</p>
                        </div>
                    </div>

                    {feedbackData?.feedback_groups?.length === 0 ? (
                        <div className="empty-state-card">
                            <FiCheckCircle className="text-green text-3xl" />
                            <h4>No Content Gaps Detected!</h4>
                            <p>No unanswered or low-quality questions have been flagged in the last 60 days.</p>
                        </div>
                    ) : (
                        <div className="feedback-groups-container">
                            {(feedbackData?.feedback_groups || []).map((grp) => (
                                <div key={grp.subject} className="subject-feedback-card">
                                    <div className="subject-feedback-header">
                                        <div className="subject-title-box">
                                            <h4>{grp.subject}</h4>
                                            <span className="total-issues-pill">{grp.total_count} Issues Flagged</span>
                                        </div>
                                        <div className="subject-meta-badges">
                                            <span className="badge-no-notes">{grp.no_notes_count} Missing Notes</span>
                                            <span className="badge-thumbs-down">{grp.thumbs_down_count} Thumbs Down</span>
                                        </div>
                                    </div>

                                    <div className="questions-sample-list">
                                        <h5>Recent Sample Student Questions:</h5>
                                        <ul>
                                            {grp.questions.slice(0, 8).map((q) => (
                                                <li key={q.id} className="sample-question-item">
                                                    <span className="question-text">"{q.question}"</span>
                                                    <div className="question-badges">
                                                        {q.status === 'no_notes_found' && (
                                                            <span className="mini-badge-missing">No Notes Found</span>
                                                        )}
                                                        {q.feedback_rating === 'down' && (
                                                            <span className="mini-badge-down"><FiThumbsDown /> Low Quality</span>
                                                        )}
                                                        <span className="question-time">{q.created_at}</span>
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            )}

            {/* TAB 5: FAQ MANAGEMENT */}
            {activeTab === 'faqs' && (
                <div className="tab-pane faqs-pane">
                    <div className="faqs-header-bar">
                        <div>
                            <h4>Assistant Knowledge Base: Frequently Asked Questions</h4>
                            <p>Curated official Q&As ingested by the assistant to answer student and public inquiries.</p>
                        </div>
                        <button className="btn-add-faq" onClick={() => handleOpenFaqModal()}>
                            <FiPlus /> Add FAQ
                        </button>
                    </div>

                    {faqs.length === 0 ? (
                        <div className="empty-state-card">
                            <FiHelpCircle className="text-3xl text-slate" />
                            <h4>No FAQs Defined</h4>
                            <p>Add official questions and answers to power the website assistant.</p>
                        </div>
                    ) : (
                        <div className="table-card">
                            <table className="rag-table">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Language</th>
                                        <th>Question</th>
                                        <th>Answer</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {faqs.map((f) => (
                                        <tr key={f.id}>
                                            <td>{f.sort_order}</td>
                                            <td><span className="lang-tag uppercase">{f.language || 'en'}</span></td>
                                            <td className="font-semibold faq-q-cell">{f.question}</td>
                                            <td className="faq-a-cell">{f.answer}</td>
                                            <td>
                                                <span className={`pill ${f.is_active ? 'pill-success' : 'pill-slate'}`}>
                                                    {f.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td>
                                                <div className="action-buttons-group">
                                                    <button
                                                        className="btn-icon-action edit"
                                                        onClick={() => handleOpenFaqModal(f)}
                                                        title="Edit FAQ"
                                                    >
                                                        <FiEdit2 />
                                                    </button>
                                                    <button
                                                        className="btn-icon-action delete"
                                                        onClick={() => handleDeleteFaq(f.id)}
                                                        title="Delete FAQ"
                                                    >
                                                        <FiTrash2 />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* FAQ Form Modal */}
                    {isFaqModalOpen && (
                        <div className="modal-backdrop">
                            <div className="faq-modal-card">
                                <h3>{editingFaq ? 'Edit FAQ Item' : 'Create FAQ Item'}</h3>
                                <form onSubmit={handleSaveFaq}>
                                    <div className="form-group">
                                        <label>Question *</label>
                                        <input
                                            type="text"
                                            required
                                            value={faqForm.question}
                                            onChange={(e) => setFaqForm({ ...faqForm, question: e.target.value })}
                                            placeholder="e.g. How do I join the online Zoom class?"
                                        />
                                    </div>
                                    <div className="form-group">
                                        <label>Official Answer *</label>
                                        <textarea
                                            required
                                            rows={4}
                                            value={faqForm.answer}
                                            onChange={(e) => setFaqForm({ ...faqForm, answer: e.target.value })}
                                            placeholder="Provide the accurate answer that the AI assistant will deliver..."
                                        />
                                    </div>
                                    <div className="form-row-2">
                                        <div className="form-group">
                                            <label>Language</label>
                                            <select
                                                value={faqForm.language}
                                                onChange={(e) => setFaqForm({ ...faqForm, language: e.target.value })}
                                            >
                                                <option value="en">English (en)</option>
                                                <option value="ta">Tamil (ta)</option>
                                                <option value="si">Sinhala (si)</option>
                                            </select>
                                        </div>
                                        <div className="form-group">
                                            <label>Sort Order</label>
                                            <input
                                                type="number"
                                                min={0}
                                                value={faqForm.sort_order}
                                                onChange={(e) => setFaqForm({ ...faqForm, sort_order: parseInt(e.target.value) || 0 })}
                                            />
                                        </div>
                                    </div>
                                    <div className="form-group checkbox-group">
                                        <label>
                                            <input
                                                type="checkbox"
                                                checked={faqForm.is_active}
                                                onChange={(e) => setFaqForm({ ...faqForm, is_active: e.target.checked })}
                                            />
                                            Active (Include in Assistant Knowledge)
                                        </label>
                                    </div>
                                    <div className="modal-actions">
                                        <button
                                            type="button"
                                            className="btn-cancel"
                                            onClick={() => setIsFaqModalOpen(false)}
                                        >
                                            Cancel
                                        </button>
                                        <button type="submit" className="btn-save">
                                            <FiSave /> Save FAQ
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* TAB 6: PUBLIC CONTACT SETTINGS */}
            {activeTab === 'contact' && (
                <div className="tab-pane contact-pane">
                    <div className="contact-intro-card">
                        <FiPhone className="text-2xl text-blue" />
                        <div>
                            <h4>Assistant Public Contact Configuration</h4>
                            <p>The website assistant strictly uses these official contact coordinates from the <code>assistant_public_contact</code> group to direct visitor enquiries. It will never guess or use placeholder links.</p>
                        </div>
                    </div>

                    <form onSubmit={handleSaveContactSettings} className="contact-settings-form">
                        <div className="form-group">
                            <label><FiPhone /> Official Phone Number</label>
                            <input
                                type="text"
                                placeholder="e.g. +94 11 234 5678"
                                value={contactSettings.phone}
                                onChange={(e) => setContactSettings({ ...contactSettings, phone: e.target.value })}
                            />
                        </div>

                        <div className="form-group">
                            <label><FiMessageSquare /> WhatsApp Number</label>
                            <input
                                type="text"
                                placeholder="e.g. +94 77 123 4567"
                                value={contactSettings.whatsapp}
                                onChange={(e) => setContactSettings({ ...contactSettings, whatsapp: e.target.value })}
                            />
                        </div>

                        <div className="form-group">
                            <label><FiMail /> Official Enquiries Email</label>
                            <input
                                type="email"
                                placeholder="e.g. info@institute.lk"
                                value={contactSettings.email}
                                onChange={(e) => setContactSettings({ ...contactSettings, email: e.target.value })}
                            />
                        </div>

                        <div className="form-group">
                            <label><FiMapPin /> Institute Physical Address</label>
                            <input
                                type="text"
                                placeholder="e.g. 123 Galle Road, Colombo 03, Sri Lanka"
                                value={contactSettings.address}
                                onChange={(e) => setContactSettings({ ...contactSettings, address: e.target.value })}
                            />
                        </div>

                        <div className="form-group">
                            <label><FiClock /> Operating / Support Hours</label>
                            <input
                                type="text"
                                placeholder="e.g. Monday - Saturday, 8:00 AM - 6:00 PM"
                                value={contactSettings.hours}
                                onChange={(e) => setContactSettings({ ...contactSettings, hours: e.target.value })}
                            />
                        </div>

                        <div className="form-actions">
                            <button type="submit" className="btn-save-contact">
                                <FiSave /> Save Contact Settings
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </div>
    )
}
