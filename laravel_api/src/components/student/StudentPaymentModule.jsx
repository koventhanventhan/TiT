import React, { useState, useEffect, useRef } from 'react';
import { FiCreditCard, FiCheckCircle, FiAlertCircle, FiLoader } from 'react-icons/fi';
import { getStudentPaymentStatus, initializeMonthlyPayment, initializeOfflineMonthlyPayment } from '../../services/dashboardService';
import { useLanguage } from '../../context/LanguageContext';
import './StudentPaymentModule.css';
import { formatRs } from '../../utils/format';
import FeeRow from '../shared/FeeRow';

const StudentPaymentModule = () => {
    const { t } = useLanguage();
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(true);
    const [paying, setPaying] = useState(false);
    const [payingOffline, setPayingOffline] = useState(false);
    const [offlineSuccess, setOfflineSuccess] = useState(false);
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
        } catch (err) {
            clearTimeout(timeoutId);
            setError(err.name === 'AbortError' ? 'Request timed out. Please check your connection.' : (err.message || 'Failed to submit offline payment'));
            setPayingOffline(false);
            submittingOfflineRef.current = false;
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
                        <div style={{
                            marginTop: '1rem',
                            padding: '1rem',
                            borderRadius: '0.75rem',
                            background: 'var(--t-primary-soft)',
                            border: '1px solid var(--t-primary-border)',
                            width: '100%',
                            boxSizing: 'border-box',
                            textAlign: 'left'
                        }}>
                            {status.subjects && status.subjects.length > 0 && (
                                <div className="fee-subjects-section">
                                    <div className="fee-subjects-heading">{t('fee_subjects') || 'Subjects'}</div>
                                    {status.has_discount ? (
                                        <div className="fee-subject-chips">
                                            {status.subjects.map(s => (
                                                <span key={s.name} className="fee-subject-chip">
                                                    {s.name}
                                                </span>
                                            ))}
                                        </div>
                                    ) : (
                                        status.subjects.map(s => (
                                            <FeeRow
                                                key={s.name}
                                                label={s.name}
                                                value={formatRs(s.price)}
                                            />
                                        ))
                                    )}
                                </div>
                            )}

                            <FeeRow
                                label={t('fee_monthly') || 'Monthly Fee:'}
                                value={formatRs(typeof status.monthly_fee === 'number' ? status.monthly_fee : amount)}
                            />

                            {status.admission_fee > 0 && (
                                <FeeRow
                                    label={t('fee_admission') || 'Admission Fee (First Time):'}
                                    value={formatRs(status.admission_fee)}
                                />
                            )}

                            <FeeRow
                                label={t('fee_total') || 'Total Amount:'}
                                value={formatRs(amount)}
                                isTotal={true}
                            />
                        </div>
                    )}
                </div>
                
                {!is_paid && !isPendingOffline && (
                    <div className="payment-actions-stack">
                        <button 
                            className="pay-now-btn" 
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

                {offlineSuccess && (
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
