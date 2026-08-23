import React, { useState, useMemo } from 'react'
import { registerStep1, registerStep2, registerPaymentSuccess } from '../services/authService'
import { useLocation } from 'react-router-dom'
import { FiX } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './StudentRegistrationForm.css'

// Subject data structures
// (Removed hardcoded arrays — subjects are now fetched from backend API grouped by category)

const MONTHLY_AMOUNT = 500
const gradeLevels = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]

const StudentRegistrationForm = ({ isOpen = true, onClose }) => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()
  const location = useLocation()
  
  // Read step from URL
  const initialStep = React.useMemo(() => {
    const searchParams = new window.URLSearchParams(location.search)
    const stepParam = searchParams.get('step')
    return stepParam ? parseInt(stepParam, 10) : 1
  }, [location.search])

  const [step, setStep] = useState(initialStep)
  const [subjectsByCategory, setSubjectsByCategory] = useState({})

  // Fetch subjects grouped by category from backend
  const [packages, setPackages] = useState([])
  React.useEffect(() => {
    const fetchData = async () => {
      try {
        const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'
        const [subRes, pkgRes] = await Promise.all([
          fetch(`${API_BASE_URL}/subjects/prices`),
          fetch(`${API_BASE_URL}/packages`)
        ])
        
        if (subRes.ok) {
          const data = await subRes.json()
          setSubjectsByCategory(data)
        }
        if (pkgRes.ok) {
          const pkgData = await pkgRes.json()
          setPackages(pkgData)
        }
      } catch (err) {
        console.error('Failed to fetch data:', err)
      }
    }
    fetchData()
  }, [])

  // Auto-initialize email from currently authenticated user
  React.useEffect(() => {
    const userStr = localStorage.getItem('user') || sessionStorage.getItem('user')
    if (userStr) {
      try {
        const u = JSON.parse(userStr)
        if (u && u.email) {
          setFormData(prev => ({ ...prev, email: u.email }))
        }
        
        // Also initialize selected subjects to accurately calculate the amount in step 2
        if (u && u.selected_subjects) {
          let subs = u.selected_subjects
          if (typeof subs === 'string') {
            try { subs = JSON.parse(subs) } catch(e) { subs = subs.split(',').map(s => s.trim()) }
          }
          if (Array.isArray(subs)) {
             // In setFormData, also need to know the stream/grade to figure out availableSubjects
             if (u.current_grade) {
               setFormData(prev => ({ ...prev, currentGrade: u.current_grade }))
             }
             if (u.stream) {
               setSelectedStream(u.stream)
             }
             
             // Wait for state updates, we can just set selected subjects directly 
             // but they need to match the API data format.
             // Usually it's just an array of names.
             setSelectedSubjects(subs)
          }
        }
      } catch (e) {
        console.error('Failed to parse user for initialization:', e)
      }
    }
  }, [])

  const [formData, setFormData] = useState({
    fullName: '',
    dateOfBirth: '',
    gender: '',
    schoolName: '',
    medium: '',
    onlineExperience: '',
    deviceUsed: '',
    currentGrade: '',
    username: '',
    phoneNumber: '',
    email: ''
  })
  const [regTitle, setRegTitle] = useState('')
  const [regSubtitle, setRegSubtitle] = useState('')
  const [labelFullname, setLabelFullname] = useState('')
  const [labelPhone, setLabelPhone] = useState('')
  const [labelDob, setLabelDob] = useState('')
  const [labelGender, setLabelGender] = useState('')
  const [labelSchool, setLabelSchool] = useState('')
  const [labelMedium, setLabelMedium] = useState('')
  const [labelExperience, setLabelExperience] = useState('')
  const [labelDevice, setLabelDevice] = useState('')
  const [labelGrade, setLabelGrade] = useState('')
  const [labelStream, setLabelStream] = useState('')
  const [customFieldLabels, setCustomFieldLabels] = useState([])
  const [btnNext, setBtnNext] = useState('')

  React.useEffect(() => {
    const defaultEn = {
      reg_title: 'Student Details',
      reg_subtitle: 'Please fill in all the required information',
      reg_fullname: 'Full Name',
      reg_phone: 'Phone Number (WhatsApp)',
      reg_dob: 'Date of Birth',
      reg_gender: 'Gender',
      reg_school: 'School Name',
      reg_medium: 'Medium of Learning',
      reg_experience: 'Do you have online class experience?',
      reg_device: 'Device Used for Online Classes',
      reg_grade: 'Current Grade (2026)',
      reg_stream: 'Stream / Section',
      reg_next_payment: 'Next: Payment'
    }

    if (language !== 'en') {
      const translateForm = async () => {
        const valTitle = getSetting('register_title', defaultEn.reg_title)
        if (valTitle === defaultEn.reg_title) setRegTitle(t('reg_title'))
        else setRegTitle(await translate(valTitle))

        const valSub = getSetting('register_subtitle', defaultEn.reg_subtitle)
        if (valSub === defaultEn.reg_subtitle) setRegSubtitle(t('reg_subtitle'))
        else setRegSubtitle(await translate(valSub))

        const valFullname = getSetting('register_fullname_label', defaultEn.reg_fullname)
        if (valFullname === defaultEn.reg_fullname) setLabelFullname(t('reg_fullname'))
        else setLabelFullname(await translate(valFullname))

        const valPhone = getSetting('register_phone_label', defaultEn.reg_phone)
        if (valPhone === defaultEn.reg_phone) setLabelPhone(t('reg_phone'))
        else setLabelPhone(await translate(valPhone))

        const valDob = getSetting('register_dob_label', defaultEn.reg_dob)
        if (valDob === defaultEn.reg_dob) setLabelDob(t('reg_dob'))
        else setLabelDob(await translate(valDob))

        const valGender = getSetting('register_gender_label', defaultEn.reg_gender)
        if (valGender === defaultEn.reg_gender) setLabelGender(t('reg_gender'))
        else setLabelGender(await translate(valGender))

        const valSchool = getSetting('register_school_label', defaultEn.reg_school)
        if (valSchool === defaultEn.reg_school) setLabelSchool(t('reg_school'))
        else setLabelSchool(await translate(valSchool))

        const valMedium = getSetting('register_medium_label', defaultEn.reg_medium)
        if (valMedium === defaultEn.reg_medium) setLabelMedium(t('reg_medium'))
        else setLabelMedium(await translate(valMedium))

        const valExp = getSetting('register_experience_label', defaultEn.reg_experience)
        if (valExp === defaultEn.reg_experience) setLabelExperience(t('reg_experience'))
        else setLabelExperience(await translate(valExp))

        const valDevice = getSetting('register_device_label', defaultEn.reg_device)
        if (valDevice === defaultEn.reg_device) setLabelDevice(t('reg_device'))
        else setLabelDevice(await translate(valDevice))

        const valGrade = getSetting('register_grade_label', defaultEn.reg_grade)
        if (valGrade === defaultEn.reg_grade) setLabelGrade(t('reg_grade'))
        else setLabelGrade(await translate(valGrade))

        const valStream = getSetting('register_stream_label', defaultEn.reg_stream)
        if (valStream === defaultEn.reg_stream) setLabelStream(t('reg_stream'))
        else setLabelStream(await translate(valStream))

        const valNext = getSetting('register_next_btn', defaultEn.reg_next_payment)
        if (valNext === defaultEn.reg_next_payment) setBtnNext(t('reg_next_payment'))
        else setBtnNext(await translate(valNext))
      }
      translateForm()
    } else {
      setRegTitle(getSetting('register_title', t('reg_title')))
      setRegSubtitle(getSetting('register_subtitle', t('reg_subtitle')))
      setLabelFullname(getSetting('register_fullname_label', t('reg_fullname')))
      setLabelPhone(getSetting('register_phone_label', t('reg_phone')))
      setLabelDob(getSetting('register_dob_label', t('reg_dob')))
      setLabelGender(getSetting('register_gender_label', t('reg_gender')))
      setLabelSchool(getSetting('register_school_label', t('reg_school')))
      setLabelMedium(getSetting('register_medium_label', t('reg_medium')))
      setLabelExperience(getSetting('register_experience_label', t('reg_experience')))
      setLabelDevice(getSetting('register_device_label', t('reg_device')))
      setLabelGrade(getSetting('register_grade_label', t('reg_grade')))
      setLabelStream(getSetting('register_stream_label', t('reg_stream')))

      const customVal = getSetting('register_custom_fields', '[]')
      try {
        setCustomFieldLabels(typeof customVal === 'string' ? JSON.parse(customVal) : (Array.isArray(customVal) ? customVal : []))
      } catch (e) {
        setCustomFieldLabels([])
      }

      setBtnNext(getSetting('register_next_btn', t('reg_next_payment')))
    }
  }, [language, getSetting, t, translate])
  const [selectedStream, setSelectedStream] = useState('')
  const [selectedSubjects, setSelectedSubjects] = useState([])
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')
  const [paymentChoice, setPaymentChoice] = useState(null)
  const [cardData, setCardData] = useState({ number: '', holder: '', expiry: '', cvv: '' })
  const [isFlipped, setIsFlipped] = useState(false)

  // Helper function to extract grade number from any format (தரம் X, Grade X, or just X)
  const getGradeNumber = (gradeValue) => {
    if (!gradeValue) return null
    if (typeof gradeValue === 'number') return gradeValue
    const match = gradeValue.toString().match(/(\d+)/)
    return match ? parseInt(match[1], 10) : null
  }

  // Get available subjects based on grade and stream (from API data)
  const getAvailableSubjects = () => {
    const gradeNum = getGradeNumber(formData.currentGrade)
    if (!gradeNum) return []

    let subs = []
    if (gradeNum >= 1 && gradeNum <= 2) {
      subs = subjectsByCategory['grade_1_to_2'] || []
    } else if (gradeNum === 3) {
      subs = subjectsByCategory['grade_3'] || []
    } else if (gradeNum === 4) {
      subs = subjectsByCategory['grade_4'] || []
    } else if (gradeNum === 5) {
      subs = subjectsByCategory['grade_5'] || []
    } else if (gradeNum >= 6 && gradeNum <= 9) {
      subs = subjectsByCategory['grade_6_to_9'] || []
    } else if (gradeNum >= 10 && gradeNum <= 11) {
      subs = subjectsByCategory['grade_10_to_11'] || []
    } else if (gradeNum >= 12 && gradeNum <= 13) {
      subs = subjectsByCategory[selectedStream] || []
    }
    
    // Filter by selected medium if any
    if (formData.medium) {
      subs = subs.filter(s => s.medium === formData.medium || s.medium === 'both')
    }

    // Ensure unique subjects by name
    const uniqueSubs = []
    const seen = new Set()
    for (const s of subs) {
      if (!seen.has(s.name)) {
        seen.add(s.name)
        uniqueSubs.push(s)
      }
    }
    
    return uniqueSubs
  }

  const availableSubjects = useMemo(() => getAvailableSubjects(), [formData.currentGrade, formData.medium, selectedStream, subjectsByCategory])

  // Retrieve admission fees config
  const admissionFeesConfigStr = getSetting('admission_fees_config', '{}')
  const admissionFeesConfig = useMemo(() => {
    try {
      return JSON.parse(admissionFeesConfigStr)
    } catch (e) {
      return {}
    }
  }, [admissionFeesConfigStr])

  // Calculate total amount based on selected subjects
  const { monthlyAmount, admissionFee, totalAmount, appliedPackage, originalAmount } = useMemo(() => {
    if (selectedSubjects.length === 0) return { monthlyAmount: 0, admissionFee: 0, totalAmount: 0, appliedPackage: null, originalAmount: 0 }
    
    const gradeNum = getGradeNumber(formData.currentGrade)
    
    // Original Monthly Fee Calculation (without package)
    const originalMonthly = selectedSubjects.reduce((sum, subjectName) => {
      const subjectObj = availableSubjects.find(s => s.name === subjectName)
      return sum + (subjectObj ? parseFloat(subjectObj.price) : 0)
    }, 0)
    
    let mAmount = originalMonthly
    let appliedPkg = null

    // Find applicable packages for this grade and medium
    if (gradeNum && packages && packages.length > 0) {
      const applicablePkgs = packages.filter(p => {
        let grades = p.applicable_grades
        if (typeof grades === 'string') {
          try { grades = JSON.parse(grades) } catch(e) { grades = [] }
        }
        const hasGrade = Array.isArray(grades) && grades.some(g => parseInt(g, 10) === gradeNum)
        const hasMedium = p.medium === 'both' || p.medium === formData.medium
        return hasGrade && hasMedium
      })
      
      const allSubjPkg = applicablePkgs.find(p => p.type === 'all_subjects')
      if (allSubjPkg && availableSubjects.length > 0 && selectedSubjects.length === availableSubjects.length) {
         mAmount = parseFloat(allSubjPkg.package_price)
         appliedPkg = allSubjPkg
      } else {
         const mainSubjPkg = applicablePkgs.find(p => p.type === 'main_subjects')
         // Apply main_subjects package when student selects 2+ subjects
         // and their total would exceed the package price
         if (mainSubjPkg && selectedSubjects.length >= 2) {
            const pkgBasePrice = parseFloat(mainSubjPkg.package_price)
            let price = pkgBasePrice
            // If addon_price exists and they selected more than base subjects count,
            // calculate: how many subjects fit in the base package price?
            // Extra subjects beyond base count get addon pricing
            if (mainSubjPkg.addon_price && parseFloat(mainSubjPkg.addon_price) > 0) {
               const addonPrice = parseFloat(mainSubjPkg.addon_price)
               // Determine base subject count from the package
               // Base count = how many subjects the package covers at its base price
               // We estimate base count by: subjects whose individual sum first exceeds package price
               const baseCount = Math.max(2, Math.floor(pkgBasePrice / (originalMonthly / selectedSubjects.length)))
               if (selectedSubjects.length > baseCount) {
                  const extraCount = selectedSubjects.length - baseCount
                  price += extraCount * addonPrice
               }
            }
            // Only apply if it actually saves money
            if (price < originalMonthly) {
               mAmount = price
               appliedPkg = mainSubjPkg
            }
         }
      }
    }

    let admFee = 0
    if (gradeNum && admissionFeesConfig[gradeNum] && admissionFeesConfig[gradeNum].enabled) {
      admFee = parseFloat(admissionFeesConfig[gradeNum].amount) || 0
    }

    return { 
      monthlyAmount: mAmount, 
      admissionFee: admFee, 
      totalAmount: mAmount + admFee,
      appliedPackage: appliedPkg,
      originalAmount: originalMonthly
    }
  }, [selectedSubjects, availableSubjects, formData.currentGrade, formData.medium, admissionFeesConfig, packages])

  // Dynamic Fee Info Message based on available subjects and packages
  const dynamicFeeMessage = useMemo(() => {
    if (availableSubjects.length === 0) return null;

    const gradeNum = getGradeNumber(formData.currentGrade);
    
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
        const hasMedium = p.medium === 'both' || p.medium === formData.medium;
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
      <>
        <div style={{ marginBottom: '6px' }}>{msgEn}</div>
        <div>{msgTa}</div>
      </>
    );
  }, [availableSubjects, formData.currentGrade, formData.medium, packages]);

  const handleChange = (e) => {
    const { name, value } = e.target

    // If currentGrade changes, reset stream and subjects
    if (name === 'currentGrade') {
      setSelectedStream('')
      setSelectedSubjects([])
    }

    setFormData(prev => ({
      ...prev,
      [name]: value
    }))
    setError('')
  }

  const handleStreamChange = (e) => {
    setSelectedStream(e.target.value)
    setSelectedSubjects([]) // Reset subjects when stream changes
    setError('')
  }

  const handleSubjectToggle = (subject) => {
    setSelectedSubjects(prev => {
      if (prev.includes(subject)) {
        return prev.filter(s => s !== subject)
      } else {
        return [...prev, subject]
      }
    })
    setError('')
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')

    // Validation
    if (!formData.fullName || formData.fullName.length < 2) {
      setError('Full name must be at least 2 characters')
      return
    }

    if (!formData.dateOfBirth) {
      setError('Date of birth is required')
      return
    }

    if (!formData.gender) {
      setError('Gender is required')
      return
    }

    if (!formData.schoolName) {
      setError('School name is required')
      return
    }

    if (!formData.medium) {
      setError('Medium of learning is required')
      return
    }

    if (!formData.onlineExperience) {
      setError('Please specify if you have online class experience')
      return
    }

    if (!formData.deviceUsed) {
      setError('Please select a device')
      return
    }

    if (!formData.currentGrade) {
      setError('Current grade is required')
      return
    }

    const phoneDigits = formData.phoneNumber.trim().replace(/\D/g, '')
    if (!formData.phoneNumber || phoneDigits.length < 9 || phoneDigits.length > 15) {
      setError('Phone number must be valid (9-15 digits) / தொலைபேசி எண் சரியாக இருக்க வேண்டும் (9 இலக்கங்கள்)')
      return
    }

    const gradeNum = getGradeNumber(formData.currentGrade)

    // For grades 12-13, stream is required
    if (gradeNum && gradeNum >= 12 && gradeNum <= 13) {
      if (!selectedStream) {
        setError('Please select a stream (A/L – ARTS or A/L – BIO & MATHS)')
        return
      }
    }

    // At least one subject must be selected
    if (selectedSubjects.length === 0) {
      setError('Please select at least one subject')
      return
    }

    // Generate username if not provided (use full name or default)
    const username = formData.username || formData.fullName.toLowerCase().replace(/\s+/g, '') || 'student'

    setIsLoading(true)
    setError('')

    try {
      const userData = {
        username: formData.email || username, // Use email as priority for username
        full_name: formData.fullName,
        phone_number: formData.phoneNumber.trim().replace(/\D/g, ''),
        date_of_birth: formData.dateOfBirth,
        gender: formData.gender,
        school_name: formData.schoolName,
        medium: formData.medium,
        online_experience: formData.onlineExperience === 'yes',
        device_used: formData.deviceUsed,
        current_grade: formData.currentGrade,
        stream: gradeNum && gradeNum >= 12 && gradeNum <= 13 ? selectedStream : null,
        selected_subjects: JSON.stringify(selectedSubjects),
        ...Object.fromEntries(
          customFieldLabels.map(label => [label.toLowerCase().replace(/\s+/g, '_'), formData[label.toLowerCase().replace(/\s+/g, '_')] || ''])
        )
      }

      await registerStep1(userData)
      setIsLoading(false)
      setStep(2)
      setError('')
    } catch (err) {
      setIsLoading(false)
      setError(err.message || 'Registration failed. Please try again.')
      console.error('Registration error:', err)
    }
  }

  const handlePaymentOffline = async (amount) => {
    setError('')
    setIsLoading(true)
    try {
      await registerStep2('offline', amount)
      alert(t('pay_offline_success'))
      if (onClose) onClose()
      window.location.href = '/student/dashboard'
    } catch (err) {
      setIsLoading(false)
      setError(err.message || 'Failed.')
    }
  }

  const handleCardInput = (e) => {
    const { name, value } = e.target
    if (name === 'number') {
      const cleaned = value.replace(/\D/g, '').slice(0, 16)
      const formatted = cleaned.replace(/(.{4})/g, '$1 ').trim()
      setCardData(prev => ({ ...prev, number: formatted }))
    } else if (name === 'expiry') {
      const cleaned = value.replace(/\D/g, '').slice(0, 4)
      const formatted = cleaned.length > 2 ? cleaned.slice(0, 2) + '/' + cleaned.slice(2) : cleaned
      setCardData(prev => ({ ...prev, expiry: formatted }))
    } else if (name === 'cvv') {
      setCardData(prev => ({ ...prev, cvv: value.replace(/\D/g, '').slice(0, 3) }))
    } else {
      setCardData(prev => ({ ...prev, [name]: value }))
    }
  }

  const handlePaymentOnline = async (amount) => {
    setError('')
    setIsLoading(true)
    try {
      const resp = await registerStep2('online', amount)
      setIsLoading(false)

      if (!window.payhere) {
        throw new Error('PayHere SDK not loaded. Please check your internet connection.')
      }

      const payment = {
        sandbox: resp.payhere_url.includes('sandbox'),
        ...resp.params
      }

      window.payhere.onCompleted = async function onCompleted(orderId) {
        console.log("Payment completed. OrderID:" + orderId)
        try {
          await registerPaymentSuccess(resp.params.order_id, orderId)
          alert(t('pay_online_success'))
          if (onClose) onClose()
          window.location.href = '/student/dashboard'
        } catch (err) {
          console.error('Failed to notify backend of payment success:', err)
          alert('Payment succeeded but we couldn\'t update your status. Please contact support or login to check.')
          if (onClose) onClose()
          window.location.href = '/student/dashboard'
        }
      }

      window.payhere.onDismissed = function onDismissed() {
        console.log("Payment dismissed")
      }

      window.payhere.onError = function onError(error) {
        console.log("PayHere Error:" + error)
        setError("Payment Error: " + error)
      }

      window.payhere.startPayment(payment)
    } catch (err) {
      setIsLoading(false)
      setError(err.message || 'Failed to initialize payment.')
      console.error('PayHere Init Error:', err)
    }
  }

  if (!isOpen) return null

  return (
    <div className="tit-reg-overlay">
      <div className={`tit-reg-wrapper ${step === 3 ? 'tit-reg-step-payment-active' : ''}`}>
        {onClose && (
          <button className="tit-reg-close" onClick={onClose}>
            <FiX />
          </button>
        )}

        {step === 1 && (
          <div className="tit-reg-container">
            <h2 className="tit-reg-title">{regTitle}</h2>
            <p className="tit-reg-subtitle">{regSubtitle}</p>

            {error && <div className="tit-reg-error">{error}</div>}

            <form onSubmit={handleSubmit} className="tit-reg-form">
              {/* Full Name */}
              {labelFullname && (
                <div className="tit-reg-group">
                  <label htmlFor="fullName" className="tit-reg-label">{labelFullname} <span className="tit-reg-required">*</span></label>
                  <input
                    type="text"
                    id="fullName"
                    name="fullName"
                    className="tit-reg-input"
                    value={formData.fullName}
                    onChange={handleChange}
                    required
                    placeholder={t('reg_fullname_placeholder')}
                  />
                </div>
              )}

              {/* Phone (for WhatsApp) */}
              {labelPhone && (
                <div className="tit-reg-group">
                  <label htmlFor="phoneNumber" className="tit-reg-label">{labelPhone} <span className="tit-reg-required">*</span></label>
                  <input
                    type="tel"
                    id="phoneNumber"
                    name="phoneNumber"
                    className="tit-reg-input"
                    value={formData.phoneNumber}
                    onChange={handleChange}
                    required
                    placeholder="e.g. 07XXXXXXXX"
                  />
                </div>
              )}


              {/* Date of Birth */}
              {labelDob && (
                <div className="tit-reg-group">
                  <label htmlFor="dateOfBirth" className="tit-reg-label">{labelDob} <span className="tit-reg-required">*</span></label>
                  <input
                    type="date"
                    id="dateOfBirth"
                    name="dateOfBirth"
                    className="tit-reg-input"
                    value={formData.dateOfBirth}
                    onChange={handleChange}
                    required
                    max={new Date().toISOString().split('T')[0]}
                  />
                </div>
              )}

              {/* Gender */}
              {labelGender && (
                <div className="tit-reg-group">
                  <label htmlFor="gender" className="tit-reg-label">{labelGender} <span className="tit-reg-required">*</span></label>
                  <select
                    id="gender"
                    name="gender"
                    className="tit-reg-select"
                    value={formData.gender}
                    onChange={handleChange}
                    required
                  >
                    <option value="">{t('reg_gender_select')}</option>
                    <option value="male">{t('reg_male')}</option>
                    <option value="female">{t('reg_female')}</option>
                  </select>
                </div>
              )}


              {/* School Name */}
              {labelSchool && (
                <div className="tit-reg-group">
                  <label htmlFor="schoolName" className="tit-reg-label">{labelSchool} <span className="tit-reg-required">*</span></label>
                  <input
                    type="text"
                    id="schoolName"
                    name="schoolName"
                    className="tit-reg-input"
                    value={formData.schoolName}
                    onChange={handleChange}
                    required
                    placeholder={t('reg_school_placeholder')}
                  />
                </div>
              )}

              {/* Medium of Learning */}
              {labelMedium && (
                <div className="tit-reg-group">
                  <label htmlFor="medium" className="tit-reg-label">{labelMedium} <span className="tit-reg-required">*</span></label>
                  <select
                    id="medium"
                    name="medium"
                    className="tit-reg-select"
                    value={formData.medium}
                    onChange={handleChange}
                    required
                  >
                    <option value="">{t('reg_medium_select')}</option>
                    <option value="tamil">{t('reg_medium_tamil')}</option>
                    <option value="english">{t('reg_medium_english')}</option>
                  </select>
                </div>
              )}

              {/* Online Class Experience */}
              {labelExperience && (
                <div className="tit-reg-group">
                  <label htmlFor="onlineExperience" className="tit-reg-label">{labelExperience} <span className="tit-reg-required">*</span></label>
                  <select
                    id="onlineExperience"
                    name="onlineExperience"
                    className="tit-reg-select"
                    value={formData.onlineExperience}
                    onChange={handleChange}
                    required
                  >
                    <option value="">{t('reg_experience_select')}</option>
                    <option value="yes">{t('reg_exp_yes')}</option>
                    <option value="no">{t('reg_exp_no')}</option>
                  </select>
                </div>
              )}

              {/* Device Used */}
              {labelDevice && (
                <div className="tit-reg-group">
                  <label htmlFor="deviceUsed" className="tit-reg-label">{labelDevice} <span className="tit-reg-required">*</span></label>
                  <select
                    id="deviceUsed"
                    name="deviceUsed"
                    className="tit-reg-select"
                    value={formData.deviceUsed}
                    onChange={handleChange}
                    required
                  >
                    <option value="">{t('reg_device_select')}</option>
                    <option value="Mobile">{t('reg_device_mobile')}</option>
                    <option value="Tablet">{t('reg_device_tablet')}</option>
                    <option value="Laptop">{t('reg_device_laptop')}</option>
                    <option value="Desktop">{t('reg_device_desktop')}</option>
                  </select>
                </div>
              )}

              {/* Current Grade (2026) */}
              {labelGrade && (
                <div className="tit-reg-group">
                  <label htmlFor="currentGrade" className="tit-reg-label">{labelGrade} <span className="tit-reg-required">*</span></label>
                  <select
                    id="currentGrade"
                    name="currentGrade"
                    className="tit-reg-select"
                    value={formData.currentGrade}
                    onChange={handleChange}
                    required
                  >
                    <option value="">{t('reg_grade_select')}</option>
                    {gradeLevels.map((grade) => (
                      <option key={grade} value={grade}>
                        {language === 'ta' ? `தரம் ${grade}` : (language === 'si' ? `ශ්‍රේණිය ${grade}` : `Grade ${grade}`)}
                      </option>
                    ))}
                  </select>
                </div>
              )}

              {/* Stream Selection for Grades 12-13 */}
              {(() => {
                const gradeNum = getGradeNumber(formData.currentGrade)
                if (gradeNum && gradeNum >= 12 && gradeNum <= 13 && labelStream) {
                  return (
                    <div className="tit-reg-group">
                      <label htmlFor="stream" className="tit-reg-label">{labelStream} <span className="tit-reg-required">*</span></label>
                      <select
                        id="stream"
                        name="stream"
                        className="tit-reg-select"
                        value={selectedStream}
                        onChange={handleStreamChange}
                        required
                      >
                        <option value="">{t('reg_stream_select')}</option>
                        <option value="commerce_stream">{t('reg_stream_commerce') || 'Commerce'}</option>
                        <option value="arts_stream">{t('reg_stream_art') || 'Arts'}</option>
                        <option value="bio_maths_stream">{t('reg_stream_science') || 'Bio & Maths'}</option>
                        <option value="tech_stream">{t('reg_stream_tech') || 'Technology'}</option>
                      </select>
                    </div>
                  )
                }
                return null
              })()}

              {/* Custom Dynamic Fields */}
              {customFieldLabels.map((customLabel, idx) => {
                const fieldName = customLabel.toLowerCase().replace(/\s+/g, '_')
                return (
                  <div className="tit-reg-group" key={idx}>
                    <label htmlFor={fieldName} className="tit-reg-label">{customLabel} <span className="tit-reg-required">*</span></label>
                    <input
                      type="text"
                      id={fieldName}
                      name={fieldName}
                      className="tit-reg-input"
                      value={formData[fieldName] || ''}
                      onChange={handleChange}
                      required
                      placeholder={`Enter ${customLabel}`}
                    />
                  </div>
                )
              })}

              {/* Subject Selection */}
              {availableSubjects.length > 0 && (
                <div className="tit-reg-group">
                  <label className="tit-reg-label">{t('reg_subjects')} <span className="tit-reg-required">*</span></label>
                  
                  {/* Dynamic Fee Info Message */}
                  <div style={{ backgroundColor: '#e0e7ff', color: '#3730a3', padding: '10px 15px', borderRadius: '6px', fontSize: '13px', marginBottom: '12px', borderLeft: '4px solid #4f46e5' }}>
                    <strong>{dynamicFeeMessage}</strong>
                  </div>
                  <div className="tit-reg-checkbox-group">
                    {availableSubjects.map((subjectObj) => (
                      <label
                        key={subjectObj.name}
                        className={`tit-reg-checkbox-label ${selectedSubjects.includes(subjectObj.name) ? 'tit-reg-checked' : ''}`}
                      >
                        <input
                          type="checkbox"
                          checked={selectedSubjects.includes(subjectObj.name)}
                          onChange={() => handleSubjectToggle(subjectObj.name)}
                        />
                        <span>
                          {language === 'ta' ? (subjectObj.name_ta || subjectObj.name) :
                            (language === 'si' ? (subjectObj.name_si || subjectObj.name) : subjectObj.name)}
                          {subjectObj.price ? ` (Rs. ${parseFloat(subjectObj.price).toFixed(0)})` : ''}
                        </span>
                      </label>
                    ))}
                  </div>
                </div>
              )}

              <button
                type="submit"
                className="tit-reg-submit-btn"
                disabled={isLoading}
              >
                {isLoading ? t('reg_submitting') : btnNext}
              </button>
            </form>
          </div>
        )}

        {step === 2 && (
          <div className="tit-reg-container">
            <h2 className="tit-reg-title">{t('pay_title')}</h2>
            <p className="tit-reg-subtitle">{t('pay_subtitle')}</p>
            {error && <div className="tit-reg-error">{error}</div>}
            <div className="tit-reg-payment-options">
              {totalAmount > 0 && (
                <div style={{ marginBottom: '1rem', background: 'rgba(235, 129, 83, 0.1)', padding: '1rem', borderRadius: '0.5rem', border: '1px solid rgba(235, 129, 83, 0.3)' }}>
                  
                  {appliedPackage ? (
                    <>
                      <div style={{ display: 'flex', justifyContent: 'space-between', color: '#64748b', marginBottom: '0.5rem' }}>
                        <span>Subjects Total:</span>
                        <span style={{ textDecoration: 'line-through' }}>Rs. {originalAmount.toFixed(2)}</span>
                      </div>
                      <div style={{ display: 'flex', justifyContent: 'space-between', color: '#16a34a', fontWeight: 'bold', marginBottom: '0.5rem' }}>
                        <span>Package Discount ({appliedPackage.name}):</span>
                        <span>Rs. {monthlyAmount.toFixed(2)}</span>
                      </div>
                    </>
                  ) : (
                    <div style={{ display: 'flex', justifyContent: 'space-between', color: '#64748b', marginBottom: '0.5rem' }}>
                      <span>Monthly Fee:</span>
                      <span>Rs. {monthlyAmount.toFixed(2)}</span>
                    </div>
                  )}

                  {admissionFee > 0 && (
                    <div style={{ display: 'flex', justifyContent: 'space-between', color: '#64748b', marginBottom: '0.5rem' }}>
                      <span>Admission Fee (First Time):</span>
                      <span>Rs. {admissionFee.toFixed(2)}</span>
                    </div>
                  )}

                  <hr style={{ borderColor: 'rgba(235, 129, 83, 0.3)', margin: '0.5rem 0' }} />
                  <div style={{ display: 'flex', justifyContent: 'space-between', color: '#4f46e5', fontWeight: 'bold', fontSize: '1.25rem' }}>
                    <span>Total Amount:</span>
                    <span>Rs. {totalAmount.toFixed(2)}</span>
                  </div>
                </div>
              )}
              {totalAmount === 0 && (
                <p style={{ fontSize: '1.25rem', fontWeight: 'bold', color: '#4f46e5', marginBottom: '1.25rem' }}>
                  {t('pay_total')}: Rs. {MONTHLY_AMOUNT} (Monthly)
                </p>
              )}
              <button type="button" className="tit-reg-submit-btn" onClick={() => handlePaymentOffline(totalAmount > 0 ? totalAmount : MONTHLY_AMOUNT)} disabled={isLoading}>
                {t('pay_offline')}
              </button>
              <button type="button" className="tit-reg-submit-btn tit-reg-secondary"
                onClick={() => handlePaymentOnline(totalAmount > 0 ? totalAmount : MONTHLY_AMOUNT)}
                disabled={isLoading}>
                {isLoading ? t('pay_processing') : t('pay_online')}
              </button>
            </div>
            <button type="button" className="tit-reg-back-link" onClick={() => { setStep(1); setError(''); }}>
              {t('pay_back')}
            </button>
          </div>
        )}
      </div>
    </div>
  )
}

export default StudentRegistrationForm
