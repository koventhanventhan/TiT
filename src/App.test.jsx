// Temporary test file to check if React is working
import React from 'react'

function TestApp() {
  return (
    <div style={{ 
      padding: '3.125rem', 
      background: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
      minHeight: '100vh',
      color: 'white',
      textAlign: 'center',
      fontFamily: 'Arial, sans-serif'
    }}>
      <h1 style={{ fontSize: '3rem', marginBottom: '1.25rem' }}>✅ React is Working!</h1>
      <p style={{ fontSize: '1.5rem' }}>If you see this, React is rendering correctly.</p>
      <p style={{ fontSize: '1.125rem', marginTop: '1.25rem' }}>The issue is likely in one of the components.</p>
    </div>
  )
}

export default TestApp
