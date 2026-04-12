import React from 'react';
import { FiDollarSign, FiCalendar, FiArrowRight, FiX } from 'react-icons/fi';
import { logout } from '../../services/authService';
import '../StudentRegistrationForm.css';

const PaymentRequiredDashboard = ({ user }) => {
    const handleLogout = () => logout();

    return (
        <div className="student-registration-overlay">
            <div className="student-registration-wrapper" style={{ maxWidth: '600px' }}>
                <button className="student-registration-close" onClick={handleLogout}>
                    <FiX />
                </button>

                <div className="student-registration-container" style={{ textAlign: 'center', padding: '3rem 2rem' }}>
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

                        <h2 style={{ fontSize: '1.75rem', fontWeight: '800', marginBottom: '1rem', background: 'linear-gradient(to right, #f87171, #f43f5e)', WebkitBackgroundClip: 'text', WebkitTextFillColor: 'transparent' }}>
                            Monthly Payment Required
                        </h2>

                        <p style={{ color: '#c7d2fe', fontSize: '1.1rem', lineHeight: '1.6', marginBottom: '2rem' }}>
                            Hello <strong>{user?.full_name || 'Student'}</strong>, your account is confirmed, but we couldn't find a record of payment for the current month.
                            Please complete your monthly tuition fee to access your classes and materials.
                        </p>
                    </div>

                    <div style={{ background: 'rgba(255, 255, 255, 0.03)', borderRadius: '1rem', padding: '1.5rem', border: '1px solid rgba(255, 255, 255, 0.05)', textAlign: 'left', marginBottom: '2.5rem' }}>
                        <h3 style={{ color: '#fff', fontSize: '1rem', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                            <FiCalendar color="#fbbf24" /> Payment Details:
                        </h3>
                        <p style={{ color: 'rgba(199, 210, 254, 0.8)', margin: 0 }}>
                            Month: <strong>{new Date().toLocaleString('default', { month: 'long', year: 'numeric' })}</strong><br />
                            Status: <span style={{ color: '#f87171' }}>Pending</span>
                        </p>
                    </div>

                    <button
                        className="submit-button"
                        style={{ width: '100%', background: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.5rem' }}
                        onClick={() => window.location.href = '/student/dashboard/payments'}
                    >
                        Go to Payments <FiArrowRight />
                    </button>

                    <button
                        className="back-link"
                        style={{ marginTop: '1rem', background: 'transparent', border: 'none', color: '#94a3b8', cursor: 'pointer' }}
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
