import React, { useState, useRef } from 'react'
import {
  FiUploadCloud,
  FiFile,
  FiImage,
  FiVideo,
  FiCheckCircle,
  FiX,
  FiCopy,
  FiCheck,
  FiAlertCircle,
  FiTrash2
} from 'react-icons/fi'
import {
  uploadFileToFirebase,
  deleteFileFromFirebase,
  formatFileSize
} from '../../services/firebaseStorageService'
import { isFirebaseConfigured } from '../../services/firebase'
import './FirebaseUploader.css'

const FirebaseUploader = ({
  folder = 'uploads',
  accept = '*/*',
  maxSizeMB = 50,
  onUploadSuccess,
  onUploadError,
  label = 'Drag & drop a file here, or browse',
  hint = 'Supports PDF, Images, Videos, & Docs',
  buttonText = 'Choose File',
  showPreview = true,
  autoUpload = true,
}) => {
  const [file, setFile] = useState(null)
  const [previewUrl, setPreviewUrl] = useState(null)
  const [progress, setProgress] = useState(0)
  const [isUploading, setIsUploading] = useState(false)
  const [uploadedData, setUploadedData] = useState(null)
  const [error, setError] = useState('')
  const [copied, setCopied] = useState(false)
  const [isDragging, setIsDragging] = useState(false)
  const fileInputRef = useRef(null)

  const handleFileSelect = (selectedFile) => {
    setError('')
    setCopied(false)

    if (!selectedFile) return

    // Check configuration
    if (!isFirebaseConfigured()) {
      setError(
        'Firebase is not yet configured. Please add your Firebase project keys in the .env file.'
      )
      return
    }

    // Check size limit
    const sizeInMB = selectedFile.size / (1024 * 1024)
    if (sizeInMB > maxSizeMB) {
      setError(`File size exceeds the limit of ${maxSizeMB} MB.`)
      return
    }

    setFile(selectedFile)

    // Generate local preview if image
    if (selectedFile.type.startsWith('image/')) {
      const reader = new FileReader()
      reader.onload = () => setPreviewUrl(reader.result)
      reader.readAsDataURL(selectedFile)
    } else {
      setPreviewUrl(null)
    }

    if (autoUpload) {
      startUpload(selectedFile)
    }
  }

  const startUpload = async (fileToUpload = file) => {
    if (!fileToUpload) return

    setIsUploading(true)
    setProgress(0)
    setError('')

    try {
      const result = await uploadFileToFirebase(fileToUpload, folder, (pct) => {
        setProgress(pct)
      })

      setUploadedData(result)
      setIsUploading(false)
      if (typeof onUploadSuccess === 'function') {
        onUploadSuccess(result)
      }
    } catch (err) {
      setIsUploading(false)
      setError(err.message || 'Upload failed. Please check your internet connection.')
      if (typeof onUploadError === 'function') {
        onUploadError(err)
      }
    }
  }

  const handleDragOver = (e) => {
    e.preventDefault()
    setIsDragging(true)
  }

  const handleDragLeave = () => {
    setIsDragging(false)
  }

  const handleDrop = (e) => {
    e.preventDefault()
    setIsDragging(false)
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFileSelect(e.dataTransfer.files[0])
    }
  }

  const handleRemove = async () => {
    if (uploadedData?.downloadUrl) {
      await deleteFileFromFirebase(uploadedData.downloadUrl)
    }
    setFile(null)
    setPreviewUrl(null)
    setUploadedData(null)
    setProgress(0)
    setError('')
    if (fileInputRef.current) {
      fileInputRef.current.value = ''
    }
  }

  const handleCopyUrl = () => {
    if (uploadedData?.downloadUrl) {
      navigator.clipboard.writeText(uploadedData.downloadUrl)
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    }
  }

  const getFileIcon = () => {
    if (!file) return <FiUploadCloud />
    if (file.type.startsWith('image/')) return <FiImage />
    if (file.type.startsWith('video/')) return <FiVideo />
    return <FiFile />
  }

  return (
    <div className="firebase-uploader-container">
      {!file ? (
        <div
          className={`firebase-dropzone ${isDragging ? 'dragging' : ''}`}
          onDragOver={handleDragOver}
          onDragLeave={handleDragLeave}
          onDrop={handleDrop}
          onClick={() => fileInputRef.current?.click()}
        >
          <input
            type="file"
            ref={fileInputRef}
            onChange={(e) => handleFileSelect(e.target.files?.[0])}
            accept={accept}
            style={{ display: 'none' }}
          />
          <div className="dropzone-icon-wrap">
            <FiUploadCloud className="dropzone-icon" />
          </div>
          <p className="dropzone-label">{label}</p>
          <span className="dropzone-hint">{hint} (Max: {maxSizeMB}MB)</span>
          <button type="button" className="btn-browse-file">
            {buttonText}
          </button>
        </div>
      ) : (
        <div className="firebase-file-card">
          <div className="file-info-row">
            <div className="file-icon-box">{getFileIcon()}</div>
            <div className="file-details">
              <span className="file-name" title={file.name}>
                {file.name}
              </span>
              <span className="file-meta">
                {formatFileSize(file.size)} • {file.type || 'File'}
              </span>
            </div>
            {!isUploading && (
              <button
                type="button"
                className="btn-remove-file"
                onClick={handleRemove}
                title="Remove file"
              >
                <FiTrash2 />
              </button>
            )}
          </div>

          {/* Image Preview */}
          {showPreview && previewUrl && (
            <div className="uploader-image-preview">
              <img src={previewUrl} alt="Upload preview" />
            </div>
          )}

          {/* Upload Progress Bar */}
          {isUploading && (
            <div className="upload-progress-wrapper">
              <div className="progress-info">
                <span>Uploading to Firebase Storage...</span>
                <span className="progress-percent">{progress}%</span>
              </div>
              <div className="progress-bar-bg">
                <div
                  className="progress-bar-fill"
                  style={{ width: `${progress}%` }}
                />
              </div>
            </div>
          )}

          {/* Upload Complete Success */}
          {uploadedData && !isUploading && (
            <div className="upload-success-badge">
              <div className="success-left">
                <FiCheckCircle className="success-icon" />
                <span>Uploaded successfully to Firebase Cloud</span>
              </div>
              <button
                type="button"
                className="btn-copy-url"
                onClick={handleCopyUrl}
                title="Copy Download URL"
              >
                {copied ? <FiCheck /> : <FiCopy />}
                {copied ? 'Copied!' : 'Copy Link'}
              </button>
            </div>
          )}
        </div>
      )}

      {/* Error display */}
      {error && (
        <div className="uploader-error-message">
          <FiAlertCircle />
          <span>{error}</span>
        </div>
      )}
    </div>
  )
}

export default FirebaseUploader
