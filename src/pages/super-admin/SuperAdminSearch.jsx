import React, { useState } from 'react'
import { searchSuperAdminUsers } from '../../services/dashboardService'
import { FiSearch, FiUser, FiMail, FiPhone } from 'react-icons/fi'
import './SuperAdminInstitutes.css'

export default function SuperAdminSearch() {
    const [query, setQuery] = useState('')
    const [results, setResults] = useState([])
    const [searching, setSearching] = useState(false)

    const handleSearch = async (e) => {
        e.preventDefault()
        if (!query.trim()) return
        
        setSearching(true)
        try {
            const res = await searchSuperAdminUsers(query)
            setResults(res)
        } catch (error) {
            console.error(error)
        } finally {
            setSearching(false)
        }
    }

    return (
        <div className="super-institutes">
            <div className="page-header">
                <div className="header-info">
                    <h1>Global Search</h1>
                    <p>Search across all institutes by name, email, or phone number.</p>
                </div>
            </div>

            <div className="admin-form-card" style={{ marginBottom: '2rem', padding: '1.5rem', background: 'var(--card-bg)', borderRadius: '12px' }}>
                <form onSubmit={handleSearch} style={{ display: 'flex', gap: '1rem' }}>
                    <input 
                        type="text" 
                        placeholder="Search by name, email, or phone..." 
                        value={query} 
                        onChange={e => setQuery(e.target.value)} 
                        style={{ flex: 1, padding: '0.75rem', borderRadius: '8px', border: '1px solid var(--border-color)' }}
                    />
                    <button type="submit" className="add-btn" disabled={searching}>
                        <FiSearch /> {searching ? 'Searching...' : 'Search'}
                    </button>
                </form>
            </div>

            <div className="institutes-grid">
                {results.length === 0 && query && !searching && <p>No users found.</p>}
                
                {results.map(user => (
                    <div key={user.id} className="institute-card">
                        <div className="inst-header">
                            <span className="status-pill active">{user.role}</span>
                            <span className="plan-pill">{user.institute?.name || 'No Institute'}</span>
                        </div>
                        <h3>{user.full_name || user.name}</h3>
                        <div className="inst-details">
                            <div className="detail-item">
                                <FiMail /> <span>{user.email}</span>
                            </div>
                            <div className="detail-item">
                                <FiPhone /> <span>{user.phone_number || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    )
}
