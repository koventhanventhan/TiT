import React, { useState, useEffect, useRef } from 'react'
import {
    FiSearch, FiPlus, FiUpload, FiDownload, FiTrash2, FiEdit2,
    FiX, FiCheck, FiTag, FiCalendar, FiFilter, FiAlertCircle
} from 'react-icons/fi'
import {
    getAdminExamResults, createExamResult, updateExamResult,
    deleteExamResult, bulkDeleteExamResults, importExamResults,
    getAdminExamTerms, createExamTerm, deleteExamTerm,
    getExamResultsExportUrl
} from '../../services/dashboardService'
import './AdminExamResults.css'

const RESULT_GRADES = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'S', 'W', 'F']
const DEFAULT_GRADES = [
    'Grade-1', 'Grade-2', 'Grade-3', 'Grade-4', 'Grade-5',
    'Grade-6', 'Grade-7', 'Grade-8', 'Grade-9', 'Grade-10',
    'Grade-11', 'Grade-12', 'Grade-13', 'O/L', 'A/L'
]

const emptyForm = {
    student_name: '', index_no: '', term: '', grade: '',
    subject: '', marks: '', result_grade: '', rank: '', year: new Date().getFullYear().toString()
}

export default function AdminExamResults() {
    const [results, setResults] = useState([])
    const [pagination, setPagination] = useState({})
    const [loading, setLoading] = useState(true)
    const [searchTerm, setSearchTerm] = useState('')
    const [filterTerm, setFilterTerm] = useState('')
    const [filterGrade, setFilterGrade] = useState('')
    const [selectedIds, setSelectedIds] = useState([])
    const [isDeleting, setIsDeleting] = useState(false)

    // Modal states
    const [showFormModal, setShowFormModal] = useState(false)
    const [showImportModal, setShowImportModal] = useState(false)
    const [showTermsModal, setShowTermsModal] = useState(false)
    const [formData, setFormData] = useState({ ...emptyForm })
    const [editingId, setEditingId] = useState(null)
    const [formError, setFormError] = useState('')
    const [formLoading, setFormLoading] = useState(false)

    // Import states
    const [importFile, setImportFile] = useState(null)
    const [importTerm, setImportTerm] = useState('')
    const [importGrade, setImportGrade] = useState('')
    const [importYear, setImportYear] = useState(new Date().getFullYear().toString())
    const [importLoading, setImportLoading] = useState(false)
    const [importResult, setImportResult] = useState(null)

    // Terms management
    const [terms, setTerms] = useState([])
    const [newTermName, setNewTermName] = useState('')
    const [termsLoading, setTermsLoading] = useState(false)

    const fileInputRef = useRef(null)

    // Load results
    const loadResults = async () => {
        setLoading(true)
        try {
            const params = {}
            if (searchTerm) params.search = searchTerm
            if (filterTerm) params.term = filterTerm
            if (filterGrade) params.grade = filterGrade
            const res = await getAdminExamResults(params)
            setResults(res.data || [])
            setPagination({ total: res.total, per_page: res.per_page, current_page: res.current_page })
        } catch (err) {
            console.error('Error loading results:', err)
        } finally {
            setLoading(false)
        }
    }

    useEffect(() => { loadResults() }, [filterTerm, filterGrade])

    // Load terms
    const loadTerms = async () => {
        try {
            const res = await getAdminExamTerms()
            setTerms(res.data || [])
        } catch (err) {
            console.error('Error loading terms:', err)
        }
    }

    useEffect(() => { loadTerms() }, [])

    // Search debounce
    useEffect(() => {
        const timer = setTimeout(() => { loadResults() }, 500)
        return () => clearTimeout(timer)
    }, [searchTerm])

    // ── Selection ──
    const handleSelectAll = (e) => {
        setSelectedIds(e.target.checked ? results.map(r => r.id) : [])
    }

    const handleSelect = (id) => {
        setSelectedIds(prev => prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id])
    }

    const handleBulkDelete = async () => {
        if (!window.confirm(`Delete ${selectedIds.length} results?`)) return
        setIsDeleting(true)
        try {
            await bulkDeleteExamResults(selectedIds)
            setSelectedIds([])
            loadResults()
        } catch (err) {
            alert('Error deleting: ' + err.message)
        } finally {
            setIsDeleting(false)
        }
    }

    // ── Form Modal ──
    const openAddModal = () => {
        setFormData({ ...emptyForm })
        setEditingId(null)
        setFormError('')
        setShowFormModal(true)
    }

    const openEditModal = (result) => {
        setFormData({
            student_name: result.student_name || '',
            index_no: result.index_no || '',
            term: result.term || '',
            grade: result.grade || '',
            subject: result.subject || '',
            marks: result.marks ?? '',
            result_grade: result.result_grade || '',
            rank: result.rank ?? '',
            year: result.year || '',
        })
        setEditingId(result.id)
        setFormError('')
        setShowFormModal(true)
    }

    const handleFormSubmit = async (e) => {
        e.preventDefault()
        setFormLoading(true)
        setFormError('')

        try {
            const data = { ...formData }
            if (data.marks === '') data.marks = null
            if (data.rank === '') data.rank = null

            if (editingId) {
                await updateExamResult(editingId, data)
            } else {
                await createExamResult(data)
            }
            setShowFormModal(false)
            loadResults()
        } catch (err) {
            setFormError(err.message)
        } finally {
            setFormLoading(false)
        }
    }

    const handleDeleteSingle = async (id) => {
        if (!window.confirm('Delete this result?')) return
        try {
            await deleteExamResult(id)
            loadResults()
        } catch (err) {
            alert('Error: ' + err.message)
        }
    }

    // ── Import Modal ──
    const handleImport = async () => {
        if (!importFile || !importTerm || !importGrade) {
            setImportResult({ error: 'Please select file, term, and grade' })
            return
        }

        setImportLoading(true)
        setImportResult(null)

        try {
            const fd = new FormData()
            fd.append('file', importFile)
            fd.append('term', importTerm)
            fd.append('grade', importGrade)
            if (importYear) fd.append('year', importYear)

            const res = await importExamResults(fd)
            setImportResult({ success: res.message, imported: res.imported, errors: res.errors })
            loadResults()
        } catch (err) {
            setImportResult({ error: err.message })
        } finally {
            setImportLoading(false)
        }
    }

    // ── Export ──
    const handleExport = () => {
        const params = {}
        if (filterTerm) params.term = filterTerm
        if (filterGrade) params.grade = filterGrade
        const url = getExamResultsExportUrl(params)
        window.open(url, '_blank')
    }

    // ── Terms Management ──
    const handleAddTerm = async () => {
        if (!newTermName.trim()) return
        setTermsLoading(true)
        try {
            await createExamTerm(newTermName.trim())
            setNewTermName('')
            loadTerms()
        } catch (err) {
            alert('Error: ' + err.message)
        } finally {
            setTermsLoading(false)
        }
    }

    const handleDeleteTerm = async (id) => {
        if (!window.confirm('Delete this term?')) return
        try {
            await deleteExamTerm(id)
            loadTerms()
        } catch (err) {
            alert('Error: ' + err.message)
        }
    }

    // ── Grade color helper ──
    const gradeClass = (g) => {
        if (!g) return ''
        const u = g.toUpperCase()
        if (u === 'A+' || u === 'A') return 'g-excellent'
        if (u === 'A-' || u === 'B+' || u === 'B') return 'g-good'
        if (u === 'B-' || u === 'C+' || u === 'C') return 'g-average'
        if (u === 'S') return 'g-simple'
        if (u === 'W' || u === 'F') return 'g-fail'
        return ''
    }

    if (loading && results.length === 0) {
        return <div className="aer-loading">Loading exam results...</div>
    }

    return (
        <div className="admin-exam-results">
            {/* Page Header */}
            <div className="aer-header">
                <div className="aer-header-info">
                    <h1>📊 Exam Results Management</h1>
                    <p>Add, import, and manage student examination results</p>
                </div>
                <div className="aer-header-actions">
                    <button className="aer-btn secondary" onClick={() => setShowTermsModal(true)}>
                        <FiTag /> Manage Terms
                    </button>
                    <button className="aer-btn secondary" onClick={handleExport}>
                        <FiDownload /> Export CSV
                    </button>
                    <button className="aer-btn secondary" onClick={() => { setShowImportModal(true); setImportResult(null); }}>
                        <FiUpload /> Import File
                    </button>
                    <button className="aer-btn primary" onClick={openAddModal}>
                        <FiPlus /> Add Result
                    </button>
                </div>
            </div>

            {/* Controls */}
            <div className="aer-controls">
                <div className="aer-search">
                    <FiSearch />
                    <input
                        type="text"
                        placeholder="Search by name, index, or subject..."
                        value={searchTerm}
                        onChange={e => setSearchTerm(e.target.value)}
                    />
                </div>
                <div className="aer-filters">
                    <div className="aer-filter">
                        <FiFilter />
                        <select value={filterTerm} onChange={e => setFilterTerm(e.target.value)}>
                            <option value="">All Terms</option>
                            {terms.map(t => <option key={t.id} value={t.name}>{t.name}</option>)}
                        </select>
                    </div>
                    <div className="aer-filter">
                        <FiFilter />
                        <select value={filterGrade} onChange={e => setFilterGrade(e.target.value)}>
                            <option value="">All Grades</option>
                            {DEFAULT_GRADES.map(g => <option key={g} value={g}>{g}</option>)}
                        </select>
                    </div>
                </div>
                {selectedIds.length > 0 && (
                    <button className="aer-btn danger" onClick={handleBulkDelete} disabled={isDeleting}>
                        <FiTrash2 /> Delete Selected ({selectedIds.length})
                    </button>
                )}
            </div>

            {/* Results Table */}
            <div className="aer-table-wrap">
                <table className="aer-table">
                    <thead>
                        <tr>
                            <th>
                                <input
                                    type="checkbox"
                                    onChange={handleSelectAll}
                                    checked={results.length > 0 && selectedIds.length === results.length}
                                />
                            </th>
                            <th>Student</th>
                            <th>Index No</th>
                            <th>Term</th>
                            <th>Grade</th>
                            <th>Subject</th>
                            <th>Marks</th>
                            <th>Result</th>
                            <th>Rank</th>
                            <th>Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {results.length === 0 ? (
                            <tr>
                                <td colSpan="11" className="aer-empty">
                                    No exam results found. Click "Add Result" or "Import File" to get started.
                                </td>
                            </tr>
                        ) : (
                            results.map(r => (
                                <tr key={r.id} className={selectedIds.includes(r.id) ? 'selected' : ''}>
                                    <td>
                                        <input
                                            type="checkbox"
                                            checked={selectedIds.includes(r.id)}
                                            onChange={() => handleSelect(r.id)}
                                        />
                                    </td>
                                    <td className="td-name">{r.student_name}</td>
                                    <td className="td-index">{r.index_no}</td>
                                    <td>{r.term}</td>
                                    <td>{r.grade}</td>
                                    <td className="td-subject">{r.subject}</td>
                                    <td className="td-marks">{r.marks ?? '-'}</td>
                                    <td>
                                        <span className={`aer-grade-pill ${gradeClass(r.result_grade)}`}>
                                            {r.result_grade || '-'}
                                        </span>
                                    </td>
                                    <td>{r.rank || '-'}</td>
                                    <td>{r.year || '-'}</td>
                                    <td>
                                        <div className="aer-actions">
                                            <button className="aer-icon-btn edit" onClick={() => openEditModal(r)} title="Edit">
                                                <FiEdit2 />
                                            </button>
                                            <button className="aer-icon-btn delete" onClick={() => handleDeleteSingle(r.id)} title="Delete">
                                                <FiTrash2 />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {pagination.total > 0 && (
                <div className="aer-pagination-info">
                    Showing {results.length} of {pagination.total} results
                </div>
            )}

            {/* ══════ ADD/EDIT MODAL ══════ */}
            {showFormModal && (
                <div className="aer-modal-overlay" onClick={() => setShowFormModal(false)}>
                    <div className="aer-modal" onClick={e => e.stopPropagation()}>
                        <div className="aer-modal-header">
                            <h3>{editingId ? '✏️ Edit Result' : '➕ Add New Result'}</h3>
                            <button className="aer-modal-close" onClick={() => setShowFormModal(false)}><FiX /></button>
                        </div>
                        <form onSubmit={handleFormSubmit} className="aer-modal-body">
                            <div className="aer-form-grid">
                                <div className="aer-form-group">
                                    <label>Student Name *</label>
                                    <input type="text" value={formData.student_name} onChange={e => setFormData({ ...formData, student_name: e.target.value })} required />
                                </div>
                                <div className="aer-form-group">
                                    <label>Index Number *</label>
                                    <input type="text" value={formData.index_no} onChange={e => setFormData({ ...formData, index_no: e.target.value })} required />
                                </div>
                                <div className="aer-form-group">
                                    <label>Term *</label>
                                    <select value={formData.term} onChange={e => setFormData({ ...formData, term: e.target.value })} required>
                                        <option value="">Select Term</option>
                                        {terms.map(t => <option key={t.id} value={t.name}>{t.name}</option>)}
                                    </select>
                                </div>
                                <div className="aer-form-group">
                                    <label>Grade *</label>
                                    <select value={formData.grade} onChange={e => setFormData({ ...formData, grade: e.target.value })} required>
                                        <option value="">Select Grade</option>
                                        {DEFAULT_GRADES.map(g => <option key={g} value={g}>{g}</option>)}
                                    </select>
                                </div>
                                <div className="aer-form-group">
                                    <label>Subject *</label>
                                    <input type="text" value={formData.subject} onChange={e => setFormData({ ...formData, subject: e.target.value })} required />
                                </div>
                                <div className="aer-form-group">
                                    <label>Marks</label>
                                    <input type="number" min="0" max="100" step="0.01" value={formData.marks} onChange={e => setFormData({ ...formData, marks: e.target.value })} />
                                </div>
                                <div className="aer-form-group">
                                    <label>Result Grade</label>
                                    <select value={formData.result_grade} onChange={e => setFormData({ ...formData, result_grade: e.target.value })}>
                                        <option value="">Select Grade</option>
                                        {RESULT_GRADES.map(g => <option key={g} value={g}>{g}</option>)}
                                    </select>
                                </div>
                                <div className="aer-form-group">
                                    <label>Rank</label>
                                    <input type="number" min="1" value={formData.rank} onChange={e => setFormData({ ...formData, rank: e.target.value })} />
                                </div>
                                <div className="aer-form-group">
                                    <label>Year</label>
                                    <input type="text" value={formData.year} onChange={e => setFormData({ ...formData, year: e.target.value })} placeholder="2026" />
                                </div>
                            </div>

                            {formError && <div className="aer-form-error"><FiAlertCircle /> {formError}</div>}

                            <div className="aer-modal-footer">
                                <button type="button" className="aer-btn secondary" onClick={() => setShowFormModal(false)}>Cancel</button>
                                <button type="submit" className="aer-btn primary" disabled={formLoading}>
                                    {formLoading ? 'Saving...' : <><FiCheck /> {editingId ? 'Update' : 'Add Result'}</>}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* ══════ IMPORT MODAL ══════ */}
            {showImportModal && (
                <div className="aer-modal-overlay" onClick={() => setShowImportModal(false)}>
                    <div className="aer-modal" onClick={e => e.stopPropagation()}>
                        <div className="aer-modal-header">
                            <h3>📤 Import Results from File</h3>
                            <button className="aer-modal-close" onClick={() => setShowImportModal(false)}><FiX /></button>
                        </div>
                        <div className="aer-modal-body">
                            <div className="import-info-box">
                                <h4>📋 Expected File Format</h4>
                                <p>Excel/CSV file must have these column headers:</p>
                                <code>student_name, index_no, subject, marks, result_grade, rank</code>
                                <p className="import-note">Term, Grade, and Year will be applied to all imported rows from the fields below.</p>
                            </div>

                            <div className="aer-form-grid">
                                <div className="aer-form-group full-width">
                                    <label>Choose File (Excel/CSV) *</label>
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept=".xlsx,.xls,.csv"
                                        onChange={e => setImportFile(e.target.files[0])}
                                        className="file-input"
                                    />
                                </div>
                                <div className="aer-form-group">
                                    <label>Term *</label>
                                    <select value={importTerm} onChange={e => setImportTerm(e.target.value)}>
                                        <option value="">Select Term</option>
                                        {terms.map(t => <option key={t.id} value={t.name}>{t.name}</option>)}
                                    </select>
                                </div>
                                <div className="aer-form-group">
                                    <label>Grade *</label>
                                    <select value={importGrade} onChange={e => setImportGrade(e.target.value)}>
                                        <option value="">Select Grade</option>
                                        {DEFAULT_GRADES.map(g => <option key={g} value={g}>{g}</option>)}
                                    </select>
                                </div>
                                <div className="aer-form-group">
                                    <label>Year</label>
                                    <input type="text" value={importYear} onChange={e => setImportYear(e.target.value)} placeholder="2026" />
                                </div>
                            </div>

                            {importResult && (
                                <div className={`import-result ${importResult.error ? 'error' : 'success'}`}>
                                    {importResult.error ? (
                                        <><FiAlertCircle /> {importResult.error}</>
                                    ) : (
                                        <>
                                            <FiCheck /> {importResult.success}
                                            {importResult.errors?.length > 0 && (
                                                <div className="import-warnings">
                                                    <strong>Warnings:</strong>
                                                    {importResult.errors.map((e, i) => <div key={i}>• {e}</div>)}
                                                </div>
                                            )}
                                        </>
                                    )}
                                </div>
                            )}

                            <div className="aer-modal-footer">
                                <button type="button" className="aer-btn secondary" onClick={() => setShowImportModal(false)}>Cancel</button>
                                <button className="aer-btn primary" onClick={handleImport} disabled={importLoading}>
                                    {importLoading ? 'Importing...' : <><FiUpload /> Import</>}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* ══════ TERMS MANAGEMENT MODAL ══════ */}
            {showTermsModal && (
                <div className="aer-modal-overlay" onClick={() => setShowTermsModal(false)}>
                    <div className="aer-modal modal-sm" onClick={e => e.stopPropagation()}>
                        <div className="aer-modal-header">
                            <h3>🏷️ Manage Exam Terms</h3>
                            <button className="aer-modal-close" onClick={() => setShowTermsModal(false)}><FiX /></button>
                        </div>
                        <div className="aer-modal-body">
                            <div className="terms-add-row">
                                <input
                                    type="text"
                                    value={newTermName}
                                    onChange={e => setNewTermName(e.target.value)}
                                    placeholder="e.g. Mid-Term, Final..."
                                    onKeyDown={e => e.key === 'Enter' && handleAddTerm()}
                                />
                                <button className="aer-btn primary" onClick={handleAddTerm} disabled={termsLoading}>
                                    <FiPlus /> Add
                                </button>
                            </div>

                            <div className="terms-list">
                                {terms.length === 0 ? (
                                    <p className="terms-empty">No terms added yet</p>
                                ) : (
                                    terms.map(t => (
                                        <div key={t.id} className="term-item">
                                            <span className="term-name">{t.name}</span>
                                            <button className="aer-icon-btn delete" onClick={() => handleDeleteTerm(t.id)} title="Delete">
                                                <FiTrash2 />
                                            </button>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    )
}
