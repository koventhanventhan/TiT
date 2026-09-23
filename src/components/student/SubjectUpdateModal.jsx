import React, { useState, useEffect, useMemo } from 'react';
import { FiX, FiCheck } from 'react-icons/fi';
import { updateStudentSubjects } from '../../services/dashboardService';

export default function SubjectUpdateModal({ isOpen, onClose, currentGrade, medium, initialSubjects = [], onUpdateSuccess }) {
  const [availableSubjects, setAvailableSubjects] = useState([]);
  const [selectedSubjects, setSelectedSubjects] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Normalize initialSubjects if it comes as JSON string
  const normalizedInitialSubjects = useMemo(() => {
    if (typeof initialSubjects === 'string') {
      try {
        return JSON.parse(initialSubjects);
      } catch (e) {
        return [];
      }
    }
    return Array.isArray(initialSubjects) ? initialSubjects : [];
  }, [initialSubjects]);

  useEffect(() => {
    setSelectedSubjects(normalizedInitialSubjects);
  }, [normalizedInitialSubjects, isOpen]);

  useEffect(() => {
    if (isOpen) {
      fetchSubjects();
    }
  }, [isOpen, currentGrade, medium]);

  const handleToggle = (subjectName) => {
    setSelectedSubjects(prev => {
      if (prev.includes(subjectName)) {
        return prev.filter(s => s !== subjectName);
      } else {
        return [...prev, subjectName];
      }
    });
    setError('');
  };

  const getNumericGrade = (gradeValue) => {
    if (!gradeValue) return null;
    if (typeof gradeValue === 'number') return gradeValue;
    const match = gradeValue.toString().match(/(\d+)/);
    return match ? parseInt(match[1], 10) : null;
  };

  const fetchSubjects = async () => {
    try {
      const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';
      const res = await fetch(`${API_BASE_URL}/subjects/prices`);
      if (res.ok) {
        const data = await res.json();
        
        const gradeNum = getNumericGrade(currentGrade);
        let subs = [];
        if (gradeNum >= 1 && gradeNum <= 2) subs = data['grade_1_to_2'] || [];
        else if (gradeNum === 3) subs = data['grade_3'] || [];
        else if (gradeNum === 4) subs = data['grade_4'] || [];
        else if (gradeNum === 5) subs = data['grade_5'] || [];
        else if (gradeNum >= 6 && gradeNum <= 9) subs = data['grade_6_to_9'] || [];
        else if (gradeNum >= 10 && gradeNum <= 11) subs = data['grade_10_to_11'] || [];
        else if (gradeNum >= 12 && gradeNum <= 13) {
            subs = [
                ...(data['arts_stream'] || []),
                ...(data['bio_maths_stream'] || []),
                ...(data['commerce_stream'] || []),
                ...(data['tech_stream'] || [])
            ];
        }

        if (medium) {
          subs = subs.filter(s => s.medium === medium || s.medium === 'both');
        }

        // Deduplicate
        const uniqueSubs = [];
        const seen = new Set();
        for (const s of subs) {
          if (!seen.has(s.name)) {
            seen.add(s.name);
            uniqueSubs.push(s);
          }
        }
        setAvailableSubjects(uniqueSubs);
      }
    } catch (err) {
      console.error('Failed to fetch subjects:', err);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (selectedSubjects.length === 0) {
      setError('Please select at least one subject.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      await updateStudentSubjects(selectedSubjects);
      onUpdateSuccess(selectedSubjects);
    } catch (err) {
      setError(err.message || 'Failed to update subjects.');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div style={{
      position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
      background: 'rgba(15,23,42,0.6)', backdropFilter: 'blur(4px)',
      display: 'flex', alignItems: 'center', justifyContent: 'center',
      zIndex: 9999, padding: 20
    }}>
      <div style={{
        background: '#fff', borderRadius: 24, width: '100%', maxWidth: 500,
        boxShadow: '0 25px 50px -12px rgba(0,0,0,0.25)', overflow: 'hidden',
        display: 'flex', flexDirection: 'column', maxHeight: '90vh',
        margin: '16px'
      }}>
        <div style={{
          padding: '20px', borderBottom: '1px solid #f1f5f9',
          display: 'flex', alignItems: 'center', justifyContent: 'space-between'
        }}>
          <div>
            <h2 style={{ margin: 0, fontSize: 20, fontWeight: 700, color: '#1e293b' }}>Update Your Subjects</h2>
            <p style={{ margin: '4px 0 0', fontSize: 14, color: '#64748b' }}>For {currentGrade} ({medium || 'All'} Medium)</p>
          </div>
          <button onClick={onClose} style={{
            background: '#f1f5f9', border: 'none', width: 36, height: 36,
            borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center',
            color: '#64748b', cursor: 'pointer', transition: 'all 0.2s'
          }} onMouseEnter={e => e.currentTarget.style.background = '#e2e8f0'}
             onMouseLeave={e => e.currentTarget.style.background = '#f1f5f9'}>
            <FiX size={18} />
          </button>
        </div>

        <div style={{ padding: '20px', overflowY: 'auto' }}>
          {error && (
            <div style={{ background: '#fef2f2', color: '#ef4444', padding: '12px 16px', borderRadius: 12, fontSize: 14, marginBottom: 20, fontWeight: 500 }}>
              {error}
            </div>
          )}

          <p style={{ fontSize: 15, color: '#475569', marginBottom: 20, lineHeight: 1.5 }}>
            Please review and select the subjects you will be taking in your new grade. 
            Any valid subjects from your previous grade have been pre-selected.
          </p>

          <div style={{ display: 'grid', gap: 12 }}>
            {availableSubjects.length > 0 ? availableSubjects.map((sub, idx) => {
              const isSelected = selectedSubjects.includes(sub.name);
              return (
                <label key={idx} style={{
                  display: 'flex', alignItems: 'center', gap: 14, padding: '16px',
                  border: `2px solid ${isSelected ? '#6366f1' : '#e2e8f0'}`,
                  borderRadius: 16, cursor: 'pointer', transition: 'all 0.2s',
                  background: isSelected ? '#f5f3ff' : '#fff'
                }}>
                  <div style={{
                    width: 24, height: 24, borderRadius: 6,
                    border: `2px solid ${isSelected ? '#6366f1' : '#cbd5e1'}`,
                    background: isSelected ? '#6366f1' : '#fff',
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    color: '#fff', transition: 'all 0.2s'
                  }}>
                    {isSelected && <FiCheck size={16} strokeWidth={3} />}
                  </div>
                  <input type="checkbox" checked={isSelected} onChange={() => handleToggle(sub.name)} style={{ display: 'none' }} />
                  <div>
                    <div style={{ fontSize: 15, fontWeight: 600, color: isSelected ? '#4f46e5' : '#334155' }}>
                      {sub.name}
                    </div>
                    {sub.name_ta && <div style={{ fontSize: 13, color: '#64748b', marginTop: 2 }}>{sub.name_ta}</div>}
                  </div>
                </label>
              );
            }) : (
              <div style={{ textAlign: 'center', padding: '30px', color: '#94a3b8', fontSize: 14 }}>
                Loading subjects...
              </div>
            )}
          </div>
        </div>

        <div style={{ padding: '20px', borderTop: '1px solid #f1f5f9', background: '#f8fafc', display: 'flex', justifyContent: 'flex-end', gap: 12 }}>
          <button onClick={onClose} style={{
            padding: '12px 24px', borderRadius: 12, border: '1px solid #cbd5e1',
            background: '#fff', color: '#475569', fontSize: 15, fontWeight: 600, cursor: 'pointer'
          }}>Cancel</button>
          
          <button onClick={handleSubmit} disabled={loading} style={{
            padding: '12px 28px', borderRadius: 12, border: 'none',
            background: 'linear-gradient(135deg, #6366f1, #4f46e5)',
            color: '#fff', fontSize: 15, fontWeight: 600, cursor: loading ? 'not-allowed' : 'pointer',
            opacity: loading ? 0.7 : 1, display: 'flex', alignItems: 'center', gap: 8
          }}>
            {loading ? 'Saving...' : 'Save Subjects'}
          </button>
        </div>
      </div>
    </div>
  );
}
