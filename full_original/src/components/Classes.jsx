import React from 'react'
import { FiArrowRight, FiBook, FiGlobe, FiTarget } from 'react-icons/fi'
import './Classes.css'

const Classes = () => {
  const classTypes = [
    {
      icon: <FiBook />,
      title: 'Sri Lankan National Syllabus',
      description: 'Comprehensive coverage of the national curriculum from Grade 1 to Advanced Level',
      color: 'gradient-1'
    },
    {
      icon: <FiGlobe />,
      title: 'Cambridge / EDEXCEL Syllabus',
      description: 'International curriculum support for Cambridge and EDEXCEL qualifications',
      color: 'gradient-2'
    },
    {
      icon: <FiTarget />,
      title: 'Essential Skill Development',
      description: 'Build critical thinking, problem-solving, and communication skills',
      color: 'gradient-3'
    }
  ]

  return (
    <section id="classes" className="classes section">
      <div className="container">
        <div className="classes-header">
          <h2 className="section-title">Explore & Enroll</h2>
          <p className="section-subtitle">
            Online Tuition for all subjects - Grade 1 to Advanced Level. 
            Group or one-on-one? We got you!
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

