import React, { useState, useEffect } from 'react';
import { FiX, FiSave } from 'react-icons/fi';
import { useToast } from '../shared/ToastContext';
import { getSettings, updateSettings } from '../../services/adminService';

const GRADES = [
  'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5',
  'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10',
  'Grade 11', 'Grade 12', 'Grade 13'
];

export default function ZoomRecordingSettingsModal({ isOpen, onClose }) {
  const toast = useToast();
  const [disabledGrades, setDisabledGrades] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (isOpen) {
      loadSettings();
    }
  }, [isOpen]);

  const loadSettings = async () => {
    try {
      const res = await getSettings();
      if (res.zoom_recordings_disabled_grades) {
        setDisabledGrades(JSON.parse(res.zoom_recordings_disabled_grades));
      }
    } catch (err) {
      console.error(err);
      toast.error('Failed to load recording settings');
    }
  };

  const handleToggle = (grade) => {
    if (disabledGrades.includes(grade)) {
      setDisabledGrades(disabledGrades.filter(g => g !== grade));
    } else {
      setDisabledGrades([...disabledGrades, grade]);
    }
  };

  const handleSave = async () => {
    setLoading(true);
    try {
      await updateSettings({
        settings: {
          zoom_recordings_disabled_grades: JSON.stringify(disabledGrades)
        }
      });
      toast.success('Recording settings updated successfully');
      onClose();
    } catch (err) {
      console.error(err);
      toast.error('Failed to update recording settings');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="modal-overlay" style={{
      position: 'fixed', top: 0, left: 0, width: '100%', height: '100%',
      backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1000, display: 'flex',
      alignItems: 'center', justifyContent: 'center'
    }}>
      <div className="modal-content" style={{
        backgroundColor: '#fff', borderRadius: '8px', padding: '2rem',
        width: '90%', maxWidth: '500px', maxHeight: '90vh', overflowY: 'auto'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
          <h2 style={{ margin: 0, fontSize: '1.5rem', color: '#1e293b' }}>Automated Zoom Recordings</h2>
          <button onClick={onClose} style={{ background: 'none', border: 'none', cursor: 'pointer', fontSize: '1.5rem', color: '#64748b' }}>
            <FiX />
          </button>
        </div>

        <p style={{ color: '#64748b', marginBottom: '1.5rem' }}>
          Enable or disable automatic fetching of Zoom Cloud Recordings for students per grade.
        </p>

        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginBottom: '2rem' }}>
          {GRADES.map(grade => (
            <label key={grade} style={{
              display: 'flex', alignItems: 'center', gap: '0.5rem',
              padding: '0.75rem', border: '1px solid #e2e8f0', borderRadius: '4px', cursor: 'pointer'
            }}>
              <input
                type="checkbox"
                checked={!disabledGrades.includes(grade)}
                onChange={() => handleToggle(grade)}
                style={{ cursor: 'pointer', width: '16px', height: '16px' }}
              />
              <span style={{ color: '#334155', fontWeight: '500' }}>{grade}</span>
            </label>
          ))}
        </div>

        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '1rem' }}>
          <button onClick={onClose} style={{
            padding: '0.5rem 1rem', background: '#f1f5f9', color: '#475569', border: 'none', borderRadius: '4px', cursor: 'pointer'
          }}>
            Cancel
          </button>
          <button onClick={handleSave} disabled={loading} style={{
            display: 'flex', alignItems: 'center', gap: '0.5rem',
            padding: '0.5rem 1rem', background: '#3b82f6', color: '#fff', border: 'none', borderRadius: '4px', cursor: 'pointer'
          }}>
            <FiSave /> {loading ? 'Saving...' : 'Save Settings'}
          </button>
        </div>
      </div>
    </div>
  );
}
