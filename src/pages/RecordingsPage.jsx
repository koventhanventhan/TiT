import React, { useState, useEffect } from 'react'
import { useSearchParams } from 'react-router-dom'
import { FiVideo, FiPlay, FiBook, FiArrowRight, FiClock } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './RecordingsPage.css'

const RecordingsPage = () => {
  const { getSetting } = useSettings()
  const [searchParams] = useSearchParams()
  const grade = searchParams.get('grade') || 'All Grades'
  const [dynamicMaterials, setDynamicMaterials] = useState([])
  const [loadingMaterials, setLoadingMaterials] = useState(true)

  useEffect(() => {
    const fetchMaterials = async () => {
      try {
        const response = await fetch(`http://localhost:8000/api/learning-materials?grade=${grade}&type=recording`)
        const data = await response.json()
        setDynamicMaterials(data)
      } catch (error) {
        console.error('Failed to fetch materials:', error)
      } finally {
        setLoadingMaterials(false)
      }
    }
    fetchMaterials()
  }, [grade])

  const recTitle = getSetting('learning_recordings_title', 'Recording Section')
  const recDesc = getSetting('learning_recordings_description', 'Watch recorded class sessions at your own pace.')

  const staticRecordings = [
    { id: 's2', title: 'Science Lesson 1', subject: 'Science', duration: '50 min', views: '980' },
    { id: 's3', title: 'English Lesson 1', subject: 'English', duration: '40 min', views: '850' },
  ]

  const backendUrl = 'http://localhost:8000/' // Assuming Laravel runs here

  return (
    <div className="recordings-page">
      <div className="recordings-page-hero">
        <div className="container">
          <h1 className="recordings-page-title">{recTitle}</h1>
          <p className="recordings-page-subtitle">
            Video recordings of classes for {grade.replace(/-/g, ' ').toUpperCase()}
          </p>
        </div>
      </div>

      <div className="container">
        <div className="recordings-content">
          <div className="recordings-header">
            <div className="grade-badge">
              <FiBook />
              <span>{grade.replace(/-/g, ' ').toUpperCase()}</span>
            </div>
            <p className="recordings-description">
              {recDesc}
            </p>
          </div>

          <div className="recordings-grid">
            {/* Dynamic Recordings from Admin */}
            {!loadingMaterials && dynamicMaterials.map((recording) => {
              const watchUrl = recording.url || (recording.file_path ? `${backendUrl}${recording.file_path}` : '#')
              return (
                <div key={recording.id} className="recording-card highlight">
                  <div className="recording-thumbnail">
                    <div className="recording-icon">
                      <FiVideo />
                    </div>
                    <div className="play-overlay">
                      <FiPlay />
                    </div>
                  </div>
                  <div className="recording-content">
                    <h3 className="recording-title">{recording.title}</h3>
                    <div className="recording-meta">
                      <span className="recording-subject">Dynamic recording</span>
                      <span className="recording-duration">
                        <FiClock />
                        {recording.file_size || 'Full'}
                      </span>
                      <span className="recording-views">Latest</span>
                    </div>
                    <a
                      href={watchUrl}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="btn-watch"
                      style={{ textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '8px' }}
                    >
                      <FiPlay />
                      Watch Now
                    </a>
                  </div>
                </div>
              )
            })}

            {staticRecordings.map((recording) => (
              <div key={recording.id} className="recording-card">
                <div className="recording-thumbnail">
                  <div className="recording-icon">
                    <FiVideo />
                  </div>
                  <div className="play-overlay">
                    <FiPlay />
                  </div>
                </div>
                <div className="recording-content">
                  <h3 className="recording-title">{recording.title}</h3>
                  <div className="recording-meta">
                    <span className="recording-subject">{recording.subject}</span>
                    <span className="recording-duration">
                      <FiClock />
                      {recording.duration}
                    </span>
                    <span className="recording-views">{recording.views} views</span>
                  </div>
                  <button className="btn-watch">
                    <FiPlay />
                    Watch Now
                  </button>
                </div>
              </div>
            ))}
          </div>

          <div className="recordings-cta">
            <h2>Need More Recordings?</h2>
            <p>Contact us to access premium recordings or request specific topics</p>
            <a href="/contact" className="btn-primary-large">
              Contact Us
              <FiArrowRight />
            </a>
          </div>
        </div>
      </div>
    </div>
  )
}

export default RecordingsPage

