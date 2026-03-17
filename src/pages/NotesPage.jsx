import React, { useState, useEffect } from 'react'
import { useSearchParams } from 'react-router-dom'
import { FiFileText, FiDownload, FiBook, FiArrowRight, FiLoader } from 'react-icons/fi'
import { getLearningMaterials } from '../services/materialService'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './NotesPage.css'

const NotesPage = () => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()
  const [searchParams] = useSearchParams()
  const grade = searchParams.get('grade') || 'All Grades'
  const [dynamicMaterials, setDynamicMaterials] = useState([])
  const [loadingMaterials, setLoadingMaterials] = useState(true)
  const isAllGrades = grade === 'All Grades' || grade === 'All'

  const [title, setTitle] = useState(getSetting('learning_notes_title', t('study_notes')))
  const [desc, setDesc] = useState(getSetting('learning_notes_description', t('learning_notes_description')))
  const [missionTitle, setMissionTitle] = useState(getSetting('mission_title', t('need_more_resources_title')))
  const [missionDesc, setMissionDesc] = useState(getSetting('mission_desc', t('need_more_resources_desc')))

  useEffect(() => {
    const fetchMaterials = async () => {
      setLoadingMaterials(true)
      try {
        const params = { type: 'note' }
        if (!isAllGrades) {
          params.grade = grade.toLowerCase().replace(' ', '-')
        }
        const data = await getLearningMaterials(params)
        setDynamicMaterials(data)
      } catch (err) {
        console.error('Error fetching notes:', err)
      } finally {
        setLoadingMaterials(false)
      }
    }
    fetchMaterials()
  }, [grade, isAllGrades])

  useEffect(() => {
    if (language !== 'en') {
      const translateAll = async () => {
        setTitle(await translate(getSetting('learning_notes_title', t('study_notes'))))
        setDesc(await translate(getSetting('learning_notes_description', t('learning_notes_description'))))
        setMissionTitle(await translate(getSetting('learning_cta_title', t('need_more_resources_title'))))
        setMissionDesc(await translate(getSetting('learning_cta_desc', t('need_more_resources_desc'))))
      }
      translateAll()
    } else {
      setTitle(getSetting('learning_notes_title', t('study_notes')))
      setDesc(getSetting('learning_notes_description', t('learning_notes_description')))
      setMissionTitle(getSetting('learning_cta_title', t('need_more_resources_title')))
      setMissionDesc(getSetting('learning_cta_desc', t('need_more_resources_desc')))
    }
  }, [language, translate, getSetting, t])

  const backendUrl = import.meta.env.VITE_API_URL?.replace('/api', '/') || 'http://localhost:8000/'

  const formatGradeDisplay = (g) => {
    if (!g || g.toLowerCase() === 'all grades' || g.toLowerCase() === 'all-grades') return t('all_grades')
    const num = g.match(/\d+/)
    if (num) {
      return `${t('grade_prefix')} ${num[0]}`
    }
    return g.replace(/-/g, ' ').toUpperCase()
  }

  return (
    <div className="notes-page">
      <div className="notes-page-hero">
        <div className="container">
          <h1 className="notes-page-title">{title}</h1>
          <p className="notes-page-subtitle">
            {t('notes_for')} {formatGradeDisplay(grade)}
          </p>
        </div>
      </div>

      <div className="container">
        <div className="notes-content">
          <div className="notes-header">
            <div className="grade-badge">
              <FiBook />
              <span>{formatGradeDisplay(grade)}</span>
            </div>
            <p className="notes-description">
              {desc}
            </p>
          </div>

          <div className="notes-grid">
            {loadingMaterials && (
              <div className="notes-loading">
                <FiLoader className="spin" />
                <p>{t('loading_notes') || 'Loading study notes...'}</p>
              </div>
            )}

            {/* Dynamic Notes from Admin */}
            {!loadingMaterials && dynamicMaterials.length > 0 ? dynamicMaterials.map((note) => (
              <div key={note.id} className="note-card highlight">
                <div className="note-icon">
                  <FiFileText />
                </div>
                <div className="note-content">
                  <h3 className="note-title">{note.title}</h3>
                  <div className="note-meta">
                    <span className="note-subject">
                      {new Date(note.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })}
                    </span>
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
                      {t('download')}
                    </a>
                  </div>
                </div>
              </div>
            )) : !loadingMaterials && (
              <div className="no-notes">
                <p>{t('no_notes_found') || 'No study notes found for this grade.'}</p>
              </div>
            )}


          </div>

          <div className="notes-cta">
            <h3>{missionTitle}</h3>
            <p>{missionDesc}</p>
            <a href={getSetting('learning_cta_link', '/contact')} className="btn-primary-large">
              {getSetting('learning_cta_btn', t('btn_contact'))}
              <FiArrowRight />
            </a>
          </div>
        </div>
      </div>
    </div>
  )
}

export default NotesPage

