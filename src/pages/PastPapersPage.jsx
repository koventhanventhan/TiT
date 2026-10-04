import React, { useState, useEffect } from 'react'
import { useSearchParams } from 'react-router-dom'
import { FiFile, FiDownload, FiBook, FiArrowRight, FiCalendar, FiLoader } from 'react-icons/fi'
import { getLearningMaterials } from '../services/materialService'
import { useSettings } from '../context/SettingsContext'
import { useLanguage } from '../context/LanguageContext'
import './PastPapersPage.css'

const PastPapersPage = () => {
  const { getSetting } = useSettings()
  const { t, translate, language } = useLanguage()
  const [searchParams] = useSearchParams()
  const grade = searchParams.get('grade') || 'All Grades'
  const [dynamicMaterials, setDynamicMaterials] = useState([])
  const [loadingMaterials, setLoadingMaterials] = useState(true)
  const isAllGrades = grade === 'All Grades' || grade === 'All'

  const [title, setTitle] = useState(getSetting('learning_pastpapers_title', t('past_papers')))
  const [desc, setDesc] = useState(getSetting('learning_pastpapers_description', t('past_papers_description')))
  const [missionTitle, setMissionTitle] = useState(getSetting('mission_title', t('need_more_resources_title')))
  const [missionDesc, setMissionDesc] = useState(getSetting('mission_desc', t('need_more_resources_desc')))

  useEffect(() => {
    const fetchMaterials = async () => {
      setLoadingMaterials(true)
      try {
        const params = { type: 'past_paper' }
        if (!isAllGrades) {
          params.grade = grade.toLowerCase().replace(' ', '-')
        }
        const data = await getLearningMaterials(params)
        setDynamicMaterials(data)
      } catch (err) {
        console.error('Error fetching papers:', err)
      } finally {
        setLoadingMaterials(false)
      }
    }
    fetchMaterials()
  }, [grade, isAllGrades])

  useEffect(() => {
    if (language !== 'en') {
      const translateAll = async () => {
        setTitle(await translate(getSetting('learning_pastpapers_title', t('past_papers'))))
        setDesc(await translate(getSetting('learning_pastpapers_description', t('past_papers_description'))))
        setMissionTitle(await translate(getSetting('learning_cta_title', t('need_more_resources_title'))))
        setMissionDesc(await translate(getSetting('learning_cta_desc', t('need_more_resources_desc'))))
      }
      translateAll()
    } else {
      setTitle(getSetting('learning_pastpapers_title', t('past_papers')))
      setDesc(getSetting('learning_pastpapers_description', t('past_papers_description')))
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
    <div className="past-papers-page">
      <div className="past-papers-page-hero">
        <div className="container">
          <h1 className="past-papers-page-title">{title}</h1>
          <p className="past-papers-page-subtitle">
            {t('past_papers_for')} {formatGradeDisplay(grade)}
          </p>
        </div>
      </div>

      <div className="container">
        <div className="past-papers-content">
          <div className="past-papers-header">
            <div className="grade-badge">
              <FiBook />
              <span>{formatGradeDisplay(grade)}</span>
            </div>
            <p className="past-papers-description">
              {desc}
            </p>
          </div>

          <div className="past-papers-grid">
            {loadingMaterials && (
              <div className="papers-loading">
                <FiLoader className="spin" />
                <p>{t('loading_papers') || 'Loading past papers...'}</p>
              </div>
            )}

            {/* Dynamic Past Papers from Admin */}
            {!loadingMaterials && dynamicMaterials.length > 0 ? dynamicMaterials.map((paper) => (
              <div key={paper.id} className="paper-card highlight">
                <div className="paper-icon">
                  <FiFile />
                </div>
                <div className="paper-content">
                  <h3 className="paper-title">{paper.title}</h3>
                  <div className="paper-meta">
                    <span className="paper-year">
                      <FiCalendar />
                      {t('latest')}
                    </span>
                    <span className="paper-subject">
                      {new Date(paper.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })}
                    </span>
                    <span className="paper-size">{paper.file_size}</span>
                  </div>
                  <div className="paper-footer">
                    <span className="paper-type">PDF</span>
                    <a
                      href={`${backendUrl}${paper.file_path}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="btn-download"
                      style={{ textDecoration: 'none', display: 'flex', alignItems: 'center', gap: '0.3125rem' }}
                    >
                      <FiDownload />
                      {t('download')}
                    </a>
                  </div>
                </div>
              </div>
            )) : !loadingMaterials && (
              <div className="no-papers">
                <p>{t('no_papers_found') || 'No past papers found for this grade.'}</p>
              </div>
            )}


          </div>

          <div className="past-papers-cta">
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

export default PastPapersPage

