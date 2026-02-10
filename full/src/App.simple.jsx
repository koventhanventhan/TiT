// Simple test version - uncomment this and comment out the main App.jsx to test
import React from 'react'

function App() {
  return (
    <div style={{ 
      padding: '50px', 
      background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      minHeight: '100vh',
      color: 'white',
      textAlign: 'center',
      fontFamily: 'Arial, sans-serif'
    }}>
      <h1 style={{ fontSize: '48px', marginBottom: '20px' }}>✅ React is Working!</h1>
      <p style={{ fontSize: '24px' }}>If you see this, React is rendering correctly.</p>
      <p style={{ fontSize: '18px', marginTop: '20px' }}>The issue is likely in one of the components.</p>
    </div>
  )
}

export default App

