import React, { useState, useMemo } from 'react'
import { registerStep1, registerStep2, registerPaymentSuccess } from '../services/authService'
import { FiX } from 'react-icons/fi'
import './StudentRegistrationForm.css'

// Subject data structures
const subjectsGrade1to5 = [
  'தமிழ்',
  'ஆங்கிலம்',
  'சூழற்­றாடல்',
  'சமயம்',
  'சிங்களம்',
  'புலமைப்பரிசில் வகுப்புகள்'
]

const subjectsGrade6to11 = [
  'தமிழ்',
  'ஆங்கிலம்',
  'கணிதம்',
  'வரலாறு',
  'சமயம்',
  'விஞ்ஞானம்',
  'குடியியல் கல்வி',
  'புவியியல்',
  'சிங்களம்',
  'ICT',
  'சுகாதாரம் உள்கல்வியும்',
  'வணிகக் கல்வி',
  'இலக்கியம் (தமிழ்)'
]

const subjectsArtsStream = [
  'தமிழ்',
  'வரலாறு',
  'புவியியல்',
  'ICT',
  'அரசியல் விஞ்ஞானம்',
  'இந்து நாகரிகம்',
  'மனைப்பொருளியல்',
  'ஊடகக் கல்வி',
  'நடனம்',
  'நாடகம்',
  'சித்திரம்',
  'சங்கீதம்',
  'கிறிஸ்தவ நாகரிகம்',
  'அளவையியல்'
]

const subjectsBioMathsStream = [
  'இணைந்த கணிதம்',
  'உயிரியல்',
  'பெளதிகவியல்',
  'இரசாயனவியல்',
  'ICT'
]

const MONTHLY_AMOUNT = 500

const StudentRegistrationForm = ({ isOpen = true, onClose }) => {
  const [step, setStep] = useState(1)
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

  // Helper function to extract grade number from "தரம் X / Grade X" format
  const getGradeNumber = (gradeValue) => {
    if (!gradeValue) return null
    const match = gradeValue.match(/தரம்\s*(\d+)/)
    return match ? parseInt(match[1], 10) : null
  }

  // Get available subjects based on grade and stream
  const getAvailableSubjects = () => {
    const gradeNum = getGradeNumber(formData.currentGrade)
    if (!gradeNum) return []

    if (gradeNum >= 1 && gradeNum <= 5) {
      return subjectsGrade1to5
    } else if (gradeNum >= 6 && gradeNum <= 11) {
      return subjectsGrade6to11
    } else if (gradeNum >= 12 && gradeNum <= 13) {
      if (selectedStream === 'arts') {
        return subjectsArtsStream
      } else if (selectedStream === 'bio_maths') {
        return subjectsBioMathsStream
      }
      return []
    }
    return []
  }

  const availableSubjects = useMemo(() => getAvailableSubjects(), [formData.currentGrade, selectedStream])

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

    if (!formData.phoneNumber || formData.phoneNumber.trim().length < 10) {
      setError('Valid phone number is required (for WhatsApp confirmation)')
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

  const handlePaymentOffline = async () => {
    setError('')
    setIsLoading(true)
    try {
      await registerStep2('offline')
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

  const handlePaymentOnline = async () => {
    setError('')
    setIsLoading(true)
    try {
      const res = await registerStep2('online', MONTHLY_AMOUNT)
      setIsLoading(false)
      setPaymentChoice(res)
      if (res.key) {
        // Razorpay: load script and open checkout (optional - if Razorpay key is set)
        if (window.Razorpay) {
          const options = {
            key: res.key,
            amount: res.amount,
            currency: res.currency || 'INR',
            order_id: res.order_id,
            name: 'Education',
            description: 'Monthly fee',
            handler: async (response) => {
              try {
                await registerPaymentSuccess(res.order_id, response.razorpay_payment_id)
                alert('Payment successful. Admin will confirm and you will receive a WhatsApp message.')
                setStep(1)
                setFormData({ fullName: '', dateOfBirth: '', gender: '', schoolName: '', medium: '', onlineExperience: '', deviceUsed: '', currentGrade: '', username: '', phoneNumber: '' })
                setSelectedStream('')
                setSelectedSubjects([])
                if (onClose) onClose()
              } catch (e) {
                setError(e.message || 'Payment confirmation failed.')
              }
            }
          }
          const rzp = new window.Razorpay(options)
          rzp.open()
        } else {
          // No Razorpay: simulate success (admin can mark paid)
          await registerPaymentSuccess(res.order_id)
          alert('Payment recorded. Admin will confirm and you will receive a WhatsApp message.')
          setStep(1)
          setFormData({ fullName: '', dateOfBirth: '', gender: '', schoolName: '', medium: '', onlineExperience: '', deviceUsed: '', currentGrade: '', username: '', phoneNumber: '' })
          setSelectedStream('')
          setSelectedSubjects([])
          if (onClose) onClose()
        }
      } else {
        await registerPaymentSuccess(res.order_id)
        alert('Payment recorded. Admin will confirm and you will receive a WhatsApp message.')
        setStep(1)
        setFormData({ fullName: '', dateOfBirth: '', gender: '', schoolName: '', medium: '', onlineExperience: '', deviceUsed: '', currentGrade: '', username: '', phoneNumber: '' })
        setSelectedStream('')
        setSelectedSubjects([])
        if (onClose) onClose()
      }
    } catch (err) {
      setIsLoading(false)
      setError(err.message || 'Payment failed.')
    }
  }

  if (!isOpen) return null

  return (
    <div className="student-registration-overlay" onClick={onClose}>
      <div className="student-registration-wrapper" onClick={(e) => e.stopPropagation()}>
        <button className="student-registration-close" onClick={onClose}>
          <FiX />
        </button>

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
                  {availableSubjects.map((subject) => (
                    <label
                      key={subject}
                      className={`checkbox-label ${selectedSubjects.includes(subject) ? 'checked' : ''}`}
                    >
                      <input
                        type="checkbox"
                        checked={selectedSubjects.includes(subject)}
                        onChange={() => handleSubjectToggle(subject)}
                      />
                      <span>{subject}</span>
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
            <p>Amount: Rs. {MONTHLY_AMOUNT} (monthly)</p>
            <button type="button" className="submit-button" onClick={handlePaymentOffline} disabled={isLoading}>
              I will pay offline / நான் ஆஃப்லைனில் செலுத்துவேன்
            </button>
            <button type="button" className="submit-button secondary" onClick={handlePaymentOnline} disabled={isLoading}>
              {isLoading ? 'Processing...' : 'Pay online / ஆன்லைனில் செலுத்து'}
            </button>
          </div>
          <button type="button" className="back-link" onClick={() => { setStep(1); setError(''); }}>
            Back to form
          </button>
        </div>
      )}
      </div>
    </div>
  )
}

export default StudentRegistrationForm

