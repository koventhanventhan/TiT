import React from 'react';
import { FiClock, FiCheckCircle, FiShield, FiX } from 'react-icons/fi';
import { logout } from '../../services/authService';
import '../StudentRegistrationForm.css';

const PendingApprovalDashboard = ({ user }) => {
    const handleLogout = () => logout();

    return (
        <div className="tit-reg-overlay" data-testid="gating-pending-approval">
            <div className="tit-reg-wrapper" style={{ maxWidth: '600px' }}>
                <button className="tit-reg-close" onClick={handleLogout} aria-label="Close" data-testid="gating-close-btn">
                    <FiX />
                </button>

                <div className="tit-reg-container" style={{ textAlign: 'center', padding: '3rem 2rem' }}>
                    <div style={{ marginBottom: '2rem' }}>
                        <div style={{
                            width: '80px',
                            height: '80px',
                            background: 'var(--t-primary-soft)',
                            borderRadius: '50%',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            margin: '0 auto 1.5rem',
                            border: '2px solid var(--t-primary-border)'
                        }}>
                            <FiClock size={40} style={{ color: 'var(--t-primary)' }} />
                        </div>

                        <h2 style={{ fontSize: '1.35rem', fontWeight: '800', marginBottom: '0.5rem', color: '#ffffff', lineHeight: '1.4' }}>
                            The admin has not confirmed your registration yet. Please wait.
                        </h2>
                        <h3 style={{ fontSize: '1.1rem', fontWeight: '600', marginBottom: '1.5rem', color: 'var(--t-primary-border)', lineHeight: '1.4' }}>
                            நிர்வாகி இன்னும் உங்கள் பதிவை உறுதிப்படுத்தவில்லை. தயவுசெய்து காத்திருக்கவும்.
                        </h3>

                        <p style={{ color: '#ffffff', fontSize: '0.95rem', lineHeight: '1.5', marginBottom: '0.5rem' }}>
                            If you selected Manual/Offline Payment, your account will be confirmed once the admin verifies your payment.
                        </p>
                        <p style={{ color: 'var(--t-sidebar-text)', fontSize: '0.875rem', lineHeight: '1.5', marginBottom: '1.5rem' }}>
                            நீங்கள் Manual Payment முறையைத் தேர்ந்தெடுத்திருந்தால், நிர்வாகி உங்கள் கட்டணத்தைச் சரிபார்த்த பிறகு உங்கள் கணக்கு உறுதிப்படுத்தப்படும்.
                        </p>
                    </div>

                    <div style={{ background: 'rgba(255, 255, 255, 0.03)', borderRadius: '1rem', padding: '1.25rem', border: '1px solid rgba(255, 255, 255, 0.05)', textAlign: 'left', marginBottom: '2rem' }}>
                        <h3 style={{ color: '#fff', fontSize: '1rem', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                            <FiShield style={{ color: 'var(--t-success)' }} /> Next Steps / அடுத்த கட்டங்கள்:
                        </h3>
                        <ul style={{ color: 'var(--t-sidebar-text)', paddingLeft: '1.25rem', margin: 0, fontSize: '0.875rem' }}>
                            <li style={{ marginBottom: '0.75rem' }}>
                                <strong style={{ color: '#fff' }}>Verification:</strong> The admin will verify your payment and details. <br />
                                <span>நிர்வாகி உங்கள் கட்டணம் மற்றும் விபரங்களைச் சரிபார்ப்பார்.</span>
                            </li>
                            <li style={{ marginBottom: '0.75rem' }}>
                                <strong style={{ color: '#fff' }}>Notification:</strong> You will be notified via WhatsApp ({user?.phone_number?.startsWith('94') ? '+' + user.phone_number : (user?.phone_number || 'N/A')}) or Email. <br />
                                <span>உங்கள் WhatsApp எண் அல்லது Email மூலம் உங்களுக்குத் தகவல் தெரிவிக்கப்படும்.</span>
                            </li>
                            <li>
                                <strong style={{ color: '#fff' }}>Access:</strong> Once confirmed, you will be able to access all classes and subjects. <br />
                                <span>உறுதிப்படுத்தப்பட்ட பிறகு, நீங்கள் அனைத்து பாடங்கள் மற்றும் வகுப்புகளில் இணைய முடியும்.</span>
                            </li>
                        </ul>
                    </div>

                    <button
                        className="tit-reg-submit-btn"
                        style={{ width: '100%', background: 'var(--t-primary)' }}
                        onClick={() => window.location.reload()}
                    >
                        Check Status / Refresh
                    </button>

                    <button
                        className="tit-reg-back-link"
                        style={{ marginTop: '1rem' }}
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

export default PendingApprovalDashboard;
