import React, { useState, useEffect, useRef } from 'react'
import { FiSearch, FiPrinter, FiAward, FiHash, FiBookOpen, FiCalendar, FiChevronDown } from 'react-icons/fi'
import { searchExamResults, getExamTerms, getExamGrades, getExamYears } from '../services/dashboardService'
import { useLanguage } from '../context/LanguageContext'
import { useSettings } from '../context/SettingsContext'
import './ExamResultsPage.css'

export default function ExamResultsPage() {
    const { t } = useLanguage()
    const { getSetting } = useSettings()

    // Search state
    const [term, setTerm] = useState('')
    const [grade, setGrade] = useState('')
    const [indexNo, setIndexNo] = useState('')
    const [year, setYear] = useState('')
    
    // Data state
    const [terms, setTerms] = useState([])
    const [grades, setGrades] = useState([])
    const [years, setYears] = useState([])
    const [results, setResults] = useState(null)
    const [summary, setSummary] = useState(null)
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState('')
    const [hasSearched, setHasSearched] = useState(false)
    
    const resultsRef = useRef(null)

    // Load filter options
    useEffect(() => {
        const loadFilterData = async () => {
            try {
                const [termsRes, gradesRes, yearsRes] = await Promise.all([
                    getExamTerms(),
                    getExamGrades(),
                    getExamYears(),
                ])
                setTerms(termsRes.data || [])
                setGrades(gradesRes.data || [])
                setYears(yearsRes.data || [])
            } catch (err) {
                console.error('Error loading filter data:', err)
            }
        }
        loadFilterData()
    }, [])

    const handleSearch = async (e) => {
        e.preventDefault()
        if (!term || !grade || !indexNo) {
            setError('Please fill Term, Grade, and Index Number')
            return
        }

        setLoading(true)
        setError('')
        setResults(null)
        setSummary(null)
        setHasSearched(true)

        try {
            const params = { term, grade, index_no: indexNo }
            if (year) params.year = year

            const res = await searchExamResults(params)
            setResults(res.data || [])
            setSummary(res.summary || null)

            // Scroll to results
            setTimeout(() => {
                resultsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })
            }, 200)
        } catch (err) {
            setError(err.message || 'No results found')
        } finally {
            setLoading(false)
        }
    }

    const handlePrint = () => {
        window.print()
    }

    const getGradeColor = (grade) => {
        if (!grade) return ''
        const g = grade.toUpperCase()
        if (g === 'A+' || g === 'A') return 'grade-excellent'
        if (g === 'A-' || g === 'B+' || g === 'B') return 'grade-good'
        if (g === 'B-' || g === 'C+' || g === 'C') return 'grade-average'
        if (g === 'S') return 'grade-simple'
        if (g === 'W' || g === 'F') return 'grade-fail'
        return ''
    }

    return (
        <div className="exam-results-page">
            {/* Hero Section */}
            <section className="exam-hero">
                <div className="exam-hero-bg">
                    <div className="hero-orb hero-orb-1"></div>
                    <div className="hero-orb hero-orb-2"></div>
                    <div className="hero-orb hero-orb-3"></div>
                </div>
                <div className="exam-hero-content">
                    <div className="hero-badge">
                        <FiAward /> {t('exam_results_badge') || 'Student Portal'}
                    </div>
                    <h1>{t('exam_results_title') || 'Exam Results'}</h1>
                    <p>{t('exam_results_subtitle') || 'Search and view your examination results instantly'}</p>
                </div>
            </section>

            {/* Search Section */}
            <section className="exam-search-section">
                <div className="search-card">
                    <div className="search-card-header">
                        <FiSearch className="search-header-icon" />
                        <h2>{t('exam_search_title') || 'Search Your Results'}</h2>
                    </div>

                    <form onSubmit={handleSearch} className="search-form">
                        <div className="search-grid">
                            <div className="form-group">
                                <label>
                                    <FiCalendar /> {t('exam_term') || 'Term'}
                                </label>
                                <div className="select-wrapper">
                                    <select value={term} onChange={e => setTerm(e.target.value)} required>
                                        <option value="">{t('exam_select_term') || '-- Select Term --'}</option>
                                        {terms.map((t, i) => (
                                            <option key={i} value={t}>{t}</option>
                                        ))}
                                    </select>
                                    <FiChevronDown className="select-icon" />
                                </div>
                            </div>

                            <div className="form-group">
                                <label>
                                    <FiBookOpen /> {t('exam_grade') || 'Grade'}
                                </label>
                                <div className="select-wrapper">
                                    <select value={grade} onChange={e => setGrade(e.target.value)} required>
                                        <option value="">{t('exam_select_grade') || '-- Select Grade --'}</option>
                                        {grades.map((g, i) => (
                                            <option key={i} value={g}>{g}</option>
                                        ))}
                                    </select>
                                    <FiChevronDown className="select-icon" />
                                </div>
                            </div>

                            <div className="form-group">
                                <label>
                                    <FiHash /> {t('exam_index_no') || 'Index Number'}
                                </label>
                                <input
                                    type="text"
                                    value={indexNo}
                                    onChange={e => setIndexNo(e.target.value)}
                                    placeholder={t('exam_enter_index') || 'Enter your index number'}
                                    required
                                />
                            </div>

                            <div className="form-group">
                                <label>
                                    <FiCalendar /> {t('exam_year') || 'Year'} <span className="optional">(Optional)</span>
                                </label>
                                <div className="select-wrapper">
                                    <select value={year} onChange={e => setYear(e.target.value)}>
                                        <option value="">{t('exam_all_years') || 'All Years'}</option>
                                        {years.map((y, i) => (
                                            <option key={i} value={y}>{y}</option>
                                        ))}
                                    </select>
                                    <FiChevronDown className="select-icon" />
                                </div>
                            </div>
                        </div>

                        {error && <div className="search-error">{error}</div>}

                        <button type="submit" className="search-submit-btn" disabled={loading}>
                            {loading ? (
                                <><span className="spinner"></span> Searching...</>
                            ) : (
                                <><FiSearch /> {t('exam_search_btn') || 'Search Results'}</>
                            )}
                        </button>
                    </form>
                </div>
            </section>

            {/* Results Section */}
            <section className="exam-results-section" ref={resultsRef}>
                {loading && (
                    <div className="results-loading">
                        <div className="loading-pulse"></div>
                        <p>Searching for results...</p>
                    </div>
                )}

                {!loading && hasSearched && !results && error && (
                    <div className="no-results-card">
                        <div className="no-results-icon">📭</div>
                        <h3>{t('exam_no_results') || 'No Results Found'}</h3>
                        <p>{error}</p>
                    </div>
                )}

                {results && results.length > 0 && summary && (
                    <div className="results-container">
                        {/* Student Info Card */}
                        <div className="student-info-card">
                            <div className="student-info-header">
                                <div className="student-avatar-circle">
                                    {summary.student_name?.charAt(0)?.toUpperCase()}
                                </div>
                                <div className="student-details">
                                    <h2>{summary.student_name}</h2>
                                    <div className="student-meta">
                                        <span className="meta-badge index">
                                            <FiHash /> {summary.index_no}
                                        </span>
                                        <span className="meta-badge term">
                                            <FiCalendar /> {summary.term}
                                        </span>
                                        <span className="meta-badge grade">
                                            <FiBookOpen /> {summary.grade}
                                        </span>
                                        {summary.year && (
                                            <span className="meta-badge year">{summary.year}</span>
                                        )}
                                    </div>
                                </div>
                                <button className="print-btn" onClick={handlePrint} title="Print Results">
                                    <FiPrinter />
                                </button>
                            </div>
                        </div>

                        {/* Results Table */}
                        <div className="results-table-card">
                            <table className="results-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{t('exam_subject') || 'Subject'}</th>
                                        <th>{t('exam_marks') || 'Marks'}</th>
                                        <th>{t('exam_result_grade') || 'Grade'}</th>
                                        <th>{t('exam_rank') || 'Rank'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {results.map((r, idx) => (
                                        <tr key={r.id || idx} className="result-row">
                                            <td className="row-num">{idx + 1}</td>
                                            <td className="subject-name">{r.subject}</td>
                                            <td className="marks-cell">
                                                <div className="marks-bar-container">
                                                    <span className="marks-value">{r.marks ?? '-'}</span>
                                                    {r.marks !== null && (
                                                        <div className="marks-bar">
                                                            <div
                                                                className="marks-bar-fill"
                                                                style={{ width: `${Math.min(r.marks, 100)}%` }}
                                                            ></div>
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td>
                                                <span className={`grade-pill ${getGradeColor(r.result_grade)}`}>
                                                    {r.result_grade || '-'}
                                                </span>
                                            </td>
                                            <td className="rank-cell">{r.rank || '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Summary Card */}
                        <div className="summary-card">
                            <div className="summary-grid">
                                <div className="summary-item">
                                    <span className="summary-label">{t('exam_total_marks') || 'Total Marks'}</span>
                                    <span className="summary-value">{summary.total_marks}</span>
                                </div>
                                <div className="summary-item">
                                    <span className="summary-label">{t('exam_subjects') || 'Subjects'}</span>
                                    <span className="summary-value">{summary.subject_count}</span>
                                </div>
                                <div className="summary-item highlight">
                                    <span className="summary-label">{t('exam_average') || 'Average'}</span>
                                    <span className="summary-value">{summary.average}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </section>
        </div>
    )
}
