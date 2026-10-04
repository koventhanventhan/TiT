import React, { useState, useEffect } from 'react'
import {
    FiFileText,
    FiUser,
    FiCheckCircle,
    FiClock,
    FiFilter,
    FiEye
} from 'react-icons/fi'
import { getAdminAssignments } from '../../services/dashboardService'
import './AdminAssignments.css'

export default function AdminAssignments() {
    const [assignments, setAssignments] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function loadAssignments() {
            try {
                const data = await getAdminAssignments()
                setAssignments(data.data || [])
            } catch (error) {
                console.error('Error loading assignments:', error)
            } finally {
                setLoading(false)
            }
        }
        loadAssignments()
    }, [])

    if (loading) return <div className="loading-shimmer">Indexing academic tasks...</div>

    return (
        <div className="admin-assignments">
            <div className="page-header">
                <div className="header-info">
                    <h1>Assignment Command Center</h1>
                    <p>Monitor all tasks created by teachers and track student submission rates.</p>
                </div>
            </div>

            <div className="assignments-table-container">
                <table className="admin-table">
                    <thead>
                        <tr>
                            <th>Assignment Title</th>
                            <th>Assigned By</th>
                            <th>Due Date</th>
                            <th>Submissions</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {assignments.map((asm) => (
                            <tr key={asm.id}>
                                <td>
                                    <div className="asm-title">
                                        <FiFileText />
                                        <div>
                                            <p className="title">{asm.title}</p>
                                            <span className="grade">{asm.grade} • {asm.subject}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div className="teacher-ref">
                                        <div className="t-avatar">{asm.teacher?.name?.charAt(0)}</div>
                                        <span>{asm.teacher?.name}</span>
                                    </div>
                                </td>
                                <td>{new Date(asm.due_date).toLocaleDateString()}</td>
                                <td>
                                    <div className="submission-count">
                                        <span className="count">{asm.submissions_count}</span>
                                        <span className="total">/ {asm.total_students || '--'}</span>
                                    </div>
                                </td>
                                <td>
                                    <span className={`status-pill ${new Date(asm.due_date) < new Date() ? 'expired' : 'active'}`}>
                                        {new Date(asm.due_date) < new Date() ? 'Completed' : 'Active'}
                                    </span>
                                </td>
                                <td>
                                    <button className="icon-btn blue"><FiEye /></button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    )
}
