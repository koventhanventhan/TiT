import {
  ref,
  uploadBytesResumable,
  getDownloadURL,
  deleteObject
} from 'firebase/storage'
import { storage, isFirebaseConfigured } from './firebase'

/**
 * Format bytes to readable size (e.g. 2.4 MB, 131.6 KB)
 */
export const formatFileSize = (bytes) => {
  if (!bytes || bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

/**
 * Sanitize filename to avoid broken URLs
 */
const sanitizeFileName = (fileName) => {
  return fileName
    .replace(/[^a-zA-Z0-9._-]/g, '_')
    .replace(/_+/g, '_')
}

/**
 * Core upload function with progress tracking
 * @param {File} file - The file to upload
 * @param {string} folderPath - Folder path inside storage bucket
 * @param {function} onProgress - Optional callback receiving progress percentage (0 to 100)
 * @returns {Promise<Object>} { downloadUrl, fullPath, fileName, fileSize, formattedSize, contentType }
 */
export const uploadFileToFirebase = (file, folderPath = 'uploads', onProgress = null) => {
  return new Promise((resolve, reject) => {
    if (!isFirebaseConfigured() || !storage) {
      return reject(
        new Error(
          'Firebase Storage is not configured. Please add your Firebase credentials in the .env file.'
        )
      )
    }

    if (!file) {
      return reject(new Error('No file provided for upload.'))
    }

    // Generate unique file path with timestamp
    const timestamp = Date.now()
    const sanitizedName = sanitizeFileName(file.name)
    const fullPath = `${folderPath.replace(/^\/+|\/+$/g, '')}/${timestamp}_${sanitizedName}`
    const storageRef = ref(storage, fullPath)

    // Set metadata
    const metadata = {
      contentType: file.type || 'application/octet-stream',
      customMetadata: {
        originalName: file.name,
        uploadedAt: new Date().toISOString(),
      },
    }

    // Start resumable upload
    const uploadTask = uploadBytesResumable(storageRef, file, metadata)

    uploadTask.on(
      'state_changed',
      (snapshot) => {
        const progress = Math.round(
          (snapshot.bytesTransferred / snapshot.totalBytes) * 100
        )
        if (typeof onProgress === 'function') {
          onProgress(progress, snapshot)
        }
      },
      (error) => {
        console.error('❌ Firebase upload error:', error)
        reject(error)
      },
      async () => {
        try {
          const downloadUrl = await getDownloadURL(uploadTask.snapshot.ref)
          resolve({
            downloadUrl,
            fullPath,
            fileName: file.name,
            fileSize: file.size,
            formattedSize: formatFileSize(file.size),
            contentType: file.type,
          })
        } catch (urlError) {
          reject(urlError)
        }
      }
    )
  })
}

/**
 * Upload Study Note (PDF/Doc)
 */
export const uploadStudyNote = (file, grade = 'all', onProgress = null) => {
  const sanitizedGrade = String(grade).toLowerCase().replace(/\s+/g, '-')
  return uploadFileToFirebase(file, `learning-suite/notes/${sanitizedGrade}`, onProgress)
}

/**
 * Upload Past Paper (PDF)
 */
export const uploadPastPaper = (file, grade = 'all', onProgress = null) => {
  const sanitizedGrade = String(grade).toLowerCase().replace(/\s+/g, '-')
  return uploadFileToFirebase(file, `learning-suite/past-papers/${sanitizedGrade}`, onProgress)
}

/**
 * Upload Class Recording (Video / MP4 / WebM)
 */
export const uploadClassRecording = (file, grade = 'all', onProgress = null) => {
  const sanitizedGrade = String(grade).toLowerCase().replace(/\s+/g, '-')
  return uploadFileToFirebase(file, `learning-suite/recordings/${sanitizedGrade}`, onProgress)
}

/**
 * Upload Student Assignment / Submission
 */
export const uploadAssignmentFile = (file, folder = 'submissions', onProgress = null) => {
  return uploadFileToFirebase(file, `assignments/${folder}`, onProgress)
}

/**
 * Upload Avatar / Profile Image
 */
export const uploadAvatarImage = (file, userId = 'user', onProgress = null) => {
  return uploadFileToFirebase(file, `avatars/${userId}`, onProgress)
}

/**
 * Upload Payment Slip (Bank Transfer Screenshot/Receipt)
 */
export const uploadPaymentSlip = (file, userId = 'guest', onProgress = null) => {
  return uploadFileToFirebase(file, `payment-slips/${userId}`, onProgress)
}

/**
 * Delete a file from Firebase Storage by its download URL or Storage Path
 */
export const deleteFileFromFirebase = async (urlOrPath) => {
  if (!isFirebaseConfigured() || !storage || !urlOrPath) return false

  try {
    let fileRef
    if (urlOrPath.startsWith('http://') || urlOrPath.startsWith('https://')) {
      // Create ref from full URL
      fileRef = ref(storage, urlOrPath)
    } else {
      fileRef = ref(storage, urlOrPath)
    }
    await deleteObject(fileRef)
    return true
  } catch (error) {
    console.warn('⚠️ Firebase file delete warning:', error.message)
    return false
  }
}
