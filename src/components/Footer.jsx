import React, { useState, useEffect } from 'react'
import { FiMail, FiPhone, FiFacebook, FiTwitter, FiInstagram, FiLinkedin, FiYoutube, FiMapPin } from 'react-icons/fi'
import { Link } from 'react-router-dom'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './Footer.css'
import './StudentToolkit.css'

const StudentToolkit = () => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()
  const [tools, setTools] = useState([])
  const [title, setTitle] = useState(getSetting('footer_toolkit_title', t('toolkit_title') || 'All-in-One Student Toolkit'))
  const [subtitle, setSubtitle] = useState(getSetting('footer_toolkit_desc', t('toolkit_desc') || 'Supercharge your learning...'))

  useEffect(() => {
    const fetchTools = async () => {
      let rawTools = []
      try {
        rawTools = JSON.parse(getSetting('footer_toolkit_items', '[]'))
        if (rawTools.length === 0) {
          rawTools = [
            {
              icon: 'https://cdn.lordicon.com/fkdkvhlp.json',
              title: t('toolkit_vault_title'),
              description: t('toolkit_vault_desc'),
              tag: t('toolkit_tag_academic'),
              color: '#4f0bd9'
            },
            {
              icon: 'https://cdn.lordicon.com/vhyenuev.json',
              title: t('toolkit_buddy_title'),
              description: t('toolkit_buddy_desc'),
              tag: t('toolkit_tag_tech'),
              color: '#10b981'
            },
            {
              icon: 'https://cdn.lordicon.com/mrdfeebn.json',
              title: t('toolkit_hub_title'),
              description: t('toolkit_hub_desc'),
              tag: t('toolkit_tag_critical'),
              color: '#f59e0b'
            }
          ]
        }
      } catch (e) { rawTools = [] }

      if (language !== 'en') {
        const translatedTools = await Promise.all(rawTools.map(async (tool) => ({
          ...tool,
          title: await translate(tool.title),
          description: await translate(tool.description),
          tag: await translate(tool.tag)
        })))
        setTools(translatedTools)
        setTitle(await translate(getSetting('footer_toolkit_title', t('toolkit_title'))))
        setSubtitle(await translate(getSetting('footer_toolkit_desc', t('toolkit_desc'))))
      } else {
        setTools(rawTools)
        setTitle(getSetting('footer_toolkit_title', t('toolkit_title') || 'All-in-One Student Toolkit'))
        setSubtitle(getSetting('footer_toolkit_desc', t('toolkit_desc') || 'Supercharge your learning...'))
      }
    }
    fetchTools()
  }, [language, translate, t, getSetting])

  return (
    <section className="student-toolkit">
      <div className="container">
        <h2 className="toolkit-title">{title}</h2>
        <p className="toolkit-subtitle">{subtitle}</p>
        <div className="toolkit-grid">
          {tools.map((tool, index) => (
            <div key={index} className="toolkit-item" style={{ '--accent-color': tool.color }}>
              <div className="toolkit-icon">
                <lord-icon
                  src={tool.icon || 'https://cdn.lordicon.com/fkdkvhlp.json'}
                  trigger="hover"
                  colors={`primary:${tool.color || '#4f0bd9'},secondary:#1a103c`}
                  style={{ width: '40px', height: '40px' }}
                />
              </div>
              <h3 className="item-title">{tool.title}</h3>
              <p className="item-description">{tool.description}</p>
              <span className="item-tag">{tool.tag}</span>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

const Footer = () => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()

  const footer_email = getSetting('footer_email', 'info@edulearn.lk')
  const footer_phone = getSetting('footer_phone', '+94 114 477 488')
  const social_facebook = getSetting('social_facebook', '#facebook')
  const social_twitter = getSetting('social_twitter', '#twitter')
  const social_instagram = getSetting('social_instagram', '#instagram')
  const social_linkedin = getSetting('social_linkedin', '#linkedin')
  const social_youtube = getSetting('social_youtube', '#youtube')

  const footer_tagline = getSetting('footer_tagline', t('footer_your_education'))

  const footerLogoText = getSetting('footer_logo_text', 'TiT')
  const [footerInfo, setFooterInfo] = useState(getSetting('footer_description', t('footer_description')))
  const [copyright, setCopyright] = useState(getSetting('footer_copyright', `Copyrights © ${new Date().getFullYear()} ${footerLogoText}. All rights reserved by ${footerLogoText} Online Education (PVT) Ltd.`))
  const [legalTitle, setLegalTitle] = useState(getSetting('footer_legal_title', t('footer_legal')))
  const [privacyLabel, setPrivacyLabel] = useState(getSetting('footer_privacy_label', t('footer_privacy')))
  const [termsLabel, setTermsLabel] = useState(getSetting('footer_terms_label', t('footer_terms')))
  const [refundLabel, setRefundLabel] = useState(getSetting('footer_refund_label', t('footer_refund')))
  const [quickLinksTitle, setQuickLinksTitle] = useState(getSetting('footer_quick_links_title', t('footer_quick_links')))
  const [galleryLabel, setGalleryLabel] = useState(getSetting('footer_gallery_label', t('footer_gallery')))
  const [tutorLabel, setTutorLabel] = useState(getSetting('footer_tutor_label', t('footer_apply_tutor')))
  const [studentLabel, setStudentLabel] = useState(getSetting('footer_student_label', t('footer_register_student')))
  const [contactTitle, setContactTitle] = useState(getSetting('footer_contact_title', t('footer_contact')))

  useEffect(() => {
    const defaultEn = {
      footer_desc: "Sri Lanka's trusted leader in online tuition. We ensure student success through personalized learning and comprehensive support.",
      footer_copyright: `Copyrights © ${new Date().getFullYear()} ${footerLogoText}. All rights reserved by ${footerLogoText} Online Education (PVT) Ltd.`,
      footer_legal: 'Legal',
      footer_privacy: 'Privacy Policy',
      footer_terms: 'Terms & Conditions',
      footer_refund: 'Refund Policy',
      footer_quick_links: 'Quick Links',
      footer_gallery: 'Gallery',
      footer_apply_tutor: 'Apply as a Tutor',
      footer_register_student: 'Register as a student',
      footer_contact: 'Contact'
    }

    if (language !== 'en') {
      const translateAll = async () => {
        const valDesc = getSetting('footer_description', defaultEn.footer_desc)
        if (valDesc === defaultEn.footer_desc) setFooterInfo(t('footer_description'))
        else setFooterInfo(await translate(valDesc))

        const valCopy = getSetting('footer_copyright', defaultEn.footer_copyright)
        if (valCopy === defaultEn.footer_copyright) setCopyright(t('footer_copyright'))
        else setCopyright(await translate(valCopy))

        // Update labels
        const labels = [
          { key: 'footer_legal', get: 'footer_legal_title', set: setLegalTitle },
          { key: 'footer_privacy', get: 'footer_privacy_label', set: setPrivacyLabel },
          { key: 'footer_terms', get: 'footer_terms_label', set: setTermsLabel },
          { key: 'footer_refund', get: 'footer_refund_label', set: setRefundLabel },
          { key: 'footer_quick_links', get: 'footer_quick_links_title', set: setQuickLinksTitle },
          { key: 'footer_gallery', get: 'footer_gallery_label', set: setGalleryLabel },
          { key: 'footer_apply_tutor', get: 'footer_tutor_label', set: setTutorLabel },
          { key: 'footer_register_student', get: 'footer_student_label', set: setStudentLabel },
          { key: 'footer_contact', get: 'footer_contact_title', set: setContactTitle }
        ]

        for (const label of labels) {
          const val = getSetting(label.get, defaultEn[label.key])
          if (val === defaultEn[label.key]) label.set(t(label.key))
          else label.set(await translate(val))
        }
      }
      translateAll()
    } else {
      setFooterInfo(getSetting('footer_description', t('footer_description')))
      setCopyright(getSetting('footer_copyright', defaultEn.footer_copyright))
      setLegalTitle(getSetting('footer_legal_title', t('footer_legal')))
      setPrivacyLabel(getSetting('footer_privacy_label', t('footer_privacy')))
      setTermsLabel(getSetting('footer_terms_label', t('footer_terms')))
      setRefundLabel(getSetting('footer_refund_label', t('footer_refund')))
      setQuickLinksTitle(getSetting('footer_quick_links_title', t('footer_quick_links')))
      setGalleryLabel(getSetting('footer_gallery_label', t('footer_gallery')))
      setTutorLabel(getSetting('footer_tutor_label', t('footer_apply_tutor')))
      setStudentLabel(getSetting('footer_student_label', t('footer_register_student')))
      setContactTitle(getSetting('footer_contact_title', t('footer_contact')))
    }
  }, [language, translate, getSetting, t])

  return (
    <footer className="footer">
      <div className="container">
        <div className="footer-content">
          <div className="footer-section">
            <h3 className="footer-logo">{getSetting('footer_logo_text', 'TiT')}</h3>
            <p className="footer-tagline">{footer_tagline}</p>
            <p className="footer-description">
              {footerInfo}
            </p>
          </div>

          <div className="footer-section">
            <h4 className="footer-title">{legalTitle}</h4>
            <ul className="footer-links">
              <li><Link to="/privacy">{privacyLabel}</Link></li>
              <li><Link to="/terms">{termsLabel}</Link></li>
              <li><Link to="/refund">{refundLabel}</Link></li>
              <li><Link to="/gallery">{galleryLabel}</Link></li>
            </ul>
          </div>

          <div className="footer-section">
            <h4 className="footer-title">{quickLinksTitle}</h4>
            <ul className="footer-links">
              <li><Link to="/about">{t('nav_about')}</Link></li>
              <li><Link to="/classes">{t('nav_classes')}</Link></li>
              <li><Link to="/contact">{t('nav_contact')}</Link></li>
              <li><Link to="/register">{tutorLabel}</Link></li>
              <li><Link to="/register">{studentLabel}</Link></li>
            </ul>
          </div>

          <div className="footer-section">
            <h4 className="footer-title">{contactTitle}</h4>
            <div className="contact-info">
              <a href={`mailto:${footer_email}`} className="contact-item">
                <FiMail />
                <span>{footer_email}</span>
              </a>
              <a href={`tel:${footer_phone.replace(/\s/g, '')}`} className="contact-item">
                <FiPhone />
                <span>{footer_phone}</span>
              </a>
              <div className="contact-item">
                <FiMapPin />
                <span>{t('footer_location')}</span>
              </div>
            </div>
            <div className="social-links">
              {social_facebook && social_facebook !== '#' && (
                <a href={social_facebook} aria-label="Facebook">
                  <FiFacebook />
                </a>
              )}
              {social_twitter && social_twitter !== '#' && (
                <a href={social_twitter} aria-label="Twitter">
                  <FiTwitter />
                </a>
              )}
              {social_instagram && social_instagram !== '#' && (
                <a href={social_instagram} aria-label="Instagram">
                  <FiInstagram />
                </a>
              )}
              {social_linkedin && social_linkedin !== '#' && (
                <a href={social_linkedin} aria-label="LinkedIn">
                  <FiLinkedin />
                </a>
              )}
              {social_youtube && social_youtube !== '#' && (
                <a href={social_youtube} aria-label="YouTube">
                  <FiYoutube />
                </a>
              )}
            </div>
          </div>
        </div>

        <div className="footer-bottom">
          <p>{copyright}</p>
        </div>
      </div>
    </footer>
  )
}

export { StudentToolkit, Footer }
export default Footer
