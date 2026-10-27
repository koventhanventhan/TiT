import React, { useState } from 'react';
import { FiMinus, FiPlus, FiLoader } from 'react-icons/fi';
import FeeRow from '../shared/FeeRow';
import { formatRs } from '../../utils/format';

/**
 * "Pay for Advance Months" block (toggle + 1–12 months stepper + offline submit).
 *
 * Shared by BOTH states of the Edit-Subjects payment step (month already paid / month not paid)
 * so the controls and test-ids are identical in both places.
 */
export default function AdvanceMonthsBlock({ monthlyFee, disabled = false, loading = false, onSubmit, t = (k, def) => def }) {
  const [open, setOpen] = useState(false);
  const [months, setMonths] = useState(3);

  const numericMonths = parseInt(months, 10);
  const isValid = !isNaN(numericMonths) && numericMonths >= 1 && numericMonths <= 12;

  const handleChange = (e) => {
    const val = e.target.value;
    if (val === '') {
      setMonths('');
      return;
    }
    if (/^\d+$/.test(val)) {
      setMonths(val);
    }
  };

  const handleBlur = () => {
    const parsed = parseInt(months, 10);
    if (isNaN(parsed) || parsed < 1) {
      setMonths(1);
    } else if (parsed > 12) {
      setMonths(12);
    } else {
      setMonths(parsed);
    }
  };

  const stepDown = () => {
    const parsed = parseInt(months, 10);
    const current = isNaN(parsed) ? 1 : parsed;
    setMonths(Math.max(1, current - 1));
  };

  const stepUp = () => {
    const parsed = parseInt(months, 10);
    const current = isNaN(parsed) ? 1 : parsed;
    setMonths(Math.min(12, current + 1));
  };

  const stepBtnStyle = (isDisabled) => ({
    width: 48,
    height: 48,
    minWidth: 48,
    minHeight: 48,
    borderRadius: 10,
    border: '1px solid var(--t-border)',
    background: '#fff',
    color: 'var(--t-text)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    cursor: isDisabled ? 'not-allowed' : 'pointer',
    opacity: isDisabled ? 0.4 : 1,
    fontSize: 18,
    touchAction: 'manipulation',
    WebkitTapHighlightColor: 'transparent',
    boxSizing: 'border-box',
  });

  const minusDisabled = !isValid || numericMonths <= 1 || disabled;
  const plusDisabled = (isValid && numericMonths >= 12) || disabled;

  return (
    <div
      data-testid="advance-months-block"
      style={{
        border: '1px solid var(--t-border, #e2e8f0)',
        borderRadius: 14,
        padding: 14,
        background: '#fff',
        display: 'flex',
        flexDirection: 'column',
        gap: 8,
      }}
    >
      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        data-testid="pay-advance-toggle-btn"
        style={{
          width: '100%',
          padding: '12px 18px',
          borderRadius: '12px',
          border: '1px solid var(--t-border, #cbd5e1)',
          background: open ? 'var(--t-primary-soft, #f1f5f9)' : '#fff',
          color: 'var(--t-text, #1e293b)',
          fontSize: '15px',
          fontWeight: 600,
          cursor: 'pointer',
          minHeight: 46,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          transition: 'all 0.2s',
        }}
      >
        <span>{t('subj_update_pay_advance_title', 'Pay for Advance Months')}</span>
        <span style={{ fontSize: 16 }}>{open ? '▲' : '▼'}</span>
      </button>
      <div style={{ fontSize: 13, color: 'var(--t-text-3, #64748b)', paddingLeft: 4, lineHeight: 1.4 }}>
        {t('subj_update_pay_advance_desc', 'Advance months – pay for multiple months in advance offline')}
      </div>

      {open && (
        <div style={{ background: 'var(--t-bg)', padding: 16, borderRadius: 12, border: '1px solid var(--t-border)', marginTop: 8 }}>
          <label style={{ display: 'block', fontSize: 13, color: 'var(--t-text-3)', marginBottom: 8, fontWeight: 600 }}>Number of Months:</label>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 14 }}>
            <button
              type="button"
              onClick={stepDown}
              disabled={minusDisabled}
              aria-label="Decrease months"
              className="t-tap"
              style={stepBtnStyle(minusDisabled)}
            >
              <FiMinus />
            </button>

            <input
              type="text"
              inputMode="numeric"
              pattern="[0-9]*"
              value={months}
              onChange={handleChange}
              onBlur={handleBlur}
              aria-label="Number of advance months"
              data-testid="advance-months-input"
              style={{
                flex: 1,
                height: 48,
                minHeight: 48,
                borderRadius: 10,
                border: '1px solid var(--t-border)',
                textAlign: 'center',
                fontSize: 16,
                fontWeight: 600,
                color: 'var(--t-text)',
                background: '#fff',
                boxSizing: 'border-box',
              }}
            />

            <button
              type="button"
              onClick={stepUp}
              disabled={plusDisabled}
              aria-label="Increase months"
              className="t-tap"
              style={stepBtnStyle(plusDisabled)}
            >
              <FiPlus />
            </button>
          </div>

          <FeeRow
            label="Total Advance Amount:"
            value={isValid ? formatRs((monthlyFee || 0) * numericMonths) : '—'}
            isTotal={true}
            style={{ marginBottom: 16 }}
          />

          <button
            onClick={() => isValid && onSubmit(numericMonths)}
            disabled={disabled || !isValid}
            data-testid="submit-advance-payment-btn"
            style={{
              width: '100%', padding: '14px 20px', borderRadius: '12px', border: '2px solid var(--t-danger)',
              background: '#fff', color: 'var(--t-danger)', fontSize: '15px', fontWeight: 600,
              cursor: (disabled || !isValid) ? 'not-allowed' : 'pointer',
              opacity: !isValid ? 0.5 : 1,
              transition: 'all 0.2s', textAlign: 'center',
              minHeight: 48, touchAction: 'manipulation',
              display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
            }}
          >
            {loading ? <><FiLoader className="spin-icon" /> Submitting...</> : 'I WILL PAY OFFLINE (CASH/BANK)'}
          </button>
        </div>
      )}
    </div>
  );
}
