import React from 'react'
import { FiArrowRight, FiPlay } from 'react-icons/fi'
import './Hero.css'

const Hero = () => {
  return (
    <section id="home" className="hero">
      <div className="hero-background">
        <div className="hero-gradient"></div>
        <div className="hero-shapes">
          <div className="shape shape-1"></div>
          <div className="shape shape-2"></div>
          <div className="shape shape-3"></div>
        </div>
      </div>
      <div className="container">
        <div className="hero-content">
          <div className="hero-text">
            <h1 className="hero-title">
              Experience the Future of
              <span className="gradient-text"> Quality Online Learning</span>
            </h1>
            <p className="hero-description">
              Top-notch online tutoring from qualified tutors at the comfort of your home. 
              Join thousands of students achieving academic excellence with personalized learning.
            </p>
            <div className="hero-actions">
              <a href="#register" className="btn btn-primary hero-btn">
                Join Now
                <FiArrowRight className="btn-icon" />
              </a>
              <a href="#about" className="btn btn-secondary hero-btn">
                <FiPlay className="btn-icon" />
                Watch Demo
              </a>
            </div>
            <div className="hero-stats">
              <div className="stat-item">
                <div className="stat-number">10+</div>
                <div className="stat-label">Years Experience</div>
              </div>
              <div className="stat-item">
                <div className="stat-number">10K+</div>
                <div className="stat-label">Students</div>
              </div>
              <div className="stat-item">
                <div className="stat-number">200+</div>
                <div className="stat-label">Expert Tutors</div>
              </div>
            </div>
          </div>
          <div className="hero-image">
            <div className="hero-card">
              <div className="card-glow"></div>
              <div className="card-content">
                <div className="card-icon">🎓</div>
                <h3>Start Learning Today</h3>
                <p>Join our community of learners</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}

export default Hero

