import React, { useState, useEffect } from 'react'
import { FiPlus, FiGlobe, FiPackage, FiCalendar, FiActivity, FiUsers, FiDollarSign, FiDownload, FiLogOut } from 'react-icons/fi'
import {
    getSuperAdminInstitutes,
    getSuperAdminPlans,
    updateSuperAdminInstitute,
    impersonateInstituteAdmin,
    getInstitutePayments,
    exportInstituteData,
    createSuperAdminInstitute
} from '../../services/dashboardService'
import { useAuth } from '../../context/AuthContext'
import './SuperAdminInstitutes.css'

export default function SuperAdminInstitutes() {
    const { login } = useAuth()
    const [institutes, setInstitutes] = useState([])
    const [plans, setPlans] = useState([])
    const [loading, setLoading] = useState(true)
    const [selectedInst, setSelectedInst] = useState(null)
    const [payments, setPayments] = useState(null)
    const [showCreateModal, setShowCreateModal] = useState(false)
    const [createData, setCreateData] = useState({
        name: '', slug: '', domain: '', admin_name: '', admin_email: '', admin_password: ''
    })
    const [creating, setCreating] = useState(false)

    useEffect(() => {
        loadData()
    }, [])

    async function loadData() {
        try {
            const [instData, planData] = await Promise.all([
                getSuperAdminInstitutes(),
                getSuperAdminPlans()
            ])
            setInstitutes(instData)
            setPlans(planData)
        } catch (error) {
            console.error('Error loading institutes:', error)
        } finally {
            setLoading(false)
        }
    }

    const toggleStatus = async (inst) => {
        const newStatus = inst.status === 'active' ? 'suspended' : 'active'
        try {
            const updated = await updateSuperAdminInstitute(inst.id, { status: newStatus })
            setInstitutes(prev => prev.map(i => i.id === updated.id ? { ...i, status: updated.status } : i))
        } catch (error) {
            alert('Failed to update institute status')
        }
    }

    const handleImpersonate = async (inst) => {
        if (!window.confirm(`Login as an admin for ${inst.name}?`)) return
        try {
            const res = await impersonateInstituteAdmin(inst.id)
            if (res.token && res.user) {
                login(res.user, res.token)
                window.location.href = '/admin/dashboard'
            }
        } catch (error) {
            alert('Failed to impersonate admin. Ensure an admin exists for this institute.')
        }
    }

    const handleViewBilling = async (inst) => {
        setSelectedInst(inst)
        setPayments(null)
        try {
            const res = await getInstitutePayments(inst.id)
            setPayments(res.data)
        } catch (error) {
            console.error(error)
        }
    }

    const handleExport = async (inst) => {
        try {
            const res = await exportInstituteData(inst.id)
            const blob = new Blob([JSON.stringify(res, null, 2)], { type: 'application/json' })
            const url = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = url
            a.download = `institute_${inst.slug}_export.json`
            a.click()
            window.URL.revokeObjectURL(url)
        } catch (error) {
            alert('Failed to export data')
        }
    }

    const handleCreateInstitute = async (e) => {
        e.preventDefault()
        setCreating(true)
        try {
            await createSuperAdminInstitute(createData)
            setShowCreateModal(false)
            setCreateData({ name: '', slug: '', domain: '', admin_name: '', admin_email: '', admin_password: '' })
            loadData()
            alert('Institute created successfully with admin account.')
        } catch (error) {
            alert('Failed to create institute')
        } finally {
            setCreating(false)
        }
    }

    if (loading) return <div className="loading-shimmer">Scanning global tenant network...</div>

    return (
        <div className="super-institutes">
            <div className="page-header">
                <div className="header-info">
                    <h1>Institutes & Tenants</h1>
                    <p>Full lifecycle management for all educational institutions on the platform.</p>
                </div>
                <button className="add-btn" onClick={() => setShowCreateModal(true)}><FiPlus /> Onboard New Institute</button>
            </div>

            {showCreateModal && (
                <div className="billing-modal-overlay" onClick={() => setShowCreateModal(false)}>
                    <div className="billing-modal" onClick={e => e.stopPropagation()} style={{ maxWidth: '500px' }}>
                        <h2>Onboard New Institute</h2>
                        <button className="close-btn" onClick={() => setShowCreateModal(false)}>Close</button>
                        <form onSubmit={handleCreateInstitute} style={{ display: 'flex', flexDirection: 'column', gap: '1rem', marginTop: '1rem' }}>
                            <input type="text" placeholder="Institute Name" required value={createData.name} onChange={e => setCreateData({...createData, name: e.target.value})} />
                            <input type="text" placeholder="Slug (e.g. 'royal')" required value={createData.slug} onChange={e => setCreateData({...createData, slug: e.target.value})} />
                            <input type="text" placeholder="Custom Domain (optional)" value={createData.domain} onChange={e => setCreateData({...createData, domain: e.target.value})} />
                            
                            <h4>Initial Admin User (Optional)</h4>
                            <input type="text" placeholder="Admin Name" value={createData.admin_name} onChange={e => setCreateData({...createData, admin_name: e.target.value})} />
                            <input type="email" placeholder="Admin Email" value={createData.admin_email} onChange={e => setCreateData({...createData, admin_email: e.target.value})} />
                            <input type="text" placeholder="Admin Password" value={createData.admin_password} onChange={e => setCreateData({...createData, admin_password: e.target.value})} />
                            
                            <button type="submit" className="add-btn" disabled={creating} style={{ marginTop: '1rem' }}>
                                {creating ? 'Creating...' : 'Create Institute & Admin'}
                            </button>
                        </form>
                    </div>
                </div>
            )}

            {selectedInst && (
                <div className="billing-modal-overlay" onClick={() => setSelectedInst(null)}>
                    <div className="billing-modal" onClick={e => e.stopPropagation()}>
                        <h2>Billing History for {selectedInst.name}</h2>
                        <button className="close-btn" onClick={() => setSelectedInst(null)}>Close</button>
                        {payments === null ? (
                            <p>Loading payments...</p>
                        ) : (
                            <div className="payments-list">
                                {payments.length === 0 ? <p>No payments found.</p> : (
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>User</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Ref</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {payments.map(p => (
                                                <tr key={p.id}>
                                                    <td>{new Date(p.created_at).toLocaleDateString()}</td>
                                                    <td>{p.user?.full_name || p.user?.name}</td>
                                                    <td>Rs. {parseFloat(p.amount).toFixed(2)}</td>
                                                    <td><span className={`status-pill ${p.status}`}>{p.status}</span></td>
                                                    <td>{p.gateway_ref}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            )}

            <div className="institutes-grid">
                {institutes.map((inst) => (
                    <div key={inst.id} className="institute-card">
                        <div className="inst-header">
                            <div className="inst-meta">
                                <span className={`status-pill ${inst.status}`}>{inst.status}</span>
                                <span className="plan-pill">{inst.plan?.name || 'No Plan'}</span>
                            </div>
                        </div>
                        <h3>{inst.name}</h3>
                        <div className="inst-details">
                            <div className="detail-item">
                                <FiGlobe /> <span>{inst.slug}.titjaffna.lk</span>
                            </div>
                            <div className="detail-item">
                                <FiCalendar /> <span>Expires: {inst.expires_at ? new Date(inst.expires_at).toLocaleDateString() : 'Unlimited'}</span>
                            </div>
                            <div className="detail-item">
                                <FiUsers /> <span>Students: {inst.students_count} / {inst.plan?.max_students || '∞'}</span>
                            </div>
                            <div className="detail-item">
                                <FiUsers /> <span>Teachers: {inst.teachers_count} / {inst.plan?.max_teachers || '∞'}</span>
                            </div>
                        </div>
                        <div className="card-actions" style={{ display: 'flex', flexWrap: 'wrap', gap: '8px' }}>
                            <button className="manage-btn" onClick={() => handleViewBilling(inst)}><FiDollarSign /> Billing</button>
                            <button className="manage-btn" onClick={() => handleExport(inst)}><FiDownload /> Export</button>
                            <button className="manage-btn" onClick={() => handleImpersonate(inst)}><FiLogOut /> Login as Admin</button>
                            <button
                                className={inst.status === 'active' ? 'suspend-btn' : 'activate-btn'}
                                onClick={() => toggleStatus(inst)}
                            >
                                {inst.status === 'active' ? 'Suspend' : 'Activate'}
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    )
}
