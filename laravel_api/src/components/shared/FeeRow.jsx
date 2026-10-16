import React from 'react';

/**
 * Reusable FeeRow component
 * Grid layout ensuring label wraps naturally and value never wraps/truncates.
 */
export default function FeeRow({
  label,
  value,
  isTotal = false,
  isDiscount = false,
  isStrikethrough = false,
  className = '',
  style = {}
}) {
  const classes = [
    'fee-row',
    isTotal ? 'fee-row-total' : '',
    isDiscount ? 'fee-row-discount' : '',
    isStrikethrough ? 'fee-row-strikethrough' : '',
    className
  ].filter(Boolean).join(' ');

  return (
    <div className={classes} style={style}>
      <span className="fee-row-label">{label}</span>
      <span className="fee-row-value">{value}</span>
    </div>
  );
}
