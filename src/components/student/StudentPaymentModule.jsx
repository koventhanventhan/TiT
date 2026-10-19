import React, { useState, useEffect } from 'react';
import { FiCreditCard, FiCheckCircle, FiAlertCircle, FiLoader, FiDownload } from 'react-icons/fi';
import { getStudentPaymentStatus, initializeMonthlyPayment, downloadStudentReceipt } from '../../services/dashboardService';
import { useLanguage } from '../../context/LanguageContext';
import './StudentPaymentModule.css';

const StudentPaymentModule = () => {
    const { t } = useLanguage();
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(true);
    const [paying, setPaying] = useState(false);
    const [downloadingId, setDownloadingId] = useState(null);
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

    const handleDownloadReceipt = async (paymentId, receiptNo) => {
        if (!paymentId) return;
        try {
            setDownloadingId(paymentId);
            setError(null);
            await downloadStudentReceipt(paymentId, receiptNo);
        } catch (err) {
            console.error('Download receipt error:', err);
            setError(err.message || 'Failed to download receipt');
        } finally {
            setDownloadingId(null);
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

    const { is_paid, amount, year_month, payment: currentPayment, history } = status;
    const monthName = new Date(year_month + '-01').toLocaleString('default', { month: 'long', year: 'numeric' });

    return (
        <section className="dashboard-payment-section">
            <h3 className="section-title">{t('your_payments') || 'Your Payments'}</h3>
            <div className={`payment-module-card ${is_paid ? 'paid' : 'pending'}`}>
                <div className="payment-card-icon">
                    {is_paid ? <FiCheckCircle /> : <FiAlertCircle />}
                </div>
                <div className="payment-card-content">
                    <div className="payment-status-label">
                        {is_paid ? t('payment_status_paid') || 'Payment Completed' : t('payment_status_pending') || 'Monthly Fee Due'}
                    </div>
                    <div className="payment-period">{monthName}</div>
                    {!is_paid ? (
                        <div className="payment-amount">LKR {parseFloat(amount).toFixed(2)}</div>
                    ) : (
                        currentPayment?.receipt_no && (
                            <div className="payment-receipt-no">
                                {t('receipt_no') || 'Receipt'}: <strong>{currentPayment.receipt_no}</strong>
                            </div>
                        )
                    )}
                </div>
                
                <div className="payment-card-actions">
                    {!is_paid && (
                        <button 
                            type="button"
                            className="pay-now-btn" 
                            onClick={handlePayment}
                            disabled={paying}
                        >
                            {paying ? <><FiLoader className="spin-icon" /> {t('processing') || 'Processing...'}</> : t('pay_now') || 'Pay Now'}
                        </button>
                    )}

                    {is_paid && currentPayment && (
                        <button
                            type="button"
                            className="receipt-btn"
                            onClick={() => handleDownloadReceipt(currentPayment.id, currentPayment.receipt_no)}
                            disabled={downloadingId === currentPayment.id}
                            title={t('download_receipt') || 'Download Receipt'}
                        >
                            {downloadingId === currentPayment.id ? (
                                <><FiLoader className="spin-icon" /> {t('downloading') || 'Downloading...'}</>
                            ) : (
                                <><FiDownload /> {t('receipt') || 'Receipt'}</>
                            )}
                        </button>
                    )}
                </div>

                {error && <div className="payment-error-toast">{error}</div>}
            </div>

            {history && history.length > 0 && (
                <div className="payment-history-card">
                    <h4 className="payment-history-title">{t('payment_history') || 'Payment History'}</h4>
                    <div className="payment-history-table-wrapper">
                        <table className="payment-history-table">
                            <thead>
                                <tr>
                                    <th>{t('month') || 'Month'}</th>
                                    <th>{t('amount') || 'Amount'}</th>
                                    <th>{t('status') || 'Status'}</th>
                                    <th className="text-right">{t('receipt') || 'Receipt'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {history.map((item) => {
                                    const itemMonth = new Date(item.year_month + '-01').toLocaleString('default', { month: 'short', year: 'numeric' });
                                    const itemIsPaid = item.status === 'paid';
                                    return (
                                        <tr key={item.id}>
                                            <td>
                                                <div className="history-month">{itemMonth}</div>
                                                {item.paid_at && (
                                                    <div className="history-date">
                                                        {new Date(item.paid_at).toLocaleDateString()}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="history-amount">
                                                LKR {parseFloat(item.amount).toFixed(2)}
                                            </td>
                                            <td>
                                                <span className={`history-badge ${itemIsPaid ? 'paid' : 'pending'}`}>
                                                    {itemIsPaid ? (t('paid') || 'Paid') : (t('pending') || 'Pending')}
                                                </span>
                                            </td>
                                            <td className="text-right">
                                                {itemIsPaid ? (
                                                    <button
                                                        type="button"
                                                        className="history-receipt-btn"
                                                        onClick={() => handleDownloadReceipt(item.id, item.receipt_no)}
                                                        disabled={downloadingId === item.id}
                                                        aria-label={`Download receipt for ${itemMonth}`}
                                                    >
                                                        {downloadingId === item.id ? (
                                                            <FiLoader className="spin-icon" />
                                                        ) : (
                                                            <FiDownload />
                                                        )}
                                                        <span>{t('receipt') || 'Receipt'}</span>
                                                    </button>
                                                ) : (
                                                    <span className="text-muted">—</span>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </section>
    );
};

export default StudentPaymentModule;
