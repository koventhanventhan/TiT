import React from 'react'
import { FiArrowRight, FiBook, FiGlobe, FiTarget } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './Classes.css'

const Classes = () => {
  const { getSetting } = useSettings()

  const classes_title = getSetting('classes_title', 'Explore & Enroll')
  const classes_subtitle = getSetting('classes_subtitle', 'Online Tuition for all subjects - Grade 1 to Advanced Level. Group or one-on-one? We got you!')

  const rawTypes = getSetting('classes_types', '[]');
  let dynamicTypes = [];
  try {
    dynamicTypes = JSON.parse(rawTypes);
  } catch (e) {
    dynamicTypes = [];
  }

  const defaultIcons = [<FiBook />, <FiGlobe />, <FiTarget />];

  const classTypes = dynamicTypes.length > 0 ? dynamicTypes.map((type, index) => ({
    ...type,
    icon: defaultIcons[index] || <FiBook />
  })) : [
    {
      icon: <FiBook />,
      title: 'Direct Class',
      description: getSetting('classes_direct_description', 'Comprehensive face-to-face learning experience with expert tutors.'),
      color: 'gradient-1'
    },
    {
      icon: <FiGlobe />,
      title: 'Online Class',
      description: getSetting('classes_online_description', 'Interactive live sessions with high-quality digital resources.'),
      color: 'gradient-2'
    }
  ]

  return (
    <section id="classes" className="classes section">
      <div className="container">
        <div className="classes-header">
          <h2 className="section-title">{classes_title}</h2>
          <p className="section-subtitle">
            {classes_subtitle}
          </p>
        </div>

        <div className="classes-grid">
          {classTypes.map((classType, index) => (
            <div key={index} className={`class-card ${classType.color}`}>
              <div className="class-icon">{classType.icon}</div>
              <h3 className="class-title">{classType.title}</h3>
              <p className="class-description">{classType.description}</p>
              <a href="#register" className="class-link">
                Explore
                <FiArrowRight />
              </a>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

export default Classes


