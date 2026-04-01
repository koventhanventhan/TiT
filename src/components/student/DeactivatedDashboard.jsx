import React, { useState, useEffect } from 'react';
import { FiLock, FiCreditCard, FiX } from 'react-icons/fi';
import { logout, registerStep2 } from '../../services/authService';
import '../StudentRegistrationForm.css';

const DeactivatedDashboard = () => {
  const [paymentData, setPaymentData] = useState({ total: 500, subjects: [] });
  const [loading, setLoading] = useState(true);
  const [paymentStep, setPaymentStep] = useState('info'); // 'info' or 'options'
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    const fetchPaymentDetails = async () => {
      try {
        const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';
        const response = await fetch(`${API_BASE_URL}/student/payment-details`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
            'Accept': 'application/json'
          }
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
      alert('உங்கள் விண்ணப்பம் சமர்ப்பிக்கப்பட்டது. (Registration submitted.)');
    } catch (err) {
      alert('Error: ' + err.message);
    } finally {
      setProcessing(false);
    }
  };

  const handlePayOnline = async () => {
    setProcessing(true);
    try {
      const resp = await registerStep2('online', paymentData.total);
      if (!window.payhere) { alert('PayHere SDK not loaded.'); return; }
      const payment = { sandbox: resp.payhere_url.includes('sandbox'), ...resp.params };
      window.payhere.onCompleted = () => { alert('Success!'); window.location.reload(); };
      window.payhere.onDismissed = () => setProcessing(false);
      window.payhere.onError = (err) => { alert("Error: " + err); setProcessing(false); };
      window.payhere.startPayment(payment);
    } catch (err) {
      alert('Failed: ' + err.message);
      setProcessing(false);
    }
  };

  return (
    <div className="student-registration-overlay">
      <div className={`student-registration-wrapper ${paymentStep === 'options' ? 'payment-step-active' : ''}`}>
        <button className="student-registration-close" onClick={handleLogout}>
          <FiX />
        </button>

        <div className="student-registration-container">
          {paymentStep === 'info' ? (
            <div style={{ animation: 'fadeIn 0.3s ease' }}>
              <h2>Account Status</h2>
              <p className="form-subtitle">உங்கள் கணக்கு தற்காலிகமாக முடக்கப்பட்டுள்ளது. மீண்டும் தொடங்க தயவுசெய்து பணம் செலுத்தவும்.</p>
              
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
                    className="submit-button" 
                    style={{ width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.75rem' }}
                    onClick={() => setPaymentStep('options')}
                  >
                    <FiCreditCard /> Pay Now / பணம் செலுத்த
                  </button>
                </>
              )}
            </div>
          ) : (
            <div className="payment-options" style={{ animation: 'fadeIn 0.3s ease' }}>
              <h2>Payment</h2>
              <p className="form-subtitle">Choose how you would like to pay</p>
              
              <p style={{ fontSize: '1.5rem', fontWeight: 'bold', color: '#facc15', marginBottom: '1.875rem', textAlign: 'center' }}>
                Total Amount: Rs. {parseFloat(paymentData.total).toFixed(0)}
              </p>

              <button className="submit-button" style={{ width: '100%' }} onClick={handlePayOffline} disabled={processing}>
                {processing ? 'PROCESSING...' : 'I WILL PAY OFFLINE'}
              </button>
              
              <button className="submit-button secondary" style={{ width: '100%' }} onClick={handlePayOnline} disabled={processing}>
                {processing ? 'PROCESSING...' : 'PAY ONLINE'}
              </button>

              <div style={{ textAlign: 'center' }}>
                <button className="back-link" onClick={() => setPaymentStep('info')}>
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
