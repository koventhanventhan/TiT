import React, { useEffect, Suspense, lazy } from 'react'
import { BrowserRouter as Router, Routes, Route, useSearchParams, useLocation } from 'react-router-dom'
import { HelmetProvider } from 'react-helmet-async'
import { ToastProvider } from './components/shared/ToastContext'
import Header from './components/Header'
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
import ExamResultsPage from './pages/ExamResultsPage'
import TutorApplyPage from './pages/TutorApplyPage'
import SelectProfile from './pages/SelectProfile'

const StudentDashboard = lazy(() => import('./pages/StudentDashboard'))
const StudentOverview = lazy(() => import('./pages/student/StudentOverview'))
const StudentZoom = lazy(() => import('./pages/student/StudentZoom'))
const StudentAssignments = lazy(() => import('./pages/student/StudentAssignments'))
const StudentMaterials = lazy(() => import('./pages/student/StudentMaterials'))
const StudentSchedule = lazy(() => import('./pages/student/StudentSchedule'))
const StudentPerformance = lazy(() => import('./pages/student/StudentPerformance'))
const StudentSettings = lazy(() => import('./pages/student/StudentSettings'))
const SuperAdminDashboard = lazy(() => import('./pages/SuperAdminDashboard'))
const MessagingPage = lazy(() => import('./pages/MessagingPage'))
const TeacherDashboard = lazy(() => import('./pages/TeacherDashboard'))
const TeacherOverview = lazy(() => import('./pages/teacher/TeacherOverview'))
const TeacherStudents = lazy(() => import('./pages/teacher/TeacherStudents'))
const TeacherAssignments = lazy(() => import('./pages/teacher/TeacherAssignments'))
const TeacherMaterials = lazy(() => import('./pages/teacher/TeacherMaterials'))
const TeacherSchedule = lazy(() => import('./pages/teacher/TeacherSchedule'))
const TeacherReports = lazy(() => import('./pages/teacher/TeacherReports'))
const TeacherSettings = lazy(() => import('./pages/teacher/TeacherSettings'))

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

  return (
    <div className="App" style={{ minHeight: '100dvh', background: isDashboard ? '#f8fafc' : '#ffffff', width: '100%' }}>
      <CustomCursor />
      {!isDashboard && <Header />}
      <main className="main-content">
        <Suspense fallback={<div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh', width: '100%' }}>Loading...</div>}>
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
            <Route path="/exam-results" element={<PageWrapper><ExamResultsPage /></PageWrapper>} />
            <Route path="/register" element={<PageWrapper><RegisterPage /></PageWrapper>} />
            <Route path="/select-profile" element={<PageWrapper><SelectProfile /></PageWrapper>} />

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
        </Suspense>
      </main>
      {!isDashboard && <Footer />}
    </div>
  )
}

function App() {
  return (
    <HelmetProvider>
      <ToastProvider>
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
      </ToastProvider>
    </HelmetProvider>
  )
}

export default App

