import React, { useState, useEffect } from 'react'
import { useNavigate, Outlet } from 'react-router-dom'
import { getCurrentUser } from '../services/authService'
import StudentDashboardLayout from '../components/student/StudentDashboardLayout'
import DeactivatedDashboard from '../components/student/DeactivatedDashboard'

export default function StudentDashboard() {
  const navigate = useNavigate()
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    async function checkAuth() {
      const u = await getCurrentUser()
      if (!u) {
        navigate('/')
        return
      }
      const role = (u.role || '').toLowerCase()
      if (role !== 'user' && role !== 'student') {
        navigate('/')
        return
      }
      setUser(u)
      setLoading(false)
    }
    checkAuth()
  }, [navigate])

  if (loading || !user) return (
    <div className="flex items-center justify-center min-h-screen">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>
  )

  if (user.is_deactivated) {
    return <DeactivatedDashboard user={user} />
  }

  return (
    <StudentDashboardLayout user={user}>
      <Outlet />
    </StudentDashboardLayout>
  )
}
