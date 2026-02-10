import React from 'react'
import { NavLink } from 'react-router-dom'
import { 
  FiHome, 
  FiCalendar, 
  FiUsers, 
  FiFileText, 
  FiBookOpen, 
  FiBarChart2, 
  FiSettings,
  FiLogOut
} from 'react-icons/fi'
import './TeacherSidebar.css'

export default function TeacherSidebar() {
  const menuItems = [
    { name: 'Overview', icon: <FiHome />, path: '/teacher/dashboard' },
    { name: 'Schedule', icon: <FiCalendar />, path: '/teacher/schedule' },
    { name: 'Students', icon: <FiUsers />, path: '/teacher/students' },
    { name: 'Assignments', icon: <FiFileText />, path: '/teacher/assignments' },
    { name: 'Materials', icon: <FiBookOpen />, path: '/teacher/materials' },
    { name: 'Reports', icon: <FiBarChart2 />, path: '/teacher/reports' },
    { name: 'Settings', icon: <FiSettings />, path: '/teacher/settings' },
  ]

  return (
    <aside className="teacher-sidebar">
      <div className="sidebar-header">
        <div className="sidebar-logo">
          <span className="logo-icon">T</span>
          <span className="logo-text">Console</span>
        </div>
      </div>
      
      <nav className="sidebar-nav">
        {menuItems.map((item) => (
          <NavLink 
            key={item.name} 
            to={item.path} 
            className={({ isActive }) => `sidebar-link ${isActive ? 'active' : ''}`}
            end={item.path === '/teacher/dashboard'}
          >
            <span className="link-icon">{item.icon}</span>
            <span className="link-text">{item.name}</span>
          </NavLink>
        ))}
      </nav>

      <div className="sidebar-footer">
        <button className="logout-btn" onClick={() => window.location.href = '/?logout=1'}>
          <FiLogOut /> <span>Logout</span>
        </button>
      </div>
    </aside>
  )
}
