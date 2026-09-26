import React, { useState, useEffect, useMemo } from 'react';
import { FiX, FiCheck } from 'react-icons/fi';
import { updateStudentSubjects, initializeMonthlyPayment, initializeOfflineMonthlyPayment } from '../../services/dashboardService';
import useSubjectPricing from '../../hooks/useSubjectPricing';

export default function SubjectUpdateModal({ isOpen, onClose, currentGrade, medium, initialSubjects = [], onUpdateSuccess }) {
  const [step, setStep] = useState(1);
  const [localMedium, setLocalMedium] = useState(medium || '');
  const [availableSubjects, setAvailableSubjects] = useState([]);
  const [selectedSubjects, setSelectedSubjects] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  // Reset state when opened
  useEffect(() => {
    if (isOpen) {
      setStep(1);
      setLocalMedium(medium || '');
      setError('');
      setSuccessMessage('');
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
       msgEn += `But if you select 2 or more subjects, your package price will be reduced to Rs. ${pkgPrice}!`;
       msgTa += `ஆனால், நீங்கள் 2 அல்லது அதற்கு மேற்பட்ட பாடங்களை தேர்ந்தெடுத்தால் Package கட்டணம் Rs. ${pkgPrice} ஆக குறைக்கப்படும்!`;
    } else {
       msgEn = `Note: Your monthly fee is calculated based on the subjects you select. ` + msgEn;
       msgTa = `குறிப்பு: உங்கள் மாதக் கட்டணம் நீங்கள் தேர்ந்தெடுக்கும் பாடங்களின் அடிப்படையில் கணக்கிடப்படும். ` + msgTa;
    }

    return (
      <div style={{ backgroundColor: '#e0e7ff', color: '#3730a3', padding: '12px 16px', borderRadius: '8px', fontSize: '13px', marginBottom: '16px', borderLeft: '4px solid #4f46e5' }}>
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

  const getNumericGrade = (gradeValue) => {
    if (!gradeValue) return null;
    if (typeof gradeValue === 'number') return gradeValue;
    const match = gradeValue.toString().match(/(\d+)/);
    return match ? parseInt(match[1], 10) : null;
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
            subs = [
                ...(data['arts_stream'] || []),
                ...(data['bio_maths_stream'] || []),
                ...(data['commerce_stream'] || []),
                ...(data['tech_stream'] || [])
            ];
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

  const handleSaveSubjects = async (e) => {
    e.preventDefault();
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
      await updateStudentSubjects(selectedSubjects, localMedium);
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
      setTimeout(() => {
        onUpdateSuccess(selectedSubjects);
      }, 2000);
    } catch (err) {
      setError(err.message || 'Failed to initialize offline payment');
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

  const hasDiscount = appliedPackage && monthlyAmount < originalAmount;
  const savings = originalAmount - monthlyAmount;

  return (
    <div style={{
      position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
      background: 'rgba(15,23,42,0.6)', backdropFilter: 'blur(4px)',
      display: 'flex', alignItems: 'center', justifyContent: 'center',
      zIndex: 9999, padding: 20
    }}>
      <div style={{
        background: '#fff', borderRadius: 24, width: '100%', maxWidth: 500,
        boxShadow: '0 25px 50px -12px rgba(0,0,0,0.25)', overflow: 'hidden',
        display: 'flex', flexDirection: 'column', maxHeight: '90vh',
        margin: '16px'
      }}>
        <div style={{
          padding: '20px', borderBottom: '1px solid #f1f5f9',
          display: 'flex', alignItems: 'center', justifyContent: 'space-between'
        }}>
          <div>
            <h2 style={{ margin: 0, fontSize: 20, fontWeight: 700, color: '#1e293b' }}>
              {step === 1 ? 'Update Your Subjects' : 'Monthly Payment'}
            </h2>
            <p style={{ margin: '4px 0 0', fontSize: 14, color: '#64748b' }}>For {currentGrade}</p>
          </div>
          <button onClick={onClose} style={{
            background: '#f1f5f9', border: 'none', width: 36, height: 36,
            borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center',
            color: '#64748b', cursor: 'pointer', transition: 'all 0.2s'
          }} onMouseEnter={e => e.currentTarget.style.background = '#e2e8f0'}
             onMouseLeave={e => e.currentTarget.style.background = '#f1f5f9'}>
            <FiX size={18} />
          </button>
        </div>

        <div style={{ padding: '20px', overflowY: 'auto' }}>
          {error && (
            <div style={{ background: '#fef2f2', color: '#ef4444', padding: '12px 16px', borderRadius: 12, fontSize: 14, marginBottom: 20, fontWeight: 500 }}>
              {error}
            </div>
          )}
          {successMessage && (
            <div style={{ background: '#dcfce7', color: '#16a34a', padding: '12px 16px', borderRadius: 12, fontSize: 14, marginBottom: 20, fontWeight: 500 }}>
              {successMessage}
            </div>
          )}

          {step === 1 && (
            <>
              <div style={{ marginBottom: 20 }}>
                <label style={{ display: 'block', fontSize: 14, fontWeight: 600, color: '#334155', marginBottom: 8 }}>
                  Medium of Learning <span style={{ color: '#ef4444' }}>*</span>
                </label>
                <select
                  value={localMedium}
                  onChange={(e) => setLocalMedium(e.target.value)}
                  style={{
                    width: '100%', padding: '12px 16px', borderRadius: 12, border: '1px solid #cbd5e1',
                    fontSize: 15, color: '#1e293b', outline: 'none', appearance: 'none',
                    background: '#fff url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2364748b\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3E%3Cpolyline points=\'6 9 12 15 18 9\'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 16px center'
                  }}
                >
                  <option value="">Select Medium</option>
                  <option value="tamil">Tamil / தமிழ்</option>
                  <option value="english">English</option>
                </select>
              </div>

              <p style={{ fontSize: 15, color: '#475569', marginBottom: 20, lineHeight: 1.5 }}>
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
                      border: `2px solid ${isSelected ? '#6366f1' : '#e2e8f0'}`,
                      borderRadius: 16, cursor: 'pointer', transition: 'all 0.2s',
                      background: isSelected ? '#f5f3ff' : '#fff'
                    }}>
                      <div style={{
                        width: 24, height: 24, borderRadius: 6,
                        border: `2px solid ${isSelected ? '#6366f1' : '#cbd5e1'}`,
                        background: isSelected ? '#6366f1' : '#fff',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        color: '#fff', transition: 'all 0.2s'
                      }}>
                        {isSelected && <FiCheck size={16} strokeWidth={3} />}
                      </div>
                      <input type="checkbox" checked={isSelected} onChange={() => handleToggle(sub.name)} style={{ display: 'none' }} />
                      <div style={{ flex: 1 }}>
                        <div style={{ fontSize: 15, fontWeight: 600, color: isSelected ? '#4f46e5' : '#334155' }}>
                          {sub.name}
                        </div>
                        {sub.name_ta && <div style={{ fontSize: 13, color: '#64748b', marginTop: 2 }}>{sub.name_ta}</div>}
                      </div>
                      <div style={{ fontSize: 14, fontWeight: 600, color: isSelected ? '#4f46e5' : '#94a3b8', whiteSpace: 'nowrap' }}>
                        Rs. {price.toFixed(0)}
                      </div>
                    </label>
                  );
                }) : (
                  <div style={{ textAlign: 'center', padding: '30px', color: '#94a3b8', fontSize: 14 }}>
                    Loading subjects...
                  </div>
                )}
              </div>

              {/* Pricing Summary Card */}
              {selectedSubjects.length > 0 && (
                <div style={{
                  marginTop: 20, padding: '16px 20px', borderRadius: 16,
                  background: 'linear-gradient(135deg, #f5f3ff 0%, #eef2ff 100%)',
                  border: '1px solid #e0e7ff'
                }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: hasDiscount ? 8 : 0 }}>
                    <span style={{ fontSize: 14, color: '#475569', fontWeight: 500 }}>
                      {selectedSubjects.length} subject{selectedSubjects.length > 1 ? 's' : ''} selected
                    </span>
                    {hasDiscount ? (
                      <span style={{ fontSize: 14, color: '#94a3b8', textDecoration: 'line-through' }}>
                        Rs. {originalAmount.toFixed(0)}
                      </span>
                    ) : null}
                  </div>

                  {hasDiscount && (
                    <div style={{
                      display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                      marginBottom: 8
                    }}>
                      <span style={{
                        fontSize: 12, fontWeight: 600, color: '#16a34a',
                        background: '#dcfce7', padding: '2px 10px', borderRadius: 20
                      }}>
                        🎉 {appliedPackage?.name || 'Package'} — You save Rs. {savings.toFixed(0)}
                      </span>
                    </div>
                  )}

                  <div style={{
                    display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                    paddingTop: hasDiscount ? 8 : 0,
                    borderTop: hasDiscount ? '1px solid #c7d2fe' : 'none'
                  }}>
                    <span style={{ fontSize: 16, fontWeight: 700, color: '#1e293b' }}>Monthly Fee</span>
                    <span style={{ fontSize: 22, fontWeight: 800, color: '#4f46e5' }}>
                      Rs. {monthlyAmount.toFixed(0)}
                    </span>
                  </div>
                </div>
              )}
            </>
          )}

          {step === 2 && (
            <div style={{ padding: '10px 0' }}>
              <div style={{
                background: 'rgba(235, 129, 83, 0.1)', padding: '20px', borderRadius: '16px',
                border: '1px solid rgba(235, 129, 83, 0.3)', marginBottom: '24px'
              }}>
                {hasDiscount ? (
                  <>
                    <div style={{ display: 'flex', justifyContent: 'space-between', color: '#64748b', marginBottom: '8px' }}>
                      <span>Subjects Total:</span>
                      <span style={{ textDecoration: 'line-through' }}>Rs. {originalAmount.toFixed(2)}</span>
                    </div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', color: '#16a34a', fontWeight: 'bold', marginBottom: '8px' }}>
                      <span>Package Discount:</span>
                      <span>Rs. {monthlyAmount.toFixed(2)}</span>
                    </div>
                  </>
                ) : (
                  <div style={{ display: 'flex', justifyContent: 'space-between', color: '#64748b', marginBottom: '8px' }}>
                    <span>Monthly Fee:</span>
                    <span>Rs. {monthlyAmount.toFixed(2)}</span>
                  </div>
                )}
                
                <hr style={{ borderColor: 'rgba(235, 129, 83, 0.3)', margin: '12px 0' }} />
                <div style={{ display: 'flex', justifyContent: 'space-between', color: '#4f46e5', fontWeight: 'bold', fontSize: '20px' }}>
                  <span>Total Amount:</span>
                  <span>Rs. {monthlyAmount.toFixed(2)}</span>
                </div>
              </div>

              <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                <button 
                  onClick={handlePayOffline} 
                  disabled={loading || !!successMessage}
                  style={{
                    padding: '14px 20px', borderRadius: '12px',
                    background: '#fff', color: '#ef4444', fontSize: '15px', fontWeight: 600,
                    cursor: loading ? 'not-allowed' : 'pointer', border: '2px solid #ef4444',
                    transition: 'all 0.2s', textAlign: 'center'
                  }}
                  onMouseEnter={e => !loading && (e.currentTarget.style.background = '#fef2f2')}
                  onMouseLeave={e => !loading && (e.currentTarget.style.background = '#fff')}
                >
                  I WILL PAY OFFLINE
                </button>
                <button 
                  onClick={handlePayOnline} 
                  disabled={loading || !!successMessage}
                  style={{
                    padding: '14px 20px', borderRadius: '12px', border: 'none',
                    background: '#16a34a', color: '#fff', fontSize: '15px', fontWeight: 600,
                    cursor: loading ? 'not-allowed' : 'pointer',
                    transition: 'all 0.2s', textAlign: 'center'
                  }}
                  onMouseEnter={e => !loading && (e.currentTarget.style.background = '#15803d')}
                  onMouseLeave={e => !loading && (e.currentTarget.style.background = '#16a34a')}
                >
                  {loading ? 'Processing...' : 'PAY ONLINE'}
                </button>
              </div>
            </div>
          )}
        </div>

        <div style={{ padding: '20px', borderTop: '1px solid #f1f5f9', background: '#f8fafc', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          {step === 2 ? (
            <button onClick={() => setStep(1)} style={{
              padding: '10px 16px', border: 'none', background: 'transparent',
              color: '#64748b', fontSize: 14, fontWeight: 600, cursor: 'pointer'
            }}>Back</button>
          ) : <div></div>}
          
          {step === 1 && (
            <div style={{ display: 'flex', gap: 12, marginLeft: 'auto' }}>
              <button onClick={onClose} style={{
                padding: '12px 24px', borderRadius: 12, border: '1px solid #cbd5e1',
                background: '#fff', color: '#475569', fontSize: 15, fontWeight: 600, cursor: 'pointer'
              }}>Cancel</button>
              
              <button onClick={handleSaveSubjects} disabled={loading} style={{
                padding: '12px 28px', borderRadius: 12, border: 'none',
                background: 'linear-gradient(135deg, #6366f1, #4f46e5)',
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
