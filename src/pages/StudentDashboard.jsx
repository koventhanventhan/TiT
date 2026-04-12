import React, { useState, useEffect } from 'react'
import { useNavigate, Outlet } from 'react-router-dom'
import { getCurrentUser } from '../services/authService'
import StudentDashboardLayout from '../components/student/StudentDashboardLayout'
import DeactivatedDashboard from '../components/student/DeactivatedDashboard'
import PendingApprovalDashboard from '../components/student/PendingApprovalDashboard'
import PaymentRequiredDashboard from '../components/student/PaymentRequiredDashboard'

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

      // Check registration status
      console.log('🔍 Dashboard Auth Check - User:', u)

      const regStatus = (u.registration_status || '').toLowerCase()
      if (regStatus === 'pending_payment' || !u.full_name) {
        console.log('📝 Student has pending payment or incomplete profile, redirecting to registration flow...')
        navigate('/register?step=2')
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

  // Check for admin approval (unless they are still in pending_payment redirection phase)
  const isConfirmed = user.admin_confirmed_at &&
    user.admin_confirmed_at !== 'null' &&
    user.admin_confirmed_at !== '0000-00-00 00:00:00';

  if (!isConfirmed) {
    console.log('⏳ User found but not yet confirmed by admin')
    return <PendingApprovalDashboard user={user} />
  }

  if (!user.is_paid) {
    console.log('💰 User confirmed but payment missing for this month')
    return <PaymentRequiredDashboard user={user} />
  }

  return (
    <StudentDashboardLayout user={user}>
      <Outlet />
    </StudentDashboardLayout>
  )
}
