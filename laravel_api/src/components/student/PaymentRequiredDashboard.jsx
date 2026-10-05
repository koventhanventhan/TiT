import React from 'react';
import { FiDollarSign, FiCalendar, FiArrowRight, FiX } from 'react-icons/fi';
import { logout } from '../../services/authService';
import StudentPaymentModule from './StudentPaymentModule';
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
                            background: 'rgba(245, 158, 11, 0.1)',
                            borderRadius: '50%',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            margin: '0 auto 1.5rem',
                            border: '2px solid rgba(245, 158, 11, 0.2)'
                        }}>
                            <FiDollarSign size={40} color="#f59e0b" />
                        </div>

                        <h2 className="tit-reg-title" style={{ fontSize: '1.5rem', color: '#fbbf24' }}>
                            கட்டண விபரம் (Payment Status)
                        </h2>

                        <p className="tit-reg-subtitle" style={{ fontSize: '1.2rem', color: '#fff', fontWeight: '500' }}>
                            அட்மின் இன்னும் உங்கள் கட்டணத்தை உறுதிப்படுத்தவில்லை. தயவுசெய்து காத்திருக்கவும்.
                        </p>
                        
                        <p style={{ color: '#c7d2fe', fontSize: '1rem', marginTop: '0.5rem' }}>
                            (Admin yet to update your payment status. Please wait.)
                        </p>
                    </div>

                    <div style={{ background: 'rgba(255, 255, 255, 0.03)', borderRadius: '1rem', padding: '1.5rem', border: '1px solid rgba(255, 255, 255, 0.05)', textAlign: 'left', marginBottom: '2rem' }}>
                        <p style={{ color: 'rgba(199, 210, 254, 0.8)', margin: 0, fontSize: '1rem', lineHeight: '1.6' }}>
                            நீங்கள் ஏற்கனவே பணம் செலுத்தியிருந்தால் (Manual Payment), அட்மின் உங்கள் கட்டணத்தைச் சரிபார்த்து உறுதிப்படுத்திய பிறகு வகுப்பில் இணைய முடியும்.
                        </p>
                    </div>

                    <div style={{ marginBottom: '1.5rem' }}>
                        <StudentPaymentModule />
                    </div>

                    <button
                        className="tit-reg-back-link"
                        onClick={handleLogout}
                    >
                        Logout (வெளியேற)
                    </button>
                </div>
            </div>
        </div>
    );
};

export default PaymentRequiredDashboard;
