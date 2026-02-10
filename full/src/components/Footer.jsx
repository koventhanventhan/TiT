import React from 'react'
import { FiMail, FiPhone, FiFacebook, FiTwitter, FiInstagram, FiLinkedin } from 'react-icons/fi'
import './Footer.css'

const Footer = () => {
  return (
    <footer id="contact" className="footer">
      <div className="container">
        <div className="footer-content">
          <div className="footer-section">
            <h3 className="footer-logo">EduLearn</h3>
            <p className="footer-tagline">Your Education..! Our Priority..!</p>
            <p className="footer-description">
              Sri Lanka's trusted leader in online tuition. We ensure student success 
              through personalized learning and comprehensive support.
            </p>
          </div>

          <div className="footer-section">
            <h4 className="footer-title">Legal</h4>
            <ul className="footer-links">
              <li><a href="#privacy">Privacy Policy</a></li>
              <li><a href="#terms">Terms & Conditions</a></li>
              <li><a href="#refund">Refund Policy</a></li>
            </ul>
          </div>

          <div className="footer-section">
            <h4 className="footer-title">Quick Links</h4>
            <ul className="footer-links">
              <li><a href="#gallery">Gallery</a></li>
              <li><a href="#tutor">Apply as a Tutor</a></li>
              <li><a href="#register">Register as a student</a></li>
            </ul>
          </div>

          <div className="footer-section">
            <h4 className="footer-title">Contact</h4>
            <div className="contact-info">
              <a href="mailto:info@edulearn.lk" className="contact-item">
                <FiMail />
                <span>info@edulearn.lk</span>
              </a>
              <a href="tel:+94114477488" className="contact-item">
                <FiPhone />
                <span>+94 114 477 488</span>
              </a>
            </div>
            <div className="social-links">
              <a href="#facebook" aria-label="Facebook">
                <FiFacebook />
              </a>
              <a href="#twitter" aria-label="Twitter">
                <FiTwitter />
              </a>
              <a href="#instagram" aria-label="Instagram">
                <FiInstagram />
              </a>
              <a href="#linkedin" aria-label="LinkedIn">
                <FiLinkedin />
              </a>
            </div>
          </div>
        </div>

        <div className="footer-bottom">
          <p>Copyrights © 2025 EduLearn. All rights reserved by EduLearn Lanka (PVT) Ltd.</p>
        </div>
      </div>
    </footer>
  )
}

export default Footer

