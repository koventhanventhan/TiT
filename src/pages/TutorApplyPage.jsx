import React, { useState, useEffect } from 'react'
import { FiUser, FiMail, FiPhone, FiBook, FiAward, FiClock, FiCheckCircle, FiInfo, FiUpload, FiSend } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './TutorApplyPage.css'

const TutorApplyPage = () => {
    const { getSetting } = useSettings()
    const { t, translate, language } = useLanguage()

    const [formTitle, setFormTitle] = useState(getSetting('footer_tutor_form_title', 'Tutor Application Form'))
    const [formDesc, setFormDesc] = useState(getSetting('footer_tutor_form_desc', 'Join our elite team of educators. Please fill in your professional details below.'))

    // Labels
    const [lblFullname, setLblFullname] = useState(getSetting('tutor_label_fullname', 'Full Name'))
    const [lblEmail, setLblEmail] = useState(getSetting('tutor_label_email', 'Email Address'))
    const [lblPhone, setLblPhone] = useState(getSetting('tutor_label_phone', 'Phone (WhatsApp)'))
    const [lblMedium, setLblMedium] = useState(getSetting('tutor_label_medium', 'Medium of Teaching'))
    const [lblSubject, setLblSubject] = useState(getSetting('tutor_label_subject', 'Subject(s) to Teach'))
    const [lblQualification, setLblQualification] = useState(getSetting('tutor_label_qualification', 'Highest Qualification'))
    const [lblExperience, setLblExperience] = useState(getSetting('tutor_label_experience', 'Teaching Experience (Years)'))
    const [lblCv, setLblCv] = useState(getSetting('tutor_label_cv', 'CV or Portfolio Link'))
    const [lblAvailability, setLblAvailability] = useState(getSetting('tutor_label_availability', 'Availability (Days/Times)'))
    const [lblBio, setLblBio] = useState(getSetting('tutor_label_bio', 'Teaching Philosophy / Brief Bio'))

    const [loading, setLoading] = useState(false)
    const [success, setSuccess] = useState(false)
    const [error, setError] = useState('')

    const [formData, setFormData] = useState({
        fullName: '',
        email: '',
        phone: '',
        subject: '',
        qualification: '',
        experience: '',
        medium: 'Tamil',
        availability: '',
        bio: ''
    })
    const [cvFile, setCvFile] = useState(null)

    useEffect(() => {
        const translateContent = async () => {
            if (language !== 'en') {
                setFormTitle(await translate(getSetting('footer_tutor_form_title', 'Tutor Application Form')))
                setFormDesc(await translate(getSetting('footer_tutor_form_desc', 'Join our elite team of educators. Please fill in your professional details below.')))
                
                setLblFullname(await translate(getSetting('tutor_label_fullname', 'Full Name')))
                setLblEmail(await translate(getSetting('tutor_label_email', 'Email Address')))
                setLblPhone(await translate(getSetting('tutor_label_phone', 'Phone (WhatsApp)')))
                setLblMedium(await translate(getSetting('tutor_label_medium', 'Medium of Teaching')))
                setLblSubject(await translate(getSetting('tutor_label_subject', 'Subject(s) to Teach')))
                setLblQualification(await translate(getSetting('tutor_label_qualification', 'Highest Qualification')))
                setLblExperience(await translate(getSetting('tutor_label_experience', 'Teaching Experience (Years)')))
                setLblCv(await translate(getSetting('tutor_label_cv', 'CV or Portfolio Link')))
                setLblAvailability(await translate(getSetting('tutor_label_availability', 'Availability (Days/Times)')))
                setLblBio(await translate(getSetting('tutor_label_bio', 'Teaching Philosophy / Brief Bio')))
            } else {
                setFormTitle(getSetting('footer_tutor_form_title', 'Tutor Application Form'))
                setFormDesc(getSetting('footer_tutor_form_desc', 'Join our elite team of educators. Please fill in your professional details below.'))
                
                setLblFullname(getSetting('tutor_label_fullname', 'Full Name'))
                setLblEmail(getSetting('tutor_label_email', 'Email Address'))
                setLblPhone(getSetting('tutor_label_phone', 'Phone (WhatsApp)'))
                setLblMedium(getSetting('tutor_label_medium', 'Medium of Teaching'))
                setLblSubject(getSetting('tutor_label_subject', 'Subject(s) to Teach'))
                setLblQualification(getSetting('tutor_label_qualification', 'Highest Qualification'))
                setLblExperience(getSetting('tutor_label_experience', 'Teaching Experience (Years)'))
                setLblCv(getSetting('tutor_label_cv', 'CV or Portfolio Link'))
                setLblAvailability(getSetting('tutor_label_availability', 'Availability (Days/Times)'))
                setLblBio(getSetting('tutor_label_bio', 'Teaching Philosophy / Brief Bio'))
            }
        }
        translateContent()
    }, [language, getSetting, translate])

    const handleChange = (e) => {
        const { name, value, type, files } = e.target
        if (type === 'file') {
            setCvFile(files[0])
        } else {
            setFormData(prev => ({ ...prev, [name]: value }))
        }
        setError('')
    }

    const handleSubmit = async (e) => {
        e.preventDefault()
        setLoading(true)
        setError('')

        try {
            const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'
            
            // Use FormData for file upload
            const dataToSubmit = new FormData()
            Object.keys(formData).forEach(key => {
                dataToSubmit.append(key, formData[key])
            })
            if (cvFile) {
                dataToSubmit.append('cvFile', cvFile)
            }

            const response = await fetch(`${API_BASE_URL}/tutor/apply`, {
                method: 'POST',
                body: dataToSubmit
                // Headers are automatically set for FormData
            })

            const data = await response.json()

            if (response.ok && data.success) {
                setSuccess(true)
                window.scrollTo({ top: 0, behavior: 'smooth' })
            } else {
                setError(data.message || 'Failed to submit application. Please check your details.')
            }
        } catch (err) {
            setError('Connection error. Please try again later.')
        } finally {
            setLoading(false)
        }
    }

    if (success) {
        return (
            <div className="tutor-apply-page">
                <div className="container">
                    <div className="success-container">
                        <div className="success-icon">
                            <FiCheckCircle />
                        </div>
                        <h1>{language === 'ta' ? 'விண்ணப்பம் சமர்ப்பிக்கப்பட்டது!' : 'Application Submitted!'}</h1>
                        <p>
                            {language === 'ta' 
                                ? 'உங்கள் விண்ணப்பத்திற்கு நன்றி. எமது குழு உங்கள் தகவல்களை பரிசீலித்து விரைவில் உங்களை தொடர்பு கொள்ளும்.' 
                                : 'Thank you for your interest in joining TiT. Our team will review your qualifications and contact you soon.'}
                        </p>
                        <button className="btn btn-primary" onClick={() => window.location.href = '/'}>
                            {language === 'ta' ? 'முகப்புப் பக்கத்திற்குச் செல்க' : 'Back to Home'}
                        </button>
                    </div>
                </div>
            </div>
        )
    }

    return (
        <div className="tutor-apply-page">
            <div className="container">
                <div className="form-wrapper">
                    <div className="form-header">
                        <h1>{formTitle}</h1>
                        <p>{formDesc}</p>
                    </div>

                    {error && <div className="form-alert error">{error}</div>}

                    <form onSubmit={handleSubmit} className="tutor-form">
                        <div className="form-section">
                            <h3><FiUser /> {language === 'ta' ? 'தனிப்பட்ட விவரங்கள்' : 'Personal Details'}</h3>
                            <div className="form-grid">
                                <div className="form-group">
                                    <label>{lblFullname} *</label>
                                    <div className="input-with-icon">
                                        <FiUser className="icon" />
                                        <input 
                                            type="text" 
                                            name="fullName" 
                                            value={formData.fullName} 
                                            onChange={handleChange} 
                                            placeholder="e.g. John Doe" 
                                            required 
                                        />
                                    </div>
                                </div>
                                <div className="form-group">
                                    <label>{lblEmail} *</label>
                                    <div className="input-with-icon">
                                        <FiMail className="icon" />
                                        <input 
                                            type="email" 
                                            name="email" 
                                            value={formData.email} 
                                            onChange={handleChange} 
                                            placeholder="example@mail.com" 
                                            required 
                                        />
                                    </div>
                                </div>
                                <div className="form-group">
                                    <label>{lblPhone} *</label>
                                    <div className="input-with-icon">
                                        <FiPhone className="icon" />
                                        <input 
                                            type="tel" 
                                            name="phone" 
                                            value={formData.phone} 
                                            onChange={handleChange} 
                                            placeholder="e.g. 07XXXXXXXX" 
                                            required 
                                        />
                                    </div>
                                </div>
                                <div className="form-group">
                                    <label>{lblMedium} *</label>
                                    <div className="input-with-icon">
                                        <FiInfo className="icon" />
                                        <select name="medium" value={formData.medium} onChange={handleChange} required>
                                            <option value="Tamil">Tamil</option>
                                            <option value="English">English</option>
                                            <option value="Sinhala">Sinhala</option>
                                            <option value="Multi">Bilingual/Multi</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="form-section">
                            <h3><FiBook /> {language === 'ta' ? 'கல்வி மற்றும் அனுபவம்' : 'Education & Experience'}</h3>
                            <div className="form-grid">
                                <div className="form-group">
                                    <label>{lblSubject} *</label>
                                    <div className="input-with-icon">
                                        <FiBook className="icon" />
                                        <input 
                                            type="text" 
                                            name="subject" 
                                            value={formData.subject} 
                                            onChange={handleChange} 
                                            placeholder="e.g. Mathematics, Physics" 
                                            required 
                                        />
                                    </div>
                                </div>
                                <div className="form-group">
                                    <label>{lblQualification} *</label>
                                    <div className="input-with-icon">
                                        <FiAward className="icon" />
                                        <input 
                                            type="text" 
                                            name="qualification" 
                                            value={formData.qualification} 
                                            onChange={handleChange} 
                                            placeholder="e.g. B.Sc in Mathematics" 
                                            required 
                                        />
                                    </div>
                                </div>
                                <div className="form-group">
                                    <label>{lblExperience} *</label>
                                    <div className="input-with-icon">
                                        <FiClock className="icon" />
                                        <input 
                                            type="number" 
                                            name="experience" 
                                            value={formData.experience} 
                                            onChange={handleChange} 
                                            placeholder="e.g. 5" 
                                            required 
                                        />
                                    </div>
                                </div>
                                <div className="form-group">
                                    <label>{lblCv}</label>
                                    <div className="input-with-icon">
                                        <FiUpload className="icon" />
                                        <input 
                                            type="file" 
                                            name="cvFile" 
                                            onChange={handleChange} 
                                            accept=".pdf,.doc,.docx"
                                        />
                                    </div>
                                    <small className="file-help">Max 5MB (PDF/DOC/DOCX)</small>
                                </div>
                            </div>
                        </div>

                        <div className="form-section">
                            <h3><FiInfo /> {language === 'ta' ? 'மேலதிக தகவல்கள்' : 'Additional Information'}</h3>
                            <div className="form-group">
                                <label>{lblAvailability} *</label>
                                <textarea 
                                    name="availability" 
                                    value={formData.availability} 
                                    onChange={handleChange} 
                                    rows="2" 
                                    placeholder="e.g. Weekends only, Weekdays after 4 PM" 
                                    required
                                ></textarea>
                            </div>
                            <div className="form-group">
                                <label>{lblBio} *</label>
                                <textarea 
                                    name="bio" 
                                    value={formData.bio} 
                                    onChange={handleChange} 
                                    rows="4" 
                                    placeholder={language === 'ta' ? 'உங்களைப் பற்றி சிறு குறிப்பு தருக...' : 'Tell us why we should hire you and your teaching style...'}
                                    required
                                ></textarea>
                            </div>
                        </div>

                        <button type="submit" className="submit-btn" disabled={loading}>
                            {loading ? (
                                <span className="loader"></span>
                            ) : (
                                <>
                                    <FiSend /> {language === 'ta' ? 'விண்ணப்பிக்கவும்' : 'Submit Application'}
                                </>
                            )}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    )
}

export default TutorApplyPage
