import React, { useEffect } from 'react'
import { BrowserRouter as Router, Routes, Route, useSearchParams, useLocation, Navigate } from 'react-router-dom'
import Header from './components/Header'

function RagRouteGuard({ children }) {
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  if (user?.rag_enabled === false) {
    return <Navigate to="/student/dashboard" replace />
  }
  return children
}
import Footer from './components/Footer'
import ScrollToTop from './components/ScrollToTop'
import CustomCursor from './components/CustomCursor'
import HomePage from './pages/HomePage'
import AboutPage from './pages/AboutPage'
import ContactPage from './pages/ContactPage'
import ClassesPage from './pages/ClassesPage'
import BlogsPage from './pages/BlogsPage'
import BlogPage from './pages/BlogPage'
import NotesPage from './pages/NotesPage'
import PastPapersPage from './pages/PastPapersPage'
import RecordingsPage from './pages/RecordingsPage'
import RegisterPage from './pages/RegisterPage'
import PolicyPage from './pages/PolicyPage'
import TutorApplyPage from './pages/TutorApplyPage'
import StudentDashboard from './pages/StudentDashboard'
import StudentOverview from './pages/student/StudentOverview'
import StudentZoom from './pages/student/StudentZoom'
import StudentAssignments from './pages/student/StudentAssignments'
import StudentMaterials from './pages/student/StudentMaterials'
import StudentSchedule from './pages/student/StudentSchedule'
import StudentPerformance from './pages/student/StudentPerformance'
import StudentSettings from './pages/student/StudentSettings'
import StudentStudyAssistant from './components/StudentStudyAssistant'
import AdminDashboard from './pages/AdminDashboard'
import SuperAdminDashboard from './pages/SuperAdminDashboard'
import AdminOverview from './pages/admin/AdminOverview'
import AdminStudents from './pages/admin/AdminStudents'
import AdminTeachers from './pages/admin/AdminTeachers'
import AdminFinance from './pages/admin/AdminFinance'
import AdminZoom from './pages/admin/AdminZoom'
import AdminMaterials from './pages/admin/AdminMaterials'
import MessagingPage from './pages/MessagingPage'
import AdminSettings from './pages/admin/AdminSettings'
import AdminAssignments from './pages/admin/AdminAssignments'
import AdminAttendance from './pages/admin/AdminAttendance'
import AdminCalendar from './pages/admin/AdminCalendar'
import TeacherDashboard from './pages/TeacherDashboard'
import TeacherOverview from './pages/teacher/TeacherOverview'
import TeacherStudents from './pages/teacher/TeacherStudents'
import TeacherAssignments from './pages/teacher/TeacherAssignments'
import TeacherMaterials from './pages/teacher/TeacherMaterials'
import TeacherSchedule from './pages/teacher/TeacherSchedule'
import TeacherReports from './pages/teacher/TeacherReports'
import TeacherSettings from './pages/teacher/TeacherSettings'
import { ToastProvider } from './components/shared/ToastContext'
import { ConfirmProvider } from './components/shared/ConfirmDialog'
import { SettingsProvider } from './context/SettingsContext'
import { LanguageProvider } from './context/LanguageContext'
import { AuthModalProvider } from './context/AuthModalContext'
import './App.css'

// Wrapper component that includes logout handler
function PageWrapper({ children }) {
  const [searchParams, setSearchParams] = useSearchParams()

  useEffect(() => {
    // Check if logout parameter is present
    if (searchParams.get('logout') === '1') {
      // Clear all authentication data
      localStorage.removeItem('authToken')
      localStorage.removeItem('user')

      // Remove logout parameter from URL
      searchParams.delete('logout')
      searchParams.delete('from')
      setSearchParams(searchParams, { replace: true })

      console.log('✅ Logged out from dashboard - localStorage cleared')
    }
  }, [searchParams, setSearchParams])

  return <>{children}</>
}

function AppContent() {
  const location = useLocation()
  const isDashboard = location.pathname.startsWith('/teacher') ||
    location.pathname.startsWith('/student') ||
    location.pathname.startsWith('/admin') ||
    location.pathname.startsWith('/super-admin')

  const [blockedStatus, setBlockedStatus] = React.useState(null)

  React.useEffect(() => {
    const handler = (e) => {
      setBlockedStatus(e.detail)
    }
    window.addEventListener('institute-status-blocked', handler)
    return () => window.removeEventListener('institute-status-blocked', handler)
  }, [])

  if (blockedStatus) {
    const isExpired = blockedStatus.code === 'institute_expired'
    return (
      <div className="institute-blocked-fullscreen" style={{
        minHeight: '100dvh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: '#f8fafc',
        padding: '2rem',
        fontFamily: 'Inter, system-ui, sans-serif',
        color: '#0f172a'
      }}>
        <div style={{
          maxWidth: '32rem',
          width: '100%',
          background: '#ffffff',
          borderRadius: '1rem',
          padding: '2.5rem',
          boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05)',
          textAlign: 'center',
          border: '1px solid #e2e8f0'
        }}>
          <div style={{
            width: '4rem',
            height: '4rem',
            borderRadius: '50%',
            background: isExpired ? '#fef2f2' : '#fffbeb',
            color: isExpired ? '#ef4444' : '#f59e0b',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            margin: '0 auto 1.5rem',
            fontSize: '1.75rem'
          }}>
            {isExpired ? '⏳' : '🔒'}
          </div>
          <h2 style={{ fontSize: '1.5rem', fontWeight: '700', marginBottom: '0.75rem', color: '#0f172a' }}>
            {isExpired ? 'Subscription has ended' : 'This institute is temporarily unavailable'}
          </h2>
          <p style={{ color: '#64748b', fontSize: '0.95rem', lineHeight: '1.6', marginBottom: '1.5rem' }}>
            {isExpired
              ? 'The subscription for this institute has expired. Please contact the administrator or platform support to renew access.'
              : 'Access to this institute is temporarily restricted. Please contact the administrator or platform support for assistance.'}
          </p>
          <div style={{ paddingTop: '1rem', borderTop: '1px solid #f1f5f9', fontSize: '0.875rem', color: '#94a3b8' }}>
            Please contact support if you believe this is an error.
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="App" style={{ minHeight: '100dvh', background: isDashboard ? '#f8fafc' : '#ffffff', width: '100%' }}>
      <CustomCursor />
      {!isDashboard && <Header />}
      <Routes>
        <Route path="/" element={<PageWrapper><HomePage /></PageWrapper>} />
        <Route path="/about" element={<PageWrapper><AboutPage /></PageWrapper>} />
        <Route path="/contact" element={<PageWrapper><ContactPage /></PageWrapper>} />
        <Route path="/classes" element={<PageWrapper><ClassesPage /></PageWrapper>} />
        <Route path="/blogs" element={<PageWrapper><BlogsPage /></PageWrapper>} />
        <Route path="/blog/:id" element={<PageWrapper><BlogPage /></PageWrapper>} />
        <Route path="/notes" element={<PageWrapper><NotesPage /></PageWrapper>} />
        <Route path="/past-papers" element={<PageWrapper><PastPapersPage /></PageWrapper>} />
        <Route path="/recordings" element={<PageWrapper><RecordingsPage /></PageWrapper>} />
        <Route path="/register" element={<PageWrapper><RegisterPage /></PageWrapper>} />

        {/* Policy Routes */}
        <Route path="/privacy" element={<PageWrapper><PolicyPage type="privacy" /></PageWrapper>} />
        <Route path="/terms" element={<PageWrapper><PolicyPage type="terms" /></PageWrapper>} />
        <Route path="/refund" element={<PageWrapper><PolicyPage type="refund" /></PageWrapper>} />
        <Route path="/tutor-apply" element={<PageWrapper><TutorApplyPage /></PageWrapper>} />

        {/* Student Dashboard Routes */}
        <Route path="/student" element={<PageWrapper><StudentDashboard /></PageWrapper>}>
          <Route index element={<StudentOverview />} />
          <Route path="dashboard" element={<StudentOverview />} />
          <Route path="overview" element={<StudentOverview />} />
          <Route path="schedule" element={<StudentSchedule />} />
          <Route path="zoom" element={<StudentZoom />} />
          <Route path="assignments" element={<StudentAssignments />} />
          <Route path="materials" element={<StudentMaterials />} />
          <Route path="performance" element={<StudentPerformance />} />
          <Route path="messages" element={<MessagingPage />} />
          <Route path="settings" element={<StudentSettings />} />
          <Route path="study-assistant" element={<RagRouteGuard><StudentStudyAssistant /></RagRouteGuard>} />
        </Route>

        {/* Admin Master Control Routes */}
        <Route path="/admin" element={<PageWrapper><AdminDashboard /></PageWrapper>}>
          <Route index element={<AdminOverview />} />
          <Route path="dashboard" element={<AdminOverview />} />
          <Route path="analytics" element={<AdminOverview />} />
          <Route path="students" element={<AdminStudents />} />
          <Route path="teachers" element={<AdminTeachers />} />
          <Route path="calendar" element={<AdminCalendar />} />
          <Route path="zoom" element={<AdminZoom />} />
          <Route path="attendance" element={<AdminAttendance />} />
          <Route path="materials" element={<AdminMaterials />} />
          <Route path="assignments" element={<AdminAssignments />} />
          <Route path="messages" element={<MessagingPage />} />
          <Route path="payments" element={<AdminFinance />} />
          <Route path="settings" element={<AdminSettings />} />
        </Route>

        <Route path="/super-admin/*" element={<SuperAdminDashboard />} />

        {/* Teacher Dashboard Routes */}
        <Route path="/teacher" element={<PageWrapper><TeacherDashboard /></PageWrapper>}>
          <Route index element={<TeacherOverview />} />
          <Route path="dashboard" element={<TeacherOverview />} />
          <Route path="students" element={<TeacherStudents />} />
          <Route path="assignments" element={<TeacherAssignments />} />
          <Route path="materials" element={<TeacherMaterials />} />
          <Route path="schedule" element={<TeacherSchedule />} />
          <Route path="reports" element={<TeacherReports />} />
          <Route path="messages" element={<MessagingPage />} />
          <Route path="settings" element={<TeacherSettings />} />
        </Route>
      </Routes>
      {!isDashboard && <Footer />}
    </div>
  )
}

function App() {
  return (
    <ToastProvider>
      <ConfirmProvider>
        <SettingsProvider>
          <LanguageProvider>
            <AuthModalProvider>
              <Router
                future={{
                  v7_startTransition: true,
                  v7_relativeSplatPath: true
                }}
              >
                <ScrollToTop />
                <AppContent />
              </Router>
            </AuthModalProvider>
          </LanguageProvider>
        </SettingsProvider>
      </ConfirmProvider>
    </ToastProvider>
  )
}

export default App

