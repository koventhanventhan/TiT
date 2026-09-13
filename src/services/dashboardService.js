const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'

import { getAuthHeaders } from './apiClient';

export const getStudentZoomClasses = async () => {
  const res = await fetch(`${API_BASE_URL}/student/zoom-classes`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load zoom classes')
  return res.json()
}

export const getStudentUpcomingSchedules = async () => {
  const res = await fetch(`${API_BASE_URL}/student/upcoming-schedules`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load upcoming schedules')
  return res.json()
}

export const studentAttend = async (zoomScheduleId) => {
  const res = await fetch(`${API_BASE_URL}/student/attend`, {
    method: 'POST',
    headers: getAuthHeaders(),
    credentials: 'include',
    body: JSON.stringify({ zoom_schedule_id: zoomScheduleId }),
  })
  if (!res.ok) throw new Error('Failed to record attendance')
  return res.json()
}

export const getStudentMessages = async () => {
  const res = await fetch(`${API_BASE_URL}/student/messages`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load messages')
  return res.json()
}

export const markMessageRead = async (messageId) => {
  const res = await fetch(`${API_BASE_URL}/student/messages/${messageId}/read`, {
    method: 'POST',
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to mark read')
  return res.json()
}

export const getStudentStats = async () => {
  const res = await fetch(`${API_BASE_URL}/student/stats`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load student stats')
  return res.json()
}

export const getStudentAssignments = async () => {
  const res = await fetch(`${API_BASE_URL}/student/assignments`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load assignments')
  return res.json()
}

export const submitStudentAssignment = async (assignmentId, formData) => {
  const res = await fetch(`${API_BASE_URL}/student/assignments/${assignmentId}/submit`, {
    method: 'POST',
    headers: getAuthHeaders(true),
    credentials: 'include',
    body: formData,
  })
  if (!res.ok) throw new Error('Failed to submit assignment')
  return res.json()
}

export const getStudentMaterials = async () => {
  const res = await fetch(`${API_BASE_URL}/student/materials`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load materials')
  return res.json()
}

export const getStudentPaymentStatus = async () => {
  const res = await fetch(`${API_BASE_URL}/student/payment-status`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load payment status')
  return res.json()
}

export const initializeMonthlyPayment = async () => {
  const res = await fetch(`${API_BASE_URL}/student/pay-monthly`, {
    method: 'POST',
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  
  if (!res.ok) {
    const errorData = await res.json().catch(() => ({}))
    throw new Error(errorData.message || 'Failed to initialize monthly payment')
  }
  
  return res.json()
}

export const getTeacherZoomClasses = async () => {
  const res = await fetch(`${API_BASE_URL}/teacher/zoom-classes`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load zoom classes')
  return res.json()
}

export const teacherAttend = async (zoomScheduleId) => {
  const res = await fetch(`${API_BASE_URL}/teacher/attend`, {
    method: 'POST',
    headers: getAuthHeaders(),
    credentials: 'include',
    body: JSON.stringify({ zoom_schedule_id: zoomScheduleId }),
  })
  if (!res.ok) throw new Error('Failed to record attendance')
  return res.json()
}

export const getTeacherDashboardStats = async () => {
  const res = await fetch(`${API_BASE_URL}/teacher/stats`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load dashboard stats')
  return res.json()
}

export const getTeacherUpcomingSchedules = async () => {
  const res = await fetch(`${API_BASE_URL}/teacher/upcoming-schedules`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load upcoming schedules')
  return res.json()
}

export const getTeacherStudents = async () => {
  const res = await fetch(`${API_BASE_URL}/teacher/students`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load students')
  return res.json()
}

export const getTeacherAssignments = async () => {
  const res = await fetch(`${API_BASE_URL}/teacher/assignments`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load assignments')
  return res.json()
}

export const createTeacherAssignment = async (formData) => {
  const res = await fetch(`${API_BASE_URL}/teacher/assignments`, {
    method: 'POST',
    headers: getAuthHeaders(true),
    credentials: 'include',
    body: formData,
  })
  
  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.message || 'Failed to create assignment')
  }
  return res.json()
}

export const getTeacherMaterials = async () => {
  const res = await fetch(`${API_BASE_URL}/teacher/materials`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load materials')
  return res.json()
}

export const uploadTeacherMaterial = async (formData) => {
  const res = await fetch(`${API_BASE_URL}/teacher/materials`, {
    method: 'POST',
    headers: getAuthHeaders(true),
    credentials: 'include',
    body: formData,
  })
  
  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.message || 'Failed to upload material')
  }
  return res.json()
}

export const deleteTeacherMaterial = async (id) => {
  const token = localStorage.getItem('authToken')
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  const headers = {
    Authorization: `Bearer ${token}`,
    Accept: 'application/json',
  }
  if (user?.institute_id) {
    headers['X-Institute-Id'] = user.institute_id
  }

  const res = await fetch(`${API_BASE_URL}/teacher/materials/${id}`, {
    method: 'DELETE',
    headers,
    credentials: 'include',
  })
  
  if (!res.ok) throw new Error('Failed to delete material')
  return res.json()
}

export const getAdminStats = async () => {
  const res = await fetch(`${API_BASE_URL}/admin/stats`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load admin stats')
  return res.json()
}

export const getAdminStudents = async (params = {}) => {
  const query = new URLSearchParams(params).toString()
  const res = await fetch(`${API_BASE_URL}/admin/students?${query}`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load students')
  return res.json()
}

export const getAdminTeachers = async () => {
  const res = await fetch(`${API_BASE_URL}/admin/teachers`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load teachers')
  return res.json()
}

export const getAdminFinance = async () => {
  const res = await fetch(`${API_BASE_URL}/admin/finance`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load finance data')
  return res.json()
}

export const getAdminZoom = async () => {
  const res = await fetch(`${API_BASE_URL}/admin/zoom`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load zoom classes')
  return res.json()
}

export const bulkDeleteAdminStudents = async (ids) => {
  const res = await fetch(`${API_BASE_URL}/admin/students/bulk-delete`, {
    method: 'POST',
    headers: getAuthHeaders(),
    credentials: 'include',
    body: JSON.stringify({ ids }),
  })
  if (!res.ok) throw new Error('Failed to delete students')
  return res.json()
}

export const bulkDeleteAdminTeachers = async (ids) => {
  const res = await fetch(`${API_BASE_URL}/admin/teachers/bulk-delete`, {
    method: 'POST',
    headers: getAuthHeaders(),
    credentials: 'include',
    body: JSON.stringify({ ids }),
  })
  if (!res.ok) throw new Error('Failed to delete teachers')
  return res.json()
}

export const bulkDeleteAdminZoomClasses = async (ids) => {
  const res = await fetch(`${API_BASE_URL}/admin/zoom/bulk-delete`, {
    method: 'POST',
    headers: getAuthHeaders(),
    credentials: 'include',
    body: JSON.stringify({ ids }),
  })
  if (!res.ok) throw new Error('Failed to delete zoom classes')
  return res.json()
}

export const getAdminMaterials = async () => {
  const res = await fetch(`${API_BASE_URL}/admin/materials`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load materials')
  return res.json()
}

export const getAdminSettings = async () => {
  const res = await fetch(`${API_BASE_URL}/admin/settings`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load settings')
  return res.json()
}

export const getAdminAssignments = async () => {
  const res = await fetch(`${API_BASE_URL}/admin/assignments`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load assignments')
  return res.json()
}

export const getAdminCalendar = async (start, end) => {
  const query = new URLSearchParams({ start, end }).toString()
  const res = await fetch(`${API_BASE_URL}/admin/calendar?${query}`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load calendar events')
  return res.json()
}

export const getAdminAttendance = async () => {
  const res = await fetch(`${API_BASE_URL}/admin/attendance`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load attendance records')
  return res.json()
}

export const updateAdminBranding = async (formData) => {
  const res = await fetch(`${API_BASE_URL}/admin/branding`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${localStorage.getItem('authToken')}`,
      Accept: 'application/json',
    },
    credentials: 'include',
    body: formData,
  })
  if (!res.ok) throw new Error('Failed to update branding')
  return res.json()
}

export const updateAdminSettings = async (data) => {
  const res = await fetch(`${API_BASE_URL}/admin/settings`, {
    method: 'POST',
    headers: getAuthHeaders(),
    credentials: 'include',
    body: JSON.stringify(data),
  })
  if (!res.ok) throw new Error('Failed to update settings')
  return res.json()
}

// SUPER ADMIN API
export const getSuperAdminStats = async () => {
  const res = await fetch(`${API_BASE_URL}/super-admin/stats`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load super admin stats')
  return res.json()
}

export const getSuperAdminInstitutes = async () => {
  const res = await fetch(`${API_BASE_URL}/super-admin/institutes`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load institutes')
  return res.json()
}

export const getSuperAdminPlans = async () => {
  const res = await fetch(`${API_BASE_URL}/super-admin/plans`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load plans')
  return res.json()
}

export const updateSuperAdminInstitute = async (id, data) => {
  const res = await fetch(`${API_BASE_URL}/super-admin/institutes/${id}`, {
    method: 'PATCH',
    headers: getAuthHeaders(),
    credentials: 'include',
    body: JSON.stringify(data),
  })
  if (!res.ok) throw new Error('Failed to update institute')
  return res.json()
}

export const getSuperAdminActivityLogs = async () => {
  const res = await fetch(`${API_BASE_URL}/super-admin/activity-logs`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load activity logs')
  return res.json()
}

// ── EXAM RESULTS API ──

// Public: Search exam results
export const searchExamResults = async (params) => {
  const query = new URLSearchParams(params).toString()
  const res = await fetch(`${API_BASE_URL}/exam-results/search?${query}`, {
    credentials: 'include',
  })
  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.message || 'No results found')
  }
  return res.json()
}

// Public: Get available terms
export const getExamTerms = async () => {
  const res = await fetch(`${API_BASE_URL}/exam-results/terms`, {
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load terms')
  return res.json()
}

// Public: Get available grades
export const getExamGrades = async () => {
  const res = await fetch(`${API_BASE_URL}/exam-results/grades`, {
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load grades')
  return res.json()
}

// Public: Get available years
export const getExamYears = async () => {
  const res = await fetch(`${API_BASE_URL}/exam-results/years`, {
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load years')
  return res.json()
}

// Admin: Get all exam results
export const getAdminExamResults = async (params = {}) => {
  const query = new URLSearchParams(params).toString()
  const res = await fetch(`${API_BASE_URL}/admin/exam-results?${query}`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load exam results')
  return res.json()
}

// Admin: Create result
export const createExamResult = async (data) => {
  const res = await fetch(`${API_BASE_URL}/admin/exam-results`, {
    method: 'POST',
    headers: getAuthHeaders(),
    credentials: 'include',
    body: JSON.stringify(data),
  })
  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.message || 'Failed to create result')
  }
  return res.json()
}

// Admin: Update result
export const updateExamResult = async (id, data) => {
  const res = await fetch(`${API_BASE_URL}/admin/exam-results/${id}`, {
    method: 'PUT',
    headers: getAuthHeaders(),
    credentials: 'include',
    body: JSON.stringify(data),
  })
  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.message || 'Failed to update result')
  }
  return res.json()
}

// Admin: Delete result
export const deleteExamResult = async (id) => {
  const res = await fetch(`${API_BASE_URL}/admin/exam-results/${id}`, {
    method: 'DELETE',
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to delete result')
  return res.json()
}

// Admin: Bulk delete
export const bulkDeleteExamResults = async (ids) => {
  const res = await fetch(`${API_BASE_URL}/admin/exam-results/bulk-delete`, {
    method: 'POST',
    headers: getAuthHeaders(),
    credentials: 'include',
    body: JSON.stringify({ ids }),
  })
  if (!res.ok) throw new Error('Failed to delete results')
  return res.json()
}

// Admin: Import file
export const importExamResults = async (formData) => {
  const token = localStorage.getItem('authToken')
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  const headers = {
    Authorization: `Bearer ${token}`,
    Accept: 'application/json',
  }
  if (user?.institute_id) {
    headers['X-Institute-Id'] = user.institute_id
  }

  const res = await fetch(`${API_BASE_URL}/admin/exam-results/import`, {
    method: 'POST',
    headers,
    credentials: 'include',
    body: formData,
  })
  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.message || 'Import failed')
  }
  return res.json()
}

// Admin: Export results URL
export const getExamResultsExportUrl = (params = {}) => {
  const query = new URLSearchParams(params).toString()
  return `${API_BASE_URL}/admin/exam-results/export?${query}`
}

// Admin: Get terms list (for management)
export const getAdminExamTerms = async () => {
  const res = await fetch(`${API_BASE_URL}/admin/exam-results/terms`, {
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to load terms')
  return res.json()
}

// Admin: Add term
export const createExamTerm = async (name) => {
  const res = await fetch(`${API_BASE_URL}/admin/exam-results/terms`, {
    method: 'POST',
    headers: getAuthHeaders(),
    credentials: 'include',
    body: JSON.stringify({ name }),
  })
  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.message || 'Failed to add term')
  }
  return res.json()
}

// Admin: Delete term
export const deleteExamTerm = async (id) => {
  const res = await fetch(`${API_BASE_URL}/admin/exam-results/terms/${id}`, {
    method: 'DELETE',
    headers: getAuthHeaders(),
    credentials: 'include',
  })
  if (!res.ok) throw new Error('Failed to delete term')
  return res.json()
}
