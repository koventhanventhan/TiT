import React, { useState, useMemo } from 'react'
import { registerStep1, registerStep2, registerPaymentSuccess } from '../services/authService'
import { FiX } from 'react-icons/fi'
import './StudentRegistrationForm.css'

// Subject data structures
// (Removed hardcoded arrays — subjects are now fetched from backend API grouped by category)

const MONTHLY_AMOUNT = 500

const StudentRegistrationForm = ({ isOpen = true, onClose }) => {
  const [step, setStep] = useState(1)
  const [subjectsByCategory, setSubjectsByCategory] = useState({})

  // Fetch subjects grouped by category from backend
  React.useEffect(() => {
    const fetchSubjects = async () => {
      try {
        const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
        const response = await fetch(`${API_BASE_URL}/subjects/prices`)
        if (response.ok) {
          const data = await response.json()
          setSubjectsByCategory(data)
        }
      } catch (err) {
        console.error('Failed to fetch subjects:', err)
      }
    }
    fetchSubjects()
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
    phoneNumber: ''
  })
  const [selectedStream, setSelectedStream] = useState('')
  const [selectedSubjects, setSelectedSubjects] = useState([])
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')
  const [paymentChoice, setPaymentChoice] = useState(null)
  const [cardData, setCardData] = useState({ number: '', holder: '', expiry: '', cvv: '' })
  const [isFlipped, setIsFlipped] = useState(false)

  // Helper function to extract grade number from "தரம் X / Grade X" format
  const getGradeNumber = (gradeValue) => {
    if (!gradeValue) return null
    const match = gradeValue.match(/தரம்\s*(\d+)/)
    return match ? parseInt(match[1], 10) : null
  }

  // Get available subjects based on grade and stream (from API data)
  const getAvailableSubjects = () => {
    const gradeNum = getGradeNumber(formData.currentGrade)
    if (!gradeNum) return []

    if (gradeNum >= 1 && gradeNum <= 5) {
      return subjectsByCategory['grade_1_to_5'] || []
    } else if (gradeNum >= 6 && gradeNum <= 11) {
      return subjectsByCategory['grade_6_to_11'] || []
    } else if (gradeNum >= 12 && gradeNum <= 13) {
      if (selectedStream === 'arts') {
        return subjectsByCategory['arts_stream'] || []
      } else if (selectedStream === 'bio_maths') {
        return subjectsByCategory['bio_maths_stream'] || []
      }
      return []
    }
    return []
  }

  const availableSubjects = useMemo(() => getAvailableSubjects(), [formData.currentGrade, selectedStream, subjectsByCategory])

  // Calculate total amount based on selected subjects
  const totalAmount = useMemo(() => {
    if (selectedSubjects.length === 0) return 0
    return selectedSubjects.reduce((sum, subjectName) => {
      // Find the subject's price from the available subjects array
      const subjectObj = availableSubjects.find(s => s.name === subjectName)
      return sum + (subjectObj ? parseFloat(subjectObj.price) : 0)
    }, 0)
  }, [selectedSubjects, availableSubjects])

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
    if (!formData.phoneNumber || phoneDigits.length < 10 || phoneDigits.length > 15) {
      setError('Phone number must be 10-15 digits / தொலைபேசி எண் 10-15 இலக்கங்களாக இருக்க வேண்டும்')
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
        username: username,
        full_name: formData.fullName,
        phone_number: formData.phoneNumber.trim(),
        date_of_birth: formData.dateOfBirth,
        gender: formData.gender,
        school_name: formData.schoolName,
        medium: formData.medium,
        online_experience: formData.onlineExperience === 'yes',
        device_used: formData.deviceUsed,
        current_grade: formData.currentGrade,
        stream: gradeNum && gradeNum >= 12 && gradeNum <= 13 ? selectedStream : null,
        selected_subjects: JSON.stringify(selectedSubjects)
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
      setIsLoading(false)
      alert('Registration submitted. Please complete payment offline. Admin will confirm and you will receive a WhatsApp message.')
      setStep(1)
      setFormData({ fullName: '', dateOfBirth: '', gender: '', schoolName: '', medium: '', onlineExperience: '', deviceUsed: '', currentGrade: '', username: '', phoneNumber: '' })
      setSelectedStream('')
      setSelectedSubjects([])
      if (onClose) onClose()
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

  const handlePaymentOnline = (amount) => {
    setError('')
    setStep(3)
  }

  const handleTransferConfirmed = async () => {
    setError('')
    if (!cardData.number || !cardData.holder || !cardData.expiry || !cardData.cvv) {
      setError('Please fill in all card details')
      return
    }
    setIsLoading(true)
    try {
      const amount = totalAmount > 0 ? totalAmount : MONTHLY_AMOUNT
      await registerStep2('online', amount)
      setIsLoading(false)
      alert('Payment successful! You will receive a WhatsApp confirmation.')
      setStep(1)
      setFormData({ fullName: '', dateOfBirth: '', gender: '', schoolName: '', medium: '', onlineExperience: '', deviceUsed: '', currentGrade: '', username: '', phoneNumber: '' })
      setCardData({ number: '', holder: '', expiry: '', cvv: '' })
      setSelectedStream('')
      setSelectedSubjects([])
      if (onClose) onClose()
    } catch (err) {
      setIsLoading(false)
      setError(err.message || 'Payment failed.')
    }
  }

  if (!isOpen) return null

  return (
    <div className="student-registration-overlay">
      <div className={`student-registration-wrapper ${step === 3 ? 'payment-step-active' : ''}`}>
        {onClose && (
          <button className="student-registration-close" onClick={onClose}>
            <FiX />
          </button>
        )}

        {step === 1 && (
          <div className="student-registration-container">
            <h2>மாணவர் விவரங்கள் / Student Details</h2>
            <p className="form-subtitle">Please fill in all the required information / தயவுசெய்து அனைத்து தேவையான தகவல்களையும் நிரப்பவும்</p>

            {error && <div className="error-message">{error}</div>}

            <form onSubmit={handleSubmit} className="student-registration-form">
              {/* Full Name */}
              <div className="form-group">
                <label htmlFor="fullName">மாணவர் முழுப் பெயர் / Full Name <span className="required">*</span></label>
                <input
                  type="text"
                  id="fullName"
                  name="fullName"
                  value={formData.fullName}
                  onChange={handleChange}
                  required
                  placeholder="Enter your full name / உங்கள் முழுப் பெயரை உள்ளிடவும்"
                />
              </div>

              {/* Phone (for WhatsApp) */}
              <div className="form-group">
                <label htmlFor="phoneNumber">தொலைபேசி எண் / Phone Number (WhatsApp) <span className="required">*</span></label>
                <input
                  type="tel"
                  id="phoneNumber"
                  name="phoneNumber"
                  value={formData.phoneNumber}
                  onChange={handleChange}
                  required
                  placeholder="e.g. 07XXXXXXXX"
                />
              </div>

              {/* Date of Birth */}
              <div className="form-group">
                <label htmlFor="dateOfBirth">பிறந்த திகதி / Date of Birth <span className="required">*</span></label>
                <input
                  type="date"
                  id="dateOfBirth"
                  name="dateOfBirth"
                  value={formData.dateOfBirth}
                  onChange={handleChange}
                  required
                  max={new Date().toISOString().split('T')[0]}
                />
              </div>

              {/* Gender */}
              <div className="form-group">
                <label htmlFor="gender">பாலினம் / Gender <span className="required">*</span></label>
                <select
                  id="gender"
                  name="gender"
                  value={formData.gender}
                  onChange={handleChange}
                  required
                >
                  <option value="">Select Gender / பாலினம் தேர்ந்தெடுக்கவும்</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                </select>
              </div>


              {/* School Name */}
              <div className="form-group">
                <label htmlFor="schoolName">பாடசாலை பெயர் / School Name <span className="required">*</span></label>
                <input
                  type="text"
                  id="schoolName"
                  name="schoolName"
                  value={formData.schoolName}
                  onChange={handleChange}
                  required
                  placeholder="Enter your school name / உங்கள் பாடசாலை பெயரை உள்ளிடவும்"
                />
              </div>

              {/* Medium of Learning */}
              <div className="form-group">
                <label htmlFor="medium">கற்கவிருக்கும் மொழி மூலம் / Medium of Learning <span className="required">*</span></label>
                <select
                  id="medium"
                  name="medium"
                  value={formData.medium}
                  onChange={handleChange}
                  required
                >
                  <option value="">Select Medium / மொழி தேர்ந்தெடுக்கவும்</option>
                  <option value="tamil">தமிழ் / Tamil</option>
                  <option value="english">ஆங்கிலம் / English</option>
                </select>
              </div>

              {/* Online Class Experience */}
              <div className="form-group">
                <label htmlFor="onlineExperience">Online class அனுபவம் உள்ளதா? / Do you have online class experience? <span className="required">*</span></label>
                <select
                  id="onlineExperience"
                  name="onlineExperience"
                  value={formData.onlineExperience}
                  onChange={handleChange}
                  required
                >
                  <option value="">Select Option / விருப்பத்தை தேர்ந்தெடுக்கவும்</option>
                  <option value="yes">ஆம் / Yes</option>
                  <option value="no">இல்லை / No</option>
                </select>
              </div>

              {/* Device Used */}
              <div className="form-group">
                <label htmlFor="deviceUsed">Online வகுப்பிற்கு பயன்படுத்தும் சாதனம் / Device Used for Online Classes <span className="required">*</span></label>
                <select
                  id="deviceUsed"
                  name="deviceUsed"
                  value={formData.deviceUsed}
                  onChange={handleChange}
                  required
                >
                  <option value="">Select Device / சாதனம் தேர்ந்தெடுக்கவும்</option>
                  <option value="Mobile">Mobile</option>
                  <option value="Tablet">Tablet</option>
                  <option value="Laptop">Laptop</option>
                  <option value="Desktop">Desktop</option>
                </select>
              </div>

              {/* Current Grade (2026) */}
              <div className="form-group">
                <label htmlFor="currentGrade">தற்போதைய தரம் (2026) / Current Grade (2026) <span className="required">*</span></label>
                <select
                  id="currentGrade"
                  name="currentGrade"
                  value={formData.currentGrade}
                  onChange={handleChange}
                  required
                >
                  <option value="">Select Grade / தரம் தேர்ந்தெடுக்கவும்</option>
                  {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13].map(grade => (
                    <option key={grade} value={`தரம் ${grade} / Grade ${grade}`}>தரம் {grade} / Grade {grade}</option>
                  ))}
                </select>
              </div>

              {/* Stream Selection for Grades 12-13 */}
              {(() => {
                const gradeNum = getGradeNumber(formData.currentGrade)
                if (gradeNum && gradeNum >= 12 && gradeNum <= 13) {
                  return (
                    <div className="form-group">
                      <label htmlFor="stream">Stream / பிரிவு <span className="required">*</span></label>
                      <select
                        id="stream"
                        name="stream"
                        value={selectedStream}
                        onChange={handleStreamChange}
                        required
                      >
                        <option value="">Select Stream / பிரிவு தேர்ந்தெடுக்கவும்</option>
                        <option value="arts">A/L – ARTS</option>
                        <option value="bio_maths">A/L – BIO & MATHS</option>
                      </select>
                    </div>
                  )
                }
                return null
              })()}

              {/* Subject Selection */}
              {availableSubjects.length > 0 && (
                <div className="form-group">
                  <label>இணைய விரும்பும் பாடம்/பாடங்கள் / Preferred Online Subject(s) <span className="required">*</span></label>
                  <div className="checkbox-group">
                    {availableSubjects.map((subjectObj) => (
                      <label
                        key={subjectObj.name}
                        className={`checkbox-label ${selectedSubjects.includes(subjectObj.name) ? 'checked' : ''}`}
                      >
                        <input
                          type="checkbox"
                          checked={selectedSubjects.includes(subjectObj.name)}
                          onChange={() => handleSubjectToggle(subjectObj.name)}
                        />
                        <span>{subjectObj.name} {subjectObj.price ? `(Rs. ${parseFloat(subjectObj.price).toFixed(0)})` : ''}</span>
                      </label>
                    ))}
                  </div>
                </div>
              )}

              <button
                type="submit"
                className="submit-button"
                disabled={isLoading}
              >
                {isLoading ? 'Submitting...' : 'Next: Payment'}
              </button>
            </form>
          </div>
        )}

        {step === 2 && (
          <div className="student-registration-container">
            <h2>கட்டணம் / Payment</h2>
            <p className="form-subtitle">Choose how you would like to pay / கட்டணம் செலுத்தும் முறையை தேர்ந்தெடுக்கவும்</p>
            {error && <div className="error-message">{error}</div>}
            <div className="payment-options">
              <p style={{ fontSize: '20px', fontWeight: 'bold', color: '#4f46e5', marginBottom: '20px' }}>
                Total Amount: Rs. {totalAmount > 0 ? totalAmount : MONTHLY_AMOUNT} {totalAmount > 0 ? '(Initial Payment)' : '(Monthly)'}
              </p>
              <button type="button" className="submit-button" onClick={() => handlePaymentOffline(totalAmount > 0 ? totalAmount : MONTHLY_AMOUNT)} disabled={isLoading}>
                I WILL PAY OFFLINE / நான் ஆஃப்லைனில் செலுத்துவேன்
              </button>
              <button type="button" className="submit-button secondary" onClick={() => handlePaymentOnline(totalAmount > 0 ? totalAmount : MONTHLY_AMOUNT)} disabled={isLoading}>
                PAY ONLINE / ஆன்லைனில் செலுத்து
              </button>
            </div>
            <button type="button" className="back-link" onClick={() => { setStep(1); setError(''); }}>
              Back to form
            </button>
          </div>
        )}

        {step === 3 && (
          <div className="glass-checkout">
            <div className="glass-bg-blob glass-bg-blob-1"></div>
            <div className="glass-bg-blob glass-bg-blob-2"></div>
            <div className="glass-bg-blob glass-bg-blob-3"></div>

            <h2 className="glass-checkout-title">Payment Details</h2>
            <p className="glass-checkout-subtitle">Amount: Rs. {totalAmount > 0 ? totalAmount : MONTHLY_AMOUNT}</p>

            {error && <div className="error-message">{error}</div>}

            {/* Content Wrapper for Side-by-Side Layout */}
            <div className="glass-checkout-content">
              {/* Live Card Preview */}
              <div className="glass-card-flip-wrapper">
                <div className={`glass-card-flip ${isFlipped ? 'flipped' : ''}`}>
                  <div className="glass-card-face glass-card-front">
                    <div className="glass-card-front-row">
                      <svg viewBox="0 0 50 40" width="44" height="34">
                        <rect x="2" y="2" width="46" height="36" rx="6" fill="#d4af37" opacity="0.85" />
                        <line x1="2" y1="14" x2="48" y2="14" stroke="#b8941f" strokeWidth="1.5" />
                        <line x1="2" y1="22" x2="48" y2="22" stroke="#b8941f" strokeWidth="1.5" />
                        <line x1="25" y1="2" x2="25" y2="38" stroke="#b8941f" strokeWidth="1.5" />
                      </svg>
                      <span className="glass-visa-text">VISA</span>
                    </div>
                    <div className="glass-live-number">
                      {cardData.number || '•••• •••• •••• ••••'}
                    </div>
                    <div className="glass-live-bottom">
                      <div>
                        <div className="glass-tiny-label">CARD HOLDER</div>
                        <div className="glass-live-name">{cardData.holder.toUpperCase() || 'YOUR NAME'}</div>
                      </div>
                      <div>
                        <div className="glass-tiny-label">EXPIRES</div>
                        <div className="glass-live-name">{cardData.expiry || 'MM/YY'}</div>
                      </div>
                    </div>
                  </div>
                  <div className="glass-card-face glass-card-back-face">
                    <div className="glass-mag-stripe"></div>
                    <div className="glass-cvv-row">
                      <span className="glass-tiny-label">CVV</span>
                      <div className="glass-cvv-display">{cardData.cvv || '•••'}</div>
                    </div>
                  </div>
                </div>
              </div>

              {/* Checkout Form */}
              <div className="glass-form-panel">
                <div className="glass-field">
                  <label>Card Number</label>
                  <input type="text" name="number" value={cardData.number} onChange={handleCardInput} placeholder="1234 5678 9012 3456" maxLength={19} />
                </div>
                <div className="glass-field">
                  <label>Card Holder</label>
                  <input type="text" name="holder" value={cardData.holder} onChange={handleCardInput} placeholder="Your full name" />
                </div>
                <div className="glass-field-row">
                  <div className="glass-field">
                    <label>Expiry</label>
                    <input type="text" name="expiry" value={cardData.expiry} onChange={handleCardInput} placeholder="MM/YY" maxLength={5} />
                  </div>
                  <div className="glass-field">
                    <label>CVV</label>
                    <input type="text" name="cvv" value={cardData.cvv} onChange={handleCardInput} placeholder="•••" maxLength={3} onFocus={() => setIsFlipped(true)} onBlur={() => setIsFlipped(false)} />
                  </div>
                </div>
                <button type="button" className="glass-pay-now" onClick={handleTransferConfirmed} disabled={isLoading}>
                  {isLoading ? 'Processing...' : `Pay Now`}
                </button>
              </div>
            </div>

            <button type="button" className="glass-back" onClick={() => { setStep(2); setCardData({ number: '', holder: '', expiry: '', cvv: '' }); setError(''); }}>
              ← Back to payment options
            </button>
          </div>
        )}
      </div>
    </div>
  )
}

export default StudentRegistrationForm
