import React, { useState, useEffect, useRef } from 'react';
import { FiCreditCard, FiCheckCircle, FiAlertCircle, FiLoader, FiClock } from 'react-icons/fi';
import { getStudentPaymentStatus, initializeMonthlyPayment, initializeOfflineMonthlyPayment } from '../../services/dashboardService';
import { useLanguage } from '../../context/LanguageContext';
import './StudentPaymentModule.css';
import { formatRs } from '../../utils/format';

const formatDate = (isoString) => {
    if (!isoString) return new Date().toISOString().split('T')[0];
    if (typeof isoString === 'string' && isoString.includes('T')) {
        return isoString.split('T')[0];
    }
    return String(isoString).substring(0, 10);
};

const formatAmount = (amt) => {
    const num = typeof amt === 'number' ? amt : (parseFloat(amt) || 0);
    return num % 1 === 0 ? num : num.toFixed(2);
};

const StudentPaymentModule = () => {
    const { t } = useLanguage();
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(true);
    const [paying, setPaying] = useState(false);
    const [payingOffline, setPayingOffline] = useState(false);
    const [offlineSuccess, setOfflineSuccess] = useState(false);
    const [confirmingPayment, setConfirmingPayment] = useState(false);
    const [confirmingTimeout, setConfirmingTimeout] = useState(false);
    const [error, setError] = useState(null);
    const submittingOfflineRef = useRef(false);

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
        if (paying) return;
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

            window.payhere.onCompleted = async function onCompleted(orderId) {
                console.log("Payment completed. OrderID:" + orderId);
                setPaying(false);
                setConfirmingPayment(true);
                setConfirmingTimeout(false);

                const pollInterval = 3000;
                const maxAttempts = 20;
                let attempts = 0;
                let confirmed = false;

                while (attempts < maxAttempts && !confirmed) {
                    attempts++;
                    try {
                        const newStatus = await getStudentPaymentStatus();
                        if (newStatus && (newStatus.has_paid || newStatus.is_paid)) {
                            confirmed = true;
                            setStatus(newStatus);
                            setConfirmingPayment(false);
                            return;
                        }
                    } catch (e) {
                        console.warn('Polling monthly payment status error:', e);
                    }

                    if (attempts < maxAttempts) {
                        await new Promise((r) => setTimeout(r, pollInterval));
                    }
                }

                if (!confirmed) {
                    setConfirmingPayment(false);
                    setConfirmingTimeout(true);
                    fetchStatus();
                }
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

    const handleOfflinePayment = async () => {
        if (submittingOfflineRef.current || paying || payingOffline) return;
        submittingOfflineRef.current = true;
        setPayingOffline(true);
        setError(null);

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 20000);

        try {
            await initializeOfflineMonthlyPayment({ signal: controller.signal });
            clearTimeout(timeoutId);
            setOfflineSuccess(true);
            await fetchStatus();
            setTimeout(() => {
                setPayingOffline(false);
                submittingOfflineRef.current = false;
            }, 1500);
        } catch (err) {
            clearTimeout(timeoutId);
            setError(err.name === 'AbortError' ? 'Request timed out. Please check your connection.' : (err.message || 'Failed to submit offline payment'));
            setTimeout(() => {
                setPayingOffline(false);
                submittingOfflineRef.current = false;
            }, 1500);
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
    const isCancelled = payment && payment.status === 'cancelled';

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
                        <div className="payment-amount" style={{ fontVariantNumeric: 'tabular-nums', whiteSpace: 'nowrap' }}>
                            {formatRs(amount)}
                        </div>
                    )}
                </div>

                {confirmingPayment && (
                    <div
                        className="status-card-confirming"
                        data-testid="status-card-confirming"
                        style={{
                            width: '100%',
                            marginTop: 12,
                            padding: '12px 16px',
                            borderRadius: 10,
                            background: '#eff6ff',
                            color: '#1d4ed8',
                            border: '1px solid #bfdbfe',
                            fontSize: 14,
                            fontWeight: 600,
                            display: 'flex',
                            alignItems: 'center',
                            gap: 8,
                            textAlign: 'left',
                            boxSizing: 'border-box'
                        }}
                    >
                        <FiLoader className="spin-icon" style={{ flexShrink: 0, fontSize: 18 }} />
                        <span>Payment received – confirming…</span>
                    </div>
                )}

                {confirmingTimeout && (
                    <div
                        className="status-card-timeout"
                        data-testid="status-card-timeout"
                        style={{
                            width: '100%',
                            marginTop: 12,
                            padding: '12px 16px',
                            borderRadius: 10,
                            background: '#fffbeb',
                            color: '#b45309',
                            border: '1px solid #fde68a',
                            fontSize: 14,
                            fontWeight: 600,
                            display: 'flex',
                            alignItems: 'center',
                            gap: 8,
                            textAlign: 'left',
                            boxSizing: 'border-box'
                        }}
                    >
                        <FiClock style={{ flexShrink: 0, fontSize: 18 }} />
                        <span>We have not received the confirmation yet – you will get an e-mail/WhatsApp as soon as it arrives. Do not pay again.</span>
                    </div>
                )}

                {isPendingOffline && (
                    <div
                        className="status-card-pending"
                        data-testid="status-card-pending"
                        style={{
                            width: '100%',
                            marginTop: 12,
                            padding: '12px 16px',
                            borderRadius: 10,
                            background: '#fffbeb',
                            color: '#b45309',
                            border: '1px solid #fde68a',
                            fontSize: 14,
                            fontWeight: 600,
                            display: 'flex',
                            alignItems: 'center',
                            gap: 8,
                            textAlign: 'left',
                            boxSizing: 'border-box'
                        }}
                    >
                        <FiClock style={{ flexShrink: 0, fontSize: 18 }} />
                        <span>Request received on {formatDate(payment.created_at)}, waiting for admin confirmation – Rs {formatAmount(payment.amount || amount)}</span>
                    </div>
                )}

                {isCancelled && (
                    <div
                        className="status-card-cancelled"
                        data-testid="status-card-cancelled"
                        style={{
                            width: '100%',
                            marginTop: 12,
                            padding: '12px 16px',
                            borderRadius: 10,
                            background: '#fef2f2',
                            color: '#b91c1c',
                            border: '1px solid #fecaca',
                            fontSize: 14,
                            fontWeight: 600,
                            display: 'flex',
                            alignItems: 'center',
                            gap: 8,
                            textAlign: 'left',
                            boxSizing: 'border-box'
                        }}
                    >
                        <FiAlertCircle style={{ flexShrink: 0, fontSize: 18 }} />
                        <span>Request cancelled – please contact the institute or submit again</span>
                    </div>
                )}
                
                {!is_paid && !isPendingOffline && (
                    <div className="payment-actions-stack">
                        <button 
                            className="pay-now-btn" 
                            data-testid="pay-now-btn"
                            onClick={handlePayment}
                            disabled={paying || payingOffline}
                            style={{ touchAction: 'manipulation' }}
                        >
                            {paying ? <><FiLoader className="spin-icon" /> {t('processing') || 'Processing...'}</> : t('pay_now') || 'Pay Now'}
                        </button>
                        <button
                            type="button"
                            onClick={handleOfflinePayment}
                            disabled={paying || payingOffline}
                            className="pay-offline-btn t-tap"
                            style={{ touchAction: 'manipulation' }}
                        >
                            {payingOffline ? <><FiLoader className="spin-icon" /> {t('submitting') || 'Submitting...'}</> : (t('pay_offline') || 'I WILL PAY OFFLINE')}
                        </button>
                    </div>
                )}

                {offlineSuccess && !isPendingOffline && (
                    <div style={{
                        marginTop: 10,
                        padding: '10px 14px',
                        borderRadius: 8,
                        background: 'var(--t-success-bg)',
                        color: 'var(--t-success)',
                        fontSize: 13,
                        fontWeight: 500
                    }}>
                        {t('pay_offline_success') || 'Offline payment submitted. Admin will confirm shortly.'}
                    </div>
                )}

                {error && <div className="payment-error-toast">{error}</div>}
            </div>
        </section>
    );
};

export default StudentPaymentModule;
