import React, { useState, useEffect } from 'react'
import {
    FiShield,
    FiTrendingUp,
    FiUsers,
    FiActivity,
    FiPlus,
    FiExternalLink
} from 'react-icons/fi'
import { getSuperAdminStats } from '../../services/dashboardService'
import './SuperAdminOverview.css'

export default function SuperAdminOverview() {
    const [stats, setStats] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function loadStats() {
            try {
                const data = await getSuperAdminStats()
                setStats(data)
            } catch (error) {
                console.error('Error loading super admin stats:', error)
            } finally {
                setLoading(false)
            }
        }
        loadStats()
    }, [])

    if (loading) return <div className="loading-shimmer">Accessing global system state...</div>

    return (
        <div className="super-overview">
            <div className="stats-grid">
                <div className="stat-card">
                    <div className="card-info">
                        <span className="label">Total Institutes</span>
                        <h3>{stats?.total_institutes || 0}</h3>
                        <span className="trend positive">Live & Running</span>
                    </div>
                    <div className="card-icon super-green"><FiShield /></div>
                </div>
                <div className="stat-card">
                    <div className="card-info">
                        <span className="label">System Revenue</span>
                        <h3>₹{stats?.total_revenue?.toLocaleString() || 0}</h3>
                        <span className="trend positive">+12% this month</span>
                    </div>
                    <div className="card-icon super-blue"><FiTrendingUp /></div>
                </div>
                <div className="stat-card">
                    <div className="card-info">
                        <span className="label">Global Users</span>
                        <h3>{stats?.total_users || 0}</h3>
                        <span className="sub-label">Across all tenants</span>
                    </div>
                    <div className="card-icon super-orange"><FiUsers /></div>
                </div>
                <div className="stat-card">
                    <div className="card-info">
                        <span className="label">System Health</span>
                        <h3>99.9%</h3>
                        <span className="trend positive">Optimal Performance</span>
                    </div>
                    <div className="card-icon super-purple"><FiActivity /></div>
                </div>
            </div>

            <div className="overview-main">
                <div className="recent-activity-card">
                    <div className="card-header">
                        <h4>System-Wide Events</h4>
                        <button className="view-all">View Logs</button>
                    </div>
                    <div className="activity-list">
                        {[
                            { id: 1, text: "Jaffna Science Academy joined the platform", time: "2 hours ago", type: "new_tenant" },
                            { id: 2, text: "Subscription renewal for Royal College (Annual Plan)", time: "5 hours ago", type: "payment" },
                            { id: 3, text: "Server maintenance completed successfully", time: "Yesterday", type: "system" }
                        ].map((act) => (
                            <div key={act.id} className="activity-item">
                                <div className={`act-dot ${act.type}`}></div>
                                <div className="act-details">
                                    <p>{act.text}</p>
                                    <span>{act.time}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    )
}
