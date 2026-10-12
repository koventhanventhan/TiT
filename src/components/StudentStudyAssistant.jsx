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
    FiFileText
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
                // Citation token [n]
                const num = match[2]
                parts.push(
                    <span key={match.index} className="citation-badge-inline" title={`Citation [${num}]`}>
                        [{num}]
                    </span>
                )
            } else if (match[3]) {
                // Bold **text**
                parts.push(<strong key={match.index}>{match[4]}</strong>)
            } else if (match[5]) {
                // Italic *text*
                parts.push(<em key={match.index}>{match[6]}</em>)
            } else if (match[7]) {
                // Inline code `code`
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

        // Handle code blocks
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

        // Handle Bullet Lists
        if (trimmed.startsWith('- ') || trimmed.startsWith('* ')) {
            if (listType !== 'ul') flushList(idx)
            listType = 'ul'
            listBuffer.push(trimmed.slice(2))
            return
        }

        // Handle Numbered Lists
        const numMatch = trimmed.match(/^([0-9]+)\.\s+(.*)$/)
        if (numMatch) {
            if (listType !== 'ol') flushList(idx)
            listType = 'ol'
            listBuffer.push(numMatch[2])
            return
        }

        // If not a list item, flush pending lists
        flushList(idx)

        if (trimmed === '') {
            elements.push(<div key={`empty-${idx}`} className="ai-paragraph-break" />)
            return
        }

        // Blockquotes
        if (trimmed.startsWith('>')) {
            elements.push(
                <blockquote key={`quote-${idx}`} className="ai-blockquote">
                    {renderInlineSpans(trimmed.replace(/^>\s*/, ''))}
                </blockquote>
            )
            return
        }

        // Headings
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

        // Regular paragraph
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

export default function StudentStudyAssistant({ user }) {
    const { language, t } = useLanguage()

    const [messages, setMessages] = useState(() => {
        try {
            const saved = sessionStorage.getItem('study_assistant_chat_history')
            return saved ? JSON.parse(saved) : []
        } catch (_) {
            return []
        }
    })

    const [question, setQuestion] = useState('')
    const [selectedSubject, setSelectedSubject] = useState('')
    const [isStreaming, setIsStreaming] = useState(false)
    const [streamedText, setStreamedText] = useState('')
    const [streamError, setStreamError] = useState(null)
    const [feedbackMap, setFeedbackMap] = useState({}) // { queryId: 'up' | 'down' }

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

    // Available subjects list
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

    const handleClearChat = () => {
        if (isStreaming) {
            handleStop()
        }
        setMessages([])
        setStreamedText('')
        setStreamError(null)
        sessionStorage.removeItem('study_assistant_chat_history')
    }

    const handleStop = () => {
        if (abortControllerRef.current) {
            abortControllerRef.current.abort()
            abortControllerRef.current = null
        }
        setIsStreaming(false)
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

    const handleSubmit = async (e) => {
        e?.preventDefault()
        const trimmed = question.trim()

        if (trimmed.length < 3) {
            setStreamError('Question must be at least 3 characters long.')
            return
        }
        if (trimmed.length > 500) {
            setStreamError('Question exceeds 500 characters limit.')
            return
        }
        if (isStreaming) return

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
                let errorMsg = `Server error (${response.status})`
                try {
                    const errJson = await response.json()
                    errorMsg = errJson.message || errorMsg
                } catch (_) {}

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
                buffer = lines.pop() // keep last partial line

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

    return (
        <div className="study-assistant-container">
            {/* Header bar */}
            <div className="assistant-header-bar">
                <div className="assistant-title-group">
                    <div className="assistant-avatar-badge">
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
                    {/* Subject Filter Dropdown */}
                    <div className="subject-select-wrapper">
                        <select
                            id="rag-subject-dropdown"
                            className="subject-select"
                            value={selectedSubject}
                            onChange={(e) => setSelectedSubject(e.target.value)}
                            disabled={isStreaming}
                        >
                            <option value="">{t('study_assistant_all_subjects', 'All Subjects')}</option>
                            {availableSubjects.map((sub) => (
                                <option key={sub} value={sub}>{sub}</option>
                            ))}
                        </select>
                    </div>

                    {/* Clear Chat Button */}
                    <button
                        type="button"
                        id="rag-clear-chat-btn"
                        className="btn-clear-chat"
                        onClick={handleClearChat}
                        title={t('study_assistant_clear_btn', 'Clear Chat')}
                        disabled={messages.length === 0 && !streamedText}
                    >
                        <FiTrash2 /> <span>{t('study_assistant_clear_btn', 'Clear Chat')}</span>
                    </button>
                </div>
            </div>

            {/* Disclaimer Alert */}
            <div className="assistant-disclaimer-banner">
                <FiAlertCircle className="disclaimer-icon" />
                <span>{t('study_assistant_disclaimer', 'AI answers may contain mistakes. Check with your teacher.')}</span>
            </div>

            {/* Chat Messages Feed */}
            <div className="chat-messages-scroll" id="rag-chat-messages-container">
                {messages.length === 0 && !streamedText && (
                    <div className="chat-welcome-card">
                        <div className="welcome-icon-box">
                            <FiBookOpen />
                        </div>
                        <h3>Welcome to your Study Assistant</h3>
                        <p>
                            Ask questions about your uploaded lessons, past papers, or homework notes.
                            The AI answers strictly from your teacher&apos;s verified materials.
                        </p>
                        <div className="welcome-quick-prompts">
                            <button
                                type="button"
                                className="quick-prompt-chip"
                                onClick={() => setQuestion('Explain the main steps of photosynthesis from my biology notes')}
                            >
                                💡 &ldquo;Explain the main steps of photosynthesis from my biology notes&rdquo;
                            </button>
                            <button
                                type="button"
                                className="quick-prompt-chip"
                                onClick={() => setQuestion('What are the key formulas for kinematics?')}
                            >
                                💡 &ldquo;What are the key formulas for kinematics?&rdquo;
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
                                    {/* Personal distress / self-harm special care banner */}
                                    {msg.status === 'distress_blocked' ? (
                                        <div className="distress-caring-card">
                                            <div className="distress-header">
                                                <FiHeart className="heart-icon" />
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
                                                        <FiFileText /> <span>{t('study_assistant_sources', 'Sources & Citations')}:</span>
                                                    </div>
                                                    <div className="citations-chips-list">
                                                        {msg.sources.map((src, i) => (
                                                            <div
                                                                key={i}
                                                                className="citation-chip"
                                                                title={`From material: ${src.material_title}`}
                                                            >
                                                                <span className="chip-index">[{src.citation_number}]</span>
                                                                <span className="chip-title">{src.material_title}</span>
                                                                {src.page_number && (
                                                                    <span className="chip-page">
                                                                        ({t('study_assistant_page', 'Page')} {src.page_number})
                                                                    </span>
                                                                )}
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}

                                            {/* Thumbs Up / Down Feedback */}
                                            {msg.queryId && (
                                                <div className="feedback-row">
                                                    <span className="feedback-label">
                                                        {t('study_assistant_helpful', 'Was this answer helpful?')}
                                                    </span>
                                                    <div className="feedback-buttons">
                                                        <button
                                                            type="button"
                                                            className={`btn-feedback ${feedbackMap[msg.queryId] === 'up' ? 'active-up' : ''}`}
                                                            onClick={() => handleFeedback(msg.queryId, 'up')}
                                                            disabled={!!feedbackMap[msg.queryId]}
                                                            title={t('study_assistant_rated_up', 'Marked as helpful')}
                                                        >
                                                            <FiThumbsUp />
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className={`btn-feedback ${feedbackMap[msg.queryId] === 'down' ? 'active-down' : ''}`}
                                                            onClick={() => handleFeedback(msg.queryId, 'down')}
                                                            disabled={!!feedbackMap[msg.queryId]}
                                                            title={t('study_assistant_rated_down', 'Marked as unhelpful')}
                                                        >
                                                            <FiThumbsDown />
                                                        </button>
                                                    </div>
                                                    {feedbackMap[msg.queryId] && (
                                                        <span className="feedback-confirmed">
                                                            <FiCheckCircle />
                                                            {feedbackMap[msg.queryId] === 'up'
                                                                ? t('study_assistant_rated_up', 'Marked as helpful')
                                                                : t('study_assistant_rated_down', 'Marked as unhelpful')}
                                                        </span>
                                                    )}
                                                </div>
                                            )}
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
                                <div className="streaming-dots">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            )}
                            <span className="typing-cursor"></span>
                        </div>
                    </div>
                )}

                <div ref={chatEndRef} />
            </div>

            {/* Error Message Alert */}
            {streamError && (
                <div className="chat-error-banner">
                    <FiAlertCircle />
                    <span>{streamError}</span>
                    <button type="button" onClick={() => setStreamError(null)} className="error-close-btn">&times;</button>
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
                    />

                    <div className="input-controls-group">
                        <span className={`char-counter ${question.length > 450 ? 'near-limit' : ''}`}>
                            {question.length}/500
                        </span>

                        {isStreaming ? (
                            <button
                                type="button"
                                id="rag-stop-btn"
                                onClick={handleStop}
                                className="btn-stop-stream"
                                title={t('study_assistant_stop_btn', 'Stop')}
                            >
                                <FiStopCircle /> <span>{t('study_assistant_stop_btn', 'Stop')}</span>
                            </button>
                        ) : (
                            <button
                                type="submit"
                                id="rag-send-btn"
                                disabled={question.trim().length < 3 || isStreaming}
                                className="btn-send-question"
                                title={t('study_assistant_ask_btn', 'Ask')}
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
