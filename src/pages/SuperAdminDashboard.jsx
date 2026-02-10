import React, { useState, useEffect } from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import SuperAdminLayout from '../components/super-admin/SuperAdminLayout'
import SuperAdminOverview from './super-admin/SuperAdminOverview'
import SuperAdminInstitutes from './super-admin/SuperAdminInstitutes'
import SuperAdminPlans from './super-admin/SuperAdminPlans'
import { getCurrentUser } from '../services/authService'

export default function SuperAdminDashboard() {
    const [user, setUser] = useState(null)
    const [loading, setLoading] = useState(true)

    useEffect(() => {
        async function checkAuth() {
            const u = await getCurrentUser()
            if (!u || u.role !== 'super_admin') {
                // Redirect if not super admin
                window.location.href = '/'
                return
            }
            setUser(u)
            setLoading(false)
        }
        checkAuth()
    }, [])

    if (loading) return <div>Initialising SaaS Control Plane...</div>

    return (
        <SuperAdminLayout user={user}>
            <Routes>
                <Route index element={<Navigate to="dashboard" />} />
                <Route path="dashboard" element={<SuperAdminOverview />} />
                <Route path="institutes" element={<SuperAdminInstitutes />} />
                <Route path="plans" element={<SuperAdminPlans />} />
                <Route path="*" element={<SuperAdminOverview />} />
            </Routes>
        </SuperAdminLayout>
    )
}
