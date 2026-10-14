/**
 * Helper to format monetary values as "Rs. X,XXX.XX" with thousands separators and 2 decimals.
 * @param {number|string} amount
 * @returns {string}
 */
export function formatRs(amount) {
  const num = typeof amount === 'number' ? amount : (parseFloat(amount) || 0);
  return `Rs. ${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}
