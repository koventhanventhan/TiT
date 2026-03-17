import React from 'react'
import { FiFolder, FiZap, FiTarget, FiArrowRight } from 'react-icons/fi'
import './StudentToolkit.css'

const StudentToolkit = () => {
  const tools = [
    {
      icon: <FiFolder />,
      title: 'Resource Vault',
      description: 'Access comprehensive study materials, notes, and resources all in one place',
      gradient: 'gradient-1'
    },
    {
      icon: <FiZap />,
      title: 'AI Study Buddy',
      description: 'Get instant help with AI-powered study assistance and personalized learning support',
      gradient: 'gradient-2'
    },
    {
      icon: <FiTarget />,
      title: 'ThinkTank',
      description: 'Engage in brain games and puzzles to enhance critical thinking and problem-solving skills',
      gradient: 'gradient-3'
    }
  ]

  // return (
  //   <section id="learning-suite" className="student-toolkit section">
  //     <div className="container">
  //       <div className="toolkit-header">
  //         <h2 className="section-title">All-in-One Student Toolkit</h2>
  //         <p className="section-subtitle">
  //           Supercharge Your Studies - The Future of Learning is Here: Streamlined Resources, 
  //           AI-Powered Support & Engaging Brain Games
  //         </p>
  //       </div>

  //       <div className="tools-grid">
  //         {tools.map((tool, index) => (
  //           <div key={index} className={`tool-card ${tool.gradient}`}>
  //             <div className="tool-icon-wrapper">
  //               <div className="tool-icon">{tool.icon}</div>
  //             </div>
  //             <h3 className="tool-title">{tool.title}</h3>
  //             <p className="tool-description">{tool.description}</p>
  //             <a href="#explore" className="tool-link">
  //               Explore
  //               <FiArrowRight />
  //             </a>
  //           </div>
  //         ))}
  //       </div>
  //     </div>
  //   </section>
  // )
}

export default StudentToolkit

