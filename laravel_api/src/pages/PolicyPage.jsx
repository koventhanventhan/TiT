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

            const rawContent = getSetting(key, 'Content coming soon. Please check back later.');
            
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
