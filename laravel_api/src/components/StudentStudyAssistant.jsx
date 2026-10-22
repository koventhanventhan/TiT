import React, { useState, useEffect, useRef } from 'react'
import {
    FiSend,
    FiStopCircle,
    FiTrash2,
    FiThumbsUp,
    FiThumbsDown,
    FiBookOpen,
    FiAlertCircle,
    FiHeart,
    FiCheckCircle,
    FiHelpCircle,
    FiFileText,
    FiPlus,
    FiRefreshCw,
    FiCopy,
    FiCheck,
    FiClock,
    FiX,
    FiSidebar
} from 'react-icons/fi'
import { useLanguage } from '../context/LanguageContext'
import './StudentStudyAssistant.css'

const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'

/**
 * 100% Safe Markdown Formatter without dangerouslySetInnerHTML.
 * Parses headings, bullet points, numbered lists, blockquotes, code blocks,
 * bold/italic spans, and styled citation references [1], [2].
 */
function SafeMarkdownRenderer({ text }) {
    if (!text) return null

    const lines = text.split('\n')
    const elements = []
    let inCodeBlock = false
    let codeBuffer = []
    let listBuffer = []
    let listType = null // 'ul' | 'ol'

    const flushList = (key) => {
        if (listBuffer.length === 0) return
        if (listType === 'ol') {
            elements.push(
                <ol key={`ol-${key}`} className="ai-markdown-ol">
                    {listBuffer.map((item, i) => (
                        <li key={i}>{renderInlineSpans(item)}</li>
                    ))}
                </ol>
            )
        } else {
            elements.push(
                <ul key={`ul-${key}`} className="ai-markdown-ul">
                    {listBuffer.map((item, i) => (
                        <li key={i}>{renderInlineSpans(item)}</li>
                    ))}
                </ul>
            )
        }
        listBuffer = []
        listType = null
    }

    const renderInlineSpans = (raw) => {
        // Tokenize citations like [1], [2] or bold **bold** or inline code `code`
        const parts = []
        let lastIndex = 0
        const regex = /(\[([0-9]+)\])|(\*\*([^*]+)\*\*)|(\*([^*]+)\*)|(`([^`]+)`)/g
        let match

        while ((match = regex.exec(raw)) !== null) {
            if (match.index > lastIndex) {
                parts.push(raw.slice(lastIndex, match.index))
            }

            if (match[1]) {
                const num = match[2]
                parts.push(
                    <span key={match.index} className="citation-badge-inline" title={`Citation [${num}]`}>
                        [{num}]
                    </span>
                )
            } else if (match[3]) {
                parts.push(<strong key={match.index}>{match[4]}</strong>)
            } else if (match[5]) {
                parts.push(<em key={match.index}>{match[6]}</em>)
            } else if (match[7]) {
                parts.push(<code key={match.index} className="inline-code">{match[8]}</code>)
            }

            lastIndex = regex.lastIndex
        }

        if (lastIndex < raw.length) {
            parts.push(raw.slice(lastIndex))
        }

        return parts.length > 0 ? parts : raw
    }

    lines.forEach((line, idx) => {
        const trimmed = line.trim()

        if (trimmed.startsWith('```')) {
            if (inCodeBlock) {
                elements.push(
                    <pre key={`code-${idx}`} className="ai-code-block">
                        <code>{codeBuffer.join('\n')}</code>
                    </pre>
                )
                codeBuffer = []
                inCodeBlock = false
            } else {
                flushList(idx)
                inCodeBlock = true
            }
            return
        }

        if (inCodeBlock) {
            codeBuffer.push(line)
            return
        }

        if (trimmed.startsWith('- ') || trimmed.startsWith('* ')) {
            if (listType !== 'ul') flushList(idx)
            listType = 'ul'
            listBuffer.push(trimmed.slice(2))
            return
        }

        const numMatch = trimmed.match(/^([0-9]+)\.\s+(.*)$/)
        if (numMatch) {
            if (listType !== 'ol') flushList(idx)
            listType = 'ol'
            listBuffer.push(numMatch[2])
            return
        }

        flushList(idx)

        if (trimmed === '') {
            elements.push(<div key={`empty-${idx}`} className="ai-paragraph-break" />)
            return
        }

        if (trimmed.startsWith('>')) {
            elements.push(
                <blockquote key={`quote-${idx}`} className="ai-blockquote">
                    {renderInlineSpans(trimmed.replace(/^>\s*/, ''))}
                </blockquote>
            )
            return
        }

        if (trimmed.startsWith('### ')) {
            elements.push(<h4 key={`h4-${idx}`} className="ai-heading-3">{renderInlineSpans(trimmed.slice(4))}</h4>)
            return
        }
        if (trimmed.startsWith('## ')) {
            elements.push(<h3 key={`h3-${idx}`} className="ai-heading-2">{renderInlineSpans(trimmed.slice(3))}</h3>)
            return
        }
        if (trimmed.startsWith('# ')) {
            elements.push(<h2 key={`h2-${idx}`} className="ai-heading-1">{renderInlineSpans(trimmed.slice(2))}</h2>)
            return
        }

        elements.push(
            <p key={`p-${idx}`} className="ai-paragraph">
                {renderInlineSpans(line)}
            </p>
        )
    })

    flushList('final')
    if (codeBuffer.length > 0) {
        elements.push(
            <pre key="code-final" className="ai-code-block">
                <code>{codeBuffer.join('\n')}</code>
            </pre>
        )
    }

    return <div className="ai-markdown-body">{elements}</div>
}

export default function StudentStudyAssistant({ user: userProp }) {
    const { t } = useLanguage()
    const currentUser = userProp || JSON.parse(localStorage.getItem('user') || '{}')
    const isRagDisabled = currentUser?.rag_enabled === false

    const [messages, setMessages] = useState(() => {
        try {
            const saved = sessionStorage.getItem('study_assistant_chat_history')
            return saved ? JSON.parse(saved) : []
        } catch (_) {
            return []
        }
    })

    const [question, setQuestion] = useState('')
    const [lastQuestion, setLastQuestion] = useState('')
    const [selectedSubject, setSelectedSubject] = useState('')
    const [isStreaming, setIsStreaming] = useState(false)
    const [streamedText, setStreamedText] = useState('')
    const [streamError, setStreamError] = useState(null)
    const [feedbackMap, setFeedbackMap] = useState({})
    const [copiedId, setCopiedId] = useState(null)

    // Daily usage counter state
    const [usageStats, setUsageStats] = useState({ used_today: 0, daily_limit: 30, remaining: 30 })

    // Conversation history drawer state
    const [isHistoryOpen, setIsHistoryOpen] = useState(false)
    const [historyList, setHistoryList] = useState([])
    const [loadingHistory, setLoadingHistory] = useState(false)

    // First-time notice banner state
    const [isNoticeDismissed, setIsNoticeDismissed] = useState(() => {
        return localStorage.getItem('study_assistant_notice_dismissed') === 'true'
    })

    const abortControllerRef = useRef(null)
    const chatEndRef = useRef(null)
    const inputRef = useRef(null)

    // Save message history to session
    useEffect(() => {
        try {
            sessionStorage.setItem('study_assistant_chat_history', JSON.stringify(messages))
        } catch (_) {}
    }, [messages])

    // Auto-scroll to bottom
    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: 'smooth' })
    }, [messages, streamedText])

    // Load daily usage stats
    const fetchUsage = async () => {
        const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken')
        if (!token) return
        try {
            const res = await fetch(`${API_BASE_URL}/student/ai-chat/usage`, {
                headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
            })
            if (res.ok) {
                const data = await res.json()
                setUsageStats(data)
            }
        } catch (_) {}
    }

    // Load past question history
    const fetchHistory = async () => {
        const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken')
        if (!token) return
        setLoadingHistory(true)
        try {
            const res = await fetch(`${API_BASE_URL}/student/ai-chat/history`, {
                headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
            })
            if (res.ok) {
                const data = await res.json()
                setHistoryList(Array.isArray(data) ? data : [])
            }
        } catch (_) {
            setHistoryList([])
        } finally {
            setLoadingHistory(false)
        }
    }

    useEffect(() => {
        if (!isRagDisabled) {
            fetchUsage()
        }
    }, [isRagDisabled])

    const availableSubjects = [
        'Science',
        'Mathematics',
        'English',
        'Tamil',
        'Sinhala',
        'History',
        'Geography',
        'Civics',
        'Health & Physical Education',
        'ICT',
        'Commerce',
        'Business Studies',
        'Accounting',
        'Economics',
        'Physics',
        'Chemistry',
        'Biology',
        'Combined Mathematics',
    ]

    const handleDismissNotice = () => {
        setIsNoticeDismissed(true)
        localStorage.setItem('study_assistant_notice_dismissed', 'true')
    }

    const handleNewChat = () => {
        if (isStreaming) {
            handleStop()
        }
        setMessages([])
        setStreamedText('')
        setStreamError(null)
        sessionStorage.removeItem('study_assistant_chat_history')
        if (inputRef.current) {
            inputRef.current.focus()
        }
    }

    const handleClearChat = () => {
        handleNewChat()
    }

    const handleStop = () => {
        if (abortControllerRef.current) {
            abortControllerRef.current.abort()
            abortControllerRef.current = null
        }
        setIsStreaming(false)
    }

    const handleCopyAnswer = async (id, text) => {
        if (!text) return
        try {
            await navigator.clipboard.writeText(text)
            setCopiedId(id)
            setTimeout(() => setCopiedId(null), 2000)
        } catch (err) {
            console.error('Copy failed:', err)
        }
    }

    const handleRetry = () => {
        if (lastQuestion) {
            setQuestion(lastQuestion)
            setStreamError(null)
            handleSubmit(null, lastQuestion)
        }
    }

    const handleSelectHistoryItem = (item) => {
        setIsHistoryOpen(false)
        // Add user question and assistant answer to messages feed
        const uMsg = {
            id: 'h_u_' + item.id,
            sender: 'user',
            text: item.question,
            subject: item.subject,
            timestamp: new Date(item.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        }
        const aMsg = {
            id: 'h_a_' + item.id,
            queryId: item.id,
            sender: 'assistant',
            text: item.answer,
            citations: [],
            sources: [],
            status: item.status || 'answered',
            timestamp: new Date(item.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        }
        setMessages([uMsg, aMsg])
    }

    const handleFeedback = async (queryId, rating) => {
        if (!queryId || feedbackMap[queryId]) return

        const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken')
        if (!token) return

        try {
            setFeedbackMap(prev => ({ ...prev, [queryId]: rating }))
            const res = await fetch(`${API_BASE_URL}/student/ai-chat/${queryId}/feedback`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ rating }),
            })

            if (!res.ok) {
                console.warn('Feedback request failed with status:', res.status)
            }
        } catch (err) {
            console.error('Error recording feedback:', err)
        }
    }

    const handleSubmit = async (e, overrideQuestion = null) => {
        e?.preventDefault()
        const textToSubmit = overrideQuestion || question
        const trimmed = textToSubmit.trim()

        if (trimmed.length < 3) {
            setStreamError('Question must be at least 3 characters long.')
            return
        }
        if (trimmed.length > 500) {
            setStreamError('Question exceeds 500 characters limit.')
            return
        }
        if (isStreaming) return

        setLastQuestion(trimmed)
        setStreamError(null)
        const userMsg = {
            id: 'u_' + Date.now(),
            sender: 'user',
            text: trimmed,
            subject: selectedSubject || null,
            timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        }

        setMessages(prev => [...prev, userMsg])
        setQuestion('')
        setIsStreaming(true)
        setStreamedText('')

        const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken')
        const controller = new AbortController()
        abortControllerRef.current = controller

        let accumulatedAnswer = ''
        let queryData = null

        try {
            const response = await fetch(`${API_BASE_URL}/student/ai-chat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'text/event-stream',
                },
                body: JSON.stringify({
                    question: trimmed,
                    subject: selectedSubject || undefined,
                }),
                signal: controller.signal,
            })

            if (!response.ok) {
                let errorMsg = null
                try {
                    const errJson = await response.json()
                    errorMsg = errJson.message
                } catch (_) {}

                if (!errorMsg) {
                    if (response.status === 403) {
                        errorMsg = t('study_assistant_error_forbidden', 'Access is not permitted for your account or institute.')
                    } else if (response.status === 429) {
                        errorMsg = t('study_assistant_error_daily_limit', 'You have reached your daily question limit. Please continue tomorrow.')
                    } else if (response.status === 503) {
                        errorMsg = t('study_assistant_error_unavailable', 'The Study Assistant is temporarily unavailable. Please try again later.')
                    } else {
                        errorMsg = t('study_assistant_error_generic', 'An error occurred while connecting to the assistant. Please try again.')
                    }
                }

                throw new Error(errorMsg)
            }

            const reader = response.body.getReader()
            const decoder = new TextDecoder('utf-8')
            let buffer = ''

            while (true) {
                const { done, value } = await reader.read()
                if (done) break

                buffer += decoder.decode(value, { stream: true })
                const lines = buffer.split('\n')
                buffer = lines.pop()

                let currentEvent = 'message'

                for (let i = 0; i < lines.length; i++) {
                    const line = lines[i].trim()
                    if (!line) continue

                    if (line.startsWith('event:')) {
                        currentEvent = line.replace('event:', '').trim()
                    } else if (line.startsWith('data:')) {
                        const dataStr = line.replace('data:', '').trim()
                        try {
                            const parsed = JSON.parse(dataStr)

                            if (currentEvent === 'message' && parsed.text) {
                                accumulatedAnswer += parsed.text
                                setStreamedText(accumulatedAnswer)
                            } else if (currentEvent === 'end') {
                                queryData = parsed
                            } else if (currentEvent === 'error') {
                                throw new Error(parsed.message || 'An error occurred generating the answer.')
                            }
                        } catch (err) {
                            if (currentEvent === 'error') throw err
                        }
                    }
                }
            }

            // Save completed assistant message
            const assistantMsg = {
                id: 'a_' + Date.now(),
                queryId: queryData?.query_id || null,
                sender: 'assistant',
                text: accumulatedAnswer,
                citations: queryData?.citations || [],
                sources: queryData?.sources || [],
                status: queryData?.status || 'answered',
                timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            }

            setMessages(prev => [...prev, assistantMsg])
            setStreamedText('')
            // Refresh usage stats
            fetchUsage()
        } catch (err) {
            if (err.name === 'AbortError') {
                if (accumulatedAnswer) {
                    setMessages(prev => [
                        ...prev,
                        {
                            id: 'a_' + Date.now(),
                            queryId: null,
                            sender: 'assistant',
                            text: accumulatedAnswer + '\n\n*(Stopped)*',
                            citations: [],
                            sources: [],
                            status: 'stopped',
                            timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                        },
                    ])
                }
            } else {
                setStreamError(err.message || 'Failed to reach AI Study Assistant.')
            }
        } finally {
            setIsStreaming(false)
            setStreamedText('')
            abortControllerRef.current = null
        }
    }

    if (isRagDisabled) {
        return (
            <div className="study-assistant-container" style={{ padding: '2rem', textAlign: 'center' }}>
                <div style={{ maxWidth: 500, margin: '4rem auto', background: '#fff', borderRadius: 12, padding: '2.5rem 2rem', boxShadow: '0 4px 12px rgba(0,0,0,0.06)' }}>
                    <FiHelpCircle style={{ fontSize: '3rem', color: '#94a3b8', marginBottom: '1rem' }} />
                    <h2 style={{ fontSize: '1.25rem', fontWeight: 600, color: '#1e293b', marginBottom: '0.5rem' }}>
                        Study Assistant Disabled
                    </h2>
                    <p style={{ color: '#64748b', fontSize: '0.95rem', lineHeight: 1.5 }}>
                        The AI Study Assistant is currently not enabled for your institute. Please contact your institute administrator or teacher for more details.
                    </p>
                </div>
            </div>
        )
    }

    return (
        <div className="study-assistant-container">
            {/* Header bar */}
            <header className="assistant-header-bar">
                <div className="assistant-title-group">
                    <div className="assistant-avatar-badge" aria-hidden="true">
                        <FiHelpCircle className="assistant-icon" />
                    </div>
                    <div>
                        <h2 className="assistant-title">
                            {t('study_assistant_title', 'AI Study Assistant')}
                        </h2>
                        <p className="assistant-subtitle">
                            {t('study_assistant_subtitle', 'Ask questions strictly answered from your notes and learning materials')}
                        </p>
                    </div>
                </div>

                <div className="assistant-actions-group">
                    {/* Daily Usage Counter Pill */}
                    <div
                        className="daily-usage-pill"
                        title={t('study_assistant_daily_usage', 'Daily questions used: {used} / {limit}')
                            .replace('{used}', usageStats.used_today)
                            .replace('{limit}', usageStats.daily_limit)}
                        aria-label={`Questions used today: ${usageStats.used_today} of ${usageStats.daily_limit}`}
                    >
                        <FiClock size={13} style={{ marginRight: 5 }} />
                        <span>
                            {t('study_assistant_daily_usage', 'Daily: {used} / {limit}')
                                .replace('{used}', usageStats.used_today)
                                .replace('{limit}', usageStats.daily_limit)}
                        </span>
                    </div>

                    {/* Subject Filter Dropdown */}
                    <div className="subject-select-wrapper">
                        <select
                            id="rag-subject-dropdown"
                            className="subject-select"
                            value={selectedSubject}
                            onChange={(e) => setSelectedSubject(e.target.value)}
                            disabled={isStreaming}
                            aria-label="Filter notes by subject"
                        >
                            <option value="">{t('study_assistant_all_subjects', 'All Subjects')}</option>
                            {availableSubjects.map((sub) => (
                                <option key={sub} value={sub}>{sub}</option>
                            ))}
                        </select>
                    </div>

                    {/* History Drawer Toggle Button */}
                    <button
                        type="button"
                        id="rag-history-toggle-btn"
                        className="btn-header-action"
                        onClick={() => {
                            if (!isHistoryOpen) fetchHistory()
                            setIsHistoryOpen(!isHistoryOpen)
                        }}
                        title={t('study_assistant_history_toggle', 'History')}
                        aria-label="View question history"
                    >
                        <FiSidebar /> <span>{t('study_assistant_history_toggle', 'History')}</span>
                    </button>

                    {/* New Chat Button */}
                    <button
                        type="button"
                        id="rag-new-chat-btn"
                        className="btn-header-action btn-new-chat"
                        onClick={handleNewChat}
                        title={t('study_assistant_new_chat', 'New Chat')}
                        aria-label="Start a new chat session"
                    >
                        <FiPlus /> <span>{t('study_assistant_new_chat', 'New Chat')}</span>
                    </button>

                    {/* Clear Chat Button */}
                    <button
                        type="button"
                        id="rag-clear-chat-btn"
                        className="btn-header-action"
                        onClick={handleClearChat}
                        title={t('study_assistant_clear_btn', 'Clear Chat')}
                        disabled={messages.length === 0 && !streamedText}
                        aria-label="Clear chat messages"
                    >
                        <FiTrash2 /> <span>{t('study_assistant_clear_btn', 'Clear')}</span>
                    </button>
                </div>
            </header>

            {/* First-Time Notice Banner */}
            {!isNoticeDismissed && (
                <div className="assistant-first-time-notice" role="note" aria-label="First-time guidance notice">
                    <FiAlertCircle className="notice-icon" aria-hidden="true" />
                    <span className="notice-text">
                        {t('study_assistant_first_time_notice', "AI answers may contain mistakes; it only uses your teacher's notes.")}
                    </span>
                    <button
                        type="button"
                        onClick={handleDismissNotice}
                        className="notice-dismiss-btn"
                        aria-label="Dismiss first-time notice"
                    >
                        <FiX size={16} />
                    </button>
                </div>
            )}

            {/* Main Chat Layout (with optional history drawer) */}
            <div className="assistant-main-layout">
                {/* Chat Messages Feed */}
                <div
                    className="chat-messages-scroll"
                    id="rag-chat-messages-container"
                    role="log"
                    aria-live="polite"
                    aria-atomic="false"
                    aria-label="Chat messages history"
                >
                    {messages.length === 0 && !streamedText && (
                        <div className="chat-welcome-card">
                            <div className="welcome-icon-box" aria-hidden="true">
                                <FiBookOpen />
                            </div>
                            <h3>{t('study_assistant_title', 'AI Study Assistant')}</h3>
                            <p>
                                {t('study_assistant_subtitle', 'Ask questions strictly answered from your notes and learning materials')}
                            </p>
                            <div className="welcome-quick-prompts">
                                <button
                                    type="button"
                                    className="quick-prompt-chip"
                                    onClick={() => setQuestion('Explain the main steps of photosynthesis from my biology notes')}
                                    aria-label="Ask prompt: Explain photosynthesis"
                                >
                                    &ldquo;Explain the main steps of photosynthesis from my biology notes&rdquo;
                                </button>
                                <button
                                    type="button"
                                    className="quick-prompt-chip"
                                    onClick={() => setQuestion('What are the key formulas for kinematics?')}
                                    aria-label="Ask prompt: Kinematics formulas"
                                >
                                    &ldquo;What are the key formulas for kinematics?&rdquo;
                                </button>
                            </div>
                        </div>
                    )}

                    {messages.map((msg) => (
                        <div
                            key={msg.id}
                            className={`chat-bubble-row ${msg.sender === 'user' ? 'row-user' : 'row-assistant'}`}
                        >
                            <div className={`chat-bubble ${msg.sender === 'user' ? 'bubble-user' : 'bubble-assistant'}`}>
                                {msg.sender === 'user' ? (
                                    <div className="user-message-content">
                                        <div className="user-text">{msg.text}</div>
                                        <div className="bubble-meta">
                                            {msg.subject && <span className="bubble-subject-badge">{msg.subject}</span>}
                                            <span className="bubble-timestamp">{msg.timestamp}</span>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="assistant-message-content">
                                        {msg.status === 'distress_blocked' ? (
                                            <div className="distress-caring-card">
                                                <div className="distress-header">
                                                    <FiHeart className="heart-icon" aria-hidden="true" />
                                                    <h4>{t('study_assistant_distress_help', 'Support & Guidance')}</h4>
                                                </div>
                                                <p className="distress-text">{msg.text}</p>
                                            </div>
                                        ) : (
                                            <>
                                                <SafeMarkdownRenderer text={msg.text} />

                                                {/* Citations & Sources Chips */}
                                                {msg.sources && msg.sources.length > 0 && (
                                                    <div className="citations-container">
                                                        <div className="citations-title">
                                                            <FiFileText aria-hidden="true" /> <span>{t('study_assistant_sources', 'Sources & Citations')}:</span>
                                                        </div>
                                                        <div className="citations-chips-list">
                                                            {msg.sources.map((src, i) => (
                                                                <div
                                                                    key={i}
                                                                    className={`citation-chip ${(src.ocr_used || src.is_ocr) ? 'citation-chip-ocr' : ''}`}
                                                                    title={(src.ocr_used || src.is_ocr) ? `${src.material_title} (${t('study_assistant_ocr_notice', 'text read by AI from a scan, may contain errors')})` : `From material: ${src.material_title}`}
                                                                >
                                                                    <span className="chip-index">[{src.citation_number}]</span>
                                                                    <span className="chip-title">{src.material_title}</span>
                                                                    {src.page_number && (
                                                                        <span className="chip-page">
                                                                            ({t('study_assistant_page', 'Page')} {src.page_number})
                                                                        </span>
                                                                    )}
                                                                    {(src.ocr_used || src.is_ocr) && (
                                                                        <span className="chip-ocr-tag" style={{ marginLeft: '4px', fontSize: '0.7rem', opacity: 0.85, fontWeight: 600 }}>
                                                                            [{t('study_assistant_ocr_badge', 'Scan')}]
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            ))}
                                                        </div>
                                                        {msg.sources.some(s => s.ocr_used || s.is_ocr) && (
                                                            <div className="citations-ocr-notice" style={{ fontSize: '0.75rem', color: '#64748b', marginTop: '6px', fontStyle: 'italic' }}>
                                                                * {t('study_assistant_ocr_notice', 'text read by AI from a scan, may contain errors')}
                                                            </div>
                                                        )}
                                                    </div>
                                                )}

                                                {/* Action Bar: Copy Answer & Feedback */}
                                                <div className="assistant-action-bar">
                                                    <button
                                                        type="button"
                                                        className="btn-copy-answer"
                                                        onClick={() => handleCopyAnswer(msg.id, msg.text)}
                                                        title={copiedId === msg.id ? t('study_assistant_copied', 'Copied!') : t('study_assistant_copy', 'Copy Answer')}
                                                        aria-label="Copy answer to clipboard"
                                                    >
                                                        {copiedId === msg.id ? (
                                                            <>
                                                                <FiCheck className="text-success" />
                                                                <span>{t('study_assistant_copied', 'Copied!')}</span>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <FiCopy />
                                                                <span>{t('study_assistant_copy', 'Copy')}</span>
                                                            </>
                                                        )}
                                                    </button>

                                                    {msg.queryId && msg.status !== 'blocked_safety' && (
                                                        <div className="feedback-group">
                                                            <button
                                                                type="button"
                                                                className={`btn-feedback ${feedbackMap[msg.queryId] === 'up' ? 'active-up' : ''}`}
                                                                onClick={() => handleFeedback(msg.queryId, 'up')}
                                                                disabled={!!feedbackMap[msg.queryId]}
                                                                title={t('study_assistant_rated_up', 'Marked as helpful')}
                                                                aria-label="Mark answer as helpful"
                                                            >
                                                                <FiThumbsUp />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                className={`btn-feedback ${feedbackMap[msg.queryId] === 'down' ? 'active-down' : ''}`}
                                                                onClick={() => handleFeedback(msg.queryId, 'down')}
                                                                disabled={!!feedbackMap[msg.queryId]}
                                                                title={t('study_assistant_rated_down', 'Marked as unhelpful')}
                                                                aria-label="Mark answer as unhelpful"
                                                            >
                                                                <FiThumbsDown />
                                                            </button>
                                                            {feedbackMap[msg.queryId] && (
                                                                <span className="feedback-confirmed">
                                                                    <FiCheckCircle aria-hidden="true" />
                                                                    {feedbackMap[msg.queryId] === 'up'
                                                                        ? t('study_assistant_rated_up', 'Marked as helpful')
                                                                        : t('study_assistant_rated_down', 'Marked as unhelpful')}
                                                                </span>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            </>
                                        )}

                                        <div className="bubble-meta">
                                            <span className="bubble-timestamp">{msg.timestamp}</span>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}

                    {/* Currently Streaming Response */}
                    {isStreaming && (
                        <div className="chat-bubble-row row-assistant">
                            <div className="chat-bubble bubble-assistant streaming-active">
                                {streamedText ? (
                                    <SafeMarkdownRenderer text={streamedText} />
                                ) : (
                                    <div className="streaming-dots-container" aria-label="Loading answer">
                                        <div className="streaming-dots">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </div>
                                        <span className="loading-helper-text">
                                            {t('study_assistant_loading', 'Finding answers from your notes...')}
                                        </span>
                                    </div>
                                )}
                                <span className="typing-cursor" aria-hidden="true"></span>
                            </div>
                        </div>
                    )}

                    <div ref={chatEndRef} />
                </div>

                {/* Question History Drawer */}
                {isHistoryOpen && (
                    <aside className="assistant-history-drawer" aria-label="Recent questions list">
                        <div className="history-drawer-header">
                            <h4>{t('study_assistant_history', 'Recent Questions')}</h4>
                            <button
                                type="button"
                                className="history-close-btn"
                                onClick={() => setIsHistoryOpen(false)}
                                aria-label="Close history"
                            >
                                <FiX size={18} />
                            </button>
                        </div>
                        <div className="history-drawer-body">
                            {loadingHistory ? (
                                <div className="history-loading">
                                    <div className="history-spinner" />
                                </div>
                            ) : historyList.length === 0 ? (
                                <p className="history-empty">
                                    {t('study_assistant_no_history', 'No recent questions yet.')}
                                </p>
                            ) : (
                                <ul className="history-items-list">
                                    {historyList.map(item => (
                                        <li key={item.id}>
                                            <button
                                                type="button"
                                                className="history-item-btn"
                                                onClick={() => handleSelectHistoryItem(item)}
                                            >
                                                <div className="history-item-question">{item.question}</div>
                                                <div className="history-item-meta">
                                                    {item.subject && <span className="history-subject">{item.subject}</span>}
                                                    <span className="history-date">
                                                        {new Date(item.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
                                                    </span>
                                                </div>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </aside>
                )}
            </div>

            {/* Error Message Alert with Retry Button */}
            {streamError && (
                <div className="chat-error-banner" role="alert">
                    <FiAlertCircle aria-hidden="true" />
                    <span className="error-text">{streamError}</span>
                    {lastQuestion && (
                        <button
                            type="button"
                            onClick={handleRetry}
                            className="btn-retry-error"
                            title={t('study_assistant_retry', 'Retry')}
                            aria-label="Retry question"
                        >
                            <FiRefreshCw size={13} style={{ marginRight: 4 }} />
                            <span>{t('study_assistant_retry', 'Retry')}</span>
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={() => setStreamError(null)}
                        className="error-close-btn"
                        aria-label="Dismiss error"
                    >
                        &times;
                    </button>
                </div>
            )}

            {/* Input Bar */}
            <form onSubmit={handleSubmit} className="assistant-input-form">
                <div className="input-bar-container">
                    <textarea
                        ref={inputRef}
                        id="rag-question-input"
                        rows={1}
                        value={question}
                        onChange={(e) => setQuestion(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault()
                                handleSubmit()
                            }
                        }}
                        placeholder={t('study_assistant_placeholder', 'Ask a question from your notes (e.g., Explain photosynthesis)...')}
                        className="assistant-textarea"
                        disabled={isStreaming}
                        maxLength={500}
                        aria-label="Type your study question here"
                    />

                    <div className="input-controls-group">
                        <span className={`char-counter ${question.length > 450 ? 'near-limit' : ''}`} aria-hidden="true">
                            {question.length}/500
                        </span>

                        {isStreaming ? (
                            <button
                                type="button"
                                id="rag-stop-btn"
                                onClick={handleStop}
                                className="btn-stop-stream"
                                title={t('study_assistant_stop_btn', 'Stop')}
                                aria-label="Stop generating response"
                            >
                                <FiStopCircle /> <span>{t('study_assistant_stop_btn', 'Stop')}</span>
                            </button>
                        ) : (
                            <button
                                type="submit"
                                id="rag-submit-btn"
                                disabled={question.trim().length < 3 || isStreaming}
                                className="btn-send-question"
                                title={t('study_assistant_ask_btn', 'Ask')}
                                aria-label="Send study question"
                            >
                                <FiSend /> <span>{t('study_assistant_ask_btn', 'Ask')}</span>
                            </button>
                        )}
                    </div>
                </div>
            </form>
        </div>
    )
}
