import React, { createContext, useContext, useState, useCallback } from 'react';
import { FiCheckCircle, FiAlertCircle, FiInfo, FiX, FiAlertTriangle } from 'react-icons/fi';
import './ToastContext.css';

const ToastContext = createContext(null);

export const ToastProvider = ({ children }) => {
  const [toasts, setToasts] = useState([]);

  const addToast = useCallback((message, type = 'info', duration = 4000) => {
    const id = Date.now() + Math.random();
    setToasts((prev) => [...prev, { id, message, type }]);

    if (duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, duration);
    }
  }, []);

  const removeToast = useCallback((id) => {
    setToasts((prev) => prev.filter((toast) => toast.id !== id));
  }, []);

  const toast = {
    success: (msg, duration) => addToast(msg, 'success', duration || 4000),
    error: (msg, duration) => addToast(msg, 'error', duration || 8000),
    info: (msg, duration) => addToast(msg, 'info', duration || 4000),
    warning: (msg, duration) => addToast(msg, 'warning', duration || 6000),
  };

  React.useEffect(() => {
    window.__titToast = toast;
    return () => {
      delete window.__titToast;
    };
  }, [addToast]);

  return (
    <ToastContext.Provider value={toast}>
      {children}
      <div className="tit-toast-container" role="region" aria-label="Notifications">
        {toasts.map((t) => {
          const isAlert = t.type === 'error' || t.type === 'warning';
          return (
            <div
              key={t.id}
              className={`tit-toast tit-toast-${t.type} animate-toast`}
              role={isAlert ? 'alert' : 'status'}
              aria-live={isAlert ? 'assertive' : 'polite'}
            >
              <div className="tit-toast-icon">
                {t.type === 'success' && <FiCheckCircle />}
                {t.type === 'error' && <FiAlertCircle />}
                {t.type === 'info' && <FiInfo />}
                {t.type === 'warning' && <FiAlertTriangle />}
              </div>
              <div className="tit-toast-message">{t.message}</div>
              <button
                type="button"
                className="tit-toast-close"
                onClick={() => removeToast(t.id)}
                aria-label="Close notification"
              >
                <FiX />
              </button>
            </div>
          );
        })}
      </div>
    </ToastContext.Provider>
  );
};

export const useToast = () => {
  const context = useContext(ToastContext);
  if (!context) {
    throw new Error('useToast must be used within a ToastProvider');
  }
  return context;
};

export default ToastProvider;
