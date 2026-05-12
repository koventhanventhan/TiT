import React from 'react';
import { FiClock, FiCheckCircle, FiShield, FiX } from 'react-icons/fi';
import { logout } from '../../services/authService';
import '../StudentRegistrationForm.css';

const PendingApprovalDashboard = ({ user }) => {
    const handleLogout = () => logout();

    return (
        <div className="tit-reg-overlay">
            <div className="tit-reg-wrapper" style={{ maxWidth: '600px' }}>
                <button className="tit-reg-close" onClick={handleLogout}>
                    <FiX />
                </button>

                <div className="tit-reg-container" style={{ textAlign: 'center', padding: '3rem 2rem' }}>
                    <div style={{ marginBottom: '2rem' }}>
                        <div style={{
                            width: '80px',
                            height: '80px',
                            background: 'rgba(59, 130, 246, 0.1)',
                            borderRadius: '50%',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            margin: '0 auto 1.5rem',
                            border: '2px solid rgba(59, 130, 246, 0.2)'
                        }}>
                            <FiClock size={40} color="#3b82f6" />
                        </div>

                        <h2 style={{ fontSize: '1.5rem', fontWeight: '800', marginBottom: '1rem', background: 'linear-gradient(to right, #60a5fa, #a78bfa)', WebkitBackgroundClip: 'text', WebkitTextFillColor: 'transparent', lineHeight: '1.4' }}>
                            Admin இன்னும் உங்கள் பதிவை உறுதிப்படுத்தவில்லை. தயவுசெய்து காத்திருக்கவும்.
                        </h2>

                        <p style={{ color: '#c7d2fe', fontSize: '1.1rem', lineHeight: '1.6', marginBottom: '2rem' }}>
                            (Account pending admin approval. Please wait.) <br/>
                            நீங்கள் Manual Payment முறையைத் தேர்ந்தெடுத்திருந்தால், Admin உங்கள் கட்டணத்தைச் சரிபார்த்த பிறகு உங்கள் கணக்கு உறுதிப்படுத்தப்படும்.
                        </p>
                    </div>

                    <div style={{ background: 'rgba(255, 255, 255, 0.03)', borderRadius: '1rem', padding: '1.5rem', border: '1px solid rgba(255, 255, 255, 0.05)', textAlign: 'left', marginBottom: '2.5rem' }}>
                        <h3 style={{ color: '#fff', fontSize: '1rem', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                            <FiShield color="#10b981" /> Next Steps:
                        </h3>
                        <ul style={{ color: 'rgba(199, 210, 254, 0.8)', paddingLeft: '1.25rem', margin: 0 }}>
                            <li style={{ marginBottom: '0.75rem' }}>Admin உங்கள் கட்டணம் மற்றும் விபரங்களைச் சரிபார்ப்பார். (Admin will verify details)</li>
                            <li style={{ marginBottom: '0.75rem' }}>உங்கள் WhatsApp எண் ({user?.phone_number || 'N/A'}) மூலம் உங்களுக்குத் தகவல் தெரிவிக்கப்படும். (WhatsApp notification)</li>
                            <li>உறுதிப்படுத்தப்பட்ட பிறகு, நீங்கள் அனைத்து பாடங்கள் மற்றும் வகுப்புகளில் இணைய முடியும். (Access after approval)</li>
                        </ul>
                    </div>

                    <button
                        className="tit-reg-submit-btn"
                        style={{ width: '100%', background: 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)' }}
                        onClick={() => window.location.reload()}
                    >
                        Check Status / Refresh
                    </button>

                    <button
                        className="tit-reg-back-link"
                        style={{ marginTop: '1rem' }}
                        onClick={handleLogout}
                    >
                        Logout
                    </button>
                </div>
            </div>
        </div>
    );
};

export default PendingApprovalDashboard;
