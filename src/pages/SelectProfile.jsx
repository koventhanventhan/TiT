import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { FiUser, FiPlus } from 'react-icons/fi';
import '../components/AnimatedAuth.css'; // Reuse auth styles or define custom
import { getActiveStorage } from '../services/apiClient';

const SelectProfile = () => {
  const navigate = useNavigate();
  const [profiles, setProfiles] = useState([]);

  useEffect(() => {
    const savedProfiles = localStorage.getItem('availableProfiles');
    if (savedProfiles) {
      try {
        setProfiles(JSON.parse(savedProfiles));
      } catch (e) {
        console.error('Failed to parse availableProfiles', e);
        navigate('/');
      }
    } else {
      // If no profiles found, something went wrong or user only has one profile
      navigate('/student/dashboard');
    }
  }, [navigate]);

  const getAvatarUrl = (avatar) => {
    if (!avatar) return null;
    if (avatar.startsWith('http')) return avatar;
    const baseUrl = (import.meta.env.VITE_API_URL || '').replace(/\/api$/, '');
    return baseUrl ? `${baseUrl}${avatar}` : avatar;
  };

  const handleSelectProfile = (profile) => {
    const storage = getActiveStorage();
    storage.setItem('user', JSON.stringify(profile));
    
    // We should probably inform the backend that we're switching profile to get a profile-specific token?
    // Actually we decided to use the same token for now, and the token identifies the parent.
    // If the frontend needs to make requests for a specific child, it will use the `id` from the current `user` in localStorage.
    
    navigate('/student/dashboard');
  };

  return (
    <div className="select-profile-container" style={{ minHeight: '100vh', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', background: '#0f172a', padding: '20px' }}>
      <motion.div 
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        style={{ textAlign: 'center', marginBottom: '40px' }}
      >
        <h1 style={{ color: 'white', fontSize: '2.5rem', fontWeight: 'bold' }}>Who's learning today?</h1>
      </motion.div>

      <div style={{ display: 'flex', gap: '30px', flexWrap: 'wrap', justifyContent: 'center' }}>
        {profiles.map((profile, index) => (
          <motion.div
            key={profile.id}
            initial={{ opacity: 0, scale: 0.8 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ delay: index * 0.1 }}
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
            onClick={() => handleSelectProfile(profile)}
            style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              cursor: 'pointer',
              width: '150px'
            }}
          >
            <div style={{
              width: '120px',
              height: '120px',
              borderRadius: '20px',
              background: `linear-gradient(135deg, ${profile.id % 2 === 0 ? '#3b82f6, #1d4ed8' : '#8b5cf6, #6d28d9'})`,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              boxShadow: '0 10px 25px rgba(0,0,0,0.5)',
              border: '3px solid transparent',
              transition: 'border-color 0.3s',
              overflow: 'hidden'
            }}
            onMouseOver={(e) => e.currentTarget.style.borderColor = 'white'}
            onMouseOut={(e) => e.currentTarget.style.borderColor = 'transparent'}
            >
              {profile.avatar ? (
                <img 
                  src={getAvatarUrl(profile.avatar)} 
                  alt="Avatar" 
                  style={{ width: '100%', height: '100%', objectFit: 'cover' }} 
                />
              ) : (
                <FiUser size={60} color="white" />
              )}
            </div>
            <h3 style={{ color: 'white', marginTop: '15px', fontSize: '1.2rem', fontWeight: '500', textAlign: 'center' }}>
              {profile.full_name || profile.username}
            </h3>
            <span style={{ color: '#94a3b8', fontSize: '0.9rem' }}>Grade {profile.current_grade || '-'}</span>
          </motion.div>
        ))}

        {/* Option to add a sibling right from here, redirects to login/registration maybe? */}
        <motion.div
            initial={{ opacity: 0, scale: 0.8 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ delay: profiles.length * 0.1 }}
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
            onClick={() => navigate('/student/settings')} // or a specific route
            style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              cursor: 'pointer',
              width: '150px'
            }}
          >
            <div style={{
              width: '120px',
              height: '120px',
              borderRadius: '20px',
              border: '2px dashed #475569',
              background: 'transparent',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              transition: 'all 0.3s'
            }}
            onMouseOver={(e) => {
              e.currentTarget.style.borderColor = 'white';
              e.currentTarget.style.background = 'rgba(255,255,255,0.1)';
            }}
            onMouseOut={(e) => {
              e.currentTarget.style.borderColor = '#475569';
              e.currentTarget.style.background = 'transparent';
            }}
            >
              <FiPlus size={50} color="#94a3b8" />
            </div>
            <h3 style={{ color: '#94a3b8', marginTop: '15px', fontSize: '1.2rem', fontWeight: '500', textAlign: 'center' }}>
              Add Sibling
            </h3>
          </motion.div>
      </div>
    </div>
  );
};

export default SelectProfile;
