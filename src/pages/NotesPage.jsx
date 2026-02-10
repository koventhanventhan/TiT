import React, { useState, useEffect } from 'react'
import { useSearchParams } from 'react-router-dom'
import { FiFileText, FiDownload, FiBook, FiArrowRight } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './NotesPage.css'

const NotesPage = () => {
  const { getSetting } = useSettings()
  const [searchParams] = useSearchParams()
  const grade = searchParams.get('grade') || 'All Grades'
  const [dynamicMaterials, setDynamicMaterials] = useState([])
  const [loadingMaterials, setLoadingMaterials] = useState(true)

  useEffect(() => {
    const fetchMaterials = async () => {
      try {
        const response = await fetch(`http://localhost:8000/api/learning-materials?grade=${grade}&type=note`)
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

  const noteTitle = getSetting('learning_notes_title', 'Study Notes')
  const noteDesc = getSetting('learning_notes_description', 'Access comprehensive study notes for all subjects and grades.')

  const staticNotes = [
    { id: 's2', title: 'Science Notes', subject: 'Science', fileType: 'PDF', size: '3.1 MB' },
    { id: 's3', title: 'English Notes', subject: 'English', fileType: 'PDF', size: '1.8 MB' },
  ]

  const backendUrl = 'http://localhost:8000/' // Assuming Laravel runs here

  return (
    <div className="notes-page">
      <div className="notes-page-hero">
        <div className="container">
          <h1 className="notes-page-title">{noteTitle}</h1>
          <p className="notes-page-subtitle">
            Comprehensive study notes for {grade.replace(/-/g, ' ').toUpperCase()}
          </p>
        </div>
      </div>

      <div className="container">
        <div className="notes-content">
          <div className="notes-header">
            <div className="grade-badge">
              <FiBook />
              <span>{grade.replace(/-/g, ' ').toUpperCase()}</span>
            </div>
            <p className="notes-description">
              {noteDesc}
            </p>
          </div>

          <div className="notes-grid">
            {/* Dynamic Notes from Admin */}
            {!loadingMaterials && dynamicMaterials.map((note) => (
              <div key={note.id} className="note-card highlight">
                <div className="note-icon">
                  <FiFileText />
                </div>
                <div className="note-content">
                  <h3 className="note-title">{note.title}</h3>
                  <div className="note-meta">
                    <span className="note-subject">Dynamic Note</span>
                    <span className="note-size">{note.file_size}</span>
                  </div>
                  <div className="note-footer">
                    <span className="note-type">PDF</span>
                    <a
                      href={`${backendUrl}${note.file_path}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="btn-download"
                      style={{ textDecoration: 'none', display: 'flex', alignItems: 'center', gap: '5px' }}
                    >
                      <FiDownload />
                      Download
                    </a>
                  </div>
                </div>
              </div>
            ))}

            {staticNotes.map((note) => (
              <div key={note.id} className="note-card">
                <div className="note-icon">
                  <FiFileText />
                </div>
                <div className="note-content">
                  <h3 className="note-title">{note.title}</h3>
                  <div className="note-meta">
                    <span className="note-subject">{note.subject}</span>
                    <span className="note-size">{note.size}</span>
                  </div>
                  <div className="note-footer">
                    <span className="note-type">{note.fileType}</span>
                    <button className="btn-download">
                      <FiDownload />
                      Download
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>

          <div className="notes-cta">
            <h2>Need More Notes?</h2>
            <p>Contact us to request specific notes or get access to premium content</p>
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

export default NotesPage

