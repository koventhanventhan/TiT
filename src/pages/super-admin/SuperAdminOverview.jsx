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
import { useToast } from '../../components/shared/ToastContext'
import './SuperAdminOverview.css'

export default function SuperAdminOverview() {
    const toast = useToast()
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
                        <span className="label">Registered Accounts</span>
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
                        <h4>Recent System-Wide Events</h4>
                        <button className="view-all" onClick={() => toast.info("Full system logs view coming soon")}>View All Logs</button>
                    </div>
                    <div className="activity-list">
                        {stats?.recent_activity?.length > 0 ? (
                            stats.recent_activity.map((act) => (
                                <div key={act.id} className="activity-item">
                                    <div className={`act-dot ${act.action.includes('error') ? 'system' : act.action.includes('payment') ? 'payment' : 'new_tenant'}`}></div>
                                    <div className="act-details">
                                        <p>
                                            <strong>{act.user?.full_name || act.user?.name || 'System'}:</strong> {act.description}
                                            {act.institute && <span className="inst-tag"> @ {act.institute.name}</span>}
                                        </p>
                                        <span>{new Date(act.created_at).toLocaleString()}</span>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="no-activity">No recent activity detected.</div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    )
}
