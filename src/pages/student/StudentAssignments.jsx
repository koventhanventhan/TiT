import React, { useState, useEffect } from 'react'
import { FiFileText, FiCalendar, FiCheckCircle, FiClock, FiUpload } from 'react-icons/fi'
import { getStudentAssignments } from '../../services/dashboardService'
import './StudentSections.css'

export default function StudentAssignments() {
    const [assignments, setAssignments] = useState([])
    const [loading, setLoading] = useState(true)
    const [filter, setFilter] = useState('all')

    useEffect(() => {
        async function load() {
            try {
                const data = await getStudentAssignments()
                setAssignments(Array.isArray(data) ? data : data.data || [])
            } catch (e) {
                console.error('Error loading assignments:', e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    const getStatusBadge = (assign) => {
        if (assign.submitted_at || assign.status === 'submitted') return 'submitted'
        if (assign.graded_at || assign.status === 'graded') return 'graded'
        const due = new Date(assign.due_date)
        if (due < new Date()) return 'completed'
        return 'pending'
    }

    const filtered = filter === 'all' ? assignments : assignments.filter(a => getStatusBadge(a) === filter)

    return (
        <div className="student-section">
            <div className="section-top">
                <h2>My Assignments</h2>
                <div className="section-actions">
                    <div className="material-categories">
                        {['all', 'pending', 'submitted', 'graded'].map(f => (
                            <button
                                key={f}
                                className={`category ${filter === f ? 'active' : ''}`}
                                onClick={() => setFilter(f)}
                            >
                                {f.charAt(0).toUpperCase() + f.slice(1)}
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            {loading ? (
                <div className="loading-shimmer">Loading assignments...</div>
            ) : filtered.length === 0 ? (
                <div className="empty-state">
                    <FiFileText />
                    <p>No {filter !== 'all' ? filter : ''} assignments found.</p>
                </div>
            ) : (
                <div className="cards-grid">
                    {filtered.map(assign => {
                        const status = getStatusBadge(assign)
                        return (
                            <div key={assign.id} className="section-card">
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 8 }}>
                                    <span className="card-badge">{assign.subject || 'General'}</span>
                                    <span className={`status-badge ${status}`}>
                                        {status === 'pending' && <><FiClock /> Pending</>}
                                        {status === 'submitted' && <><FiUpload /> Submitted</>}
                                        {status === 'graded' && <><FiCheckCircle /> Graded</>}
                                        {status === 'completed' && <><FiCheckCircle /> Overdue</>}
                                    </span>
                                </div>
                                <h3>{assign.title}</h3>
                                <p>{assign.description || 'No description provided.'}</p>
                                <div className="card-footer">
                                    <div className="meta-item">
                                        <FiCalendar />
                                        <span>Due: {assign.due_date ? new Date(assign.due_date).toLocaleDateString() : 'No due date'}</span>
                                    </div>
                                    {assign.grade && (
                                        <div className="meta-item">
                                            <FiCheckCircle />
                                            <span>Grade: {assign.grade}</span>
                                        </div>
                                    )}
                                </div>
                                {status === 'pending' && (
                                    <button className="btn-submit" style={{ marginTop: 12, width: '100%' }}>
                                        <FiUpload style={{ marginRight: 6 }} /> Submit Assignment
                                    </button>
                                )}
                            </div>
                        )
                    })}
                </div>
            )}
        </div>
    )
}
