import React from 'react';
import './InstituteBlockedNotice.css';

export default function InstituteBlockedNotice({ blockedData }) {
  const isExpired = blockedData?.code === 'institute_expired';
  const instituteName = blockedData?.institute_name || 'Institute';
  const logoUrl = blockedData?.institute_logo_url || null;
  const contact = blockedData?.contact || {};
  const email = contact.email || null;
  const phone = contact.phone || null;
  const whatsapp = contact.whatsapp || null;
  const hasContact = Boolean(email || phone || whatsapp);

  const title = isExpired
    ? 'Subscription has ended'
    : 'This institute is temporarily unavailable';

  const description = isExpired
    ? 'The subscription for this institute has ended. Public access and student portals are currently unavailable. If you are an administrator, please sign in or contact the platform owner to renew.'
    : 'This institute is temporarily unavailable. Public access is currently paused. Please check back later or contact the administrator for more information.';

  React.useEffect(() => {
    let metaTag = document.querySelector('meta[name="robots"]');
    let created = false;
    if (!metaTag) {
      metaTag = document.createElement('meta');
      metaTag.setAttribute('name', 'robots');
      document.head.appendChild(metaTag);
      created = true;
    }
    const previousContent = metaTag.getAttribute('content');
    metaTag.setAttribute('content', 'noindex, nofollow');

    return () => {
      if (created) {
        metaTag.remove();
      } else if (previousContent !== null) {
        metaTag.setAttribute('content', previousContent);
      } else {
        metaTag.removeAttribute('content');
      }
    };
  }, []);

  return (
    <div className="institute-blocked-container">
      <div
        className="institute-blocked-card"
        role="alert"
        aria-live="assertive"
      >
        {logoUrl && (
          <img
            src={logoUrl}
            alt={instituteName}
            className="institute-blocked-logo"
          />
        )}
        <div className="institute-blocked-name">{instituteName}</div>

        <div
          className={`institute-blocked-icon ${
            isExpired ? 'institute-blocked-icon-expired' : 'institute-blocked-icon-suspended'
          }`}
        >
          {isExpired ? '⏳' : '🔒'}
        </div>

        <h1 className="institute-blocked-title">{title}</h1>

        <p className="institute-blocked-desc">{description}</p>

        {hasContact && (
          <div className="institute-blocked-contact-box">
            <div className="institute-blocked-contact-title">Platform Support</div>
            {email && (
              <div className="institute-blocked-contact-item">
                <span>Email:</span>
                <a href={`mailto:${email}`}>{email}</a>
              </div>
            )}
            {phone && (
              <div className="institute-blocked-contact-item">
                <span>Phone:</span>
                <a href={`tel:${phone}`}>{phone}</a>
              </div>
            )}
            {whatsapp && (
              <div className="institute-blocked-contact-item">
                <span>WhatsApp:</span>
                <a
                  href={`https://wa.me/${whatsapp.replace(/\D/g, '')}`}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {whatsapp}
                </a>
              </div>
            )}
          </div>
        )}

        <div className="institute-blocked-footer-note">
          If you are an administrator of this institute, you may still access the administration portal to view your subscription details.
        </div>
      </div>
    </div>
  );
}
