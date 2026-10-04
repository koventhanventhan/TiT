import React, { useState, useEffect } from 'react'
import { FiPackage, FiPlus, FiCheck, FiInfo, FiEdit3 } from 'react-icons/fi'
import { getSuperAdminPlans } from '../../services/dashboardService'
import './SuperAdminPlans.css'

export default function SuperAdminPlans() {
    const [plans, setPlans] = useState([])
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function loadPlans() {
            try {
                const data = await getSuperAdminPlans()
                setPlans(data)
            } catch (error) {
                console.error('Error loading plans:', error)
            } finally {
                setLoading(false)
            }
        }
        loadPlans()
    }, [])

    if (loading) return <div className="loading-shimmer">Analyzing market tiers...</div>

    return (
        <div className="super-plans">
            <div className="page-header">
                <div className="header-info">
                    <h1>Subscription Plans</h1>
                    <p>Define pricing tiers, resource limits, and feature availability for institutes.</p>
                </div>
                <button className="add-btn"><FiPlus /> Create New Plan</button>
            </div>

            <div className="plans-grid">
                {plans.map((plan) => (
                    <div key={plan.id} className="plan-card">
                        <div className="plan-header">
                            <h3>{plan.name}</h3>
                            <div className="price-tag">
                                <span className="currency">₹</span>
                                <span className="amount">{plan.monthly_price}</span>
                                <span className="period">/mo</span>
                            </div>
                        </div>
                        <ul className="plan-features">
                            <li><FiCheck /> {plan.max_students === -1 ? 'Unlimited' : plan.max_students} Students</li>
                            <li><FiCheck /> {plan.max_teachers === -1 ? 'Unlimited' : plan.max_teachers} Teachers</li>
                            {plan.features?.map((f, i) => (
                                <li key={i}><FiCheck /> {f}</li>
                            ))}
                        </ul>
                        <div className="plan-actions">
                            <button className="edit-btn"><FiEdit3 /> Edit Tier</button>
                            <button className="delete-btn">Archive</button>
                        </div>
                    </div>
                ))}

                <div className="plan-card ghost">
                    <FiPlus />
                    <p>Define custom institutional tier</p>
                </div>
            </div>
        </div>
    )
}
