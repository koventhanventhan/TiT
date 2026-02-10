import React, { useState, useEffect } from 'react'
import { FiSearch, FiMail, FiPhone, FiInfo } from 'react-icons/fi'
import { getTeacherStudents } from '../../services/dashboardService'
import './TeacherSections.css'

export default function TeacherStudents() {
    const [students, setStudents] = useState([])
    const [search, setSearch] = useState('')
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function load() {
            try {
                const data = await getTeacherStudents()
                setStudents(data)
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
        <div className="teacher-section">
            <div className="section-top">
                <h2>My Students</h2>
                <div className="section-actions">
                    <div className="search-box">
                        <FiSearch />
                        <input
                            type="text"
                            placeholder="Search students..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                </div>
            </div>

            {loading ? (
                <div className="loading-shimmer">Loading students...</div>
            ) : (
                <div className="data-table-container">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Grade / Stream</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filtered.map(student => (
                                <tr key={student.id}>
                                    <td>
                                        <div className="student-name-cell">
                                            <div className="avatar-small">{student.name.charAt(0)}</div>
                                            <span>{student.name}</span>
                                        </div>
                                    </td>
                                    <td>{student.current_grade || 'N/A'} - {student.stream || 'General'}</td>
                                    <td>
                                        <div className="contact-icons">
                                            <FiMail title={student.email} />
                                            <FiPhone title={student.phone_number} />
                                        </div>
                                    </td>
                                    <td><span className="badge-active">Enrolled</span></td>
                                    <td>
                                        <button className="btn-icon-action"><FiInfo /></button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    )
}
