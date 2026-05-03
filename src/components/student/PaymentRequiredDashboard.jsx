import React from 'react';
import { FiDollarSign, FiCalendar, FiArrowRight, FiX } from 'react-icons/fi';
import { logout } from '../../services/authService';
import '../StudentRegistrationForm.css';

const PaymentRequiredDashboard = ({ user }) => {
    const handleLogout = () => logout();

    return (
        <div className="tit-reg-overlay">
            <div className="tit-reg-wrapper" style={{ maxWidth: '600px' }}>
                <button className="tit-reg-close" onClick={handleLogout}>
                    <FiX />
                </button>

                <div className="tit-reg-container" style={{ textAlign: 'center' }}>
                    <div style={{ marginBottom: '2rem' }}>
                        <div style={{
                            width: '80px',
                            height: '80px',
                            background: 'rgba(239, 68, 68, 0.1)',
                            borderRadius: '50%',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            margin: '0 auto 1.5rem',
                            border: '2px solid rgba(239, 68, 68, 0.2)'
                        }}>
                            <FiDollarSign size={40} color="#ef4444" />
                        </div>

                        <h2 className="tit-reg-title">Monthly Payment Required</h2>

                        <p className="tit-reg-subtitle">
                            Hello <strong>{user?.full_name || 'Student'}</strong>, your account is confirmed, but we couldn't find a record of payment for the current month.
                            Please complete your monthly tuition fee to access your classes and materials.
                        </p>
                    </div>

                    <div style={{ background: 'rgba(255, 255, 255, 0.03)', borderRadius: '1rem', padding: '1.5rem', border: '1px solid rgba(255, 255, 255, 0.05)', textAlign: 'left', marginBottom: '2.5rem' }}>
                        <h3 style={{ color: '#fff', fontSize: '1.125rem', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                            <FiCalendar color="#fbbf24" /> Payment Details:
                        </h3>
                        <p style={{ color: 'rgba(199, 210, 254, 0.8)', margin: 0, fontSize: '1rem', lineHeight: '1.6' }}>
                            Month: <strong>{new Date().toLocaleString('default', { month: 'long', year: 'numeric' })}</strong><br />
                            Status: <span style={{ color: '#f87171', fontWeight: 'bold' }}>Pending</span>
                        </p>
                    </div>

                    <button
                        className="tit-reg-submit-btn"
                        style={{ width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.75rem' }}
                        onClick={() => window.location.href = '/student/dashboard/payments'}
                    >
                        Go to Payments <FiArrowRight />
                    </button>

                    <button
                        className="tit-reg-back-link"
                        onClick={handleLogout}
                    >
                        Logout
                    </button>
                </div>
            </div>
        </div>
    );
};

export default PaymentRequiredDashboard;
