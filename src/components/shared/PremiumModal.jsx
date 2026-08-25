import React, { useEffect, useState, useRef } from 'react';
import { FiX } from 'react-icons/fi';
import './PremiumModal.css';

const PremiumModal = ({ isOpen, onClose, title, children, maxWidth = '500px' }) => {
  const [isRendered, setIsRendered] = useState(isOpen);
  const [isVisible, setIsVisible] = useState(false);
  const modalRef = useRef(null);
  const touchStartY = useRef(0);
  const currentY = useRef(0);

  useEffect(() => {
    if (isOpen) {
      setIsRendered(true);
      // Small delay to allow for CSS transition
      setTimeout(() => setIsVisible(true), 10);
      document.body.style.overflow = 'hidden';
    } else {
      setIsVisible(false);
      setTimeout(() => {
        setIsRendered(false);
        document.body.style.overflow = '';
      }, 300); // match transition duration
    }

    return () => {
      document.body.style.overflow = '';
    };
  }, [isOpen]);

  // Swipe to dismiss logic for mobile bottom sheet
  const handleTouchStart = (e) => {
    touchStartY.current = e.touches[0].clientY;
  };

  const handleTouchMove = (e) => {
    currentY.current = e.touches[0].clientY;
    const diff = currentY.current - touchStartY.current;
    if (diff > 0 && modalRef.current && window.innerWidth <= 767) {
      modalRef.current.style.transform = `translateY(${diff}px)`;
    }
  };

  const handleTouchEnd = () => {
    const diff = currentY.current - touchStartY.current;
    if (modalRef.current) {
      modalRef.current.style.transform = ''; // reset transform
    }
    // If swiped down more than 100px, close modal
    if (diff > 100) {
      onClose();
    }
  };

  if (!isRendered) return null;

  return (
    <div className={`tit-premium-modal-overlay ${isVisible ? 'visible' : ''}`} onClick={onClose}>
      <div
        className={`tit-premium-modal-content ${isVisible ? 'visible' : ''}`}
        style={{ maxWidth }}
        onClick={(e) => e.stopPropagation()}
        ref={modalRef}
      >
        <div 
          className="tit-mobile-drag-handle" 
          onTouchStart={handleTouchStart}
          onTouchMove={handleTouchMove}
          onTouchEnd={handleTouchEnd}
        >
          <div className="tit-drag-indicator"></div>
        </div>

        <div className="tit-premium-modal-header">
          {title && <h2 className="tit-premium-modal-title">{title}</h2>}
          <button className="tit-premium-modal-close" onClick={onClose} aria-label="Close">
            <FiX />
          </button>
        </div>
        
        <div className="tit-premium-modal-body">
          {children}
        </div>
      </div>
    </div>
  );
};

export default PremiumModal;
