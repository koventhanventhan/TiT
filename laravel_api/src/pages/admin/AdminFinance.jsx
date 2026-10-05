import React, { useState, useEffect } from 'react'
import {
    FiDollarSign,
    FiTrendingUp,
    FiAlertCircle,
    FiDownload,
    FiPieChart,
    FiSearch
} from 'react-icons/fi'
import { getAdminFinance } from '../../services/dashboardService'
import './AdminFinance.css'

export default function AdminFinance() {
    const [financeData, setFinanceData] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function loadFinance() {
            try {
                const data = await getAdminFinance()
                setFinanceData(data)
            } catch (error) {
                console.error('Error loading finance data:', error)
            } finally {
                setLoading(false)
            }
        }
        loadFinance()
    }, [])

    if (loading) return <div className="loading-shimmer">Calculating financial reports...</div>

    return (
        <div className="admin-finance">
            <div className="page-header">
                <div className="header-info">
                    <h1>Finance & Revenue</h1>
                    <p>Track payments, monitor earnings, and manage student subscriptions.</p>
                </div>
                <button className="export-btn"><FiDownload /> Generate Report</button>
            </div>

            <div className="finance-stats-grid">
                <div className="finance-card primary">
                    <div className="card-inner">
                        <span className="label">Total Revenue</span>
                        <h3>₹{financeData?.overview?.total_revenue?.toLocaleString()}</h3>
                        <div className="trend positive"><FiTrendingUp /> +15.2%</div>
                    </div>
                    <div className="card-icon"><FiDollarSign /></div>
                </div>
                <div className="finance-card">
                    <div className="card-inner">
                        <span className="label">Monthly Earning (Feb)</span>
                        <h3>₹{financeData?.overview?.monthly_revenue?.toLocaleString()}</h3>
                        <span className="sub-label">Targets: 85% Achieved</span>
                    </div>
                    <div className="card-icon blue"><FiPieChart /></div>
                </div>
                <div className="finance-card warning">
                    <div className="card-inner">
                        <span className="label">Pending Payments</span>
                        <h3>{financeData?.overview?.pending_count} Students</h3>
                        <span className="sub-label">Estimated: ₹{(financeData?.overview?.pending_count * 500).toLocaleString()}</span>
                    </div>
                    <div className="card-icon orange"><FiAlertCircle /></div>
                </div>
            </div>

            <div className="finance-main-grid">
                <div className="revenue-chart-section">
                    <div className="section-header">
                        <h3>Revenue History</h3>
                    </div>
                    <div className="history-bars">
                        {financeData?.revenue_history?.map((entry, idx) => (
                            <div key={idx} className="history-bar-item">
                                <div className="bar-container">
                                    <div
                                        className="fill-bar"
                                        style={{ height: `${(entry.total / Math.max(...financeData.revenue_history.map(h => h.total))) * 100}%` }}
                                    ></div>
                                </div>
                                <span className="month-label">{entry.month.substring(0, 3)}</span>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="recent-payments-section">
                    <div className="section-header">
                        <h3>Recent Transactions</h3>
                    </div>
                    <div className="payment-list">
                        {financeData?.recent_payments?.map((payment) => (
                            <div key={payment.id} className="payment-item">
                                <div className="pay-user-avatar">
                                    {payment.user?.full_name?.charAt(0)}
                                </div>
                                <div className="pay-details">
                                    <p className="p-name">{payment.user?.full_name}</p>
                                    <p className="p-date">{new Date(payment.paid_at).toLocaleDateString()}</p>
                                </div>
                                <div className="pay-amount">
                                    +₹{payment.amount}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    )
}
