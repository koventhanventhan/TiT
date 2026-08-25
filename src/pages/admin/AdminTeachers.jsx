import React, { useState, useEffect } from 'react'
import {
    FiSearch,
    FiUserPlus,
    FiVideo,
    FiFileText,
    FiActivity,
    FiMail,
    FiPhone,
    FiMoreVertical,
    FiTrash2
} from 'react-icons/fi'
import { getAdminTeachers, bulkDeleteAdminTeachers } from '../../services/dashboardService'
import './AdminTeachers.css'
import { useToast } from '../../components/shared/ToastContext';


export default function AdminTeachers() {
  const toast = useToast();

    const [teachers, setTeachers] = useState([])
    const [loading, setLoading] = useState(true)
    const [searchTerm, setSearchTerm] = useState('')
    const [selectedTeachers, setSelectedTeachers] = useState([])
    const [isDeleting, setIsDeleting] = useState(false)

    useEffect(() => {
        async function loadTeachers() {
            try {
                const data = await getAdminTeachers()
                setTeachers(data || [])
            } catch (error) {
                console.error('Error loading teachers:', error)
            } finally {
                setLoading(false)
            }
        }
        loadTeachers()
    }, [])

    const handleSelectAll = (e) => {
        if (e.target.checked) setSelectedTeachers(teachers.map(t => t.id));
        else setSelectedTeachers([]);
    };

    const handleSelect = (id) => {
        if (selectedTeachers.includes(id)) setSelectedTeachers(selectedTeachers.filter(tid => tid !== id));
        else setSelectedTeachers([...selectedTeachers, id]);
    };

    const handleDeleteSelected = async () => {
        if (!window.confirm(`Are you sure you want to delete ${selectedTeachers.length} teachers?`)) return
        setIsDeleting(true)
        try {
            await bulkDeleteAdminTeachers(selectedTeachers)
            setTeachers(teachers.filter(t => !selectedTeachers.includes(t.id)))
            setSelectedTeachers([])
        } catch (error) {
            console.error('Failed to delete teachers:', error)
            toast.error('Error deleting teachers')
        } finally {
            setIsDeleting(false)
        }
    }

    if (loading) return <div className="loading-shimmer">Accessing teacher records...</div>

    return (
        <div className="admin-teachers">
            <div className="page-header">
                <div className="header-info">
                    <h1>Faculty Management</h1>
                    <p>Supervise teaching staff, assign classes, and track performance.</p>
                </div>
                <div className="header-actions" style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
                    {selectedTeachers.length > 0 && (
                        <button className="delete-btn" onClick={handleDeleteSelected} disabled={isDeleting} style={{ backgroundColor: '#ef4444', color: 'white', padding: '0.5rem 1rem', borderRadius: '4px', border: 'none', cursor: 'pointer' }}>
                            <FiTrash2 /> Delete Selected ({selectedTeachers.length})
                        </button>
                    )}
                    <label style={{ display: 'flex', alignItems: 'center', gap: '5px', cursor: 'pointer', background: 'white', padding: '0.5rem 1rem', borderRadius: '4px', border: '1px solid #e2e8f0' }}>
                        <input type="checkbox" onChange={handleSelectAll} checked={teachers.length > 0 && selectedTeachers.length === teachers.length} />
                        Select All
                    </label>
                    <button className="add-btn"><FiUserPlus /> Add Instructor</button>
                </div>
            </div>

            <div className="teachers-grid">
                {teachers.map((teacher) => (
                    <div key={teacher.id} className="teacher-card" style={selectedTeachers.includes(teacher.id) ? { border: '2px solid #3b82f6' } : {}}>
                        <div className="card-top" style={{ position: 'relative' }}>
                            <input
                                type="checkbox"
                                onChange={() => handleSelect(teacher.id)}
                                checked={selectedTeachers.includes(teacher.id)}
                                style={{ position: 'absolute', top: 0, left: 0 }}
                            />
                            <div className="teacher-main">
                                <div className="avatar">{teacher.name?.charAt(0)}</div>
                                <div className="info">
                                    <h3>{teacher.name}</h3>
                                    <span>Senior Instructor</span>
                                </div>
                            </div>
                            <button className="more-btn"><FiMoreVertical /></button>
                        </div>

                        <div className="card-stats">
                            <div className="stat-item">
                                <span className="val">{teacher.schedules_count || 0}</span>
                                <span className="lbl">Upcoming Classes</span>
                            </div>
                            <div className="stat-item">
                                <span className="val">{teacher.assignments_count || 0}</span>
                                <span className="lbl">Total Assignments</span>
                            </div>
                        </div>

                        <div className="contact-links">
                            <a href={`mailto:${teacher.email}`} className="contact-pill"><FiMail /> Email</a>
                            <a href={`tel:${teacher.phone_number}`} className="contact-pill"><FiPhone /> Call</a>
                        </div>

                        <div className="card-footer">
                            <button className="action-link"><FiVideo /> View Schedule</button>
                            <button className="action-link"><FiActivity /> Performance</button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    )
}
