import React, { useEffect } from 'react'
import { BrowserRouter as Router, Routes, Route, useSearchParams, useLocation } from 'react-router-dom'
import Header from './components/Header'
import Footer from './components/Footer'
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
import StudentDashboard from './pages/StudentDashboard'
import StudentOverview from './pages/student/StudentOverview'
import StudentZoom from './pages/student/StudentZoom'
import StudentAssignments from './pages/student/StudentAssignments'
import StudentMaterials from './pages/student/StudentMaterials'
import StudentSchedule from './pages/student/StudentSchedule'
import StudentPerformance from './pages/student/StudentPerformance'
import StudentSettings from './pages/student/StudentSettings'
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
    <SettingsProvider>
      <LanguageProvider>
        <AuthModalProvider>
          <Router
            future={{
              v7_startTransition: true,
              v7_relativeSplatPath: true
            }}
          >
            <AppContent />
          </Router>
        </AuthModalProvider>
      </LanguageProvider>
    </SettingsProvider>
  )
}

export default App

