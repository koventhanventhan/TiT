import React, { createContext, useContext, useState, useCallback, useEffect, useRef } from 'react';
import './ConfirmDialog.css';

const ConfirmContext = createContext(null);

export const ConfirmProvider = ({ children }) => {
  const [dialog, setDialog] = useState(null);
  const confirmBtnRef = useRef(null);
  const cancelBtnRef = useRef(null);
  const dialogRef = useRef(null);

  const confirm = useCallback(({
    title = 'Confirm Action',
    message = 'Are you sure you want to proceed?',
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    danger = false,
  }) => {
    return new Promise((resolve) => {
      setDialog({
        title,
        message,
        confirmText,
        cancelText,
        danger,
        resolve,
      });
    });
  }, []);

  const handleClose = useCallback((result) => {
    if (dialog && dialog.resolve) {
      dialog.resolve(result);
    }
    setDialog(null);
  }, [dialog]);

  useEffect(() => {
    window.__titConfirm = confirm;
    return () => {
      delete window.__titConfirm;
    };
  }, [confirm]);

  // Keyboard navigation & focus trap
  useEffect(() => {
    if (!dialog) return;

    // Focus initial button
    const timer = setTimeout(() => {
      if (dialog.danger && cancelBtnRef.current) {
        cancelBtnRef.current.focus();
      } else if (confirmBtnRef.current) {
        confirmBtnRef.current.focus();
      }
    }, 50);

    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        e.preventDefault();
        handleClose(false);
      } else if (e.key === 'Enter') {
        if (document.activeElement === cancelBtnRef.current) {
          e.preventDefault();
          handleClose(false);
        } else {
          e.preventDefault();
          handleClose(true);
        }
      } else if (e.key === 'Tab') {
        const focusable = [cancelBtnRef.current, confirmBtnRef.current].filter(Boolean);
        if (focusable.length < 2) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => {
      clearTimeout(timer);
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, [dialog, handleClose]);

  return (
    <ConfirmContext.Provider value={confirm}>
      {children}
      {dialog && (
        <div
          className="tit-confirm-backdrop"
          onClick={() => handleClose(false)}
          role="presentation"
        >
          <div
            ref={dialogRef}
            className="tit-confirm-modal"
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="tit-confirm-title"
            aria-describedby="tit-confirm-message"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="tit-confirm-header">
              <h3 id="tit-confirm-title" className="tit-confirm-title">
                {dialog.title}
              </h3>
            </div>
            <div className="tit-confirm-body">
              <p id="tit-confirm-message" className="tit-confirm-message">
                {dialog.message}
              </p>
            </div>
            <div className="tit-confirm-footer">
              <button
                ref={cancelBtnRef}
                type="button"
                className="tit-confirm-btn tit-confirm-btn-cancel"
                onClick={() => handleClose(false)}
              >
                {dialog.cancelText}
              </button>
              <button
                ref={confirmBtnRef}
                type="button"
                className={`tit-confirm-btn ${dialog.danger ? 'tit-confirm-btn-danger' : 'tit-confirm-btn-primary'}`}
                onClick={() => handleClose(true)}
              >
                {dialog.confirmText}
              </button>
            </div>
          </div>
        </div>
      )}
    </ConfirmContext.Provider>
  );
};

export const useConfirm = () => {
  const context = useContext(ConfirmContext);
  if (!context) {
    throw new Error('useConfirm must be used within a ConfirmProvider');
  }
  return context;
};

export default ConfirmProvider;
