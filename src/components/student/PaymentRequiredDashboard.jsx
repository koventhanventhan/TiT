import React from 'react';
import { FiDollarSign, FiCalendar, FiArrowRight, FiX } from 'react-icons/fi';
import { logout } from '../../services/authService';
import StudentPaymentModule from './StudentPaymentModule';
import '../StudentRegistrationForm.css';

const PaymentRequiredDashboard = ({ user }) => {
    const handleLogout = () => logout();

    return (
        <div className="student-registration-overlay">
            <div className="student-registration-wrapper" style={{ maxWidth: '600px' }}>
                <button className="student-registration-close" onClick={handleLogout}>
                    <FiX />
                </button>

                <div className="student-registration-container" style={{ textAlign: 'center', padding: '2rem 1.5rem' }}>
                    <div style={{ marginBottom: '1.5rem' }}>
                        <div style={{
                            width: '70px',
                            height: '70px',
                            background: 'rgba(239, 68, 68, 0.1)',
                            borderRadius: '50%',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            margin: '0 auto 1rem',
                            border: '2px solid rgba(239, 68, 68, 0.2)'
                        }}>
                            <FiDollarSign size={36} color="#ef4444" />
                        </div>

                        <h2 style={{ fontSize: '1.6rem', fontWeight: '800', marginBottom: '0.75rem', background: 'linear-gradient(to right, #f87171, #f43f5e)', WebkitBackgroundClip: 'text', WebkitTextFillColor: 'transparent' }}>
                            Monthly Payment Required
                        </h2>

                        <p style={{ color: '#c7d2fe', fontSize: '1rem', lineHeight: '1.5', marginBottom: '1.5rem' }}>
                            Hello <strong>{user?.full_name || 'Student'}</strong>, please complete your monthly tuition fee or submit an offline payment request to access your classes and materials.
                        </p>
                    </div>

                    <div style={{ marginBottom: '1.5rem' }}>
                        <StudentPaymentModule />
                    </div>

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
