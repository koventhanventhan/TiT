import React, { useState, useEffect } from 'react'
import { FiSearch, FiMail, FiPhone, FiInfo, FiUsers, FiFilter, FiX, FiCalendar, FiClock } from 'react-icons/fi'
import { getTeacherStudents, getTeacherStudent } from '../../services/dashboardService'
import { useToast } from '../../components/shared/ToastContext'

export default function TeacherStudents() {
    const toast = useToast()
    const [students, setStudents] = useState([])
    const [search, setSearch] = useState('')
    const [selectedGrade, setSelectedGrade] = useState('all')
    const [loading, setLoading] = useState(true)

    // Student Info Modal state
    const [infoModalOpen, setInfoModalOpen] = useState(false)
    const [loadingInfo, setLoadingInfo] = useState(false)
    const [selectedStudentData, setSelectedStudentData] = useState(null)

    useEffect(() => {
        async function load(isInitial = false) {
            try {
                const data = await getTeacherStudents().catch(() => null)
                if (data !== null) {
                    setStudents(data)
                }
            } catch (e) {
                console.error(e)
            } finally {
                if (isInitial) {
                    setLoading(false)
                }
            }
        }
        load(true)
        const interval = setInterval(() => load(false), 30000)
        return () => clearInterval(interval)
    }, [])

    const grades = Array.from(new Set(students.map(s => s.current_grade).filter(Boolean))).sort()

    const filtered = students.filter(s => {
        const query = search.toLowerCase()
        const nameMatch = (s.name || '').toLowerCase().includes(query)
        const emailMatch = (s.email || '').toLowerCase().includes(query)
        const phoneMatch = (s.phone_number || '').includes(query)
        const matchesSearch = nameMatch || emailMatch || phoneMatch
        const matchesGrade = selectedGrade === 'all' || s.current_grade === selectedGrade
        return matchesSearch && matchesGrade
    })

    const handleViewInfo = async (studentId) => {
        setInfoModalOpen(true)
        setLoadingInfo(true)
        try {
            const data = await getTeacherStudent(studentId)
            setSelectedStudentData(data)
        } catch (err) {
            toast.error('Failed to load student info')
            setInfoModalOpen(false)
        } finally {
            setLoadingInfo(false)
        }
    }

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div className="teacher-page-header">
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: 'var(--t-text)', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>My Students</h1>
                    <p style={{ color: 'var(--t-text-3)', margin: 0, fontSize: 14 }}>Manage your enrolled students and their attendance</p>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
                    <div style={{
                        display: 'flex', alignItems: 'center', gap: 8, padding: '8px 16px',
                        background: 'var(--t-success-bg)', border: '1px solid var(--t-primary-border)', borderRadius: 99,
                        color: 'var(--t-success)', fontWeight: 700, fontSize: 13
                    }}>
                        <div style={{ width: 8, height: 8, borderRadius: '50%', background: 'var(--t-success)' }} />
                        Live • Updates every 30s
                    </div>
                    <div style={{
                        display: 'flex', alignItems: 'center', gap: 8, padding: '8px 16px',
                        background: 'var(--t-primary-soft)', border: '1px solid var(--t-primary-border)', borderRadius: 99,
                        color: 'var(--t-primary)', fontWeight: 700, fontSize: 13
                    }}>
                        <FiUsers /> Present now: {filtered.filter(s => s.is_online).length}
                    </div>
                </div>
            </div>

            {/* Toolbar */}
            <div style={{
                display: 'flex', flexWrap: 'wrap', gap: 16, marginBottom: 24,
                background: 'var(--t-surface)', padding: 16, borderRadius: 16,
                border: '1px solid var(--t-border)', boxShadow: '0 1px 3px rgba(0,0,0,0.02)',
                alignItems: 'center', justifyContent: 'space-between'
            }}>
                <div style={{ position: 'relative', flex: '1 1 300px', maxWidth: 400 }}>
                    <FiSearch style={{ position: 'absolute', left: 14, top: 12, color: 'var(--t-text-3)' }} />
                    <input
                        type="text"
                        placeholder="Search by name, email, or phone..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        style={{
                            width: '100%', padding: '10px 16px 10px 40px', borderRadius: 10,
                            border: '1px solid var(--t-border)', background: 'var(--t-bg)', fontSize: 14,
                            outline: 'none', transition: 'border-color 0.2s', boxSizing: 'border-box',
                            color: 'var(--t-text)'
                        }}
                    />
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <FiFilter style={{ color: 'var(--t-text-3)', fontSize: 16 }} />
                    <select
                        value={selectedGrade}
                        onChange={(e) => setSelectedGrade(e.target.value)}
                        style={{
                            padding: '10px 16px', background: 'var(--t-bg)', color: 'var(--t-text)',
                            border: '1px solid var(--t-border)', borderRadius: 10, fontWeight: 600,
                            fontSize: 14, cursor: 'pointer', outline: 'none'
                        }}
                    >
                        <option value="all">All Grades ({students.length})</option>
                        {grades.map(g => (
                            <option key={g} value={g}>Grade {g}</option>
                        ))}
                    </select>
                </div>
            </div>

            {/* Content */}
            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid var(--t-border)', borderTopColor: 'var(--t-primary)', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : filtered.length === 0 ? (
                <div style={{
                    background: 'var(--t-surface)', borderRadius: 16, border: '1px dashed var(--t-border)',
                    padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center'
                }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: 'var(--t-primary-soft)', color: 'var(--t-primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>
                        <FiUsers />
                    </div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: 'var(--t-text)' }}>No students found</h3>
                    <p style={{ margin: 0, color: 'var(--t-text-3)', fontSize: 14 }}>Try adjusting your search or grade filter.</p>
                </div>
            ) : (
                <div style={{ background: 'var(--t-surface)', borderRadius: 16, border: '1px solid var(--t-border)', overflowX: 'auto', boxShadow: '0 1px 3px rgba(0,0,0,0.02)' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', minWidth: 700 }}>
                        <thead>
                            <tr style={{ background: 'var(--t-bg)', borderBottom: '1px solid var(--t-border)' }}>
                                <th style={{ padding: '16px 24px', fontSize: 12, fontWeight: 700, color: 'var(--t-text-3)', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Student Info</th>
                                <th style={{ padding: '16px 24px', fontSize: 12, fontWeight: 700, color: 'var(--t-text-3)', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Grade / Stream</th>
                                <th style={{ padding: '16px 24px', fontSize: 12, fontWeight: 700, color: 'var(--t-text-3)', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Live Status</th>
                                <th style={{ padding: '16px 24px', fontSize: 12, fontWeight: 700, color: 'var(--t-text-3)', textTransform: 'uppercase', letterSpacing: '0.5px', textAlign: 'right' }}>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filtered.map((student, idx) => (
                                <tr key={student.id} style={{ borderBottom: idx === filtered.length - 1 ? 'none' : '1px solid var(--t-border)', transition: 'background 0.2s' }}
                                    onMouseEnter={e => e.currentTarget.style.background = 'var(--t-bg)'} onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                                >
                                    <td style={{ padding: '16px 24px' }}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                            <div style={{
                                                width: 44, height: 44, borderRadius: 12,
                                                background: 'var(--t-primary)',
                                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                                color: '#fff', fontWeight: 700, fontSize: 16, flexShrink: 0
                                            }}>{(student.name || 'S').charAt(0).toUpperCase()}</div>
                                            <div>
                                                <div style={{ fontSize: 15, fontWeight: 700, color: 'var(--t-text)', marginBottom: 2 }}>{student.name || 'Unnamed Student'}</div>
                                                <div style={{ fontSize: 13, color: 'var(--t-text-3)' }}>STU-{student.id} • {student.email || 'No email'}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style={{ padding: '16px 24px' }}>
                                        <div style={{ fontSize: 14, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 2 }}>{student.current_grade ? `Grade ${student.current_grade}` : '—'}</div>
                                        <div style={{ fontSize: 13, color: 'var(--t-text-3)' }}>{student.stream || 'General'}</div>
                                    </td>
                                    <td style={{ padding: '16px 24px' }}>
                                        {(() => {
                                            const isJoined = Boolean(student.is_online)
                                            return (
                                                <div style={{
                                                    display: 'inline-flex', alignItems: 'center', gap: 6,
                                                    padding: '6px 12px', borderRadius: 99,
                                                    background: isJoined ? 'var(--t-success-bg)' : 'var(--t-bg)',
                                                    border: `1px solid ${isJoined ? 'var(--t-primary-border)' : 'var(--t-border)'}`,
                                                    color: isJoined ? 'var(--t-success)' : 'var(--t-text-3)',
                                                    fontSize: 12, fontWeight: 700
                                                }}>
                                                    <div style={{
                                                        width: 6, height: 6, borderRadius: '50%',
                                                        background: isJoined ? 'var(--t-success)' : 'var(--t-text-3)'
                                                    }} />
                                                    {isJoined ? 'Joined Class' : 'Not Joined'}
                                                </div>
                                            )
                                        })()}
                                    </td>
                                    <td style={{ padding: '16px 24px', textAlign: 'right' }}>
                                        <button
                                            onClick={() => handleViewInfo(student.id)}
                                            style={{
                                                padding: '8px 14px', borderRadius: 10, background: 'var(--t-surface)', border: '1px solid var(--t-border)',
                                                display: 'inline-flex', alignItems: 'center', gap: 6, color: 'var(--t-primary)', cursor: 'pointer', transition: 'all 0.2s',
                                                fontSize: 13, fontWeight: 600
                                            }}
                                            title="View Student Profile & Attendance"
                                            onMouseEnter={e => { e.currentTarget.style.background = 'var(--t-primary-soft)'; e.currentTarget.style.borderColor = 'var(--t-primary)' }}
                                            onMouseLeave={e => { e.currentTarget.style.background = 'var(--t-surface)'; e.currentTarget.style.borderColor = 'var(--t-border)' }}
                                        >
                                            <FiInfo /> View Info
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Student Info Modal */}
            {infoModalOpen && (
                <div style={{
                    position: 'fixed', inset: 0, background: 'rgba(15, 23, 42, 0.6)',
                    backdropFilter: 'blur(4px)', zIndex: 1000, display: 'flex',
                    alignItems: 'center', justifyContent: 'center', padding: 20
                }}>
                    <div style={{
                        background: 'var(--t-surface)', width: '100%', maxWidth: 580,
                        borderRadius: 20, padding: 28, boxShadow: '0 25px 50px -12px rgba(0,0,0,0.25)',
                        maxHeight: '90vh', overflowY: 'auto'
                    }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20, borderBottom: '1px solid var(--t-border)', paddingBottom: 14 }}>
                            <h2 style={{ fontSize: 18, fontWeight: 800, color: 'var(--t-text)', margin: 0 }}>Student Profile</h2>
                            <button
                                onClick={() => { setInfoModalOpen(false); setSelectedStudentData(null); }}
                                style={{
                                    width: 32, height: 32, borderRadius: '50%', background: 'var(--t-bg)', border: 'none',
                                    display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--t-text-3)', cursor: 'pointer'
                                }}
                            >
                                <FiX />
                            </button>
                        </div>

                        {loadingInfo || !selectedStudentData ? (
                            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 160 }}>
                                <div style={{ width: 36, height: 36, border: '3px solid var(--t-border)', borderTopColor: 'var(--t-primary)', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                            </div>
                        ) : (
                            <div>
                                {/* Profile Summary */}
                                <div style={{
                                    display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 14,
                                    background: 'var(--t-bg)', padding: 16, borderRadius: 12, marginBottom: 24, border: '1px solid var(--t-border)'
                                }}>
                                    <div>
                                        <div style={{ fontSize: 11, fontWeight: 700, color: 'var(--t-text-3)', textTransform: 'uppercase' }}>Full Name</div>
                                        <div style={{ fontSize: 14, fontWeight: 700, color: 'var(--t-text)', marginTop: 2 }}>{selectedStudentData.profile?.name}</div>
                                    </div>
                                    <div>
                                        <div style={{ fontSize: 11, fontWeight: 700, color: 'var(--t-text-3)', textTransform: 'uppercase' }}>Email</div>
                                        <div style={{ fontSize: 13, color: 'var(--t-text-2)', marginTop: 2 }}>{selectedStudentData.profile?.email}</div>
                                    </div>
                                    <div>
                                        <div style={{ fontSize: 11, fontWeight: 700, color: 'var(--t-text-3)', textTransform: 'uppercase' }}>Phone</div>
                                        <div style={{ fontSize: 13, color: 'var(--t-text-2)', marginTop: 2 }}>{selectedStudentData.profile?.phone_number || 'N/A'}</div>
                                    </div>
                                    <div>
                                        <div style={{ fontSize: 11, fontWeight: 700, color: 'var(--t-text-3)', textTransform: 'uppercase' }}>Grade / Stream</div>
                                        <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--t-text)', marginTop: 2 }}>
                                            Grade {selectedStudentData.profile?.current_grade || 'N/A'} {selectedStudentData.profile?.stream ? `(${selectedStudentData.profile.stream})` : ''}
                                        </div>
                                    </div>
                                    <div>
                                        <div style={{ fontSize: 11, fontWeight: 700, color: 'var(--t-text-3)', textTransform: 'uppercase' }}>Registration Status</div>
                                        <div style={{ fontSize: 12, fontWeight: 700, color: 'var(--t-success)', textTransform: 'capitalize', marginTop: 2 }}>
                                            {selectedStudentData.profile?.registration_status || 'Approved'}
                                        </div>
                                    </div>
                                </div>

                                {/* Attendance History */}
                                <h3 style={{ fontSize: 15, fontWeight: 700, color: 'var(--t-text)', margin: '0 0 12px 0' }}>Recent Attendance (Last 20 Sessions)</h3>
                                {(!selectedStudentData.attendance || selectedStudentData.attendance.length === 0) ? (
                                    <div style={{ padding: 20, textAlign: 'center', background: 'var(--t-bg)', borderRadius: 10, color: 'var(--t-text-3)', fontSize: 13 }}>
                                        No recorded attendance rows yet.
                                    </div>
                                ) : (
                                    <div style={{ border: '1px solid var(--t-border)', borderRadius: 10, overflow: 'hidden' }}>
                                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                                            <thead>
                                                <tr style={{ background: 'var(--t-bg)', borderBottom: '1px solid var(--t-border)', textAlign: 'left' }}>
                                                    <th style={{ padding: '10px 14px', color: 'var(--t-text-3)', fontWeight: 600 }}>Class</th>
                                                    <th style={{ padding: '10px 14px', color: 'var(--t-text-3)', fontWeight: 600 }}>Date</th>
                                                    <th style={{ padding: '10px 14px', color: 'var(--t-text-3)', fontWeight: 600, textAlign: 'right' }}>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {selectedStudentData.attendance.map((att, i) => {
                                                    const schedule = att.zoomSchedule || att.zoom_schedule
                                                    const isPresent = att.status === 'present'
                                                    return (
                                                        <tr key={att.id || i} style={{ borderBottom: i === selectedStudentData.attendance.length - 1 ? 'none' : '1px solid var(--t-border)' }}>
                                                            <td style={{ padding: '10px 14px', fontWeight: 600, color: 'var(--t-text)' }}>
                                                                {schedule?.title || 'Class Session'}
                                                                {schedule?.subject && <span style={{ fontSize: 11, color: 'var(--t-text-3)', display: 'block' }}>{schedule.subject}</span>}
                                                            </td>
                                                            <td style={{ padding: '10px 14px', color: 'var(--t-text-2)' }}>
                                                                {att.marked_at ? new Date(att.marked_at).toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'}
                                                            </td>
                                                            <td style={{ padding: '10px 14px', textAlign: 'right' }}>
                                                                <span style={{
                                                                    padding: '3px 8px', borderRadius: 6, fontSize: 11, fontWeight: 700,
                                                                    background: isPresent ? 'var(--t-success-bg)' : 'var(--t-danger-bg)',
                                                                    color: isPresent ? 'var(--t-success)' : 'var(--t-danger)',
                                                                    textTransform: 'capitalize'
                                                                }}>
                                                                    {att.status}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    )
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    )
}
