import React, { useState, useEffect } from 'react'
import { FiSearch, FiMail, FiPhone, FiInfo, FiUsers, FiFilter, FiMoreVertical } from 'react-icons/fi'
import { getTeacherStudents } from '../../services/dashboardService'

export default function TeacherStudents() {
    const [students, setStudents] = useState([])
    const [search, setSearch] = useState('')
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                const data = await getTeacherStudents().catch(() => null)
                setStudents(data || [])
            } catch (e) {
                console.error(e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    const filtered = students.filter(s =>
        s.name.toLowerCase().includes(search.toLowerCase()) ||
        s.email.toLowerCase().includes(search.toLowerCase())
    )

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24,
                '@media (minWidth: 640px)': { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }
            }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: '#1e293b', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>My Students</h1>
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>Manage your enrolled students and their details</p>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
                    <div style={{
                        display: 'flex', alignItems: 'center', gap: 8, padding: '8px 16px',
                        background: '#fef2f2', border: '1px solid #fecaca', borderRadius: 99,
                        color: '#ef4444', fontWeight: 700, fontSize: 13
                    }}>
                        <div style={{ width: 8, height: 8, borderRadius: '50%', background: '#ef4444' }} />
                        LIVE: Monitoring
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '8px 16px', background: '#f0fdf4', border: '1px solid #bbf7d0', borderRadius: 99, color: '#16a34a', fontWeight: 700, fontSize: 13 }}>
                        <FiUsers /> Total Present: {filtered.filter(s => s.is_online).length}
                    </div>
                </div>
            </div>

            {/* Toolbar */}
            <div style={{
                display: 'flex', flexWrap: 'wrap', gap: 16, marginBottom: 24,
                background: '#fff', padding: 16, borderRadius: 16,
                border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.02)',
                alignItems: 'center', justifyContent: 'space-between'
            }}>
                <div style={{ position: 'relative', flex: '1 1 300px', maxWidth: 400 }}>
                    <FiSearch style={{ position: 'absolute', left: 14, top: 12, color: '#94a3b8' }} />
                    <input
                        type="text"
                        placeholder="Search by name or email..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        style={{
                            width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10,
                            border: '1px solid #e2e8f0', background: '#f8fafc', fontSize: 14,
                            outline: 'none', transition: 'border-color 0.2s', boxSizing: 'border-box'
                        }}
                        onFocus={e => e.target.style.borderColor = '#0ea5e9'}
                        onBlur={e => e.target.style.borderColor = '#e2e8f0'}
                    />
                </div>
                <button style={{
                    display: 'flex', alignItems: 'center', gap: 8, padding: '10px 16px',
                    background: '#f8fafc', color: '#475569', border: '1px solid #e2e8f0',
                    borderRadius: 10, fontWeight: 600, fontSize: 14, cursor: 'pointer', transition: 'all 0.2s'
                }} onMouseEnter={e => { e.currentTarget.style.background = '#f1f5f9'; e.currentTarget.style.borderColor = '#cbd5e1' }} onMouseLeave={e => { e.currentTarget.style.background = '#f8fafc'; e.currentTarget.style.borderColor = '#e2e8f0' }}>
                    <FiFilter /> Filter
                </button>
            </div>

            {/* Content */}
            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#0ea5e9', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : filtered.length === 0 ? (
                <div style={{
                    background: '#fff', borderRadius: 16, border: '1px dashed #cbd5e1',
                    padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center'
                }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: '#f1f5f9', color: '#94a3b8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>
                        <FiUsers />
                    </div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#334155' }}>No students found</h3>
                    <p style={{ margin: 0, color: '#64748b', fontSize: 14 }}>Try adjusting your search criteria.</p>
                </div>
            ) : (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0', overflowX: 'auto', boxShadow: '0 1px 3px rgba(0,0,0,0.02)' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', minWidth: 800 }}>
                        <thead>
                            <tr style={{ background: '#f8fafc', borderBottom: '1px solid #e2e8f0' }}>
                                <th style={{ padding: '16px 24px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Student Info</th>
                                <th style={{ padding: '16px 24px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Grade / Stream</th>
                                <th style={{ padding: '16px 24px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Live Status</th>
                                <th style={{ padding: '16px 24px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px', textAlign: 'right' }}>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filtered.map((student, idx) => (
                                <tr key={student.id} style={{ borderBottom: idx === filtered.length - 1 ? 'none' : '1px solid #f1f5f9', transition: 'background 0.2s' }}
                                    onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'} onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                                >
                                    <td style={{ padding: '16px 24px' }}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                            <div style={{
                                                width: 44, height: 44, borderRadius: 12,
                                                background: 'linear-gradient(135deg, #0ea5e9, #06b6d4)',
                                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                                color: '#fff', fontWeight: 700, fontSize: 16, flexShrink: 0
                                            }}>{student.name.charAt(0)}</div>
                                            <div>
                                                <div style={{ fontSize: 15, fontWeight: 700, color: '#1e293b', marginBottom: 2 }}>{student.name}</div>
                                                <div style={{ fontSize: 13, color: '#64748b' }}>STU-{1000 + student.id} • {student.email}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style={{ padding: '16px 24px' }}>
                                        <div style={{ fontSize: 14, fontWeight: 600, color: '#334155', marginBottom: 2 }}>{student.current_grade || 'N/A'}</div>
                                        <div style={{ fontSize: 13, color: '#64748b' }}>{student.stream || 'General'}</div>
                                    </td>
                                    <td style={{ padding: '16px 24px' }}>
                                        {(() => {
                                            const isJoined = Boolean(student.is_online); // Uses actual API field now (defaults to false)
                                            return (
                                                <div style={{
                                                    display: 'inline-flex', alignItems: 'center', gap: 6,
                                                    padding: '6px 12px', borderRadius: 99,
                                                    background: isJoined ? '#f0fdf4' : '#f8fafc',
                                                    border: `1px solid ${isJoined ? '#bbf7d0' : '#e2e8f0'}`,
                                                    color: isJoined ? '#16a34a' : '#64748b',
                                                    fontSize: 12, fontWeight: 700
                                                }}>
                                                    <div style={{
                                                        width: 6, height: 6, borderRadius: '50%',
                                                        background: isJoined ? '#22c55e' : '#cbd5e1',
                                                        boxShadow: isJoined ? '0 0 8px #4ade80' : 'none'
                                                    }} />
                                                    {isJoined ? 'Joined Class' : 'Not Joined'}
                                                </div>
                                            )
                                        })()}
                                    </td>
                                    <td style={{ padding: '16px 24px', textAlign: 'right' }}>
                                        <button style={{
                                            width: 36, height: 36, borderRadius: 10, background: '#fff', border: '1px solid #e2e8f0',
                                            display: 'inline-flex', alignItems: 'center', justifyContent: 'center', color: '#64748b', cursor: 'pointer', transition: 'all 0.2s', marginRight: 8
                                        }} title="View Info" onMouseEnter={e => { e.currentTarget.style.background = '#0ea5e9'; e.currentTarget.style.color = '#fff'; e.currentTarget.style.borderColor = '#0ea5e9' }} onMouseLeave={e => { e.currentTarget.style.background = '#fff'; e.currentTarget.style.color = '#64748b'; e.currentTarget.style.borderColor = '#e2e8f0' }}>
                                            <FiInfo />
                                        </button>
                                        <button style={{
                                            width: 36, height: 36, borderRadius: 10, background: 'transparent', border: 'none',
                                            display: 'inline-flex', alignItems: 'center', justifyContent: 'center', color: '#94a3b8', cursor: 'pointer'
                                        }} onMouseEnter={e => e.currentTarget.style.background = '#f1f5f9'} onMouseLeave={e => e.currentTarget.style.background = 'transparent'}>
                                            <FiMoreVertical />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    )
}
