import React from 'react';
import { FiDollarSign, FiCalendar, FiArrowRight, FiX } from 'react-icons/fi';
import { logout } from '../../services/authService';
import StudentPaymentModule from './StudentPaymentModule';
import MonthlyPaymentRulesCard from './MonthlyPaymentRulesCard';
import '../StudentRegistrationForm.css';

const PaymentRequiredDashboard = ({ user }) => {
    const handleLogout = () => logout();

    return (
        <div className="tit-reg-overlay" data-testid="gating-payment-required">
            <div className="tit-reg-wrapper" style={{ maxWidth: '600px' }}>
                <button className="tit-reg-close" onClick={handleLogout} aria-label="Close" data-testid="gating-close-btn">
                    <FiX />
                </button>

                <div className="tit-reg-container" style={{ textAlign: 'center' }}>
                    <div style={{ marginBottom: '2rem' }}>
                        <div style={{
                            width: '80px',
                            height: '80px',
                            background: 'var(--t-warning-bg)',
                            borderRadius: '50%',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            margin: '0 auto 1.5rem',
                            border: '2px solid var(--t-warning)'
                        }}>
                            <FiDollarSign size={40} style={{ color: 'var(--t-warning)' }} />
                        </div>

                        <h2 className="tit-reg-title" style={{ fontSize: '1.5rem', color: '#ffffff' }}>
                            Payment Status (கட்டண விபரம்)
                        </h2>

                        <p className="tit-reg-subtitle" style={{ fontSize: '1.15rem', color: '#fff', fontWeight: '600', marginBottom: '0.5rem' }}>
                            The admin has not confirmed your payment yet. Please wait.
                        </p>
                        
                        <p style={{ color: 'var(--t-sidebar-text)', fontSize: '0.95rem', marginTop: '0.25rem' }}>
                            நிர்வாகி இன்னும் உங்கள் கட்டணத்தை உறுதிப்படுத்தவில்லை. தயவுசெய்து காத்திருக்கவும்.
                        </p>
                    </div>

                    <div style={{ background: 'rgba(255, 255, 255, 0.03)', borderRadius: '1rem', padding: '1.25rem', border: '1px solid rgba(255, 255, 255, 0.05)', textAlign: 'left', marginBottom: '2rem' }}>
                        <p style={{ color: '#ffffff', margin: '0 0 0.5rem 0', fontSize: '0.95rem', lineHeight: '1.5' }}>
                            If you have already made a payment (Manual/Offline Payment), you will be able to join classes once the admin verifies and confirms your payment.
                        </p>
                        <p style={{ color: 'var(--t-sidebar-text)', margin: 0, fontSize: '0.9rem', lineHeight: '1.5' }}>
                            நீங்கள் ஏற்கனவே பணம் செலுத்தியிருந்தால் (Manual Payment), நிர்வாகி உங்கள் கட்டணத்தைச் சரிபார்த்து உறுதிப்படுத்திய பிறகு வகுப்பில் இணைய முடியும்.
                        </p>
                    </div>

                    <div style={{ marginBottom: '1.5rem' }}>
                        <StudentPaymentModule />
                    </div>

                    <div style={{ marginBottom: '1.5rem' }}>
                        <MonthlyPaymentRulesCard isDark={true} compact={true} />
                    </div>

                    <button
                        className="tit-reg-back-link"
                        onClick={handleLogout}
                        data-testid="gating-logout-btn"
                    >
                        Logout / வெளியேறு
                    </button>
                </div>
            </div>
        </div>
    );
};

export default PaymentRequiredDashboard;
