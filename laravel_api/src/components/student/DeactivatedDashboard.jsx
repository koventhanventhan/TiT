import React, { useState, useEffect } from 'react';
import { FiLock, FiCreditCard, FiX } from 'react-icons/fi';
import { logout, registerStep2 } from '../../services/authService';
import { getAuthHeaders } from '../../services/apiClient';
import '../StudentRegistrationForm.css';
import { useToast } from '../../components/shared/ToastContext';


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
        <button className="tit-reg-close" onClick={handleLogout}>
          <FiX />
        </button>

        <div className="tit-reg-container">
          {paymentStep === 'info' ? (
            <div style={{ animation: 'fadeIn 0.3s ease' }}>
              <h2 className="tit-reg-title">Account Status</h2>
              <p className="tit-reg-subtitle">உங்கள் கணக்கு தற்காலிகமாக முடக்கப்பட்டுள்ளது. மீண்டும் தொடங்க தயவுசெய்து பணம் செலுத்தவும்.</p>
              
              {loading ? (
                <div style={{ textAlign: 'center', padding: '2.5rem', color: '#c7d2fe' }}>Loading payment details...</div>
              ) : (
                <>
                  <div style={{ background: 'rgba(255,255,255,0.05)', borderRadius: '1rem', padding: '1.5rem', marginBottom: '2rem', border: '1.0px solid rgba(139, 92, 246, 0.2)' }}>
                    <h3 style={{ color: '#c7d2fe', marginBottom: '1rem', fontSize: '1.125rem' }}>Selected Subjects:</h3>
                    {paymentData.subjects && paymentData.subjects.length > 0 ? (
                      // Only show unique subjects (safety check for UI)
                      Array.from(new Map(paymentData.subjects.map(s => [s.name.toLowerCase(), s])).values()).map(s => (
                        <div key={s.name} style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem', color: 'rgba(199,210,254,0.8)' }}>
                          <span>{s.name}</span>
                          <span>Rs. {parseFloat(s.price).toFixed(0)}</span>
                        </div>
                      ))
                    ) : (
                      <p style={{ color: '#f87171' }}>No subjects found. Defaulting to base amount.</p>
                    )}
                    <div style={{ marginTop: '1rem', paddingTop: '1rem', borderTop: '1.0px solid rgba(255,255,255,0.1)', display: 'flex', justifyContent: 'space-between', fontSize: '1.375rem', fontWeight: '800', color: '#fff' }}>
                      <span>Total Amount:</span>
                      <span style={{ color: '#facc15' }}>Rs. {parseFloat(paymentData.total).toFixed(0)}</span>
                    </div>
                  </div>

                  <button 
                    className="tit-reg-submit-btn" 
                    style={{ width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.75rem' }}
                    onClick={() => setPaymentStep('options')}
                  >
                    <FiCreditCard /> Pay Now / பணம் செலுத்த
                  </button>
                </>
              )}
            </div>
          ) : paymentStep === 'submitted' ? (
            <div style={{ animation: 'fadeIn 0.3s ease', textAlign: 'center', padding: '2rem 1rem' }}>
              <div style={{ fontSize: '4rem', marginBottom: '1rem' }}>✅</div>
              <h2 className="tit-reg-title" style={{ color: '#4ade80' }}>Payment Submitted!</h2>
              <p style={{ color: '#c7d2fe', fontSize: '1.1rem', lineHeight: '1.8', marginBottom: '1.5rem' }}>
                உங்கள் Offline Payment பதிவு செய்யப்பட்டது.<br/>
                Admin உறுதிசெய்த பிறகு உங்கள் கணக்கு மீண்டும் செயல்படுத்தப்படும்.
              </p>
              <p style={{ color: 'rgba(199,210,254,0.7)', fontSize: '0.95rem', marginBottom: '2rem' }}>
                Your offline payment has been recorded. Your account will be reactivated after admin confirms the payment.
              </p>
              <button className="tit-reg-submit-btn" style={{ width: '100%' }} onClick={handleLogout}>
                OK / சரி
              </button>
            </div>
          ) : (
            <div className="tit-reg-payment-options" style={{ animation: 'fadeIn 0.3s ease' }}>
              <h2 className="tit-reg-title">Payment</h2>
              <p className="tit-reg-subtitle">Choose how you would like to pay</p>
              
              <p style={{ fontSize: '1.5rem', fontWeight: 'bold', color: '#facc15', marginBottom: '1.875rem', textAlign: 'center' }}>
                Total Amount: Rs. {parseFloat(paymentData.total).toFixed(0)}
              </p>

              <button className="tit-reg-submit-btn" style={{ width: '100%' }} onClick={handlePayOffline} disabled={processing}>
                {processing ? 'PROCESSING...' : 'I WILL PAY OFFLINE'}
              </button>
              
              <button className="tit-reg-submit-btn tit-reg-secondary" style={{ width: '100%' }} onClick={handlePayOnline} disabled={processing}>
                {processing ? 'PROCESSING...' : 'PAY ONLINE'}
              </button>

              <div style={{ textAlign: 'center' }}>
                <button className="tit-reg-back-link" onClick={() => setPaymentStep('info')}>
                  Back to form
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
