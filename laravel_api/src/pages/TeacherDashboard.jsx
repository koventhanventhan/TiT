import React, { useState, useEffect } from 'react'
import { useNavigate, Outlet, useLocation } from 'react-router-dom'
import { getCurrentUser } from '../services/authService'
import TeacherDashboardLayout from '../components/teacher/TeacherDashboardLayout'

export default function TeacherDashboard() {
  const navigate = useNavigate()
  const location = useLocation()
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    async function checkAuth() {
      const u = await getCurrentUser()
      if (!u || (u.role || '').toLowerCase() !== 'teacher') {
        setLoading(false)
        navigate('/')
        return
      }
      setUser(u)

      // Auto-redirect to /teacher/dashboard if at /teacher
      if (location.pathname === '/teacher' || location.pathname === '/teacher/') {
        navigate('/teacher/dashboard')
      }
      setLoading(false)
    }
    checkAuth()
  }, [navigate, location])

  if (loading) return (
    <div className="flex items-center justify-center min-h-screen bg-slate-50">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>
  )
  
  if (!user) return null;

  return (
    <TeacherDashboardLayout user={user}>
      <Outlet />
    </TeacherDashboardLayout>
  )
}
