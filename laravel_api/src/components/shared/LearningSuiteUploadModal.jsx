import React, { useState } from 'react'
import {
  FiX,
  FiBook,
  FiFileText,
  FiVideo,
  FiCheckCircle,
  FiAlertCircle,
  FiSave,
  FiLink,
  FiUploadCloud
} from 'react-icons/fi'
import { getAuthHeaders } from '../../services/apiClient'
import FirebaseUploader from './FirebaseUploader'
import './LearningSuiteUploadModal.css'

const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'

const LearningSuiteUploadModal = ({
  isOpen,
  onClose,
  defaultType = 'note', // 'note', 'paper', 'recording'
  onSuccess
}) => {
  const [type, setType] = useState(defaultType)
  const [title, setTitle] = useState('')
  const [grade, setGrade] = useState('grade-10')
  const [subject, setSubject] = useState('')
  const [medium, setMedium] = useState('tamil')
  const [description, setDescription] = useState('')
  const [externalUrl, setExternalUrl] = useState('')
  const [uploadedFile, setUploadedFile] = useState(null)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState('')
  const [successMessage, setSuccessMessage] = useState('')

  if (!isOpen) return null

  const getFolderForType = () => {
    switch (type) {
      case 'note':
        return `learning-suite/notes/${grade}`
      case 'paper':
        return `learning-suite/past-papers/${grade}`
      case 'recording':
        return `learning-suite/recordings/${grade}`
      default:
        return `learning-suite/materials/${grade}`
    }
  }

  const getAcceptedFileTypes = () => {
    switch (type) {
      case 'note':
      case 'paper':
        return '.pdf,.doc,.docx'
      case 'recording':
        return 'video/*,.mp4,.webm,.mkv'
      default:
        return '*/*'
    }
  }

  const handleUploadSuccess = (fileData) => {
    setUploadedFile(fileData)
    setError('')
  }

  const handleUploadError = (err) => {
    setError(err.message || 'Firebase upload failed')
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setSuccessMessage('')

    if (!title.trim()) {
      setError('Please provide a title for the material.')
      return
    }

    if (!uploadedFile && !externalUrl) {
      setError('Please upload a file or provide a video URL.')
      return
    }

    setIsSaving(true)

    try {
      const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken')
      
      const payload = {
        title: title.trim(),
        type: type,
        grade: grade,
        subject: subject.trim(),
        medium: medium,
        description: description.trim(),
        file_path: uploadedFile?.downloadUrl || null,
        file_size: uploadedFile?.formattedSize || null,
        url: externalUrl.trim() || uploadedFile?.downloadUrl || null,
      }

      // Try teacher or admin endpoint based on available token
      const response = await fetch(`${API_BASE_URL}/teacher/materials`, {
        method: 'POST',
        headers: getAuthHeaders(),
        credentials: 'include',
        body: JSON.stringify(payload),
      })

      if (!response.ok) {
        // Fallback to admin route if teacher fails
        const adminResponse = await fetch(`${API_BASE_URL}/admin/materials`, {
          method: 'POST',
          headers: getAuthHeaders(),
          credentials: 'include',
          body: JSON.stringify(payload),
        })

        if (!adminResponse.ok) {
          const errData = await adminResponse.json().catch(() => ({}))
          throw new Error(errData.message || 'Failed to save material to database')
        }
      }

      setSuccessMessage('Material saved and published successfully!')
      setTimeout(() => {
        if (typeof onSuccess === 'function') {
          onSuccess()
        }
        onClose()
      }, 1500)
    } catch (err) {
      setError(err.message || 'Failed to save material.')
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <div className="ls-upload-overlay" onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}>
      <div className="ls-upload-modal" onClick={(e) => e.stopPropagation()}>
        <button className="ls-modal-close" onClick={onClose}>
          <FiX />
        </button>

        <div className="ls-modal-header">
          <h2>Upload Learning Material</h2>
          <p>Files are uploaded directly to Firebase Cloud Storage</p>
        </div>

        {/* Type Selection Tabs */}
        <div className="ls-type-tabs">
          <button
            type="button"
            className={`ls-type-tab ${type === 'note' ? 'active' : ''}`}
            onClick={() => setType('note')}
          >
            <FiBook /> Study Note (PDF)
          </button>
          <button
            type="button"
            className={`ls-type-tab ${type === 'paper' ? 'active' : ''}`}
            onClick={() => setType('paper')}
          >
            <FiFileText /> Past Paper (PDF)
          </button>
          <button
            type="button"
            className={`ls-type-tab ${type === 'recording' ? 'active' : ''}`}
            onClick={() => setType('recording')}
          >
            <FiVideo /> Class Recording
          </button>
        </div>

        {successMessage ? (
          <div className="ls-success-banner">
            <FiCheckCircle />
            <span>{successMessage}</span>
          </div>
        ) : null}

        {error ? (
          <div className="ls-error-banner">
            <FiAlertCircle />
            <span>{error}</span>
          </div>
        ) : null}

        <form onSubmit={handleSubmit} className="ls-upload-form">
          <div className="ls-form-row">
            <div className="ls-form-group">
              <label>Title *</label>
              <input
                type="text"
                placeholder="e.g., Grade 11 Maths Term 1 Revision"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                required
              />
            </div>
          </div>

          <div className="ls-form-row-grid">
            <div className="ls-form-group">
              <label>Grade</label>
              <select value={grade} onChange={(e) => setGrade(e.target.value)}>
                <option value="grade-6">Grade 6</option>
                <option value="grade-7">Grade 7</option>
                <option value="grade-8">Grade 8</option>
                <option value="grade-9">Grade 9</option>
                <option value="grade-10">Grade 10</option>
                <option value="grade-11">Grade 11</option>
                <option value="al-arts">A/L Arts</option>
                <option value="al-bio-maths">A/L Bio / Maths</option>
                <option value="all-grades">All Grades</option>
              </select>
            </div>

            <div className="ls-form-group">
              <label>Subject</label>
              <input
                type="text"
                placeholder="e.g., Mathematics, Science"
                value={subject}
                onChange={(e) => setSubject(e.target.value)}
              />
            </div>

            <div className="ls-form-group">
              <label>Medium</label>
              <select value={medium} onChange={(e) => setMedium(e.target.value)}>
                <option value="tamil">Tamil / தமிழ்</option>
                <option value="english">English</option>
                <option value="all">Both / All</option>
              </select>
            </div>
          </div>

          {/* Firebase File Uploader */}
          <div className="ls-uploader-section">
            <label className="ls-section-label">
              Upload File to Firebase Storage {type === 'recording' ? '(Optional if using URL)' : '*'}
            </label>
            <FirebaseUploader
              folder={getFolderForType()}
              accept={getAcceptedFileTypes()}
              maxSizeMB={type === 'recording' ? 250 : 30}
              onUploadSuccess={handleUploadSuccess}
              onUploadError={handleUploadError}
              label={`Drag & drop ${type === 'recording' ? 'Video' : 'PDF Document'} here`}
              hint={type === 'recording' ? 'Supports MP4, WebM up to 250MB' : 'Supports PDF, Word up to 30MB'}
            />
          </div>

          {/* External URL (For YouTube / Vimeo / Video Links) */}
          {type === 'recording' && (
            <div className="ls-form-group">
              <label><FiLink /> Video URL (YouTube, Vimeo, or Firebase Stream Link)</label>
              <input
                type="url"
                placeholder="https://www.youtube.com/watch?v=... or https://firebasestorage..."
                value={externalUrl}
                onChange={(e) => setExternalUrl(e.target.value)}
              />
            </div>
          )}

          <div className="ls-form-group">
            <label>Description / Notes (Optional)</label>
            <textarea
              rows="2"
              placeholder="Brief description about this note, past paper, or recording..."
              value={description}
              onChange={(e) => setDescription(e.target.value)}
            />
          </div>

          <div className="ls-modal-actions">
            <button type="button" className="btn-ls-cancel" onClick={onClose}>
              Cancel
            </button>
            <button
              type="submit"
              className="btn-ls-submit"
              disabled={isSaving}
            >
              <FiSave />
              {isSaving ? 'Publishing...' : 'Save & Publish'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default LearningSuiteUploadModal
