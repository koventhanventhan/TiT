import React from 'react'
import { FiMail, FiPhone, FiFacebook, FiTwitter, FiInstagram, FiLinkedin, FiYoutube } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './Footer.css'

const Footer = () => {
  const { getSetting } = useSettings()
  const { t } = useLanguage()

  const footer_email = getSetting('footer_email', 'info@edulearn.lk')
  const footer_phone = getSetting('footer_phone', '+94 114 477 488')
  const social_facebook = getSetting('social_facebook', '#facebook')
  const social_twitter = getSetting('social_twitter', '#twitter')
  const social_instagram = getSetting('social_instagram', '#instagram')
  const social_linkedin = getSetting('social_linkedin', '#linkedin')
  const social_youtube = getSetting('social_youtube', '#youtube')
  const footer_copyright = getSetting('footer_copyright', 'Copyrights © 2025 TiT. All rights reserved by TiT Online Education (PVT) Ltd.')

  const footer_description = getSetting('footer_description', "Sri Lanka's trusted leader in online tuition. We ensure student success through personalized learning and comprehensive support.")
  const footer_logo_text = getSetting('footer_logo_text', 'TiT')
  const footer_tagline = getSetting('footer_tagline', t('footer_your_education'))

  return (
    <footer className="footer">
      <div className="container">
        <div className="footer-content">
          <div className="footer-section">
            <h3 className="footer-logo">{footer_logo_text}</h3>
            <p className="footer-tagline">{footer_tagline}</p>
            <p className="footer-description">
              {footer_description}
            </p>
          </div>

          <div className="footer-section">
            <h4 className="footer-title">{t('footer_legal')}</h4>
            <ul className="footer-links">
              <li><a href={getSetting('footer_privacy_link', '#privacy')}>{t('footer_privacy')}</a></li>
              <li><a href={getSetting('footer_terms_link', '#terms')}>{t('footer_terms')}</a></li>
              <li><a href={getSetting('footer_refund_link', '#refund')}>{t('footer_refund')}</a></li>
            </ul>
          </div>

          <div className="footer-section">
            <h4 className="footer-title">{t('footer_quick_links')}</h4>
            <ul className="footer-links">
              <li><a href={getSetting('footer_gallery_link', '#gallery')}>{t('footer_gallery')}</a></li>
              <li><a href={getSetting('footer_tutor_link', '#tutor')}>{t('footer_apply_tutor')}</a></li>
              <li><a href={getSetting('footer_register_link', '#register')}>{t('footer_register_student')}</a></li>
            </ul>
          </div>

          <div className="footer-section">
            <h4 className="footer-title">{t('footer_contact')}</h4>
            <div className="contact-info">
              <a href={`mailto:${footer_email}`} className="contact-item">
                <FiMail />
                <span>{footer_email}</span>
              </a>
              <a href={`tel:${footer_phone.replace(/\s/g, '')}`} className="contact-item">
                <FiPhone />
                <span>{footer_phone}</span>
              </a>
            </div>
            <div className="social-links">
              <a href={social_facebook} aria-label="Facebook">
                <FiFacebook />
              </a>
              <a href={social_twitter} aria-label="Twitter">
                <FiTwitter />
              </a>
              <a href={social_instagram} aria-label="Instagram">
                <FiInstagram />
              </a>
              <a href={social_linkedin} aria-label="LinkedIn">
                <FiLinkedin />
              </a>
              <a href={social_youtube} aria-label="YouTube">
                <FiYoutube />
              </a>
            </div>
          </div>
        </div>

        <div className="footer-bottom">
          <p>{footer_copyright}</p>
        </div>
      </div>
    </footer>
  )
}

export default Footer


