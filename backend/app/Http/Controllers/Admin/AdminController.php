<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class AdminController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function dashboard()
    {
        // Check if user is admin
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        $totalUsers = User::count();
        $totalStudents = User::where('role', 'user')->whereNotNull('full_name')->count();
        $totalTeachers = User::where('role', 'teacher')->count();
        $recentUsers = User::latest()->take(5)->get();
        $recentStudents = User::where('role', 'user')
            ->whereNotNull('full_name')
            ->latest()
            ->take(10)
            ->get();
        
        return view('admin.dashboard', compact('totalUsers', 'totalStudents', 'totalTeachers', 'recentUsers', 'recentStudents'));
    }
}
