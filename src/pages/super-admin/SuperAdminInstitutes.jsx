import React, { useState, useEffect } from 'react'
import { FiPlus, FiGlobe, FiPackage, FiCalendar, FiActivity } from 'react-icons/fi'
import { getSuperAdminInstitutes, getSuperAdminPlans } from '../../services/dashboardService'
import './SuperAdminInstitutes.css'

export default function SuperAdminInstitutes() {
    const [institutes, setInstitutes] = useState([])
    const [plans, setPlans] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
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
        loadData()
    }, [])

    if (loading) return <div className="loading-shimmer">Scanning global tenant network...</div>

    return (
        <div className="super-institutes">
            <div className="page-header">
                <div className="header-info">
                    <h1>Institutes & Tenants</h1>
                    <p>Full lifecycle management for all educational institutions on the platform.</p>
                </div>
                <button className="add-btn"><FiPlus /> Onboard New Institute</button>
            </div>

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
                                <FiGlobe /> <span>{inst.slug}.lenova.lk</span>
                            </div>
                            <div className="detail-item">
                                <FiCalendar /> <span>Expires: {inst.expires_at ? new Date(inst.expires_at).toLocaleDateString() : 'Unlimited'}</span>
                            </div>
                        </div>
                        <div className="card-actions">
                            <button className="manage-btn">Manage</button>
                            <button className="suspend-btn">Suspend</button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    )
}
