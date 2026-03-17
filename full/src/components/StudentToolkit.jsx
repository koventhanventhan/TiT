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


}

export default StudentToolkit

