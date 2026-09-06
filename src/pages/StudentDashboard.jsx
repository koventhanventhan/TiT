import React, { useState, useEffect } from 'react'
import { useNavigate, Outlet } from 'react-router-dom'
import { getCurrentUser } from '../services/authService'
import StudentDashboardLayout from '../components/student/StudentDashboardLayout'
import DeactivatedDashboard from '../components/student/DeactivatedDashboard'
import PendingApprovalDashboard from '../components/student/PendingApprovalDashboard'
import PaymentRequiredDashboard from '../components/student/PaymentRequiredDashboard'
import StudentRegistrationForm from '../components/StudentRegistrationForm'

export default function StudentDashboard() {
  const navigate = useNavigate()
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)
  const [incompleteStep, setIncompleteStep] = useState(null)

  useEffect(() => {
    async function checkAuth() {
      const u = await getCurrentUser()
      if (!u) {
        setLoading(false)
        navigate('/')
        return
      }
      const role = (u.role || '').toLowerCase()
      if (role !== 'user' && role !== 'student') {
        setLoading(false)
        navigate('/')
        return
      }

      // Check registration status
      console.log('🔍 Dashboard Auth Check - User:', u)

      const regStatus = (u.registration_status || '').toLowerCase()
      if (!u.full_name) {
        console.log('📝 Student has incomplete profile, showing registration flow step 1...')
        setIncompleteStep(1)
      } else if (regStatus === 'pending_payment') {
        console.log('📝 Student has pending payment, showing registration flow step 2...')
        setIncompleteStep(2)
      }

      setUser(u)
      setLoading(false)
    }
    checkAuth()
  }, [navigate])

  if (loading) return (
    <div className="flex items-center justify-center min-h-screen bg-slate-50">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>
  )
  
  if (!user) return null;

  if (incompleteStep) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center p-4">
        <div style={{ maxWidth: '800px', width: '100%', background: '#fff', borderRadius: '12px', boxShadow: '0 4px 20px rgba(0,0,0,0.1)', overflow: 'hidden' }}>
          <StudentRegistrationForm inline={true} />
        </div>
      </div>
    )
  }

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
