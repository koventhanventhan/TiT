import React, { useState, useEffect } from 'react'
import {
    FiSearch,
    FiFilter,
    FiCheckCircle,
    FiXCircle,
    FiEye,
    FiMoreVertical,
    FiUser
} from 'react-icons/fi'
import { getAdminStudents } from '../../services/dashboardService'
import './AdminStudents.css'

export default function AdminStudents() {
    const [students, setStudents] = useState([])
    const [loading, setLoading] = useState(true)
    const [searchTerm, setSearchTerm] = useState('')

    useEffect(() => {
        async function loadStudents() {
            try {
                const data = await getAdminStudents()
                setStudents(data.data || [])
            } catch (error) {
                console.error('Error loading students:', error)
            } finally {
                setLoading(false)
            }
        }
        loadStudents()
    }, [])

    const filteredStudents = students.filter(s =>
        s.full_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        s.email?.toLowerCase().includes(searchTerm.toLowerCase())
    )

    if (loading) return <div className="loading-shimmer">Loading student directory...</div>

    return (
        <div className="admin-students">
            <div className="page-header">
                <div className="header-info">
                    <h1>Student Directory</h1>
                    <p>Manage registrations, payments, and academic access.</p>
                </div>
                <button className="add-btn">+ Register New Student</button>
            </div>

            <div className="table-controls">
                <div className="search-box">
                    <FiSearch />
                    <input
                        type="text"
                        placeholder="Search by name or email..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                    />
                </div>
                <div className="filter-group">
                    <button className="filter-btn"><FiFilter /> Grade</button>
                    <button className="filter-btn"><FiFilter /> Status</button>
                </div>
            </div>

            <div className="admin-table-container">
                <table className="admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Grade/Stream</th>
                            <th>Registration</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredStudents.map((student) => (
                            <tr key={student.id}>
                                <td>
                                    <div className="student-profile">
                                        <div className="student-avatar">{student.full_name?.charAt(0)}</div>
                                        <div className="student-info">
                                            <span className="name">{student.full_name}</span>
                                            <span className="email">{student.email}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div className="grade-box">
                                        <span className="grade">{student.current_grade}</span>
                                        <span className="stream">{student.stream || 'General'}</span>
                                    </div>
                                </td>
                                <td>{new Date(student.created_at).toLocaleDateString()}</td>
                                <td>
                                    <span className={`status-pill ${student.registration_status}`}>
                                        {student.registration_status.replace('_', ' ')}
                                    </span>
                                </td>
                                <td>
                                    <span className={`payment-pill ${student.has_paid ? 'paid' : 'unpaid'}`}>
                                        {student.has_paid ? 'Paid' : 'Pending'}
                                    </span>
                                </td>
                                <td>
                                    <div className="action-btns">
                                        <button title="View Dashboard" className="icon-btn blue"><FiEye /></button>
                                        {student.registration_status === 'pending_confirm' && (
                                            <button title="Confirm Registration" className="icon-btn green"><FiCheckCircle /></button>
                                        )}
                                        <button title="More Actions" className="icon-btn grey"><FiMoreVertical /></button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    )
}
