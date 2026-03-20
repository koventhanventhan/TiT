const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

const getAuthHeaders = () => {
  const token = localStorage.getItem('authToken')
  const user = JSON.parse(localStorage.getItem('user') || '{}')

  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  }

  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  if (user?.institute_id) {
    headers['X-Institute-Id'] = user.institute_id
  }

  return headers
}

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
    headers: {
      Authorization: `Bearer ${localStorage.getItem('authToken')}`,
      Accept: 'application/json',
    },
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
    headers: {
      Authorization: `Bearer ${localStorage.getItem('authToken')}`,
      Accept: 'application/json',
    },
    credentials: 'include',
    body: formData,
  })
  if (!res.ok) throw new Error('Failed to create assignment')
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
