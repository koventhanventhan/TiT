import React, { useState, useEffect, useMemo } from 'react';
import { FiX, FiCheck } from 'react-icons/fi';
import { updateStudentSubjects, initializeMonthlyPayment, initializeOfflineMonthlyPayment, requestAdvancePaymentOffline } from '../../services/dashboardService';
import useSubjectPricing from '../../hooks/useSubjectPricing';
import FeeRow from '../shared/FeeRow';
import { formatRs } from '../../utils/format';

export default function SubjectUpdateModal({ isOpen, onClose, currentGrade, medium, stream, initialSubjects = [], onUpdateSuccess }) {
  const [serverAmount, setServerAmount] = useState(null);
  const [serverMonthly, setServerMonthly] = useState(null);
  const [serverAdmission, setServerAdmission] = useState(0);
  const [alreadyPaid, setAlreadyPaid] = useState(false);
  const [paidUntil, setPaidUntil] = useState(null);
  const [saved, setSaved] = useState(false);
  const [paymentDone, setPaymentDone] = useState(false);
  const [step, setStep] = useState(1);
  const [localMedium, setLocalMedium] = useState(medium || '');
  const [availableSubjects, setAvailableSubjects] = useState([]);
  const [selectedSubjects, setSelectedSubjects] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  
  const [wantAdvancePayment, setWantAdvancePayment] = useState(false);
  const [advanceMonths, setAdvanceMonths] = useState(3);

  // Reset state when opened
  useEffect(() => {
    if (isOpen) {
      setStep(1);
      setLocalMedium(medium || '');
      setError('');
      setSuccessMessage('');
      setServerAmount(null);
      setServerMonthly(null);
      setServerAdmission(0);
      setAlreadyPaid(false);
      setPaidUntil(null);
      setSaved(false);
      setPaymentDone(false);
    }
  }, [isOpen, medium]);

  // Normalize initialSubjects if it comes as JSON string
  const normalizedInitialSubjects = useMemo(() => {
    if (typeof initialSubjects === 'string') {
      try {
        return JSON.parse(initialSubjects);
      } catch (e) {
        return [];
      }
    }
    return Array.isArray(initialSubjects) ? initialSubjects : [];
  }, [initialSubjects]);

  useEffect(() => {
    setSelectedSubjects(normalizedInitialSubjects);
  }, [normalizedInitialSubjects, isOpen]);

  useEffect(() => {
    if (isOpen) {
      fetchSubjects();
    }
  }, [isOpen, currentGrade, localMedium]);

  // Live pricing via shared hook
  const { monthlyAmount, originalAmount, appliedPackage, packages } = useSubjectPricing(
    selectedSubjects,
    availableSubjects,
    currentGrade,
    localMedium
  );

  const getNumericGrade = (gradeValue) => {
    if (!gradeValue) return null;
    if (typeof gradeValue === 'number') return gradeValue;
    const match = gradeValue.toString().match(/(\d+)/);
    return match ? parseInt(match[1], 10) : null;
  };

  // Dynamic Fee Info Message based on available subjects and packages
  const dynamicFeeMessage = useMemo(() => {
    if (availableSubjects.length === 0) return null;

    const gradeNum = getNumericGrade(currentGrade);
    
    // Calculate total price
    let totalIndividualPrice = 0;
    
    const subjectPartsEn = availableSubjects.map(s => {
      const price = parseFloat(s.price || 0);
      totalIndividualPrice += price;
      return `${s.name} = Rs. ${price.toFixed(0)}`;
    });
    
    const subjectPartsTa = availableSubjects.map(s => {
      const price = parseFloat(s.price || 0);
      return `${s.name_ta || s.name} = Rs. ${price.toFixed(0)}`;
    });

    let msgEn = subjectPartsEn.join(', ') + ` (Total: Rs. ${totalIndividualPrice.toFixed(0)}). `;
    let msgTa = subjectPartsTa.join(', ') + ` (மொத்தம்: Rs. ${totalIndividualPrice.toFixed(0)}). `;

    // Find if there's a package for this grade and medium
    let applicablePkgs = [];
    if (gradeNum && packages && packages.length > 0) {
      applicablePkgs = packages.filter(p => {
        let grades = p.applicable_grades;
        if (typeof grades === 'string') {
          try { grades = JSON.parse(grades); } catch(e) { grades = []; }
        }
        const hasGrade = Array.isArray(grades) && grades.some(g => parseInt(g, 10) === gradeNum);
        const hasMedium = p.medium === 'both' || p.medium === localMedium;
        return hasGrade && hasMedium;
      });
    }

    const allSubjPkg = applicablePkgs.find(p => p.type === 'all_subjects');
    const mainSubjPkg = applicablePkgs.find(p => p.type === 'main_subjects');

    if (allSubjPkg) {
      const pkgPrice = parseFloat(allSubjPkg.package_price).toFixed(0);
      msgEn += `But if you select all subjects, your package price is only Rs. ${pkgPrice}!`;
      msgTa += `ஆனால், நீங்கள் அனைத்துப் பாடங்களையும் தேர்ந்தெடுத்தால் உங்களுக்கான Package கட்டணம் Rs. ${pkgPrice} மட்டுமே!`;
    } else if (mainSubjPkg) {
       const pkgPrice = parseFloat(mainSubjPkg.package_price).toFixed(0);
       const baseCount = mainSubjPkg.base_subject_count || 5;
       const addonPrice = mainSubjPkg.addon_price ? parseFloat(mainSubjPkg.addon_price).toFixed(0) : 0;

       if (addonPrice > 0) {
           msgEn += `But if you select ${baseCount} subjects, your package price will be Rs. ${pkgPrice}! Each additional subject costs Rs. ${addonPrice}.`;
           msgTa += `ஆனால், நீங்கள் ${baseCount} பாடங்களை தேர்ந்தெடுத்தால் Package கட்டணம் Rs. ${pkgPrice} மட்டுமே! அதன்பின் தேர்ந்தெடுக்கப்படும் ஒவ்வொரு கூடுதல் பாடத்திற்கும் கட்டணம் Rs. ${addonPrice} மட்டுமே!`;
       } else {
           msgEn += `But if you select ${baseCount} or more subjects, your package price will be reduced to Rs. ${pkgPrice}!`;
           msgTa += `ஆனால், நீங்கள் ${baseCount} அல்லது அதற்கு மேற்பட்ட பாடங்களை தேர்ந்தெடுத்தால் Package கட்டணம் Rs. ${pkgPrice} ஆக குறைக்கப்படும்!`;
       }
    } else {
       msgEn = `Note: Your monthly fee is calculated based on the subjects you select. ` + msgEn;
       msgTa = `குறிப்பு: உங்கள் மாதக் கட்டணம் நீங்கள் தேர்ந்தெடுக்கும் பாடங்களின் அடிப்படையில் கணக்கிடப்படும். ` + msgTa;
    }

    return (
      <div style={{ backgroundColor: 'var(--t-primary-soft)', color: 'var(--t-primary)', padding: '12px 16px', borderRadius: '8px', fontSize: '13px', marginBottom: '16px', borderLeft: '4px solid var(--t-primary)' }}>
        <div style={{ marginBottom: '6px' }}><strong>{msgEn}</strong></div>
        <div><strong>{msgTa}</strong></div>
      </div>
    );
  }, [availableSubjects, currentGrade, localMedium, packages]);

  const handleToggle = (subjectName) => {
    setSelectedSubjects(prev => {
      if (prev.includes(subjectName)) {
        return prev.filter(s => s !== subjectName);
      } else {
        return [...prev, subjectName];
      }
    });
    setError('');
  };



  const fetchSubjects = async () => {
    try {
      const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';
      const res = await fetch(`${API_BASE_URL}/subjects/prices`);
      if (res.ok) {
        const data = await res.json();
        
        const gradeNum = getNumericGrade(currentGrade);
        let subs = [];
        if (gradeNum >= 1 && gradeNum <= 2) subs = data['grade_1_to_2'] || [];
        else if (gradeNum === 3) subs = data['grade_3'] || [];
        else if (gradeNum === 4) subs = data['grade_4'] || [];
        else if (gradeNum === 5) subs = data['grade_5'] || [];
        else if (gradeNum >= 6 && gradeNum <= 9) subs = data['grade_6_to_9'] || [];
        else if (gradeNum >= 10 && gradeNum <= 11) subs = data['grade_10_to_11'] || [];
        else if (gradeNum >= 12 && gradeNum <= 13) {
            const streamKey = (stream || '').toLowerCase();
            if (data[streamKey]) {
                subs = data[streamKey];
            } else {
                subs = [
                    ...(data['arts_stream'] || []),
                    ...(data['bio_maths_stream'] || []),
                    ...(data['commerce_stream'] || []),
                    ...(data['tech_stream'] || [])
                ];
            }
        }

        if (localMedium) {
          subs = subs.filter(s => s.medium === localMedium || s.medium === 'both');
        }

        // Deduplicate
        const uniqueSubs = [];
        const seen = new Set();
        for (const s of subs) {
          if (!seen.has(s.name)) {
            seen.add(s.name);
            uniqueSubs.push(s);
          }
        }
        setAvailableSubjects(uniqueSubs);
      }
    } catch (err) {
      console.error('Failed to fetch subjects:', err);
    }
  };

  const handleClose = () => {
    // Subjects are already persisted once saved, so refresh the parent even if payment is skipped
    if (saved || successMessage) {
      onUpdateSuccess(selectedSubjects);
    } else {
      onClose();
    }
  };

  const handleSaveSubjects = async (e) => {
    e.preventDefault();
    if (loading) return;
    if (selectedSubjects.length === 0) {
      setError('Please select at least one subject.');
      return;
    }
    if (!localMedium) {
      setError('Please select a medium of learning.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      const resp = await updateStudentSubjects(selectedSubjects, localMedium);
      if (resp && resp.mediumChangeRequested) {
        setSuccessMessage('Subjects updated. Your medium change request is pending admin approval.');
      }
      setServerAmount(resp && typeof resp.amount === 'number' ? resp.amount : null);
      setServerMonthly(resp && typeof resp.monthly_fee === 'number' ? resp.monthly_fee : null);
      setServerAdmission(resp && typeof resp.admission_fee === 'number' ? resp.admission_fee : 0);
      setAlreadyPaid(!!(resp && resp.already_paid));
      if (resp && resp.paid_until) {
        setPaidUntil(resp.paid_until);
      }
      setSaved(true);
      setStep(2); // Move to payment step
    } catch (err) {
      setError(err.message || 'Failed to update subjects.');
    } finally {
      setLoading(false);
    }
  };

  const handlePayOffline = async () => {
    setLoading(true);
    setError('');
    try {
      const resp = await initializeOfflineMonthlyPayment();
      setSuccessMessage(resp.message || 'Offline payment initiated.');
      setPaymentDone(true);
      setTimeout(() => {
        onUpdateSuccess(selectedSubjects);
      }, 2000);
    } catch (err) {
      setError(err.message || 'Failed to initialize offline payment');
      setLoading(false);
    }
  };

  const handlePayAdvanceOffline = async () => {
    if (advanceMonths < 1 || advanceMonths > 12) {
      setError('Please select a valid number of months (1-12).');
      return;
    }
    setLoading(true);
    setError('');
    try {
      const resp = await requestAdvancePaymentOffline(advanceMonths);
      setSuccessMessage(resp.message || 'Advance payment requested.');
      setPaymentDone(true);
      setTimeout(() => {
        onUpdateSuccess(selectedSubjects);
      }, 3000);
    } catch (err) {
      setError(err.message || 'Failed to request advance payment');
      setLoading(false);
    }
  };

  const handlePayOnline = async () => {
    setLoading(true);
    setError('');
    try {
      const resp = await initializeMonthlyPayment();
      
      if (!window.payhere) {
        throw new Error('PayHere SDK not loaded');
      }

      const payment = {
        sandbox: resp.payhere_url.includes('sandbox'),
        ...resp.params
      };

      window.payhere.onCompleted = function onCompleted(orderId) {
        setSuccessMessage('Payment completed successfully!');
        setPaymentDone(true);
        setTimeout(() => {
          onUpdateSuccess(selectedSubjects);
        }, 1500);
      };

      window.payhere.onDismissed = function onDismissed() {
        setLoading(false);
      };

      window.payhere.onError = function onError(errStr) {
        setError("Payment Error: " + errStr);
        setLoading(false);
      };

      window.payhere.startPayment(payment);
    } catch (err) {
      setError(err.message || 'Failed to start online payment');
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  const effectiveMonthly = step === 2 ? (serverMonthly ?? monthlyAmount) : monthlyAmount;
  const hasDiscount = appliedPackage && effectiveMonthly < originalAmount;
  const savings = originalAmount - monthlyAmount;

  return (
    <div className="shared-modal-overlay" style={{
      position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
      background: 'rgba(15,23,42,0.6)',
      display: 'flex', alignItems: 'center', justifyContent: 'center',
      zIndex: 9999, padding: 12
    }}>
      <div className="shared-modal-dialog" style={{
        background: '#fff', borderRadius: 24, width: 'calc(100% - 24px)', maxWidth: 500,
        boxShadow: '0 25px 50px -12px rgba(0,0,0,0.25)', overflow: 'hidden',
        display: 'flex', flexDirection: 'column', maxHeight: '90dvh',
        overscrollBehavior: 'contain'
      }}>
        <div style={{
          padding: '20px', borderBottom: '1px solid var(--t-border)',
          display: 'flex', alignItems: 'center', justifyContent: 'space-between'
        }}>
          <div>
            <h2 style={{ margin: 0, fontSize: 20, fontWeight: 700, color: 'var(--t-text)' }}>
              {step === 1 ? 'Update Your Subjects' : 'Monthly Payment'}
            </h2>
            <p style={{ margin: '4px 0 0', fontSize: 14, color: 'var(--t-text-3)' }}>For {currentGrade}</p>
          </div>
          <button onClick={handleClose} aria-label="Close" style={{
            background: 'var(--t-bg)', border: 'none', width: 44, height: 44, minWidth: 44, minHeight: 44,
            borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center',
            color: 'var(--t-text-3)', cursor: 'pointer', transition: 'all 0.2s'
          }}>
            <FiX size={18} />
          </button>
        </div>

        <div className="shared-modal-body" style={{ padding: '20px', overflowY: 'auto', overscrollBehavior: 'contain' }}>
          {error && (
            <div style={{ background: 'var(--t-danger-bg)', color: 'var(--t-danger)', padding: '12px 16px', borderRadius: 12, fontSize: 14, marginBottom: 20, fontWeight: 500 }}>
              {error}
            </div>
          )}
          {successMessage && (
            <div style={{ background: 'var(--t-success-bg)', color: 'var(--t-success)', padding: '12px 16px', borderRadius: 12, fontSize: 14, marginBottom: 20, fontWeight: 500 }}>
              {successMessage}
            </div>
          )}

          {step === 1 && (
            <>
              <div style={{ marginBottom: 20 }}>
                <label style={{ display: 'block', fontSize: 14, fontWeight: 600, color: 'var(--t-text-2)', marginBottom: 8 }}>
                  Medium of Learning <span style={{ color: 'var(--t-danger)' }}>*</span>
                </label>
                <select
                  value={localMedium}
                  onChange={(e) => setLocalMedium(e.target.value)}
                  style={{
                    width: '100%', padding: '12px 16px', borderRadius: 12, border: '1px solid var(--t-border)',
                    fontSize: 16, color: 'var(--t-text)', outline: 'none', appearance: 'none',
                    background: '#fff', minHeight: 44
                  }}
                >
                  <option value="">Select Medium</option>
                  <option value="tamil">Tamil / தமிழ்</option>
                  <option value="english">English</option>
                </select>
              </div>

              <p style={{ fontSize: 15, color: 'var(--t-text-2)', marginBottom: 20, lineHeight: 1.5 }}>
                Please review and select the subjects you will be taking in your new grade.
              </p>

              {dynamicFeeMessage}

              <div style={{ display: 'grid', gap: 12 }}>
                {availableSubjects.length > 0 ? availableSubjects.map((sub, idx) => {
                  const isSelected = selectedSubjects.includes(sub.name);
                  const price = parseFloat(sub.price || 0);
                  return (
                    <label key={idx} style={{
                      display: 'flex', alignItems: 'center', gap: 14, padding: '16px',
                      border: `2px solid ${isSelected ? 'var(--t-primary)' : 'var(--t-border)'}`,
                      borderRadius: 16, cursor: 'pointer', transition: 'all 0.2s',
                      background: isSelected ? 'var(--t-primary-soft)' : '#fff'
                    }}>
                      <div style={{
                        width: 24, height: 24, borderRadius: 6,
                        border: `2px solid ${isSelected ? 'var(--t-primary)' : 'var(--t-border)'}`,
                        background: isSelected ? 'var(--t-primary)' : '#fff',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        color: '#fff', transition: 'all 0.2s'
                      }}>
                        {isSelected && <FiCheck size={16} strokeWidth={3} />}
                      </div>
                      <input type="checkbox" checked={isSelected} onChange={() => handleToggle(sub.name)} style={{ display: 'none' }} />
                      <div style={{ flex: 1 }}>
                        <div style={{ fontSize: 15, fontWeight: 600, color: isSelected ? 'var(--t-primary)' : 'var(--t-text)' }}>
                          {sub.name}
                        </div>
                        {sub.name_ta && <div style={{ fontSize: 13, color: 'var(--t-text-3)', marginTop: 2 }}>{sub.name_ta}</div>}
                      </div>
                      <div style={{ fontSize: 14, fontWeight: 600, color: isSelected ? 'var(--t-primary)' : 'var(--t-muted)', whiteSpace: 'nowrap' }}>
                        Rs. {price.toFixed(0)}
                      </div>
                    </label>
                  );
                }) : (
                  <div style={{ textAlign: 'center', padding: '30px', color: 'var(--t-muted)', fontSize: 14 }}>
                    Loading subjects...
                  </div>
                )}
              </div>

              {/* Pricing Summary Card */}
              {selectedSubjects.length > 0 && (
                <div style={{
                  marginTop: 20, padding: '16px 20px', borderRadius: 16,
                  background: 'var(--t-primary-soft)',
                  border: '1px solid var(--t-primary-border)'
                }}>
                  <FeeRow
                    label={`${selectedSubjects.length} subject${selectedSubjects.length > 1 ? 's' : ''} selected`}
                    value={hasDiscount ? formatRs(originalAmount) : ''}
                    isStrikethrough={hasDiscount}
                  />

                  {hasDiscount && (
                    <div style={{ margin: '6px 0' }}>
                      <span style={{
                        fontSize: 12, fontWeight: 600, color: 'var(--t-success)',
                        background: 'var(--t-success-bg)', padding: '2px 10px', borderRadius: 20
                      }}>
                        🎉 {appliedPackage?.name || 'Package'} — You save {formatRs(savings)}
                      </span>
                    </div>
                  )}

                  <FeeRow
                    label="Monthly Fee"
                    value={formatRs(monthlyAmount)}
                    isTotal={true}
                  />
                </div>
              )}
            </>
          )}

          {step === 2 && (
            <div style={{ padding: '10px 0' }}>
              <div style={{
                background: 'var(--t-primary-soft)', padding: '20px', borderRadius: '16px',
                border: '1px solid var(--t-primary-border)', marginBottom: '24px'
              }}>
                {hasDiscount ? (
                  <>
                    <FeeRow
                      label="Subjects Total:"
                      value={formatRs(originalAmount)}
                      isStrikethrough={true}
                    />
                    <FeeRow
                      label={`Package Discount (${appliedPackage?.name || 'Package'}):`}
                      value={formatRs(serverMonthly ?? monthlyAmount)}
                      isDiscount={true}
                    />
                  </>
                ) : (
                  <FeeRow
                    label="Monthly Fee:"
                    value={formatRs(serverMonthly ?? monthlyAmount)}
                  />
                )}

                {serverAdmission > 0 && (
                  <FeeRow
                    label="Admission Fee (First Time):"
                    value={formatRs(serverAdmission)}
                  />
                )}

                <FeeRow
                  label="Total Amount:"
                  value={formatRs(serverAmount ?? monthlyAmount)}
                  isTotal={true}
                />

              </div>

              {alreadyPaid ? (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                  <div style={{ background: 'var(--t-success-bg)', color: 'var(--t-success)', padding: '14px 16px', borderRadius: 12, fontSize: 14, fontWeight: 500 }}>
                    {paidUntil ? (
                      <>You have already paid up to <strong>{paidUntil}</strong>. Your updated subjects are saved and the new fee will apply from next month's payment.</>
                    ) : (
                      <>You have already paid for this month. Your updated subjects are saved and the new fee will apply from next month's payment.</>
                    )}
                  </div>
                  
                  <div style={{ marginTop: 8 }}>
                    <label style={{
                      display: 'flex',
                      alignItems: 'center',
                      gap: 12,
                      minHeight: 48,
                      padding: '8px 0',
                      cursor: 'pointer',
                      fontSize: 14,
                      color: 'var(--t-text-2)',
                      fontWeight: 600,
                      touchAction: 'manipulation',
                      WebkitTapHighlightColor: 'transparent',
                      userSelect: 'none'
                    }}>
                      <input 
                        type="checkbox" 
                        checked={wantAdvancePayment} 
                        onChange={(e) => setWantAdvancePayment(e.target.checked)} 
                      />
                      <span>I want to pay for advance months (Offline)</span>
                    </label>
                  </div>

                  {wantAdvancePayment ? (
                    <div style={{ background: 'var(--t-bg)', padding: 16, borderRadius: 12, border: '1px solid var(--t-border)', marginTop: 8 }}>
                      <label style={{ display: 'block', fontSize: 13, color: 'var(--t-text-3)', marginBottom: 6 }}>Number of Months:</label>
                      <input 
                        type="number" 
                        min="1" 
                        max="12" 
                        value={advanceMonths} 
                        onChange={(e) => setAdvanceMonths(parseInt(e.target.value) || 1)} 
                        style={{ width: '100%', padding: '10px 12px', borderRadius: 8, border: '1px solid var(--t-border)', marginBottom: 12 }} 
                      />
                      <FeeRow
                        label="Total Advance Amount:"
                        value={formatRs((serverMonthly ?? monthlyAmount) * advanceMonths)}
                        isTotal={true}
                        style={{ marginBottom: 16 }}
                      />
                      
                      <button 
                        onClick={handlePayAdvanceOffline} 
                        disabled={loading || paymentDone}
                        style={{
                          width: '100%', padding: '14px 20px', borderRadius: '12px', border: '2px solid var(--t-danger)',
                          background: '#fff', color: 'var(--t-danger)', fontSize: '15px', fontWeight: 600,
                          cursor: loading ? 'not-allowed' : 'pointer', transition: 'all 0.2s', textAlign: 'center'
                        }}
                      >
                        I WILL PAY OFFLINE (CASH/BANK)
                      </button>
                    </div>
                  ) : (
                    <button onClick={handleClose} style={{
                      padding: '14px 20px', borderRadius: '12px', border: 'none',
                      background: 'var(--t-primary)', color: '#fff', fontSize: '15px', fontWeight: 600, cursor: 'pointer'
                    }}>Done</button>
                  )}
                </div>
              ) : (
              <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                <button 
                  onClick={handlePayOffline} 
                  disabled={loading || paymentDone}
                  style={{
                    padding: '14px 20px', borderRadius: '12px',
                    background: '#fff', color: 'var(--t-danger)', fontSize: '15px', fontWeight: 600,
                    cursor: loading ? 'not-allowed' : 'pointer', border: '2px solid var(--t-danger)',
                    transition: 'all 0.2s', textAlign: 'center'
                  }}
                >
                  I WILL PAY OFFLINE
                </button>
                <button 
                  onClick={handlePayOnline} 
                  disabled={loading || paymentDone}
                  style={{
                    padding: '14px 20px', borderRadius: '12px', border: 'none',
                    background: 'var(--t-primary)', color: '#fff', fontSize: '15px', fontWeight: 600,
                    cursor: loading ? 'not-allowed' : 'pointer',
                    transition: 'all 0.2s', textAlign: 'center'
                  }}
                >
                  {loading ? 'Processing...' : 'PAY ONLINE'}
                </button>
              </div>
              )}
            </div>
          )}
        </div>

        <div style={{ padding: '20px', borderTop: '1px solid var(--t-border)', background: 'var(--t-bg)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          {step === 2 ? (
            <button onClick={() => setStep(1)} style={{
              padding: '10px 16px', border: 'none', background: 'transparent',
              color: 'var(--t-text-3)', fontSize: 14, fontWeight: 600, cursor: 'pointer'
            }}>Back</button>
          ) : <div></div>}
          
          {step === 1 && (
            <div style={{ display: 'flex', gap: 12, marginLeft: 'auto' }}>
              <button onClick={onClose} style={{
                minHeight: 44, padding: '12px 24px', borderRadius: 12, border: '1px solid var(--t-border)',
                background: '#fff', color: 'var(--t-text-2)', fontSize: 15, fontWeight: 600, cursor: 'pointer'
              }}>Cancel</button>
              
              <button onClick={handleSaveSubjects} disabled={loading} style={{
                minHeight: 44, padding: '12px 28px', borderRadius: 12, border: 'none',
                background: 'var(--t-primary)',
                color: '#fff', fontSize: 15, fontWeight: 600, cursor: loading ? 'not-allowed' : 'pointer',
                opacity: loading ? 0.7 : 1, display: 'flex', alignItems: 'center', gap: 8
              }}>
                {loading ? 'Saving...' : 'Save Subjects'}
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
