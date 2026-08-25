import React, { useState, useEffect } from 'react'
import {
    FiSearch,
    FiFilter,
    FiCheckCircle,
    FiXCircle,
    FiEye,
    FiMoreVertical,
    FiUser,
    FiTrash2
} from 'react-icons/fi'
import { getAdminStudents, bulkDeleteAdminStudents } from '../../services/dashboardService'
import './AdminStudents.css'
import { useToast } from '../../components/shared/ToastContext';


export default function AdminStudents() {
  const toast = useToast();

    const [students, setStudents] = useState([])
    const [loading, setLoading] = useState(true)
    const [searchTerm, setSearchTerm] = useState('')
    const [selectedStudents, setSelectedStudents] = useState([])
    const [isDeleting, setIsDeleting] = useState(false)

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

    const handleSelectAll = (e) => {
        if (e.target.checked) {
            setSelectedStudents(filteredStudents.map(s => s.id))
        } else {
            setSelectedStudents([])
        }
    }

    const handleSelect = (id) => {
        if (selectedStudents.includes(id)) {
            setSelectedStudents(selectedStudents.filter(sid => sid !== id))
        } else {
            setSelectedStudents([...selectedStudents, id])
        }
    }

    const handleDeleteSelected = async () => {
        if (!window.confirm(`Are you sure you want to delete ${selectedStudents.length} students?`)) return
        setIsDeleting(true)
        try {
            await bulkDeleteAdminStudents(selectedStudents)
            setStudents(students.filter(s => !selectedStudents.includes(s.id)))
            setSelectedStudents([])
        } catch (error) {
            console.error('Failed to delete students:', error)
            toast.error('Error deleting students')
        } finally {
            setIsDeleting(false)
        }
    }

    if (loading) return <div className="loading-shimmer">Loading student directory...</div>

    return (
        <div className="admin-students">
            <div className="page-header">
                <div className="header-info">
                    <h1>Student Directory</h1>
                    <p>Manage registrations, payments, and academic access.</p>
                </div>
                <div className="header-actions" style={{ display: 'flex', gap: '10px' }}>
                    {selectedStudents.length > 0 && (
                        <button className="delete-btn" onClick={handleDeleteSelected} disabled={isDeleting} style={{ backgroundColor: '#ef4444', color: 'white', padding: '0.5rem 1rem', borderRadius: '4px', border: 'none', cursor: 'pointer' }}>
                            <FiTrash2 /> Delete Selected ({selectedStudents.length})
                        </button>
                    )}
                    <button className="add-btn">+ Register New Student</button>
                </div>
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
                            <th>
                                <input
                                    type="checkbox"
                                    onChange={handleSelectAll}
                                    checked={filteredStudents.length > 0 && selectedStudents.length === filteredStudents.length}
                                />
                            </th>
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
                            <tr key={student.id} className={selectedStudents.includes(student.id) ? 'selected-row' : ''}>
                                <td>
                                    <input
                                        type="checkbox"
                                        checked={selectedStudents.includes(student.id)}
                                        onChange={() => handleSelect(student.id)}
                                    />
                                </td>
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
