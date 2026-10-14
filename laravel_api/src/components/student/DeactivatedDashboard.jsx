import React, { useState, useEffect } from 'react';
import { FiLock, FiCreditCard, FiX } from 'react-icons/fi';
import { logout, registerStep2 } from '../../services/authService';
import { getAuthHeaders } from '../../services/apiClient';
import '../StudentRegistrationForm.css';
import { useToast } from '../../components/shared/ToastContext';
import FeeRow from '../shared/FeeRow';
import { formatRs } from '../../utils/format';

const DeactivatedDashboard = () => {
  const toast = useToast();

  const [paymentData, setPaymentData] = useState({ total: 500, subjects: [] });
  const [loading, setLoading] = useState(true);
  const [paymentStep, setPaymentStep] = useState('info'); // 'info' or 'options'
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    const fetchPaymentDetails = async () => {
      try {
        const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';
        const response = await fetch(`${API_BASE_URL}/student/payment-details`, {
          headers: getAuthHeaders()
        });
        if (response.ok) {
          const data = await response.json();
          setPaymentData(data);
        }
      } catch (err) {
        console.error('Failed to fetch payment details:', err);
      } finally {
        setLoading(false);
      }
    };
    fetchPaymentDetails();
  }, []);

  const handleLogout = () => logout();

  const handlePayOffline = async () => {
    setProcessing(true);
    try {
      await registerStep2('offline', paymentData.total);
      setPaymentStep('submitted');
    } catch (err) {
      toast.error('Error: ' + err.message);
      setProcessing(false);
    }
  };

  const handlePayOnline = async () => {
    setProcessing(true);
    try {
      const resp = await registerStep2('online', paymentData.total);
      
      if (!window.payhere) {
          await new Promise((resolve, reject) => {
              const script = document.createElement('script');
              script.src = 'https://www.payhere.lk/lib/payhere.js';
              script.async = true;
              script.onload = resolve;
              script.onerror = () => reject(new Error('Failed to load PayHere'));
              document.body.appendChild(script);
          });
      }

      if (!window.payhere) { toast.info('PayHere SDK not loaded.'); return; }
      const payment = { sandbox: resp.payhere_url.includes('sandbox'), ...resp.params };
      window.payhere.onCompleted = () => { toast.success('Success!'); window.location.reload(); };
      window.payhere.onDismissed = () => setProcessing(false);
      window.payhere.onError = (err) => { toast.error("Error: " + err); setProcessing(false); };
      window.payhere.startPayment(payment);
    } catch (err) {
      toast.error('Failed: ' + err.message);
      setProcessing(false);
    }
  };

  return (
    <div className="tit-reg-overlay">
      <div className={`tit-reg-wrapper ${paymentStep === 'options' ? 'payment-step-active' : ''}`}>
        <button className="tit-reg-close" onClick={handleLogout} aria-label="Close">
          <FiX />
        </button>

        <div className="tit-reg-container">
          {paymentStep === 'info' ? (
            <div style={{ animation: 'fadeIn 0.3s ease' }}>
              <h2 className="tit-reg-title">Account Status / கணக்கு நிலை</h2>
              <p className="tit-reg-subtitle" style={{ marginBottom: '0.5rem', color: '#ffffff' }}>
                Your account is temporarily suspended. Please complete your payment to reactivate.
              </p>
              <p style={{ color: 'var(--t-sidebar-text)', fontSize: '0.95rem', marginBottom: '1.5rem', textAlign: 'center' }}>
                உங்கள் கணக்கு தற்காலிகமாக முடக்கப்பட்டுள்ளது. மீண்டும் தொடங்க தயவுசெய்து பணம் செலுத்தவும்.
              </p>
              
              {loading ? (
                <div style={{ textAlign: 'center', padding: '2.5rem', color: 'var(--t-sidebar-text)' }}>Loading payment details...</div>
              ) : (
                <>
                  <div style={{ background: 'rgba(255,255,255,0.05)', borderRadius: '1rem', padding: '1.25rem', marginBottom: '1.5rem', border: '1.0px solid var(--t-primary-border)' }}>
                    <h3 style={{ color: 'var(--t-primary-border)', marginBottom: '0.75rem', fontSize: '1rem' }}>Selected Subjects / பாடங்கள்:</h3>
                    {paymentData.subjects && paymentData.subjects.length > 0 ? (
                      // Only show unique subjects (safety check for UI)
                      Array.from(new Map(paymentData.subjects.map(s => [(s?.name ? s.name.toLowerCase() : ''), s])).values()).map(s => (
                        <FeeRow
                          key={s?.name || Math.random()}
                          label={s?.name}
                          value={formatRs(s?.price || 0)}
                        />
                      ))
                    ) : (
                      <p style={{ color: 'var(--t-danger)' }}>No subjects found. Defaulting to base amount.</p>
                    )}
                    <FeeRow
                      label="Total Amount:"
                      value={formatRs(paymentData.total || 0)}
                      isTotal={true}
                    />
                  </div>

                  <button 
                    className="tit-reg-submit-btn" 
                    style={{ width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.75rem', background: 'var(--t-primary)' }}
                    onClick={() => setPaymentStep('options')}
                  >
                    <FiCreditCard /> Pay Now / பணம் செலுத்த
                  </button>
                </>
              )}
            </div>
          ) : paymentStep === 'submitted' ? (
            <div style={{ animation: 'fadeIn 0.3s ease', textAlign: 'center', padding: '1.5rem 1rem' }}>
              <div style={{ fontSize: '3.5rem', marginBottom: '0.75rem' }}>✅</div>
              <h2 className="tit-reg-title" style={{ color: 'var(--t-success)', fontSize: '1.35rem' }}>
                Payment Submitted! / கட்டணம் சமர்ப்பிக்கப்பட்டது!
              </h2>
              <p style={{ color: '#ffffff', fontSize: '1rem', lineHeight: '1.6', marginBottom: '0.75rem' }}>
                Your offline payment has been recorded. Your account will be reactivated after the admin confirms the payment.
              </p>
              <p style={{ color: 'var(--t-sidebar-text)', fontSize: '0.9rem', lineHeight: '1.6', marginBottom: '1.75rem' }}>
                உங்கள் Offline Payment பதிவு செய்யப்பட்டது. நிர்வாகி உறுதிசெய்த பிறகு உங்கள் கணக்கு மீண்டும் செயல்படுத்தப்படும்.
              </p>
              <button className="tit-reg-submit-btn" style={{ width: '100%', background: 'var(--t-primary)' }} onClick={handleLogout}>
                OK / சரி
              </button>
            </div>
          ) : (
            <div className="tit-reg-payment-options" style={{ animation: 'fadeIn 0.3s ease' }}>
              <h2 className="tit-reg-title">Payment / கட்டணம்</h2>
              <p className="tit-reg-subtitle" style={{ marginBottom: '1.25rem' }}>
                Choose how you would like to pay / எவ்வாறு பணம் செலுத்த விரும்புகிறீர்கள் என்பதைத் தேர்வுசெய்க
              </p>
              
              <div style={{ marginBottom: '1.5rem' }}>
                <FeeRow
                  label="Total Amount:"
                  value={formatRs(paymentData.total || 0)}
                  isTotal={true}
                />
              </div>

              <button className="tit-reg-submit-btn" style={{ width: '100%', background: 'var(--t-primary)' }} onClick={handlePayOffline} disabled={processing}>
                {processing ? 'PROCESSING...' : 'I WILL PAY OFFLINE / ஆஃப்லைனில் செலுத்துவேன்'}
              </button>
              
              <button className="tit-reg-submit-btn tit-reg-secondary" style={{ width: '100%' }} onClick={handlePayOnline} disabled={processing}>
                {processing ? 'PROCESSING...' : 'PAY ONLINE / ஆன்லைனில் செலுத்துங்கள்'}
              </button>

              <div style={{ textAlign: 'center' }}>
                <button className="tit-reg-back-link" onClick={() => setPaymentStep('info')}>
                  Back to form / மீண்டும் படிவத்திற்கு
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default DeactivatedDashboard;
