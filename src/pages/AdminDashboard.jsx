import React, { useState, useEffect } from 'react'
import { useNavigate, Outlet } from 'react-router-dom'
import { getCurrentUser } from '../services/authService'
import AdminDashboardLayout from '../components/teacher/AdminDashboardLayout'

export default function AdminDashboard() {
    const navigate = useNavigate()
    const [user, setUser] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function checkAuth() {
            const u = await getCurrentUser()
            if (!u || u.role !== 'admin') {
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
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600"></div>
        </div>
    )

    return (
        <AdminDashboardLayout user={user}>
            <Outlet />
        </AdminDashboardLayout>
    )
}
