import React, { useState, useEffect } from 'react'
import { FiSearch, FiInfo, FiMoreVertical, FiVideo, FiClock } from 'react-icons/fi'
import { getTeacherStudents } from '../../services/dashboardService'

export default function TeacherStudents() {
    const [students, setStudents] = useState([])
    const [search, setSearch] = useState('')
    const [loading, setLoading] = useState(true)

    // In the future, this current class info should also come from the real backend API based on the timetable
    const [currentClass, setCurrentClass] = useState(null); 

    useEffect(() => {
        async function load() {
            try {
                const data = await getTeacherStudents().catch(() => null)
                // Appending a dummy 'isOnline: false' until WebSockets are implemented
                const realStudents = (data || []).map(student => ({
                    ...student,
                    isOnline: false 
                }))
                setStudents(realStudents)
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
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>Live Classroom Monitoring</p>
                </div>

                {/* Current Class Indicator (Hidden until real backend data is ready) */}
                {currentClass && currentClass.isLive && (
                    <div style={{
                        display: 'inline-flex', alignItems: 'center', gap: 12, padding: '10px 20px',
                        background: '#fef2f2', border: '1px solid #fecaca', borderRadius: 12
                    }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 6, color: '#ef4444', fontWeight: 700, fontSize: 14 }}>
                            <span style={{ width: 10, height: 10, borderRadius: '50%', background: '#ef4444', animation: 'ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite' }} />
                            LIVE: {currentClass.grade} - {currentClass.subject}
                        </div>
                        <div style={{ width: 1, height: 16, background: '#fca5a5' }} />
                        <div style={{ display: 'flex', alignItems: 'center', gap: 6, color: '#991b1b', fontSize: 13, fontWeight: 600 }}>
                            <FiClock /> Started at {currentClass.startTime}
                        </div>
                    </div>
                )}
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
                        placeholder="Search students..."
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
                <div style={{ fontSize: 14, fontWeight: 600, color: '#475569' }}>
                    Total Present: <span style={{ color: '#10b981' }}>{students.filter(s => s.isOnline).length}</span> / {students.length}
                </div>
            </div>

            {/* Content Table */}
            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#0ea5e9', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : (
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0', overflowX: 'auto', boxShadow: '0 1px 3px rgba(0,0,0,0.02)' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', minWidth: 800 }}>
                        <thead>
                            <tr style={{ background: '#f8fafc', borderBottom: '1px solid #e2e8f0' }}>
                                <th style={{ padding: '16px 24px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Student Info</th>
                                <th style={{ padding: '16px 24px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Grade</th>
                                <th style={{ padding: '16px 24px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Live Status</th>
                                <th style={{ padding: '16px 24px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px', textAlign: 'right' }}>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filtered.length === 0 ? (
                                <tr>
                                    <td colSpan={4} style={{ padding: '40px', textAlign: 'center', color: '#64748b', fontSize: 14 }}>
                                        No students found.
                                    </td>
                                </tr>
                            ) : (
                                filtered.map((student, idx) => (
                                    <tr key={student.id} style={{ borderBottom: idx === filtered.length - 1 ? 'none' : '1px solid #f1f5f9', transition: 'background 0.2s' }}
                                        onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'} onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                                    >
                                        <td style={{ padding: '16px 24px' }}>
                                            <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                                <div style={{
                                                    width: 44, height: 44, borderRadius: 12,
                                                    background: 'linear-gradient(135deg, #0ea5e9, #06b6d4)',
                                                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                                                    color: '#fff', fontWeight: 700, fontSize: 16, flexShrink: 0,
                                                    position: 'relative'
                                                }}>
                                                    {student.name ? student.name.charAt(0).toUpperCase() : 'N'}
                                                </div>
                                                <div>
                                                    <div style={{ fontSize: 15, fontWeight: 700, color: '#1e293b', marginBottom: 2 }}>{student.name}</div>
                                                    <div style={{ fontSize: 13, color: '#64748b' }}>{student.email}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style={{ padding: '16px 24px' }}>
                                            <div style={{ fontSize: 14, fontWeight: 600, color: '#334155' }}>
                                                {student.current_grade || student.grade || 'N/A'}
                                            </div>
                                        </td>
                                        <td style={{ padding: '16px 24px' }}>
                                            <div style={{ display: 'inline-flex', alignItems: 'center', gap: 8, padding: '6px 12px', borderRadius: 20, background: student.isOnline ? '#ecfdf5' : '#f1f5f9', border: `1px solid ${student.isOnline ? '#a7f3d0' : '#e2e8f0'}` }}>
                                                <div style={{
                                                    width: 8, height: 8, borderRadius: '50%',
                                                    background: student.isOnline ? '#10b981' : '#94a3b8',
                                                    boxShadow: student.isOnline ? '0 0 0 3px rgba(16, 185, 129, 0.2)' : 'none'
                                                }} />
                                                <span style={{ fontSize: 13, fontWeight: 700, color: student.isOnline ? '#059669' : '#64748b' }}>
                                                    {student.isOnline ? 'Joined Class' : 'Not Joined'}
                                                </span>
                                            </div>
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
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    )
}
