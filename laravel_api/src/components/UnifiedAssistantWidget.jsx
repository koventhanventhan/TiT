import React, { useState, useEffect, useRef } from 'react';
import { useLanguage } from '../context/LanguageContext';
import { useSettings } from '../context/SettingsContext';

const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';

export default function UnifiedAssistantWidget() {
    const { language, t } = useLanguage();
    const { settings } = useSettings();
    const [isOpen, setIsOpen] = useState(false);
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [isEnabled, setIsEnabled] = useState(true);
    const messagesEndRef = useRef(null);

    const instituteId = settings?.institute_id || 1;

    useEffect(() => {
        // If settings explicitly specify public_assistant_enabled is false, hide widget
        if (settings?.public_assistant_enabled === false || settings?.public_assistant_enabled === '0') {
            setIsEnabled(false);
        }
    }, [settings]);

    useEffect(() => {
        if (messagesEndRef.current) {
            messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    }, [messages, isLoading]);

    if (!isEnabled) {
        return null;
    }

    const handleSend = async (e) => {
        if (e) e.preventDefault();
        const trimmed = input.trim();
        if (!trimmed || isLoading) return;

        const userMsg = { id: Date.now(), sender: 'user', text: trimmed };
        setMessages((prev) => [...prev, userMsg]);
        setInput('');
        setIsLoading(true);

        const token = localStorage.getItem('authToken');
        const activeProfile = localStorage.getItem('activeProfileId');

        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Institute-Id': String(instituteId),
        };

        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        if (activeProfile) {
            headers['X-Profile-Id'] = String(activeProfile);
        }

        try {
            const res = await fetch(`${API_BASE_URL}/assistant/chat`, {
                method: 'POST',
                headers,
                body: JSON.stringify({
                    question: trimmed,
                }),
            });

            const data = await res.json();

            if (!res.ok) {
                if (res.status === 403 && data.public_enabled === false) {
                    setIsEnabled(false);
                    return;
                }
                const errText = data.message || t('study_assistant_error_generic');
                setMessages((prev) => [...prev, {
                    id: Date.now() + 1,
                    sender: 'assistant',
                    text: errText,
                    isError: true,
                }]);
                return;
            }

            setMessages((prev) => [...prev, {
                id: Date.now() + 1,
                sender: 'assistant',
                text: data.answer || '',
                tier: data.tier,
                usedStudyNotes: !!data.used_study_notes,
                citations: data.citations || [],
            }]);
        } catch (err) {
            setMessages((prev) => [...prev, {
                id: Date.now() + 1,
                sender: 'assistant',
                text: t('study_assistant_error_generic'),
                isError: true,
            }]);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div style={{
            position: 'fixed',
            bottom: 'calc(1.5rem + env(safe-area-inset-bottom, 0px))',
            right: 'calc(1.5rem + env(safe-area-inset-right, 0px))',
            zIndex: 9999,
            fontFamily: 'Inter, system-ui, -apple-system, sans-serif',
        }}>
            {/* Chat Trigger Button */}
            {!isOpen && (
                <button
                    onClick={() => setIsOpen(true)}
                    aria-label={t('unified_assistant_open') || 'Ask Assistant'}
                    style={{
                        background: 'linear-gradient(135deg, #2563eb, #1d4ed8)',
                        color: '#ffffff',
                        border: 'none',
                        borderRadius: '9999px',
                        padding: '0.85rem 1.4rem',
                        fontSize: '0.95rem',
                        fontWeight: '600',
                        boxShadow: '0 10px 25px -5px rgba(37, 99, 235, 0.4), 0 8px 10px -6px rgba(37, 99, 235, 0.2)',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '0.6rem',
                        transition: 'transform 0.2s, box-shadow 0.2s',
                    }}
                    onMouseEnter={(e) => { e.currentTarget.style.transform = 'scale(1.05)'; }}
                    onMouseLeave={(e) => { e.currentTarget.style.transform = 'scale(1)'; }}
                >
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    <span>{t('unified_assistant_open') || 'Ask Assistant'}</span>
                </button>
            )}

            {/* Chat Modal / Window */}
            {isOpen && (
                <div style={{
                    width: 'min(380px, calc(100vw - 2rem))',
                    height: 'min(520px, calc(100dvh - 5rem))',
                    background: '#ffffff',
                    borderRadius: '16px',
                    boxShadow: '0 20px 40px -10px rgba(0, 0, 0, 0.2), 0 1px 3px rgba(0, 0, 0, 0.05)',
                    display: 'flex',
                    flexDirection: 'column',
                    overflow: 'hidden',
                    border: '1px solid #e2e8f0',
                }}>
                    {/* Header */}
                    <div style={{
                        background: 'linear-gradient(135deg, #1e293b, #0f172a)',
                        color: '#ffffff',
                        padding: '1rem 1.25rem',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                    }}>
                        <div>
                            <div style={{ fontWeight: '700', fontSize: '1rem' }}>
                                {t('unified_assistant_title') || 'Website Assistant'}
                            </div>
                            <div style={{ fontSize: '0.75rem', color: '#94a3b8' }}>
                                {settings?.site_name || 'TiT Education'}
                            </div>
                        </div>
                        <button
                            onClick={() => setIsOpen(false)}
                            aria-label="Close"
                            style={{
                                background: 'transparent',
                                border: 'none',
                                color: '#94a3b8',
                                cursor: 'pointer',
                                padding: '0.25rem',
                                borderRadius: '6px',
                            }}
                        >
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {/* Disclaimer Bar */}
                    <div style={{
                        background: '#f8fafc',
                        borderBottom: '1px solid #e2e8f0',
                        padding: '0.5rem 0.85rem',
                        fontSize: '0.72rem',
                        color: '#64748b',
                        lineHeight: '1.3',
                    }}>
                        {t('unified_assistant_public_disclaimer') || 'Answers are generated by AI and may contain inaccuracies. Please verify with official website pages or teachers.'}
                    </div>

                    {/* Message Stream */}
                    <div style={{
                        flex: 1,
                        padding: '1rem',
                        overflowY: 'auto',
                        display: 'flex',
                        flexDirection: 'column',
                        gap: '0.85rem',
                    }}>
                        {messages.length === 0 && (
                            <div style={{
                                color: '#94a3b8',
                                fontSize: '0.88rem',
                                textAlign: 'center',
                                marginTop: '3rem',
                                lineHeight: '1.5',
                            }}>
                                {t('unified_assistant_welcome') || 'Hello! Ask any question about our classes, fee structure, timetable, or registration.'}
                            </div>
                        )}

                        {messages.map((m) => (
                            <div
                                key={m.id}
                                style={{
                                    alignSelf: m.sender === 'user' ? 'flex-end' : 'flex-start',
                                    maxWidth: '85%',
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: '0.25rem',
                                }}
                            >
                                {m.usedStudyNotes && (
                                    <span style={{
                                        alignSelf: 'flex-start',
                                        fontSize: '0.68rem',
                                        background: '#ecfdf5',
                                        color: '#059669',
                                        padding: '0.15rem 0.5rem',
                                        borderRadius: '9999px',
                                        fontWeight: '600',
                                        border: '1px solid #a7f3d0',
                                    }}>
                                        {t('unified_assistant_study_mode') || 'Study Mode'}
                                    </span>
                                )}
                                <div style={{
                                    background: m.sender === 'user' ? '#2563eb' : (m.isError ? '#fef2f2' : '#f1f5f9'),
                                    color: m.sender === 'user' ? '#ffffff' : (m.isError ? '#dc2626' : '#1e293b'),
                                    padding: '0.75rem 0.95rem',
                                    borderRadius: m.sender === 'user' ? '14px 14px 2px 14px' : '14px 14px 14px 2px',
                                    fontSize: '0.88rem',
                                    lineHeight: '1.45',
                                    whiteSpace: 'pre-wrap',
                                    wordBreak: 'break-word',
                                }}>
                                    {m.text}
                                </div>
                            </div>
                        ))}

                        {isLoading && (
                            <div style={{
                                alignSelf: 'flex-start',
                                background: '#f1f5f9',
                                padding: '0.6rem 0.9rem',
                                borderRadius: '12px',
                                fontSize: '0.82rem',
                                color: '#64748b',
                            }}>
                                ...
                            </div>
                        )}
                        <div ref={messagesEndRef} />
                    </div>

                    {/* Input Bar */}
                    <form
                        onSubmit={handleSend}
                        style={{
                            padding: '0.75rem 1rem',
                            borderTop: '1px solid #e2e8f0',
                            background: '#ffffff',
                            display: 'flex',
                            gap: '0.5rem',
                        }}
                    >
                        <input
                            type="text"
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            placeholder={t('unified_assistant_placeholder') || 'Ask a question...'}
                            maxLength={200}
                            style={{
                                flex: 1,
                                padding: '0.65rem 0.85rem',
                                borderRadius: '8px',
                                border: '1px solid #cbd5e1',
                                fontSize: '0.88rem',
                                outline: 'none',
                            }}
                        />
                        <button
                            type="submit"
                            disabled={!input.trim() || isLoading}
                            style={{
                                background: input.trim() && !isLoading ? '#2563eb' : '#94a3b8',
                                color: '#ffffff',
                                border: 'none',
                                borderRadius: '8px',
                                padding: '0.65rem 1rem',
                                fontWeight: '600',
                                fontSize: '0.88rem',
                                cursor: input.trim() && !isLoading ? 'pointer' : 'not-allowed',
                            }}
                        >
                            {t('study_assistant_ask_btn') || 'Ask'}
                        </button>
                    </form>
                </div>
            )}
        </div>
    );
}