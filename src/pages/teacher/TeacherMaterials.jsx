import React, { useState, useEffect } from 'react'
import { FiUpload, FiFolder, FiFileText, FiVideo, FiLink, FiDownload, FiMoreVertical, FiSearch, FiGrid, FiList } from 'react-icons/fi'
import { getTeacherMaterials } from '../../services/dashboardService'

export default function TeacherMaterials() {
    const [materials, setMaterials] = useState([])
    const [loading, setLoading] = useState(true)
    const [viewMode, setViewMode] = useState('grid')
    const [activeTab, setActiveTab] = useState('all')

    useEffect(() => {
        async function load() {
            try {
                // Mock data if API fails
                const data = await getTeacherMaterials().catch(() => null)
                setMaterials(data || [
                    { id: 1, title: 'Calculus Chapter 1 Notes', subject: 'Mathematics', grade: 'A/L 2026', type: 'pdf', size: '2.4 MB', date: '2026-05-18' },
                    { id: 2, title: 'Thermodynamics Video Lecture', subject: 'Physics', grade: 'A/L 2026', type: 'video', size: '450 MB', date: '2026-05-15' },
                    { id: 3, title: 'Organic Chemistry Reactions', subject: 'Chemistry', grade: 'A/L 2025', type: 'document', size: '1.2 MB', date: '2026-05-10' },
                    { id: 4, title: 'Exam Past Papers 2025', subject: 'Mathematics', grade: 'A/L 2026', type: 'pdf', size: '5.6 MB', date: '2026-05-08' },
                    { id: 5, title: 'Useful Physics Simulations', subject: 'Physics', grade: 'A/L 2026', type: 'link', size: '--', date: '2026-05-01' }
                ])
            } catch (e) {
                console.error(e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    const getIcon = (type) => {
        switch (type.toLowerCase()) {
            case 'video': return { icon: <FiVideo />, color: '#ec4899', bg: '#fdf2f8' }
            case 'pdf': return { icon: <FiFileText />, color: '#ef4444', bg: '#fef2f2' }
            case 'document': return { icon: <FiFileText />, color: '#3b82f6', bg: '#eff6ff' }
            case 'link': return { icon: <FiLink />, color: '#10b981', bg: '#ecfdf5' }
            default: return { icon: <FiFolder />, color: '#6366f1', bg: '#eef2ff' }
        }
    }

    const tabs = [
        { id: 'all', label: 'All Files' },
        { id: 'pdf', label: 'PDFs' },
        { id: 'video', label: 'Videos' },
        { id: 'document', label: 'Documents' }
    ]

    const filteredMaterials = activeTab === 'all' 
        ? materials 
        : materials.filter(m => m.type.toLowerCase() === activeTab)

    return (
        <div style={{ paddingBottom: 40 }}>
            {/* Header Section */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24,
                '@media (minWidth: 640px)': { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }
            }}>
                <div>
                    <h1 style={{ fontSize: 24, fontWeight: 800, color: '#1e293b', margin: '0 0 4px 0', letterSpacing: '-0.5px' }}>Teaching Materials</h1>
                    <p style={{ color: '#64748b', margin: 0, fontSize: 14 }}>Manage and share resources with your students</p>
                </div>
                <button style={{
                    display: 'flex', alignItems: 'center', gap: 8, padding: '10px 20px',
                    background: 'linear-gradient(135deg, #7c3aed, #a855f7)', color: '#fff',
                    border: 'none', borderRadius: 12, fontWeight: 600, fontSize: 14,
                    cursor: 'pointer', boxShadow: '0 4px 12px rgba(124,58,237,0.3)',
                    transition: 'all 0.2s', width: 'fit-content'
                }}
                onMouseEnter={e => e.currentTarget.style.transform = 'translateY(-2px)'}
                onMouseLeave={e => e.currentTarget.style.transform = 'translateY(0)'}
                >
                    <FiUpload style={{ fontSize: 18 }} /> Upload Material
                </button>
            </div>

            {/* Toolbar */}
            <div style={{
                display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 24,
                background: '#fff', padding: 16, borderRadius: 16,
                border: '1px solid #e2e8f0', boxShadow: '0 1px 3px rgba(0,0,0,0.02)'
            }}>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 16, alignItems: 'center', justifyContent: 'space-between' }}>
                    <div style={{ display: 'flex', gap: 8, overflowX: 'auto', paddingBottom: 4 }}>
                        {tabs.map(tab => (
                            <button key={tab.id} onClick={() => setActiveTab(tab.id)} style={{
                                padding: '8px 16px', borderRadius: 20, fontSize: 13, fontWeight: 600, cursor: 'pointer',
                                border: activeTab === tab.id ? '1px solid #7c3aed' : '1px solid transparent',
                                background: activeTab === tab.id ? '#f5f3ff' : 'transparent',
                                color: activeTab === tab.id ? '#7c3aed' : '#64748b',
                                transition: 'all 0.2s', whiteSpace: 'nowrap'
                            }}
                            onMouseEnter={e => { if (activeTab !== tab.id) e.currentTarget.style.background = '#f8fafc' }}
                            onMouseLeave={e => { if (activeTab !== tab.id) e.currentTarget.style.background = 'transparent' }}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>

                    <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
                        <div style={{ position: 'relative' }}>
                            <FiSearch style={{ position: 'absolute', left: 12, top: 10, color: '#94a3b8' }} />
                            <input type="text" placeholder="Search files..." style={{
                                width: 200, padding: '8px 12px 8px 36px', borderRadius: 8,
                                border: '1px solid #e2e8f0', background: '#f8fafc', fontSize: 13,
                                outline: 'none', transition: 'border-color 0.2s'
                            }} onFocus={e => e.target.style.borderColor = '#7c3aed'} onBlur={e => e.target.style.borderColor = '#e2e8f0'} />
                        </div>
                        <div style={{ display: 'flex', background: '#f1f5f9', borderRadius: 8, padding: 4 }}>
                            <button onClick={() => setViewMode('grid')} style={{
                                padding: 6, borderRadius: 6, border: 'none', cursor: 'pointer',
                                background: viewMode === 'grid' ? '#fff' : 'transparent',
                                color: viewMode === 'grid' ? '#334155' : '#94a3b8',
                                boxShadow: viewMode === 'grid' ? '0 1px 2px rgba(0,0,0,0.1)' : 'none'
                            }}><FiGrid /></button>
                            <button onClick={() => setViewMode('list')} style={{
                                padding: 6, borderRadius: 6, border: 'none', cursor: 'pointer',
                                background: viewMode === 'list' ? '#fff' : 'transparent',
                                color: viewMode === 'list' ? '#334155' : '#94a3b8',
                                boxShadow: viewMode === 'list' ? '0 1px 2px rgba(0,0,0,0.1)' : 'none'
                            }}><FiList /></button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Content Area */}
            {loading ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 200 }}>
                    <div style={{ width: 40, height: 40, border: '4px solid #e2e8f0', borderTopColor: '#7c3aed', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />
                </div>
            ) : filteredMaterials.length === 0 ? (
                <div style={{
                    background: '#fff', borderRadius: 16, border: '1px dashed #cbd5e1',
                    padding: '60px 20px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center'
                }}>
                    <div style={{ width: 64, height: 64, borderRadius: 16, background: '#f1f5f9', color: '#94a3b8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 28, marginBottom: 16 }}>
                        <FiFolder />
                    </div>
                    <h3 style={{ margin: '0 0 8px 0', fontSize: 18, fontWeight: 700, color: '#334155' }}>No materials found</h3>
                    <p style={{ margin: '0 0 20px 0', color: '#64748b', fontSize: 14 }}>You haven't uploaded any {activeTab !== 'all' ? activeTab : ''} materials yet.</p>
                    <button style={{
                        padding: '10px 20px', background: '#7c3aed', color: '#fff', border: 'none',
                        borderRadius: 10, fontWeight: 600, fontSize: 14, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8
                    }}>
                        <FiUpload /> Upload Material
                    </button>
                </div>
            ) : viewMode === 'grid' ? (
                /* Grid View */
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(260px, 1fr))', gap: 20 }}>
                    {filteredMaterials.map(item => {
                        const style = getIcon(item.type)
                        return (
                            <div key={item.id} style={{
                                background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0',
                                padding: 20, display: 'flex', flexDirection: 'column',
                                boxShadow: '0 2px 8px rgba(0,0,0,0.02)', transition: 'all 0.2s', cursor: 'pointer'
                            }}
                            onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-4px)'; e.currentTarget.style.boxShadow = '0 12px 24px rgba(0,0,0,0.06)'; e.currentTarget.style.borderColor = '#cbd5e1' }}
                            onMouseLeave={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,0.02)'; e.currentTarget.style.borderColor = '#e2e8f0' }}
                            >
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 16 }}>
                                    <div style={{ width: 48, height: 48, borderRadius: 12, background: style.bg, color: style.color, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 24 }}>
                                        {style.icon}
                                    </div>
                                    <button style={{ background: 'none', border: 'none', color: '#94a3b8', cursor: 'pointer', padding: 4 }}><FiMoreVertical /></button>
                                </div>
                                <h3 style={{ margin: '0 0 6px 0', fontSize: 16, fontWeight: 700, color: '#1e293b', lineHeight: 1.3, display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                                    {item.title}
                                </h3>
                                <p style={{ margin: '0 0 16px 0', color: '#64748b', fontSize: 13, fontWeight: 500 }}>
                                    {item.subject} • {item.grade}
                                </p>
                                <div style={{ marginTop: 'auto', paddingTop: 16, borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                    <div style={{ fontSize: 12, color: '#94a3b8', fontWeight: 500 }}>
                                        {new Date(item.date).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}
                                        <span style={{ margin: '0 6px' }}>•</span>
                                        {item.size}
                                    </div>
                                    <button style={{
                                        width: 32, height: 32, borderRadius: 8, background: '#f8fafc', border: '1px solid #e2e8f0',
                                        display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#64748b', cursor: 'pointer', transition: 'all 0.2s'
                                    }} onMouseEnter={e => { e.currentTarget.style.background = '#7c3aed'; e.currentTarget.style.color = '#fff'; e.currentTarget.style.borderColor = '#7c3aed' }} onMouseLeave={e => { e.currentTarget.style.background = '#f8fafc'; e.currentTarget.style.color = '#64748b'; e.currentTarget.style.borderColor = '#e2e8f0' }}>
                                        <FiDownload />
                                    </button>
                                </div>
                            </div>
                        )
                    })}
                </div>
            ) : (
                /* List View */
                <div style={{ background: '#fff', borderRadius: 16, border: '1px solid #e2e8f0', overflow: 'hidden', boxShadow: '0 1px 3px rgba(0,0,0,0.02)' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                        <thead>
                            <tr style={{ background: '#f8fafc', borderBottom: '1px solid #e2e8f0' }}>
                                <th style={{ padding: '14px 20px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Name</th>
                                <th style={{ padding: '14px 20px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Subject</th>
                                <th style={{ padding: '14px 20px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Date Modified</th>
                                <th style={{ padding: '14px 20px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Size</th>
                                <th style={{ padding: '14px 20px', fontSize: 12, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px', textAlign: 'right' }}>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredMaterials.map(item => {
                                const style = getIcon(item.type)
                                return (
                                    <tr key={item.id} style={{ borderBottom: '1px solid #f1f5f9', transition: 'background 0.2s', cursor: 'pointer' }}
                                    onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'} onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                                    >
                                        <td style={{ padding: '16px 20px' }}>
                                            <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                                <div style={{ width: 40, height: 40, borderRadius: 10, background: style.bg, color: style.color, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 20 }}>
                                                    {style.icon}
                                                </div>
                                                <div>
                                                    <div style={{ fontSize: 15, fontWeight: 600, color: '#1e293b', marginBottom: 2 }}>{item.title}</div>
                                                    <div style={{ fontSize: 12, color: '#94a3b8', fontWeight: 500, textTransform: 'uppercase' }}>{item.type}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style={{ padding: '16px 20px' }}>
                                            <div style={{ fontSize: 14, fontWeight: 500, color: '#334155', marginBottom: 2 }}>{item.subject}</div>
                                            <div style={{ fontSize: 12, color: '#94a3b8' }}>{item.grade}</div>
                                        </td>
                                        <td style={{ padding: '16px 20px', fontSize: 14, color: '#475569', fontWeight: 500 }}>
                                            {new Date(item.date).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}
                                        </td>
                                        <td style={{ padding: '16px 20px', fontSize: 14, color: '#475569', fontWeight: 500 }}>{item.size}</td>
                                        <td style={{ padding: '16px 20px', textAlign: 'right' }}>
                                            <button style={{
                                                width: 32, height: 32, borderRadius: 8, background: '#fff', border: '1px solid #e2e8f0', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', color: '#64748b', cursor: 'pointer', transition: 'all 0.2s', marginRight: 8
                                            }} onMouseEnter={e => { e.currentTarget.style.background = '#7c3aed'; e.currentTarget.style.color = '#fff'; e.currentTarget.style.borderColor = '#7c3aed' }} onMouseLeave={e => { e.currentTarget.style.background = '#fff'; e.currentTarget.style.color = '#64748b'; e.currentTarget.style.borderColor = '#e2e8f0' }}>
                                                <FiDownload />
                                            </button>
                                            <button style={{
                                                width: 32, height: 32, borderRadius: 8, background: 'transparent', border: 'none', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', color: '#94a3b8', cursor: 'pointer'
                                            }} onMouseEnter={e => e.currentTarget.style.background = '#f1f5f9'} onMouseLeave={e => e.currentTarget.style.background = 'transparent'}>
                                                <FiMoreVertical />
                                            </button>
                                        </td>
                                    </tr>
                                )
                            })}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    )
}
