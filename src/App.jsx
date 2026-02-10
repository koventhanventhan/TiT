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
import AdminDashboard from './pages/AdminDashboard'
import SuperAdminDashboard from './pages/SuperAdminDashboard'
import AdminOverview from './pages/admin/AdminOverview'
import AdminStudents from './pages/admin/AdminStudents'
import AdminTeachers from './pages/admin/AdminTeachers'
import AdminFinance from './pages/admin/AdminFinance'
import AdminZoom from './pages/admin/AdminZoom'
import AdminMaterials from './pages/admin/AdminMaterials'
import AdminMessages from './pages/admin/AdminMessages'
import AdminSettings from './pages/admin/AdminSettings'
import AdminAssignments from './pages/admin/AdminAssignments'
import AdminAttendance from './pages/admin/AdminAttendance'
import TeacherDashboard from './pages/TeacherDashboard'
import TeacherOverview from './pages/teacher/TeacherOverview'
import TeacherStudents from './pages/teacher/TeacherStudents'
import TeacherAssignments from './pages/teacher/TeacherAssignments'
import TeacherMaterials from './pages/teacher/TeacherMaterials'
import { SettingsProvider } from './context/SettingsContext'
import { LanguageProvider } from './context/LanguageContext'
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
    location.pathname.startsWith('/student/dashboard') ||
    location.pathname.startsWith('/admin/dashboard')

  return (
    <div className="App" style={{ minHeight: '100vh', background: isDashboard ? '#f8fafc' : '#ffffff', width: '100%' }}>
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
        <Route path="/student/dashboard" element={<PageWrapper><StudentDashboard /></PageWrapper>}>
          <Route index element={<StudentOverview />} />
          <Route path="overview" element={<StudentOverview />} />
          <Route path="schedule" element={<StudentOverview />} />
          <Route path="zoom" element={<StudentOverview />} />
          <Route path="assignments" element={<StudentOverview />} />
          <Route path="materials" element={<StudentOverview />} />
          <Route path="performance" element={<StudentOverview />} />
          <Route path="settings" element={<StudentOverview />} />
        </Route>

        {/* Admin Master Control Routes */}
        <Route path="/admin/dashboard" element={<PageWrapper><AdminDashboard /></PageWrapper>}>
          <Route index element={<AdminOverview />} />
          <Route path="analytics" element={<AdminOverview />} />
          <Route path="students" element={<AdminStudents />} />
          <Route path="teachers" element={<AdminTeachers />} />
          <Route path="zoom" element={<AdminZoom />} />
          <Route path="attendance" element={<AdminAttendance />} />
          <Route path="materials" element={<AdminMaterials />} />
          <Route path="assignments" element={<AdminAssignments />} />
          <Route path="messages" element={<AdminMessages />} />
          <Route path="payments" element={<AdminFinance />} />
          <Route path="settings" element={<AdminSettings />} />
        </Route>

        <Route path="/super-admin/*" element={<SuperAdminDashboard />} />

        {/* Teacher Dashboard Routes */}
        <Route path="/teacher" element={<PageWrapper><TeacherDashboard /></PageWrapper>}>
          <Route path="dashboard" element={<TeacherOverview />} />
          <Route path="students" element={<TeacherStudents />} />
          <Route path="assignments" element={<TeacherAssignments />} />
          <Route path="materials" element={<TeacherMaterials />} />
          <Route path="schedule" element={<TeacherOverview />} />
          <Route path="reports" element={<TeacherOverview />} />
          <Route path="settings" element={<TeacherOverview />} />
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
        <Router
          future={{
            v7_startTransition: true,
            v7_relativeSplatPath: true
          }}
        >
          <AppContent />
        </Router>
      </LanguageProvider>
    </SettingsProvider>
  )
}

export default App

