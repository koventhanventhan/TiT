import React, { useState, useEffect } from 'react'
import { FiPlus, FiFileText, FiCalendar, FiCheckCircle } from 'react-icons/fi'
import { getTeacherAssignments } from '../../services/dashboardService'
import './TeacherSections.css'

export default function TeacherAssignments() {
    const [assignments, setAssignments] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                const data = await getTeacherAssignments()
                setAssignments(data)
            } catch (e) {
                console.error(e)
            } finally {
                setLoading(false)
            }
        }
        load()
    }, [])

    return (
        <div className="teacher-section">
            <div className="section-top">
                <h2>Assignments & Homework</h2>
                <button className="btn-primary"><FiPlus /> Create New Assignment</button>
            </div>

            {loading ? (
                <div className="loading-shimmer">Loading assignments...</div>
            ) : (
                <div className="assignments-grid">
                    {assignments.map(assign => (
                        <div key={assign.id} className="assignment-card">
                            <div className="card-badge">{assign.subject}</div>
                            <h3>{assign.title}</h3>
                            <p className="description">{assign.description}</p>
                            <div className="card-footer">
                                <div className="meta-item">
                                    <FiCalendar />
                                    <span>Due: {assign.due_date ? new Date(assign.due_date).toLocaleDateString() : 'No due date'}</span>
                                </div>
                                <div className="meta-item">
                                    <FiCheckCircle />
                                    <span>{assign.submissions_count || 0} Submissions</span>
                                </div>
                            </div>
                            <button className="btn-view-details">View Submissions</button>
                        </div>
                    ))}
                    {assignments.length === 0 && !loading && <div className="empty-state-large">
                        <FiFileText />
                        <p>No assignments created yet.</p>
                    </div>}
                </div>
            )}
        </div>
    )
}
