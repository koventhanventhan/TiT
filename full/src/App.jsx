import React from 'react'
import Header from './components/Header'
import Hero from './components/Hero'
import About from './components/About'
import Classes from './components/Classes'
import WhyChooseUs from './components/WhyChooseUs'
import MobileApp from './components/MobileApp'
import Onboarding from './components/Onboarding'
import Testimonials from './components/Testimonials'
import StudentToolkit from './components/StudentToolkit'
import Accreditations from './components/Accreditations'
import Footer from './components/Footer'
import './App.css'

console.log('📦 App.jsx loaded')
console.log('🎨 Rendering App component...')

function App() {
  return (
    <div className="App" style={{ minHeight: '100vh', background: '#ffffff', width: '100%' }}>
      <Header />
      <Hero />
      <About />
      <Classes />
      <WhyChooseUs />
      <MobileApp />
      <Onboarding />
      <Testimonials />
      <StudentToolkit />
      <Accreditations />
      <Footer />
    </div>
  )
}

export default App

