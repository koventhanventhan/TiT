import React from 'react'
import './StudentToolkit.css'

const StudentToolkit = () => {
  const tools = [
    {
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/fkdkvhlp.json"
          trigger="hover"
          colors="primary:#4f0bd9,secondary:#1a103c"
          style={{ width: '40px', height: '40px' }}
        />
      ),
      title: 'Resource Vault',
      description: 'Access a limitless library of study materials, past papers, and expert notes curated for your success.',
      tag: 'Academic',
      color: '#4f0bd9'
    },
    {
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/vhyenuev.json"
          trigger="hover"
          colors="primary:#4f0bd9,secondary:#1a103c"
          style={{ width: '40px', height: '40px' }}
        />
      ),
      title: 'AI Study Buddy',
      description: 'Get 24/7 assistance with your homework and complex concepts using our advanced AI tutor.',
      tag: 'Technology',
      color: '#10b981'
    },
    {
      id: 'target',
      icon: (
        <lord-icon
          src="https://cdn.lordicon.com/mrdfeebn.json"
          trigger="hover"
          colors="primary:#4f0bd9,secondary:#1a103c"
          style={{ width: '40px', height: '40px' }}
        />
      ),
      title: 'ThinkTank Hub',
      description: 'Challenge your brain with interactive puzzles and games designed to sharpen your critical thinking.',
      tag: 'Critical Thinking',
      color: '#f59e0b'
    }
  ]

  return (
    <section id="learning-suite" className="premium-toolkit section">
      <div className="container">
        <div className="st-header">
          <span className="st-tag">Smart Ecosystem</span>
          <h2 className="st-title">All-in-One Student Toolkit</h2>
          <p className="st-subtitle">
            Supercharge your learning with integrated tools designed to streamline your studies,
            provide instant support, and engage your mind.
          </p>
        </div>

        <div className="st-grid">
          {tools.map((tool, index) => (
            <div key={index} className="st-card" style={{ '--tool-color': tool.color }}>
              <div className="st-card-glow"></div>
              <div className="st-card-content">
                <div className="st-card-top">
                  <div className="st-icon-box">
                    {tool.icon}
                  </div>
                  <span className="st-category-tag">{tool.tag}</span>
                </div>

                <h3 className="st-card-title">{tool.title}</h3>
                <p className="st-card-desc">{tool.description}</p>

                <div className="st-card-footer">
                  <a href="#explore" className="st-link">
                    Explore Tool{' '}
                    <lord-icon
                      src="https://cdn.lordicon.com/vduvxpxl.json"
                      trigger="hover"
                      colors="primary:#4f0bd9"
                      style={{ width: '18px', height: '18px' }}
                    />
                  </a>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

export default StudentToolkit
