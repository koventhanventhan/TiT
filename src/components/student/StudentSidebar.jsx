import React from 'react'
import { NavLink } from 'react-router-dom'
import {
    FiHome,
    FiCalendar,
    FiVideo,
    FiFileText,
    FiBookOpen,
    FiBarChart2,
    FiMessageSquare,
    FiSettings,
    FiLogOut,
    FiCreditCard,
    FiUsers
} from 'react-icons/fi'
import { SelectedChildContext } from '../../context/SelectedChildContext'
import './StudentSidebar.css'

export default function StudentSidebar() {
    const { selectedChild, changeSelectedChild, childrenList } = React.useContext(SelectedChildContext)

    const menuItems = [
        { name: 'Dashboard', icon: <FiHome />, path: '/student/dashboard' },
        { name: 'Payments', icon: <FiCreditCard />, path: '/student/overview' }, // Redirect to overview where module is
        { name: 'Schedule', icon: <FiCalendar />, path: '/student/schedule' },
        { name: 'Zoom Classes', icon: <FiVideo />, path: '/student/zoom' },
        { name: 'Assignments', icon: <FiFileText />, path: '/student/assignments' },
        { name: 'Materials', icon: <FiBookOpen />, path: '/student/materials' },
        // { name: 'Performance', icon: <FiBarChart2 />, path: '/student/performance' }, // Hidden temporarily
        { name: 'Messages', icon: <FiMessageSquare />, path: '/student/messages' },
        { name: 'Settings', icon: <FiSettings />, path: '/student/settings' },
    ]

    return (
        <aside className="student-sidebar">
            <div className="sidebar-header">
                <div className="sidebar-logo">
                    <span className="logo-icon student">S</span>
                    <span className="logo-text">Student Portal</span>
                </div>
                
                {childrenList && childrenList.length > 0 && (
                    <div className="child-selector">
                        {childrenList.length <= 3 ? (
                            <div className="child-chips">
                                {childrenList.map(child => (
                                    <button
                                        key={child.id}
                                        className={`child-chip ${selectedChild?.id === child.id ? 'active' : ''}`}
                                        onClick={() => changeSelectedChild(child)}
                                    >
                                        <FiUsers className="chip-icon" />
                                        <span>{child.first_name || child.full_name?.split(' ')[0]}</span>
                                    </button>
                                ))}
                            </div>
                        ) : (
                            <select 
                                className="child-dropdown"
                                value={selectedChild?.id || ''}
                                onChange={(e) => {
                                    const child = childrenList.find(c => c.id === parseInt(e.target.value))
                                    if (child) changeSelectedChild(child)
                                }}
                            >
                                {childrenList.map(child => (
                                    <option key={child.id} value={child.id}>
                                        {child.full_name}
                                    </option>
                                ))}
                            </select>
                        )}
                    </div>
                )}
            </div>

            <nav className="sidebar-nav">
                {menuItems.map((item) => (
                    <NavLink
                        key={item.name}
                        to={item.path}
                        className={({ isActive }) => `sidebar-link ${isActive ? 'active' : ''}`}
                        end={item.path === '/student/dashboard'}
                    >
                        <span className="link-icon">{item.icon}</span>
                        <span className="link-text">{item.name}</span>
                    </NavLink>
                ))}
            </nav>

            <div className="sidebar-footer">
                <button className="logout-btn" onClick={() => {
                    import('../../services/authService').then(m => m.logout());
                }}>
                    <FiLogOut /> <span>Logout</span>
                </button>
            </div>
        </aside>
    )
}
