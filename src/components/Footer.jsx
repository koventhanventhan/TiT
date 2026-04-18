import React, { useState, useEffect } from 'react'
import { FiMail, FiPhone, FiFacebook, FiTwitter, FiInstagram, FiLinkedin, FiYoutube, FiMapPin, FiArrowRight } from 'react-icons/fi'
import { Link } from 'react-router-dom'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import { useAuthModal } from '../context/AuthModalContext'
import './Footer.css'
import './StudentToolkit.css'

const StudentToolkit = () => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()
  const [tools, setTools] = useState([])
  const [title, setTitle] = useState('')
  const [subtitle, setSubtitle] = useState('')

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
              color: '#6366f1'
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

      const toolkitTitle = getSetting('footer_toolkit_title', t('toolkit_title') || 'All-in-One Student Toolkit')
      const toolkitDesc = getSetting('footer_toolkit_desc', t('toolkit_desc') || 'Supercharge your learning...')

      if (language !== 'en') {
        const translatedTools = await Promise.all(rawTools.map(async (tool) => ({
          ...tool,
          title: await translate(tool.title),
          description: await translate(tool.description),
          tag: await translate(tool.tag)
        })))
        setTools(translatedTools)
        setTitle(await translate(toolkitTitle))
        setSubtitle(await translate(toolkitDesc))
      } else {
        setTools(rawTools)
        setTitle(toolkitTitle)
        setSubtitle(toolkitDesc)
      }
    }
    fetchTools()
  }, [language, translate, t, getSetting])

  return (
    <section className="student-toolkit">
      <div className="container">
        <div className="toolkit-header">
          <h2 className="toolkit-title">{title}</h2>
          <p className="toolkit-subtitle">{subtitle}</p>
        </div>
        <div className="toolkit-grid">
          {tools.map((tool, index) => (
            <div key={index} className="toolkit-item" style={{ '--accent-color': tool.color }}>
              <div className="toolkit-icon">
                <lord-icon
                  src={tool.icon || 'https://cdn.lordicon.com/fkdkvhlp.json'}
                  trigger="hover"
                  colors={`primary:${tool.color || '#6366f1'},secondary:#1a103c`}
                  style={{ width: '3rem', height: '3rem' }}
                />
              </div>
              <h3 className="item-title">{tool.title}</h3>
              <p className="item-description">{tool.description}</p>
              <div className="item-footer">
                <span className="item-tag">{tool.tag}</span>
                <FiArrowRight className="arrow-icon" />
              </div>
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
  const { openRegister } = useAuthModal()

  const footer_email = getSetting('footer_email', 'info@lenova.lk')
  const footer_phone = getSetting('footer_phone', '+94 11 123 4567')
  const social_facebook = getSetting('social_facebook', '')
  const social_twitter = getSetting('social_twitter', '')
  const social_instagram = getSetting('social_instagram', '')
  const social_linkedin = getSetting('social_linkedin', '')
  const social_youtube = getSetting('social_youtube', '')

  const footer_tagline = getSetting('footer_tagline', t('footer_your_education'))
  const footerLogoText = getSetting('footer_logo_text', 'LENOVA')
  
  const [footerInfo, setFooterInfo] = useState('')
  const [copyright, setCopyright] = useState('')
  const [legalTitle, setLegalTitle] = useState('')
  const [privacyLabel, setPrivacyLabel] = useState('')
  const [termsLabel, setTermsLabel] = useState('')
  const [refundLabel, setRefundLabel] = useState('')
  const [quickLinksTitle, setQuickLinksTitle] = useState('')
  const [galleryLabel, setGalleryLabel] = useState('')
  const [tutorLabel, setTutorLabel] = useState('')
  const [studentLabel, setStudentLabel] = useState('')
  const [contactTitle, setContactTitle] = useState('')

  useEffect(() => {
    const loadLabels = async () => {
      const defaultEn = {
        footer_desc: getSetting('footer_description', "Professional online education platform providing quality learning experiences for students across Sri Lanka."),
        footer_copyright: getSetting('footer_copyright', `© ${new Date().getFullYear()} ${footerLogoText}. All rights reserved.`),
        footer_legal: getSetting('footer_legal_title', 'Legal'),
        footer_privacy: getSetting('footer_privacy_label', 'Privacy Policy'),
        footer_terms: getSetting('footer_terms_label', 'Terms & Conditions'),
        footer_refund: getSetting('footer_refund_label', 'Refund Policy'),
        footer_quick_links: getSetting('footer_quick_links_title', 'Quick Links'),
        footer_gallery: getSetting('footer_gallery_label', 'Gallery'),
        footer_apply_tutor: getSetting('footer_tutor_label', 'Apply as a Tutor'),
        footer_register_student: getSetting('footer_student_label', 'Join as Student'),
        footer_contact: getSetting('footer_contact_title', 'Contact Us')
      }

      if (language !== 'en') {
        setFooterInfo(await translate(defaultEn.footer_desc))
        setCopyright(await translate(defaultEn.footer_copyright))
        setLegalTitle(await translate(defaultEn.footer_legal))
        setPrivacyLabel(await translate(defaultEn.footer_privacy))
        setTermsLabel(await translate(defaultEn.footer_terms))
        setRefundLabel(await translate(defaultEn.footer_refund))
        setQuickLinksTitle(await translate(defaultEn.footer_quick_links))
        setGalleryLabel(await translate(defaultEn.footer_gallery))
        setTutorLabel(await translate(defaultEn.footer_apply_tutor))
        setStudentLabel(await translate(defaultEn.footer_register_student))
        setContactTitle(await translate(defaultEn.footer_contact))
      } else {
        setFooterInfo(defaultEn.footer_desc)
        setCopyright(defaultEn.footer_copyright)
        setLegalTitle(defaultEn.footer_legal)
        setPrivacyLabel(defaultEn.footer_privacy)
        setTermsLabel(defaultEn.footer_terms)
        setRefundLabel(defaultEn.footer_refund)
        setQuickLinksTitle(defaultEn.footer_quick_links)
        setGalleryLabel(defaultEn.footer_gallery)
        setTutorLabel(defaultEn.footer_apply_tutor)
        setStudentLabel(defaultEn.footer_register_student)
        setContactTitle(defaultEn.footer_contact)
      }
    }
    loadLabels()
  }, [language, translate, getSetting, footerLogoText])

  return (
    <footer className="footer">
      <div className="container">
        <div className="footer-grid">
          <div className="footer-brand">
            <h3 className="footer-logo-text">{footerLogoText}</h3>
            <p className="footer-tagline">{footer_tagline}</p>
            <p className="footer-info-text">{footerInfo}</p>
            <div className="social-links-v2">
              {social_facebook && social_facebook !== '#' && (
                <a href={social_facebook} target="_blank" rel="noopener noreferrer" className="social-icon">
                  <FiFacebook />
                </a>
              )}
              {social_instagram && social_instagram !== '#' && (
                <a href={social_instagram} target="_blank" rel="noopener noreferrer" className="social-icon">
                  <FiInstagram />
                </a>
              )}
              {social_twitter && social_twitter !== '#' && (
                <a href={social_twitter} target="_blank" rel="noopener noreferrer" className="social-icon">
                  <FiTwitter />
                </a>
              )}
              {social_linkedin && social_linkedin !== '#' && (
                <a href={social_linkedin} target="_blank" rel="noopener noreferrer" className="social-icon">
                  <FiLinkedin />
                </a>
              )}
              {social_youtube && social_youtube !== '#' && (
                <a href={social_youtube} target="_blank" rel="noopener noreferrer" className="social-icon">
                  <FiYoutube />
                </a>
              )}
            </div>
          </div>

          <div className="footer-links-column">
            <h4 className="footer-column-title">{legalTitle}</h4>
            <ul className="footer-links-list">
              <li><Link to="/privacy">{privacyLabel}</Link></li>
              <li><Link to="/terms">{termsLabel}</Link></li>
              <li><Link to="/refund">{refundLabel}</Link></li>
              <li><Link to="/about?tab=images">{galleryLabel}</Link></li>
            </ul>
          </div>

          <div className="footer-links-column">
            <h4 className="footer-column-title">{quickLinksTitle}</h4>
            <ul className="footer-links-list">
              <li><Link to="/about">{t('nav_about') || 'About'}</Link></li>
              <li><Link to="/classes">{t('nav_classes') || 'Classes'}</Link></li>
              <li><Link to="/contact">{t('nav_contact') || 'Contact'}</Link></li>
              <li><Link to="/tutor-apply">{tutorLabel}</Link></li>
              <li><a href="#" onClick={(e) => { e.preventDefault(); openRegister(); }}>{studentLabel}</a></li>
            </ul>
          </div>

          <div className="footer-contact-column">
            <h4 className="footer-column-title">{contactTitle}</h4>
            <div className="contact-details">
              <a href={`mailto:${footer_email}`} className="contact-link">
                <div className="contact-icon-box"><FiMail /></div>
                <span>{footer_email}</span>
              </a>
              <a href={`tel:${footer_phone.replace(/\s/g, '')}`} className="contact-link">
                <div className="contact-icon-box"><FiPhone /></div>
                <span>{footer_phone}</span>
              </a>
              <Link to="/contact#map-section" className="contact-link">
                <div className="contact-icon-box"><FiMapPin /></div>
                <span>{getSetting('footer_location', 'Colombo, Sri Lanka')}</span>
              </Link>
            </div>
          </div>
        </div>

        <div className="footer-bottom-v2">
          <div className="footer-divider"></div>
          <div className="footer-bottom-flex">
            <p className="copyright-text">{copyright}</p>
            <p className="developed-by">{getSetting('footer_developed_by', 'Designed & Developed by TiT Team')}</p>
          </div>
        </div>
      </div>
    </footer>
  )
}

export { StudentToolkit, Footer }
export default Footer
