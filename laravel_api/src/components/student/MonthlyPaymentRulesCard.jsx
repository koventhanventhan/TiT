import React from 'react';
import { FiCalendar, FiBell, FiAlertTriangle, FiCreditCard, FiCheckCircle, FiLayers } from 'react-icons/fi';
import { useLanguage } from '../../context/LanguageContext';

export default function MonthlyPaymentRulesCard({ isDark = false, compact = false }) {
    let t = (k, def) => def;
    try {
        const lang = useLanguage();
        if (lang && lang.t) t = lang.t;
    } catch (_) {}

    const cardBg = isDark ? 'rgba(255, 255, 255, 0.05)' : '#f9fafb';
    const cardBorder = isDark ? '1px solid rgba(255, 255, 255, 0.1)' : '1px solid #e5e7eb';
    const textColor = isDark ? '#ffffff' : '#111827';
    const subtextColor = isDark ? 'rgba(255, 255, 255, 0.75)' : '#4b5563';
    const itemBg = isDark ? 'rgba(255, 255, 255, 0.04)' : '#ffffff';
    const itemBorder = isDark ? '1px solid rgba(255, 255, 255, 0.08)' : '1px solid #e5e7eb';

    const rules = [
        {
            icon: FiCalendar,
            iconColor: '#3b82f6',
            badge: 'Day 1',
            badgeBg: isDark ? 'rgba(59, 130, 246, 0.2)' : '#dbeafe',
            badgeColor: isDark ? '#93c5fd' : '#1e40af',
            title: t('payment_rule_due_title', '1st of Every Month – Due Date'),
            desc: t('payment_rule_due_desc', 'Monthly fees are due on the 1st of each month. From the 1st, classes, materials, and recordings require an active payment for the current month.')
        },
        {
            icon: FiBell,
            iconColor: '#f59e0b',
            badge: 'Day 2',
            badgeBg: isDark ? 'rgba(245, 158, 11, 0.2)' : '#fef3c7',
            badgeColor: isDark ? '#fcd34d' : '#92400e',
            title: t('payment_rule_reminder_title', 'Day 2 – Automated Reminder'),
            desc: t('payment_rule_reminder_desc', 'An automated reminder is sent via WhatsApp and Email on Day 2 if payment has not been received.')
        },
        {
            icon: FiAlertTriangle,
            iconColor: '#ef4444',
            badge: 'Day 5',
            badgeBg: isDark ? 'rgba(239, 68, 68, 0.2)' : '#fee2e2',
            badgeColor: isDark ? '#fca5a5' : '#991b1b',
            title: t('payment_rule_suspension_title', 'Day 5 – Account Suspension'),
            desc: t('payment_rule_suspension_desc', 'If payment is still unpaid by Day 5, student access is automatically deactivated until payment is completed.')
        },
        {
            icon: FiCreditCard,
            iconColor: 'var(--t-primary)',
            badge: 'Options',
            badgeBg: isDark ? 'rgba(139, 92, 246, 0.2)' : '#ede9fe',
            badgeColor: isDark ? '#c4b5fd' : '#5b21b6',
            title: t('payment_rule_methods_title', 'Where & How to Pay'),
            desc: t('payment_rule_methods_desc', 'Pay Online (instant activation via PayHere), Pay Offline (cash/bank transfer, activated after admin confirmation), or request Advance Months (1–12 months).')
        },
        {
            icon: FiCheckCircle,
            iconColor: '#10b981',
            badge: 'Receipt',
            badgeBg: isDark ? 'rgba(16, 185, 129, 0.2)' : '#d1fae5',
            badgeColor: isDark ? '#6ee7b7' : '#065f46',
            title: t('payment_rule_receipts_title', 'Receipts & Confirmation'),
            desc: t('payment_rule_receipts_desc', 'A digital receipt is automatically sent to your registered Email and WhatsApp upon payment confirmation.')
        },
        {
            icon: FiLayers,
            iconColor: '#ec4899',
            badge: 'Top-Up',
            badgeBg: isDark ? 'rgba(236, 72, 153, 0.2)' : '#fce7f3',
            badgeColor: isDark ? '#f472b6' : '#9d174d',
            title: t('payment_rule_topup_title', 'Subject Changes (Top-Up)'),
            desc: t('payment_rule_topup_desc', 'Adding subjects during an already-paid month requires a top-up for the fee difference. Added subjects remain locked until the top-up is paid.')
        }
    ];

    return (
        <div
            className="monthly-payment-rules-card"
            data-testid="monthly-payment-rules-card"
            style={{
                background: cardBg,
                border: cardBorder,
                borderRadius: 14,
                padding: compact ? '16px' : '20px 22px',
                textAlign: 'left',
                boxSizing: 'border-box'
            }}
        >
            <div style={{ marginBottom: 14 }}>
                <h4 style={{
                    margin: '0 0 4px 0',
                    fontSize: compact ? 15 : 17,
                    fontWeight: 700,
                    color: textColor,
                    letterSpacing: '-0.3px'
                }}>
                    {t('payment_rules_title', 'How Monthly Payment Works')}
                </h4>
                <p style={{
                    margin: 0,
                    fontSize: compact ? 12 : 13,
                    color: subtextColor,
                    lineHeight: 1.4
                }}>
                    {t('payment_rules_subtitle', 'Payment schedule, options, reminders, and access rules')}
                </p>
            </div>

            <div style={{
                display: 'grid',
                gridTemplateColumns: compact ? '1fr' : 'repeat(auto-fit, minmax(240px, 1fr))',
                gap: 10
            }}>
                {rules.map((rule, idx) => {
                    const IconComponent = rule.icon;
                    return (
                        <div
                            key={idx}
                            data-testid={`payment-rule-item-${idx}`}
                            style={{
                                background: itemBg,
                                border: itemBorder,
                                borderRadius: 10,
                                padding: '12px 14px',
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 6,
                                boxSizing: 'border-box'
                            }}
                        >
                            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 8 }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                    <IconComponent style={{ fontSize: 16, color: rule.iconColor, flexShrink: 0 }} />
                                    <span style={{ fontSize: 13, fontWeight: 700, color: textColor }}>
                                        {rule.title}
                                    </span>
                                </div>
                                <span style={{
                                    fontSize: 10,
                                    fontWeight: 700,
                                    padding: '2px 7px',
                                    borderRadius: 12,
                                    background: rule.badgeBg,
                                    color: rule.badgeColor,
                                    whiteSpace: 'nowrap',
                                    textTransform: 'uppercase',
                                    letterSpacing: '0.4px'
                                }}>
                                    {rule.badge}
                                </span>
                            </div>
                            <p style={{
                                margin: 0,
                                fontSize: 12,
                                color: subtextColor,
                                lineHeight: 1.45
                            }}>
                                {rule.desc}
                            </p>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
