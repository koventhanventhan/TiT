import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useSettings } from '../context/SettingsContext';
import { useLanguage } from '../context/LanguageContext';
import { FiArrowLeft, FiShield, FiFileText, FiRefreshCw } from 'react-icons/fi';
import './PolicyPage.css';

const PolicyPage = ({ type: propType }) => {
    const { type: paramType } = useParams();
    const type = propType || paramType;
    const { getSetting, loading: settingsLoading } = useSettings();
    const { t, translate, language } = useLanguage();
    const [content, setContent] = useState('');
    const [title, setTitle] = useState('');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const loadContent = async () => {
            setLoading(true);
            let key = '';
            let defaultTitle = '';
            let icon = null;

            switch (type) {
                case 'privacy':
                    key = 'policy_privacy_content';
                    defaultTitle = t('footer_privacy') || 'Privacy Policy';
                    break;
                case 'terms':
                    key = 'policy_terms_content';
                    defaultTitle = t('footer_terms') || 'Terms & Conditions';
                    break;
                case 'refund':
                    key = 'policy_refund_content';
                    defaultTitle = t('footer_refund') || 'Refund Policy';
                    break;
                default:
                    key = 'policy_privacy_content';
                    defaultTitle = 'Policy';
            }

            const instituteName = getSetting('admin_company_name') || getSetting('site_name') || getSetting('company_name') || 'Our Institute';
            const contactEmail = getSetting('footer_email') || getSetting('contact_email') || '';
            const contactPhone = getSetting('footer_phone') || getSetting('contact_phone') || '';
            const contactLoc = getSetting('footer_location') || '';
            const contactDetails = [contactEmail, contactPhone, contactLoc].filter(Boolean).join(' • ');
            const contactText = contactDetails ? `\n\nContact Us:\nFor any questions regarding this policy, please reach out to ${instituteName} at ${contactDetails}.` : `\n\nContact Us:\nFor any questions regarding this policy, please reach out to ${instituteName}.`;

            let fallbackContent = '';
            if (type === 'privacy') {
                fallbackContent = `This Privacy Policy outlines how ${instituteName} collects, uses, and protects student and parent personal information across our online portal and educational services.\n\n1. Information Collection: We collect essential account and contact information necessary to manage student admissions, attendance, learning materials, and course communication.\n\n2. Data Security: Personal records and academic information are securely stored and restricted to authorized personnel.\n\n3. Information Sharing: We do not sell or disclose personal data to third parties except as required for service delivery (e.g. communication or payment processing).` + contactText;
            } else if (type === 'terms') {
                fallbackContent = `Welcome to ${instituteName}. By using our educational portal and attending classes, you agree to comply with and be bound by the following Terms and Conditions.\n\n1. Student Conduct: Students are expected to maintain academic integrity and professional conduct in both physical and virtual classrooms.\n\n2. Learning Materials: All study notes, lecture videos, and materials provided by ${instituteName} are intended solely for enrolled students and may not be redistributed.\n\n3. Attendance & Fees: Enrolled students must adhere to scheduled classes and submit tuition dues as scheduled.` + contactText;
            } else if (type === 'refund') {
                fallbackContent = `This Refund Policy governs fee payments and cancellations for courses and services provided by ${instituteName}.\n\n1. Tuition Fees: Monthly course fees are generally non-refundable once the academic term or monthly syllabus has commenced.\n\n2. Duplicate Payments: If an accidental duplicate or excess payment occurs, contact the administration within 7 days with your payment receipt for prompt verification and refund adjustment.\n\n3. Discretionary Review: Exceptions may be considered by institute management under verifiable extenuating circumstances.` + contactText;
            } else {
                fallbackContent = `Welcome to ${instituteName}.` + contactText;
            }

            const configuredContent = getSetting(key, '');
            const rawContent = (configuredContent && configuredContent.trim()) ? configuredContent : fallbackContent;
            
            if (language !== 'en') {
                setTitle(await translate(defaultTitle));
                setContent(await translate(rawContent));
            } else {
                setTitle(defaultTitle);
                setContent(rawContent);
            }
            setLoading(false);
        };

        if (!settingsLoading) {
            loadContent();
        }
    }, [type, language, getSetting, settingsLoading, t, translate]);

    const getIcon = () => {
        switch (type) {
            case 'privacy': return <FiShield />;
            case 'terms': return <FiFileText />;
            case 'refund': return <FiRefreshCw />;
            default: return <FiFileText />;
        }
    };

    if (loading || settingsLoading) {
        return (
            <div className="policy-loading">
                <div className="loader"></div>
                <p>Loading legal document...</p>
            </div>
        );
    }

    return (
        <div className="policy-page">
            <div className="policy-header-bg">
                <div className="container">
                    <Link to="/" className="back-link">
                        <FiArrowLeft /> Back to Home
                    </Link>
                    <div className="policy-title-section">
                        <div className="policy-icon">
                            {getIcon()}
                        </div>
                        <h1>{title}</h1>
                        <p className="last-updated">Last Updated: {new Date().toLocaleDateString()}</p>
                    </div>
                </div>
            </div>

            <div className="container">
                <div className="policy-content-card">
                    <div className="policy-text">
                        {(content || '').split('\n').map((paragraph, index) => (
                            paragraph.trim() ? <p key={index}>{paragraph}</p> : <br key={index} />
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default PolicyPage;
