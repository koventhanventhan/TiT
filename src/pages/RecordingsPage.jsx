import React, { useState, useEffect } from 'react'
import { useSearchParams } from 'react-router-dom'
import { FiVideo, FiPlay, FiBook, FiArrowRight, FiClock, FiLoader } from 'react-icons/fi'
import { getLearningMaterials } from '../services/materialService'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './RecordingsPage.css'

const RecordingsPage = () => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()
  const [searchParams] = useSearchParams()
  const grade = searchParams.get('grade') || 'All Grades'
  const [dynamicMaterials, setDynamicMaterials] = useState([])
  const [loadingMaterials, setLoadingMaterials] = useState(true)
  const isAllGrades = grade === 'All Grades' || grade === 'All'

  const [title, setTitle] = useState(getSetting('learning_recordings_title', t('recordings')))
  const [desc, setDesc] = useState(getSetting('learning_recordings_description', t('learning_recordings_description')))
  const [missionTitle, setMissionTitle] = useState(getSetting('mission_title', t('need_more_resources_title')))
  const [missionDesc, setMissionDesc] = useState(getSetting('mission_desc', t('need_more_resources_desc')))

  useEffect(() => {
    const fetchMaterials = async () => {
      setLoadingMaterials(true)
      try {
        const params = { type: 'recording' }
        if (!isAllGrades) {
          params.grade = grade.toLowerCase().replace(' ', '-')
        }
        const data = await getLearningMaterials(params)
        setDynamicMaterials(data)
      } catch (err) {
        console.error('Error fetching recordings:', err)
      } finally {
        setLoadingMaterials(false)
      }
    }
    fetchMaterials()
  }, [grade, isAllGrades])

  useEffect(() => {
    if (language !== 'en') {
      const translateAll = async () => {
        setTitle(await translate(getSetting('learning_recordings_title', t('recordings'))))
        setDesc(await translate(getSetting('learning_recordings_description', t('learning_recordings_description'))))
        setMissionTitle(await translate(getSetting('learning_cta_title', t('need_more_resources_title'))))
        setMissionDesc(await translate(getSetting('learning_cta_desc', t('need_more_resources_desc'))))
      }
      translateAll()
    } else {
      setTitle(getSetting('learning_recordings_title', t('recordings')))
      setDesc(getSetting('learning_recordings_description', t('learning_recordings_description')))
      setMissionTitle(getSetting('learning_cta_title', t('need_more_resources_title')))
      setMissionDesc(getSetting('learning_cta_desc', t('need_more_resources_desc')))
    }
  }, [language, translate, getSetting, t])

  const backendUrl = import.meta.env.VITE_API_URL?.replace('/api', '/') || (window.location.origin + '/')

  const formatGradeDisplay = (g) => {
    if (!g || g.toLowerCase() === 'all grades' || g.toLowerCase() === 'all-grades') return t('all_grades')
    const num = g.match(/\d+/)
    if (num) {
      return `${t('grade_prefix')} ${num[0]}`
    }
    return g.replace(/-/g, ' ').toUpperCase()
  }

  return (
    <div className="recordings-page">
      <div className="recordings-page-hero">
        <div className="container">
          <h1 className="recordings-page-title">{title}</h1>
          <p className="recordings-page-subtitle">
            {t('recordings_for')} {formatGradeDisplay(grade)}
          </p>
        </div>
      </div>

      <div className="container">
        <div className="recordings-content">
          <div className="recordings-header">
            <div className="grade-badge">
              <FiBook />
              <span>{formatGradeDisplay(grade)}</span>
            </div>
            <p className="recordings-description">
              {desc}
            </p>
          </div>

          <div className="recordings-grid">
            {loadingMaterials && (
              <div className="recordings-loading">
                <FiLoader className="spin" />
                <p>{t('loading_recordings') || 'Loading recordings...'}</p>
              </div>
            )}

            {/* Dynamic Recordings from Admin */}
            {!loadingMaterials && dynamicMaterials.length > 0 ? dynamicMaterials.map((recording) => {
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
                      <span className="recording-subject">
                        {new Date(recording.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })}
                      </span>
                      <span className="recording-duration">
                        <FiClock />
                        {recording.file_size || 'Full'}
                      </span>
                      <span className="recording-views">{t('latest')}</span>
                    </div>
                    <a
                      href={watchUrl}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="btn-watch"
                      style={{ textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '0.5rem' }}
                    >
                      <FiPlay />
                      {t('watch_now')}
                    </a>
                  </div>
                </div>
              )
            }) : !loadingMaterials && (
              <div className="no-recordings">
                <p>{t('no_recordings_found') || 'No recordings found for this grade.'}</p>
              </div>
            )}


          </div>

          <div className="recordings-cta">
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

export default RecordingsPage

