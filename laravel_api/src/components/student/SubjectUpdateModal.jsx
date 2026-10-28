import React, { useState, useEffect, useMemo, useRef } from 'react';
import { FiX, FiCheck, FiLoader, FiClock, FiAlertCircle, FiCheckCircle, FiLock } from 'react-icons/fi';
import {
  updateStudentSubjects,
  initializeMonthlyPayment,
  initializeOfflineMonthlyPayment,
  requestAdvancePaymentOffline,
  getStudentPaymentStatus,
  paySubjectTopupOnline,
  paySubjectTopupOffline,
  cancelSubjectTopup
} from '../../services/dashboardService';
import useSubjectPricing from '../../hooks/useSubjectPricing';
import FeeRow from '../shared/FeeRow';
import AdvanceMonthsBlock from './AdvanceMonthsBlock';
import { formatRs } from '../../utils/format';
import { useLanguage } from '../../context/LanguageContext';

const formatDate = (isoString) => {
  if (!isoString) return new Date().toISOString().split('T')[0];
  if (typeof isoString === 'string' && isoString.includes('T')) {
    return isoString.split('T')[0];
  }
  return String(isoString).substring(0, 10);
};

export const formatAmount = (amt) => {
  const num = typeof amt === 'number' ? amt : (parseFloat(amt) || 0);
  return num % 1 === 0 ? num : num.toFixed(2);
};

export const formatPendingMediumMessage = (medium, lang = 'en', t = (k, def) => def) => {
  let mediumStr = 'current medium';
  const lower = String(medium || '').toLowerCase();
  if (lower === 'tamil') {
    mediumStr = lang === 'ta' ? 'தமிழ்' : lang === 'si' ? 'දෙමළ' : 'Tamil';
  } else if (lower === 'english') {
    mediumStr = lang === 'ta' ? 'ஆங்கில' : lang === 'si' ? 'ඉංග්‍රීසි' : 'English';
  }

  if (lang === 'en') {
    return `Medium change requested – waiting for admin approval; until then you keep ${mediumStr} materials.`;
  }
  const template = t('subj_update_pending_medium_msg', 'Medium change requested – waiting for admin approval; until then you keep {medium} materials.');
  return template.replace('{medium}', mediumStr);
};

export const formatAlreadyPaidMessage = (currentMonth, nextMonth, fee, lang = 'en', t = (k, def) => def) => {
  const feeStr = formatAmount(fee);
  if (lang === 'en') {
    return `Subjects saved. This month (${currentMonth}) is already paid – nothing more to pay now. Your new monthly fee Rs ${feeStr} starts from ${nextMonth}.`;
  }
  const template = t('subj_update_already_paid_msg', 'Subjects saved. This month ({month}) is already paid – nothing more to pay now. Your new monthly fee Rs {fee} starts from {nextMonth}.');
  return template
    .replace('{month}', currentMonth)
    .replace('{fee}', feeStr)
    .replace('{nextMonth}', nextMonth);
};

/**
 * Paid month, no extra money due, fee unchanged (e.g. the monthly maximum / package price).
 */
export const formatPaidFeeStaysMessage = (currentMonth, fee, lang = 'en', t = (k, def) => def) => {
  const feeStr = formatAmount(fee);
  const template = t(
    'subj_update_paid_stays_msg',
    'Subjects saved. This month ({month}) is already paid. Your fee stays Rs {fee} (the monthly maximum / package price). Next month\'s fee: Rs {fee}.'
  );
  return template.split('{month}').join(currentMonth).split('{fee}').join(feeStr);
};

/**
 * Paid month, the new fee is LOWER than what was paid: no refund, new fee from next month.
 */
export const formatPaidFeeLowerMessage = (currentMonth, nextMonth, fee, lang = 'en', t = (k, def) => def) => {
  const feeStr = formatAmount(fee);
  const template = t(
    'subj_update_paid_lower_msg',
    'Subjects saved. This month ({month}) is already paid. Your new fee Rs {fee} starts from {nextMonth}; no refund for this month.'
  );
  return template.split('{month}').join(currentMonth).split('{fee}').join(feeStr).split('{nextMonth}').join(nextMonth);
};

export default function SubjectUpdateModal({ isOpen, onClose, currentGrade, medium, pendingMedium, stream, initialSubjects = [], onUpdateSuccess }) {
  let t = (k, def) => def;
  let language = 'en';
  try {
    const lang = useLanguage();
    if (lang) {
      t = lang.t;
      language = lang.language;
    }
  } catch (_) {}

  const [serverAmount, setServerAmount] = useState(null);
  const [serverMonthly, setServerMonthly] = useState(null);
  const [serverAdmission, setServerAdmission] = useState(0);
  const [alreadyPaid, setAlreadyPaid] = useState(false);
  const [paidUntil, setPaidUntil] = useState(null);
  const [saved, setSaved] = useState(false);
  const [paymentDone, setPaymentDone] = useState(false);
  const [existingPayment, setExistingPayment] = useState(null);
  const [isMediumChangePending, setIsMediumChangePending] = useState(Boolean(pendingMedium));
  const [step, setStep] = useState(1);
  const [localMedium, setLocalMedium] = useState(medium || '');
  const [availableSubjects, setAvailableSubjects] = useState([]);
  const [selectedSubjects, setSelectedSubjects] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  const [paidAmount, setPaidAmount] = useState(null);
  const [topupDiff, setTopupDiff] = useState(0);
  const [pendingTopup, setPendingTopup] = useState(null);
  const [lockedSubjects, setLockedSubjects] = useState([]);
  const [topupOfflineRequested, setTopupOfflineRequested] = useState(false);
  const submittingOfflineRef = useRef(false);

  useEffect(() => {
    if (isOpen && step === 2) {
      getStudentPaymentStatus().then(status => {
        if (status && status.payment) {
          setExistingPayment(status.payment);
        }
      }).catch(console.error);
    }
  }, [isOpen, step]);

  // Reset state and lock body scroll when opened
  useEffect(() => {
    if (isOpen) {
      setStep(1);
      setLocalMedium(medium || '');
      setIsMediumChangePending(Boolean(pendingMedium));
      setError('');
      setSuccessMessage('');
      setServerAmount(null);
      setServerMonthly(null);
      setServerAdmission(0);
      setAlreadyPaid(false);
      setPaidUntil(null);
      setPaidAmount(null);
      setTopupDiff(0);
      setPendingTopup(null);
      setLockedSubjects([]);
      setTopupOfflineRequested(false);
      setSaved(false);
      setPaymentDone(false);
      submittingOfflineRef.current = false;

      const prevOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';

      // Hide the floating assistant bubble while the modal is open so it doesn't overlap selects or buttons on mobile
      const assistantWidget = document.getElementById('unified-assistant-widget');
      const prevAssistantDisplay = assistantWidget ? assistantWidget.style.display : null;
      if (assistantWidget) {
        assistantWidget.style.display = 'none';
      }

      return () => {
        document.body.style.overflow = prevOverflow;
        if (assistantWidget) {
          assistantWidget.style.display = prevAssistantDisplay || '';
        }
      };
    }
  }, [isOpen, medium, pendingMedium]);

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
      onUpdateSuccess(selectedSubjects, isMediumChangePending ? localMedium : null);
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
        setIsMediumChangePending(true);
        setSuccessMessage('Subjects updated. Your medium change request is pending admin approval.');
      }
      setServerAmount(resp && typeof resp.amount === 'number' ? resp.amount : null);
      setServerMonthly(resp && typeof resp.monthly_fee === 'number' ? resp.monthly_fee : null);
      setServerAdmission(resp && typeof resp.admission_fee === 'number' ? resp.admission_fee : 0);
      setAlreadyPaid(!!(resp && resp.already_paid));
      setPaidAmount(resp && resp.paid_amount !== undefined && resp.paid_amount !== null ? Number(resp.paid_amount) : null);
      if (resp && resp.paid_until) {
        setPaidUntil(resp.paid_until);
      }
      if (resp && resp.topup) {
        setPendingTopup(resp.topup);
      } else {
        setPendingTopup(null);
      }
      setTopupDiff(resp && typeof resp.diff === 'number' ? resp.diff : 0);
      setLockedSubjects(resp && Array.isArray(resp.locked_subjects) ? resp.locked_subjects : []);
      setSaved(true);
      setStep(2); // Move to payment step
    } catch (err) {
      setError(err.message || 'Failed to update subjects.');
    } finally {
      setLoading(false);
    }
  };

  const handlePayTopupOnline = async () => {
    if (!pendingTopup || loading) return;
    setLoading(true);
    setError('');
    try {
      const resp = await paySubjectTopupOnline(pendingTopup.id);
      if (resp && resp.success && resp.message) {
        setSuccessMessage(resp.message);
        setPaymentDone(true);
        setPendingTopup(null);
        setTopupDiff(0);
        setLockedSubjects([]);
        setTimeout(() => {
          onUpdateSuccess(selectedSubjects);
        }, 1500);
        return;
      }
      if (resp && resp.payhere) {
        if (!window.payhere) {
          throw new Error('PayHere SDK not loaded');
        }
        const payment = {
          sandbox: resp.payhere.payhere_url?.includes('sandbox'),
          ...resp.payhere.params
        };
        window.payhere.onCompleted = async function onCompleted(orderId) {
          setSuccessMessage('Payment received – confirming…');
          setLoading(true);

          const pollInterval = 3000;
          const maxAttempts = 20;
          let attempts = 0;
          let confirmed = false;

          while (attempts < maxAttempts && !confirmed) {
            attempts++;
            try {
              const statusData = await getStudentPaymentStatus();
              if (!statusData.pending_topup || statusData.pending_topup.status === 'paid') {
                confirmed = true;
                setSuccessMessage('Top-up payment completed successfully! Your subjects are unlocked.');
                setPaymentDone(true);
                setPendingTopup(null);
                setTopupDiff(0);
                setLockedSubjects([]);
                setTimeout(() => {
                  onUpdateSuccess(selectedSubjects);
                }, 1500);
                return;
              }
            } catch (e) {
              console.warn('Polling top-up status error:', e);
            }

            if (attempts < maxAttempts) {
              await new Promise((r) => setTimeout(r, pollInterval));
            }
          }

          if (!confirmed) {
            setLoading(false);
            setError('We have not received the confirmation yet – you will get an e-mail/WhatsApp as soon as it arrives. Do not pay again.');
          }
        };
        window.payhere.onDismissed = function onDismissed() {
          setLoading(false);
        };
        window.payhere.onError = function onError(errStr) {
          setError('Payment failed: ' + errStr);
          setLoading(false);
        };
        window.payhere.startPayment(payment);
      }
    } catch (err) {
      setError(err.message || 'Failed to initiate online top-up payment');
    } finally {
      setLoading(false);
    }
  };

  const handlePayTopupOffline = async () => {
    if (!pendingTopup || loading) return;
    setLoading(true);
    setError('');
    try {
      const resp = await paySubjectTopupOffline(pendingTopup.id);
      setSuccessMessage(resp.message || 'Top-up offline payment requested. Added subjects will unlock once admin confirms.');
      setTopupOfflineRequested(true);
      if (resp.topup) {
        setPendingTopup(resp.topup);
      }
    } catch (err) {
      setError(err.message || 'Failed to request offline top-up');
    } finally {
      setLoading(false);
    }
  };

  const handleCancelTopup = async () => {
    if (!pendingTopup || loading) return;
    setLoading(true);
    setError('');
    try {
      const resp = await cancelSubjectTopup(pendingTopup.id);
      setSuccessMessage(resp.message || 'Top-up cancelled and added subjects removed.');
      setPendingTopup(null);
      setTopupDiff(0);
      setLockedSubjects([]);
      if (resp.user && resp.user.selected_subjects) {
        const subs = typeof resp.user.selected_subjects === 'string' ? JSON.parse(resp.user.selected_subjects) : resp.user.selected_subjects;
        setSelectedSubjects(subs);
        onUpdateSuccess(subs);
      }
      setTimeout(() => {
        handleClose();
      }, 800);
    } catch (err) {
      setError(err.message || 'Failed to cancel top-up');
    } finally {
      setLoading(false);
    }
  };

  const handlePayOffline = async () => {
    if (submittingOfflineRef.current || loading || paymentDone) return;
    submittingOfflineRef.current = true;
    setLoading(true);
    setError('');

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 20000);

    try {
      const resp = await initializeOfflineMonthlyPayment({ signal: controller.signal });
      clearTimeout(timeoutId);
      if (resp && resp.payment) {
        setExistingPayment(resp.payment);
      } else {
        setExistingPayment({
          status: 'pending',
          payment_method: 'offline',
          created_at: new Date().toISOString(),
          amount: serverMonthly ?? monthlyAmount
        });
      }
      setSuccessMessage(resp.message || 'Offline payment initiated.');
      setPaymentDone(true);
      setTimeout(() => {
        setLoading(false);
        submittingOfflineRef.current = false;
      }, 1500);
    } catch (err) {
      clearTimeout(timeoutId);
      setError(err.name === 'AbortError' ? 'Request timed out. Please check your connection.' : (err.message || 'Failed to initialize offline payment'));
      setTimeout(() => {
        setLoading(false);
        submittingOfflineRef.current = false;
      }, 1500);
    }
  };

  const handlePayAdvanceOffline = async (monthsArg) => {
    const num = parseInt(monthsArg, 10);
    if (isNaN(num) || num < 1 || num > 12) {
      setError('Please select a valid number of months (1-12).');
      return;
    }
    if (submittingOfflineRef.current || loading || paymentDone) return;
    submittingOfflineRef.current = true;
    setLoading(true);
    setError('');

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 20000);

    try {
      const resp = await requestAdvancePaymentOffline(num, { signal: controller.signal });
      clearTimeout(timeoutId);
      setSuccessMessage(resp.message || 'Advance payment requested.');
      setPaymentDone(true);
      setTimeout(() => {
        onUpdateSuccess(selectedSubjects);
      }, 3000);
    } catch (err) {
      clearTimeout(timeoutId);
      setError(err.name === 'AbortError' ? 'Request timed out. Please check your connection.' : (err.message || 'Failed to request advance payment'));
      setLoading(false);
      submittingOfflineRef.current = false;
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

      window.payhere.onCompleted = async function onCompleted(orderId) {
        setSuccessMessage('Payment received – confirming…');
        setLoading(true);

        const pollInterval = 3000;
        const maxAttempts = 20;
        let attempts = 0;
        let confirmed = false;

        while (attempts < maxAttempts && !confirmed) {
          attempts++;
          try {
            const statusData = await getStudentPaymentStatus();
            if (statusData && (statusData.is_paid || statusData.has_paid || !statusData.pending_topup)) {
              confirmed = true;
              setSuccessMessage('Payment completed successfully!');
              setPaymentDone(true);
              setTimeout(() => {
                onUpdateSuccess(selectedSubjects);
              }, 1500);
              return;
            }
          } catch (e) {
            console.warn('Polling payment status error:', e);
          }

          if (attempts < maxAttempts) {
            await new Promise((r) => setTimeout(r, pollInterval));
          }
        }

        if (!confirmed) {
          setLoading(false);
          setError('We have not received the confirmation yet – you will get an e-mail/WhatsApp as soon as it arrives. Do not pay again.');
        }
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

  const getGradeHeaderText = (grade) => {
    if (!grade) return '';
    const trimmed = String(grade).trim();
    const isNumeric = /^\d+$/.test(trimmed);
    const gradePrefix = t('grade_prefix', 'Grade');
    const displayGrade = isNumeric ? `${gradePrefix} ${trimmed}` : trimmed;
    const template = t('for_grade', language === 'ta' ? '{grade} இற்கானது' : (language === 'si' ? '{grade} සඳහා' : 'For {grade}'));
    return template.replace('{grade}', displayGrade);
  };

  return (
    <div className="shared-modal-overlay" style={{
      position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
      background: 'rgba(15,23,42,0.6)',
      display: 'flex', alignItems: 'center', justifyContent: 'center',
      zIndex: 10000, padding: 12
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
            <p style={{ margin: '4px 0 0', fontSize: 14, color: 'var(--t-text-3)' }}>{getGradeHeaderText(currentGrade)}</p>
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
                  id="subject-update-medium-select"
                  className="t-select"
                  value={localMedium}
                  onChange={(e) => setLocalMedium(e.target.value)}
                  style={{
                    width: '100%', minHeight: 44
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
                        🎉 You save {formatRs(savings)}
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

          {step === 2 && (() => {
            const now = new Date();
            const nextDate = new Date(now.getFullYear(), now.getMonth() + 1, 1);
            const locale = language === 'ta' ? 'ta-LK' : language === 'si' ? 'si-LK' : 'en-US';
            let curMonth = now.toLocaleString(locale, { month: 'long' });
            let nextMonth = nextDate.toLocaleString(locale, { month: 'long' });
            if (!curMonth || curMonth.includes('T') || /^\d+$/.test(curMonth)) {
              curMonth = now.toLocaleString('en-US', { month: 'long' });
              nextMonth = nextDate.toLocaleString('en-US', { month: 'long' });
            }
            if (paidUntil && typeof paidUntil === 'string') {
              const parts = paidUntil.trim().split(' ');
              if (parts.length > 0 && isNaN(parseInt(parts[0], 10))) {
                curMonth = parts[0];
              }
            }

            return (
              <div style={{ padding: '10px 0' }}>
                {/* Yellow Card: Medium change requested */}
                {isMediumChangePending && (
                  <div
                    className="status-card-pending-medium"
                    data-testid="modal-pending-medium-card"
                    style={{
                      background: '#fffbeb',
                      color: '#92400e',
                      border: '1px solid #fde68a',
                      borderRadius: 12,
                      padding: '14px 16px',
                      fontSize: 14,
                      fontWeight: 500,
                      display: 'flex',
                      alignItems: 'center',
                      gap: 10,
                      marginBottom: 16,
                      lineHeight: 1.5,
                      boxSizing: 'border-box'
                    }}
                  >
                    <FiAlertCircle style={{ flexShrink: 0, fontSize: 18, color: '#d97706' }} />
                    <span>{formatPendingMediumMessage(medium || localMedium, language, t)}</span>
                  </div>
                )}

                {/* Pricing Summary Card */}
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
                        label="Monthly Fee:"
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
                  /* State 1: Current month is ALREADY paid */
                  <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                    {topupDiff > 0 && pendingTopup ? (
                      <div
                        className="status-card-topup-required"
                        data-testid="status-card-topup-required"
                        style={{
                          background: '#fffbeb',
                          color: '#92400e',
                          border: '1px solid #fde68a',
                          borderRadius: 14,
                          padding: '18px 20px',
                          display: 'flex',
                          flexDirection: 'column',
                          gap: 14,
                          boxSizing: 'border-box'
                        }}
                      >
                        <div style={{ display: 'flex', alignItems: 'flex-start', gap: 12 }}>
                          <FiAlertCircle style={{ flexShrink: 0, fontSize: 22, color: '#d97706', marginTop: 2 }} />
                          <div style={{ flex: 1 }}>
                            <div style={{ fontSize: 16, fontWeight: 700, color: '#92400e', marginBottom: 4 }}>
                              {t('subj_topup_title', 'Subject Top-Up Required')}
                            </div>
                            <div style={{ fontSize: 14, lineHeight: 1.5, color: '#78350f' }}>
                              {(() => {
                                const addedStr = (pendingTopup.added_subjects || []).join(', ');
                                const template = t('subj_topup_desc', 'You added subjects ({subjects}). This month ({month}) was already paid for Rs {paid}. Top-up amount for the remainder of this month: Rs {diff}.');
                                return template
                                  .replace('{subjects}', addedStr)
                                  .replace('{month}', curMonth)
                                  .replace('{paid}', formatAmount(paidAmount))
                                  .replace('{diff}', formatAmount(topupDiff));
                              })()}
                            </div>
                            <div style={{ fontSize: 13, fontWeight: 600, color: '#b45309', marginTop: 8, display: 'flex', alignItems: 'center', gap: 6 }}>
                              <FiLock size={14} />
                              <span>{t('subj_topup_locked_notice', 'Added subjects remain locked until this top-up is paid.')}</span>
                            </div>
                          </div>
                        </div>

                        {/* Top-up Action Buttons */}
                        <div style={{ display: 'flex', flexDirection: 'column', gap: 10, marginTop: 4 }}>
                          <button
                            type="button"
                            onClick={handlePayTopupOnline}
                            disabled={loading || paymentDone}
                            data-testid="btn-topup-pay-online"
                            style={{
                              width: '100%',
                              padding: '12px 18px',
                              borderRadius: 10,
                              border: 'none',
                              background: '#d97706',
                              color: '#fff',
                              fontSize: 14,
                              fontWeight: 600,
                              cursor: (loading || paymentDone) ? 'not-allowed' : 'pointer',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              gap: 8,
                              opacity: (loading || paymentDone) ? 0.6 : 1,
                              minHeight: 44
                            }}
                          >
                            <FiCheck size={16} />
                            <span>
                              {t('subj_topup_pay_online', 'Pay Online (Rs {diff})').replace('{diff}', formatAmount(topupDiff))}
                            </span>
                          </button>

                          <button
                            type="button"
                            onClick={handlePayTopupOffline}
                            disabled={loading || paymentDone || topupOfflineRequested || pendingTopup.payment_method === 'offline'}
                            data-testid="btn-topup-pay-offline"
                            style={{
                              width: '100%',
                              padding: '12px 18px',
                              borderRadius: 10,
                              border: '1px solid #d97706',
                              background: '#fff',
                              color: '#92400e',
                              fontSize: 14,
                              fontWeight: 600,
                              cursor: (loading || paymentDone || topupOfflineRequested || pendingTopup.payment_method === 'offline') ? 'not-allowed' : 'pointer',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              gap: 8,
                              opacity: (topupOfflineRequested || pendingTopup.payment_method === 'offline') ? 0.7 : 1,
                              minHeight: 44
                            }}
                          >
                            <FiClock size={16} />
                            <span>
                              {(topupOfflineRequested || pendingTopup.payment_method === 'offline')
                                ? 'Offline payment requested (awaiting admin approval)'
                                : t('subj_topup_pay_offline', 'Pay Offline (Bank/Cash)')}
                            </span>
                          </button>

                          <button
                            type="button"
                            onClick={handleCancelTopup}
                            disabled={loading || paymentDone}
                            data-testid="btn-topup-cancel"
                            style={{
                              width: '100%',
                              padding: '10px 16px',
                              borderRadius: 10,
                              border: '1px solid #e5e7eb',
                              background: '#fff',
                              color: '#4b5563',
                              fontSize: 13,
                              fontWeight: 500,
                              cursor: (loading || paymentDone) ? 'not-allowed' : 'pointer',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              gap: 6,
                              minHeight: 38
                            }}
                          >
                            <FiX size={15} />
                            <span>{t('subj_topup_cancel', 'Remove added subjects / Cancel top-up')}</span>
                          </button>
                        </div>
                      </div>
                    ) : (
                      <div
                        className="status-card-already-paid"
                        data-testid="status-card-already-paid"
                        style={{
                          background: '#ecfdf5',
                          color: '#065f46',
                          border: '1px solid #a7f3d0',
                          borderRadius: 12,
                          padding: '16px 18px',
                          fontSize: 14,
                          fontWeight: 500,
                          display: 'flex',
                          alignItems: 'flex-start',
                          gap: 12,
                          lineHeight: 1.5,
                          boxSizing: 'border-box'
                        }}
                      >
                        <FiCheckCircle style={{ flexShrink: 0, fontSize: 20, color: '#059669', marginTop: 2 }} />
                        <div style={{ flex: 1 }}>
                          {(() => {
                            const fee = serverMonthly ?? monthlyAmount;
                            if (paidAmount !== null && fee < paidAmount) {
                              return formatPaidFeeLowerMessage(curMonth, nextMonth, fee, language, t);
                            }
                            if (paidAmount === null || fee <= paidAmount) {
                              return formatPaidFeeStaysMessage(curMonth, fee, language, t);
                            }
                            return formatAlreadyPaidMessage(curMonth, nextMonth, fee, language, t);
                          })()}
                        </div>
                      </div>
                    )}

                    {/* Advance months stay available even when this month is already paid */}
                    <AdvanceMonthsBlock
                      monthlyFee={serverMonthly ?? monthlyAmount}
                      disabled={loading || paymentDone}
                      loading={loading}
                      onSubmit={handlePayAdvanceOffline}
                      t={t}
                    />

                    <button
                      onClick={handleClose}
                      data-testid="modal-already-paid-done-btn"
                      style={{
                        width: '100%',
                        padding: '14px 20px',
                        borderRadius: '12px',
                        border: 'none',
                        background: 'var(--t-primary, #4f0bd9)',
                        color: '#fff',
                        fontSize: '15px',
                        fontWeight: 600,
                        cursor: 'pointer',
                        minHeight: 48,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        boxSizing: 'border-box'
                      }}
                    >
                      Done
                    </button>
                  </div>
                ) : (
                  /* State 2: Current month is NOT paid */
                  <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
                    {existingPayment && existingPayment.status === 'pending' && existingPayment.payment_method === 'offline' && (
                      <div
                        className="status-card-pending"
                        data-testid="status-card-pending"
                        style={{
                          background: '#fffbeb',
                          color: '#b45309',
                          border: '1px solid #fde68a',
                          borderRadius: 12,
                          padding: '14px 16px',
                          fontSize: 14,
                          fontWeight: 600,
                          display: 'flex',
                          alignItems: 'center',
                          gap: 8,
                          textAlign: 'left'
                        }}
                      >
                        <FiClock style={{ flexShrink: 0, fontSize: 18 }} />
                        <span>Request received on {formatDate(existingPayment.created_at)}, waiting for admin confirmation – Rs {formatAmount(existingPayment.amount || serverMonthly || monthlyAmount)}</span>
                      </div>
                    )}

                    {existingPayment && existingPayment.status === 'cancelled' && (
                      <div
                        className="status-card-cancelled"
                        data-testid="status-card-cancelled"
                        style={{
                          background: '#fef2f2',
                          color: '#b91c1c',
                          border: '1px solid #fecaca',
                          borderRadius: 12,
                          padding: '14px 16px',
                          fontSize: 14,
                          fontWeight: 600,
                          display: 'flex',
                          alignItems: 'center',
                          gap: 8,
                          textAlign: 'left'
                        }}
                      >
                        <FiAlertCircle style={{ flexShrink: 0, fontSize: 18 }} />
                        <span>Request cancelled – please contact the institute or submit again</span>
                      </div>
                    )}

                    {(!existingPayment || existingPayment.status !== 'pending' || existingPayment.payment_method !== 'offline') ? (
                      <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
                        {/* Option 1: Pay online */}
                        <div style={{
                          border: '1px solid var(--t-border, #e2e8f0)',
                          borderRadius: 14,
                          padding: 14,
                          background: '#fff',
                          display: 'flex',
                          flexDirection: 'column',
                          gap: 6
                        }}>
                          <button 
                            onClick={handlePayOnline} 
                            disabled={loading || paymentDone}
                            data-testid="pay-online-btn"
                            style={{
                              width: '100%',
                              padding: '12px 20px', borderRadius: '12px', border: 'none',
                              background: 'var(--t-primary)', color: '#fff', fontSize: '15px', fontWeight: 600,
                              cursor: loading ? 'not-allowed' : 'pointer',
                              transition: 'all 0.2s', textAlign: 'center', minHeight: 46,
                              display: 'flex', alignItems: 'center', justifyContent: 'center'
                            }}
                          >
                            {loading ? 'Processing...' : 'PAY ONLINE'}
                          </button>
                          <div style={{ fontSize: 13, color: 'var(--t-text-3, #64748b)', paddingLeft: 4, lineHeight: 1.4 }}>
                            {t('subj_update_pay_online_desc', 'Pay online – receipt is sent immediately')}
                          </div>
                        </div>

                        {/* Option 2: Pay offline */}
                        <div style={{
                          border: '1px solid var(--t-border, #e2e8f0)',
                          borderRadius: 14,
                          padding: 14,
                          background: '#fff',
                          display: 'flex',
                          flexDirection: 'column',
                          gap: 6
                        }}>
                          <button 
                            onClick={handlePayOffline} 
                            disabled={loading || paymentDone}
                            data-testid="pay-offline-btn"
                            style={{
                              width: '100%',
                              padding: '12px 20px', borderRadius: '12px',
                              background: '#fff', color: 'var(--t-danger)', fontSize: '15px', fontWeight: 600,
                              cursor: (loading || paymentDone) ? 'not-allowed' : 'pointer', border: '2px solid var(--t-danger)',
                              transition: 'all 0.2s', textAlign: 'center', minHeight: 46,
                              display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                              touchAction: 'manipulation'
                            }}
                          >
                            {loading ? <><FiLoader className="spin-icon" /> Submitting...</> : 'I WILL PAY OFFLINE'}
                          </button>
                          <div style={{ fontSize: 13, color: 'var(--t-text-3, #64748b)', paddingLeft: 4, lineHeight: 1.4 }}>
                            {t('subj_update_pay_offline_desc', 'Pay offline – the admin marks it paid, then you receive the receipt and your access opens')}
                          </div>
                        </div>

                        {/* Option 3: Advance months */}
                        <AdvanceMonthsBlock
                          monthlyFee={serverMonthly ?? monthlyAmount}
                          disabled={loading || paymentDone}
                          loading={loading}
                          onSubmit={handlePayAdvanceOffline}
                          t={t}
                        />
                      </div>
                    ) : (
                      <button onClick={handleClose} style={{
                        padding: '14px 20px', borderRadius: '12px', border: 'none',
                        background: 'var(--t-primary)', color: '#fff', fontSize: '15px', fontWeight: 600, cursor: 'pointer'
                      }}>Done</button>
                    )}
                  </div>
                )}
              </div>
            );
          })()}
        </div>

        <div style={{ padding: '20px', borderTop: '1px solid var(--t-border)', background: 'var(--t-bg)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          {step === 2 && !alreadyPaid ? (
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
