import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App'
import './index.css'

console.log('🚀 React is starting...')
console.log('Root element:', document.getElementById('root'))

// Error Boundary Component
class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props)
    this.state = { hasError: false, error: null }
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error }
  }

  componentDidCatch(error, errorInfo) {
    console.error('❌ React Error:', error, errorInfo)
  }

  render() {
    if (this.state.hasError) {
      return (
        <div style={{ 
          padding: '3.125rem', 
          textAlign: 'center', 
          color: 'red', 
          fontFamily: 'Arial',
          background: '#fff',
          minHeight: '100vh'
        }}>
          <h1>Error Loading App</h1>
          <p>{this.state.error?.message || 'Unknown error'}</p>
          <pre style={{ 
            textAlign: 'left', 
            background: '#f5f5f5', 
            padding: '1.25rem', 
            borderRadius: '0.5rem', 
            overflow: 'auto',
            maxWidth: '50rem',
            margin: '1.25rem auto'
          }}>
            {this.state.error?.stack}
          </pre>
          <p style={{ marginTop: '1.25rem' }}>Check the browser console (F12) for more details.</p>
          <button 
            onClick={() => window.location.reload()} 
            style={{ 
              marginTop: '1.25rem', 
              padding: '0.625rem 1.25rem', 
              background: '#1e40af', 
              color: 'white', 
              border: 'none', 
              borderRadius: '0.3125rem', 
              cursor: 'pointer' 
            }}
          >
            Reload Page
          </button>
        </div>
      )
    }

    return this.props.children
  }
}

try {
  const rootElement = document.getElementById('root')
  if (!rootElement) {
    throw new Error('Root element not found!')
  }

  const root = ReactDOM.createRoot(rootElement)
  console.log('✅ Root created')
  
  root.render(
    <React.StrictMode>
      <ErrorBoundary>
        <App />
      </ErrorBoundary>
    </React.StrictMode>
  )
  console.log('✅ App rendered')
} catch (error) {
  console.error('❌ Error rendering app:', error)
  const rootElement = document.getElementById('root')
  if (rootElement) {
    rootElement.innerHTML = `
      <div style="padding: 3.125rem; text-align: center; color: red; font-family: Arial;">
        <h1>Error Loading App</h1>
        <p>${error.message}</p>
        <pre style="text-align: left; background: #f5f5f5; padding: 1.25rem; border-radius: 0.5rem; overflow: auto; max-width: 50rem; margin: 1.25rem auto;">${error.stack}</pre>
        <p style="margin-top: 1.25rem;">Check the browser console (F12) for more details.</p>
        <button onclick="window.location.reload()" style="margin-top: 1.25rem; padding: 0.625rem 1.25rem; background: #1e40af; color: white; border: none; border-radius: 0.3125rem; cursor: pointer;">Reload Page</button>
      </div>
    `
  }
}

