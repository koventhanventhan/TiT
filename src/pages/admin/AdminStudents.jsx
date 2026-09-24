import React, { useState, useEffect } from 'react'
import {
    FiSearch,
    FiFilter,
    FiCheckCircle,
    FiXCircle,
    FiEye,
    FiMoreVertical,
    FiUser,
    FiTrash2,
    FiTrendingUp
} from 'react-icons/fi'
import { getAdminStudents, bulkDeleteAdminStudents, promoteAdminStudents, updateSubjectReviewStatus } from '../../services/dashboardService'
import './AdminStudents.css'
import { useToast } from '../../components/shared/ToastContext';


export default function AdminStudents() {
  const toast = useToast();

    const [students, setStudents] = useState([])
    const [loading, setLoading] = useState(true)
    const [searchTerm, setSearchTerm] = useState('')
    const [selectedStudents, setSelectedStudents] = useState([])
    const [isDeleting, setIsDeleting] = useState(false)
    const [isPromoting, setIsPromoting] = useState(false)
    const [reviewFilter, setReviewFilter] = useState('all')

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

    const handleToggleSubjectReview = async (id, currentStatus) => {
        const newStatus = !currentStatus;
        try {
            await updateSubjectReviewStatus(id, newStatus);
            setStudents(students.map(s => s.id === id ? { ...s, needs_subject_review: newStatus } : s));
            toast.success(`Subject review status updated to ${newStatus ? 'Needs Review' : 'OK'}`);
        } catch (error) {
            console.error('Error updating status:', error);
            toast.error('Failed to update status');
        }
    };

    const filteredStudents = students.filter(s => {
        const matchesSearch = s.full_name?.toLowerCase().includes(searchTerm.toLowerCase()) || s.email?.toLowerCase().includes(searchTerm.toLowerCase())
        const matchesReview = reviewFilter === 'all' || (reviewFilter === 'review' && s.needs_subject_review) || (reviewFilter === 'ok' && !s.needs_subject_review)
        return matchesSearch && matchesReview
    })

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
            toast.success('Students deleted successfully')
        } catch (error) {
            console.error('Failed to delete students:', error)
            toast.error('Error deleting students')
        } finally {
            setIsDeleting(false)
        }
    }

    const handlePromoteSelected = async () => {
        const confirmMsg = `Are you sure you want to promote ${selectedStudents.length} students to the next grade? This action cannot be easily undone.`;
        if (!window.confirm(confirmMsg)) return;

        setIsPromoting(true);
        try {
            const result = await promoteAdminStudents(selectedStudents);
            
            // Reload students to reflect changes
            const updatedData = await getAdminStudents();
            setStudents(updatedData.data || []);
            
            setSelectedStudents([]);
            
            toast.success(`Promoted ${result.summary.promoted} students. ${result.summary.flagged} need subject review. ${result.summary.skipped > 0 ? result.summary.skipped + ' skipped.' : ''}`);
        } catch (error) {
            console.error('Failed to promote students:', error)
            toast.error(error.message || 'Error promoting students')
        } finally {
            setIsPromoting(false)
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
                        <>
                            <button className="delete-btn" onClick={handlePromoteSelected} disabled={isPromoting || isDeleting} style={{ backgroundColor: '#6366f1', color: 'white', padding: '0.5rem 1rem', borderRadius: '4px', border: 'none', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '6px' }}>
                                <FiTrendingUp /> Promote Selected ({selectedStudents.length})
                            </button>
                            <button className="delete-btn" onClick={handleDeleteSelected} disabled={isPromoting || isDeleting} style={{ backgroundColor: '#ef4444', color: 'white', padding: '0.5rem 1rem', borderRadius: '4px', border: 'none', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '6px' }}>
                                <FiTrash2 /> Delete Selected
                            </button>
                        </>
                    )}
                    <button className="add-btn" onClick={() => toast.info("Registration feature coming soon")}>+ Register New Student</button>
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
                    <select 
                        value={reviewFilter} 
                        onChange={(e) => setReviewFilter(e.target.value)} 
                        style={{ padding: '8px 12px', borderRadius: '8px', border: '1px solid #e2e8f0', color: '#475569', outline: 'none' }}
                    >
                        <option value="all">All Subjects Status</option>
                        <option value="review">Needs Review</option>
                        <option value="ok">Subjects OK</option>
                    </select>
                    <button className="filter-btn" onClick={() => toast.info("Grade filter coming soon")}><FiFilter /> Grade</button>
                    <button className="filter-btn" onClick={() => toast.info("Status filter coming soon")}><FiFilter /> Status</button>
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
                                    <span className={`status-pill ${student.is_graduated ? 'graduated' : student.registration_status}`} style={student.is_graduated ? {background: '#f3e8ff', color: '#7e22ce'} : {}}>
                                        {student.is_graduated ? 'Graduated' : student.registration_status.replace('_', ' ')}
                                    </span>
                                    {student.needs_subject_review && (
                                        <div style={{ fontSize: '11px', color: '#ef4444', marginTop: '4px', fontWeight: '600', display: 'flex', alignItems: 'center', gap: '4px' }}>
                                            ⚠️ Needs Subject Review
                                            <button 
                                                onClick={() => handleToggleSubjectReview(student.id, student.needs_subject_review)}
                                                title="Mark as Reviewed"
                                                style={{ background: 'none', border: 'none', color: '#64748b', cursor: 'pointer', padding: '0', display: 'flex' }}
                                            >
                                                <FiCheckCircle size={14} />
                                            </button>
                                        </div>
                                    )}
                                    {!student.needs_subject_review && (
                                        <button 
                                            onClick={() => handleToggleSubjectReview(student.id, student.needs_subject_review)}
                                            style={{ fontSize: '11px', background: 'none', border: 'none', color: '#64748b', cursor: 'pointer', marginTop: '4px', textDecoration: 'underline' }}
                                        >
                                            Mark needs review
                                        </button>
                                    )}
                                </td>
                                <td>
                                    <span className={`payment-pill ${student.has_paid ? 'paid' : 'unpaid'}`}>
                                        {student.has_paid ? 'Paid' : 'Pending'}
                                    </span>
                                </td>
                                <td>
                                    <div className="action-btns">
                                        <button title="View Dashboard" className="icon-btn blue" onClick={() => toast.info("View Dashboard coming soon")}><FiEye /></button>
                                        {student.registration_status === 'pending_confirm' && (
                                            <button title="Confirm Registration" className="icon-btn green" onClick={() => toast.info("Confirm Registration coming soon")}><FiCheckCircle /></button>
                                        )}
                                        <button title="More Actions" className="icon-btn grey" onClick={() => toast.info("More Actions coming soon")}><FiMoreVertical /></button>
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
