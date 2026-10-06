import React, { useState, useEffect } from 'react';
import { FiCreditCard, FiCheckCircle, FiAlertCircle, FiLoader } from 'react-icons/fi';
import { getStudentPaymentStatus, initializeMonthlyPayment } from '../../services/dashboardService';
import { useLanguage } from '../../context/LanguageContext';
import './StudentPaymentModule.css';

const StudentPaymentModule = () => {
    const { t } = useLanguage();
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(true);
    const [paying, setPaying] = useState(false);
    const [error, setError] = useState(null);

    const fetchStatus = async () => {
        try {
            setLoading(true);
            const data = await getStudentPaymentStatus();
            setStatus(data);
        } catch (err) {
            console.error('Error fetching payment status:', err);
            setError('Failed to load payment info');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchStatus();
    }, []);

    const handlePayment = async () => {
        try {
            setPaying(true);
            setError(null);

            // Dynamically load PayHere script if not loaded
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

            const resp = await initializeMonthlyPayment();
            
            if (!window.payhere) {
                throw new Error('PayHere SDK not loaded');
            }

            const payment = {
                sandbox: resp.payhere_url.includes('sandbox'),
                ...resp.params
            };

            window.payhere.onCompleted = function onCompleted(orderId) {
                console.log("Payment completed. OrderID:" + orderId);
                fetchStatus();
                setPaying(false);
            };

            window.payhere.onDismissed = function onDismissed() {
                console.log("Payment dismissed");
                setPaying(false);
            };

            window.payhere.onError = function onError(error) {
                console.log("PayHere Error:" + error);
                setError("Payment Error: " + error);
                setPaying(false);
            };

            window.payhere.startPayment(payment);
        } catch (err) {
            console.error('Payment initialization error:', err);
            setError(err.message || 'Failed to start payment');
            setPaying(false);
        }
    };

    if (loading) return (
        <div className="payment-module-card loading">
            <FiLoader className="spin-icon" />
            <span>Checking payment status...</span>
        </div>
    );

    if (error && !status) return (
        <div className="payment-module-card error">
            <FiAlertCircle />
            <span>{error}</span>
            <button onClick={fetchStatus}>Retry</button>
        </div>
    );

    if (!status) return null;

    const { is_paid, amount, year_month, payment, paid_until } = status;
    const monthName = new Date(year_month + '-01').toLocaleString('default', { month: 'long', year: 'numeric' });
    const isPendingOffline = payment && payment.status === 'pending' && payment.payment_method === 'offline';

    return (
        <section className="dashboard-payment-section">
            <h3 className="section-title">{t('your_payments') || 'Your Payments'}</h3>
            <div className={`payment-module-card ${is_paid ? 'paid' : 'pending'}`}>
                <div className="payment-card-icon">
                    {is_paid ? <FiCheckCircle /> : <FiAlertCircle />}
                </div>
                <div className="payment-card-content">
                    <div className="payment-status-label">
                        {is_paid ? (t('payment_status_paid') || 'Payment Completed') : 
                         (isPendingOffline ? 'Offline Payment Pending Admin Confirmation' : (t('payment_status_pending') || 'Monthly Fee Due'))}
                    </div>
                    <div className="payment-period">{paid_until ? `Paid up to ${paid_until}` : monthName}</div>
                    {!is_paid && (
                        <div className="payment-amount">LKR {parseFloat(amount).toFixed(2)}</div>
                    )}
                </div>
                
                {!is_paid && !isPendingOffline && (
                    <button 
                        className="pay-now-btn" 
                        onClick={handlePayment}
                        disabled={paying}
                    >
                        {paying ? <><FiLoader className="spin-icon" /> {t('processing') || 'Processing...'}</> : t('pay_now') || 'Pay Now'}
                    </button>
                )}

                {error && <div className="payment-error-toast">{error}</div>}
            </div>
        </section>
    );
};

export default StudentPaymentModule;
