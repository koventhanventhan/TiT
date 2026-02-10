import React, { useState, useEffect } from 'react'
import {
    FiUsers,
    FiUserCheck,
    FiCalendar,
    FiDollarSign,
    FiClock,
    FiArrowUpRight,
    FiActivity
} from 'react-icons/fi'
import { getAdminStats } from '../../services/dashboardService'
import './AdminOverview.css'

export default function AdminOverview() {
    const [data, setData] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function fetchData() {
            try {
                const stats = await getAdminStats()
                setData(stats)
            } catch (e) {
                console.error('Failed to load admin stats', e)
            } finally {
                setLoading(false)
            }
        }
        fetchData()
    }, [])

    if (loading) return <div className="loading-shimmer">Analyzing system data...</div>

    const statsCards = [
        { label: 'Total Students', value: data?.stats?.total_students, icon: <FiUsers />, color: 'blue', growth: '+12%' },
        { label: 'Total Teachers', value: data?.stats?.total_teachers, icon: <FiUserCheck />, color: 'purple', growth: '+2' },
        { label: 'Classes Today', value: data?.stats?.active_classes, icon: <FiCalendar />, color: 'green', growth: '4 Live' },
        { label: 'Monthly Revenue', value: `₹${data?.stats?.monthly_revenue}`, icon: <FiDollarSign />, color: 'orange', growth: '+8.4%' },
    ]

    return (
        <div className="admin-overview">
            <div className="overview-hero">
                <div className="hero-text">
                    <h1>System Overview</h1>
                    <p>Real-time performance metrics and management center.</p>
                </div>
                <div className="pending-badge">
                    <FiClock /> {data?.stats?.pending_registrations} Pending Registrations
                </div>
            </div>

            <div className="admin-stats-grid">
                {statsCards.map((card, idx) => (
                    <div key={idx} className={`admin-stat-card ${card.color}`}>
                        <div className="stat-card-header">
                            <div className="stat-icon-box">{card.icon}</div>
                            <span className="growth-indicator"><FiArrowUpRight /> {card.growth}</span>
                        </div>
                        <div className="stat-card-body">
                            <h3>{card.value}</h3>
                            <span>{card.label}</span>
                        </div>
                    </div>
                ))}
            </div>

            <div className="admin-dashboard-grid">
                <div className="dashboard-main-col">
                    <section className="admin-card stats-chart-card">
                        <div className="card-header">
                            <h3><FiActivity /> Student Growth Trend</h3>
                            <select className="period-select">
                                <option>Last 6 Months</option>
                            </select>
                        </div>
                        <div className="chart-placeholder">
                            {/* Graphical analytics - to be implemented with Chart.js later */}
                            <div className="mock-chart">
                                {data?.growth_data?.map((g, i) => (
                                    <div key={i} className="bar-group">
                                        <div className="bar" style={{ height: `${(g.count / Math.max(...data.growth_data.map(d => d.count))) * 100}%` }}></div>
                                        <span>{g.month.substring(0, 3)}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>
                </div>

                <div className="dashboard-side-col">
                    <section className="admin-card activity-card">
                        <div className="card-header">
                            <h3>Recent System Activity</h3>
                        </div>
                        <div className="activity-list">
                            {data?.recent_activity?.map((act, i) => (
                                <div key={i} className="activity-item">
                                    <div className={`activity-dot ${act.type}`}></div>
                                    <div className="activity-info">
                                        <p>{act.message}</p>
                                        <span>{act.time}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
            </div>
        </div>
    )
}
