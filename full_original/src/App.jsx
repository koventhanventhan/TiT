import React from 'react'
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom'
import Header from './components/Header'
import Footer from './components/Footer'
import HomePage from './pages/HomePage'
import ContactPage from './pages/ContactPage'
import ClassesPage from './pages/ClassesPage'
import BlogsPage from './pages/BlogsPage'
import BlogPage from './pages/BlogPage'
import './App.css'

function App() {
  return (
    <Router>
      <div className="App" style={{ minHeight: '100vh', background: '#ffffff', width: '100%' }}>
        <Header />
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/contact" element={<ContactPage />} />
          <Route path="/classes" element={<ClassesPage />} />
          <Route path="/blogs" element={<BlogsPage />} />
          <Route path="/blog/:id" element={<BlogPage />} />
        </Routes>
        <Footer />
      </div>
    </Router>
  )
}

export default App

