import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App'
import './index.css'

console.log('🚀 React is starting...')
console.log('Root element:', document.getElementById('root'))

try {
  const root = ReactDOM.createRoot(document.getElementById('root'))
  console.log('✅ Root created')
  
  root.render(
    <React.StrictMode>
      <App />
    </React.StrictMode>
  )
  console.log('✅ App rendered')
} catch (error) {
  console.error('❌ Error rendering app:', error)
  document.getElementById('root').innerHTML = `
    <div style="padding: 50px; text-align: center; color: red;">
      <h1>Error Loading App</h1>
      <p>${error.message}</p>
      <pre>${error.stack}</pre>
    </div>
  `
}

