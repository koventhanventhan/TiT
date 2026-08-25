import React, { createContext, useContext, useState, useCallback } from 'react';
import { FiCheckCircle, FiAlertCircle, FiInfo, FiX, FiAlertTriangle } from 'react-icons/fi';
import './ToastContext.css';

const ToastContext = createContext(null);

export const ToastProvider = ({ children }) => {
  const [toasts, setToasts] = useState([]);

  const addToast = useCallback((message, type = 'info', duration = 3000) => {
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
    success: (msg, duration) => addToast(msg, 'success', duration),
    error: (msg, duration) => addToast(msg, 'error', duration),
    info: (msg, duration) => addToast(msg, 'info', duration),
    warning: (msg, duration) => addToast(msg, 'warning', duration),
  };

  return (
    <ToastContext.Provider value={toast}>
      {children}
      <div className="tit-toast-container">
        {toasts.map((toast) => (
          <div key={toast.id} className={`tit-toast tit-toast-${toast.type} animate-toast`}>
            <div className="tit-toast-icon">
              {toast.type === 'success' && <FiCheckCircle />}
              {toast.type === 'error' && <FiAlertCircle />}
              {toast.type === 'info' && <FiInfo />}
              {toast.type === 'warning' && <FiAlertTriangle />}
            </div>
            <div className="tit-toast-message">{toast.message}</div>
            <button className="tit-toast-close" onClick={() => removeToast(toast.id)}>
              <FiX />
            </button>
            <div className="tit-toast-progress"></div>
          </div>
        ))}
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
