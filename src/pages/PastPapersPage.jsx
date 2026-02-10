import React, { useState, useEffect } from 'react'
import { useSearchParams } from 'react-router-dom'
import { FiFile, FiDownload, FiBook, FiArrowRight, FiCalendar } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './PastPapersPage.css'

const PastPapersPage = () => {
  const { getSetting } = useSettings()
  const [searchParams] = useSearchParams()
  const grade = searchParams.get('grade') || 'All Grades'
  const [dynamicMaterials, setDynamicMaterials] = useState([])
  const [loadingMaterials, setLoadingMaterials] = useState(true)

  useEffect(() => {
    const fetchMaterials = async () => {
      try {
        const response = await fetch(`http://localhost:8000/api/learning-materials?grade=${grade}&type=past_paper`)
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

  const ppTitle = getSetting('learning_pastpapers_title', 'Past Papers')
  const ppDesc = getSetting('learning_pastpapers_description', 'Practice with previous exam papers to prepare for your exams.')

  const staticPapers = [
    { id: 's1', title: '2023 Science Paper', year: '2023', subject: 'Science', fileType: 'PDF', size: '1.5 MB' },
    { id: 's2', title: '2022 Mathematics Paper', year: '2022', subject: 'Mathematics', fileType: 'PDF', size: '1.1 MB' },
  ]

  const backendUrl = 'http://localhost:8000/' // Assuming Laravel runs here

  return (
    <div className="past-papers-page">
      <div className="past-papers-page-hero">
        <div className="container">
          <h1 className="past-papers-page-title">{ppTitle}</h1>
          <p className="past-papers-page-subtitle">
            Previous exam papers for {grade.replace(/-/g, ' ').toUpperCase()}
          </p>
        </div>
      </div>

      <div className="container">
        <div className="past-papers-content">
          <div className="past-papers-header">
            <div className="grade-badge">
              <FiBook />
              <span>{grade.replace(/-/g, ' ').toUpperCase()}</span>
            </div>
            <p className="past-papers-description">
              {ppDesc}
            </p>
          </div>

          <div className="past-papers-grid">
            {/* Dynamic Past Papers from Admin */}
            {!loadingMaterials && dynamicMaterials.map((paper) => (
              <div key={paper.id} className="paper-card highlight">
                <div className="paper-icon">
                  <FiFile />
                </div>
                <div className="paper-content">
                  <h3 className="paper-title">{paper.title}</h3>
                  <div className="paper-meta">
                    <span className="paper-year">
                      <FiCalendar />
                      Latest
                    </span>
                    <span className="paper-subject">Dynamic Paper</span>
                    <span className="paper-size">{paper.file_size}</span>
                  </div>
                  <div className="paper-footer">
                    <span className="paper-type">PDF</span>
                    <a
                      href={`${backendUrl}${paper.file_path}`}
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

            {staticPapers.map((paper) => (
              <div key={paper.id} className="paper-card">
                <div className="paper-icon">
                  <FiFile />
                </div>
                <div className="paper-content">
                  <h3 className="paper-title">{paper.title}</h3>
                  <div className="paper-meta">
                    <span className="paper-year">
                      <FiCalendar />
                      {paper.year}
                    </span>
                    <span className="paper-subject">{paper.subject}</span>
                    <span className="paper-size">{paper.size}</span>
                  </div>
                  <div className="paper-footer">
                    <span className="paper-type">{paper.fileType}</span>
                    <button className="btn-download">
                      <FiDownload />
                      Download
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>

          <div className="past-papers-cta">
            <h2>Need More Past Papers?</h2>
            <p>Contact us to access additional past papers or get solutions</p>
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

export default PastPapersPage

