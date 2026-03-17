<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ZoomSchedule;
use App\Models\Payment;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Support\Facades\DB;

class MasterAdminController extends Controller
{
    /**
     * Get Dashboard Stats
     */
    public function stats(Request $request)
    {

        $totalStudents = User::where('role', 'user')->count();
        $totalTeachers = User::where('role', 'teacher')->count();
        $activeClasses = ZoomSchedule::where('scheduled_at', '>=', now())->count();
        $currentMonthRevenue = Payment::where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->sum('amount');
        $pendingRegistrations = User::where('role', 'user')
            ->where('registration_status', 'pending_approval')
            ->count();

        // Growth Data (Last 6 Months)
        $growthData = User::where('role', 'user')
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(DB::raw('MONTHNAME(created_at) as month'), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->get();

        return response()->json([
            'stats' => [
                'total_students' => $totalStudents,
                'total_teachers' => $totalTeachers,
                'active_classes' => $activeClasses,
                'monthly_revenue' => $currentMonthRevenue,
                'pending_registrations' => $pendingRegistrations,
            ],
            'growth_data' => $growthData,
            'recent_activity' => [
                 // Add logic for activity logs if a table exists, otherwise mock or use latest users
                 ['type' => 'user_registered', 'message' => 'New student joined', 'time' => now()->diffForHumans()],
                 ['type' => 'payment_received', 'message' => 'Monthly fee paid by student', 'time' => now()->subMinutes(15)->diffForHumans()],
            ]
        ]);
    }

    /**
     * List all students with search and filters
     */
    public function students(Request $request)
    {
        $query = User::where('role', 'user')->whereNotNull('full_name');

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->grade) {
            $query->where('current_grade', $request->grade);
        }

        $students = $query->latest()->paginate(20);
        return response()->json($students);
    }

    /**
     * List all teachers
     */
    public function teachers(Request $request)
    {
        $teachers = User::where('role', 'teacher')
            ->withCount(['schedules' => function($q) {
                $q->where('scheduled_at', '>=', now());
            }])
            ->latest()
            ->get();
            
        return response()->json($teachers);
    }

    /**
     * Get Finance Analytics
     */
    public function finance(Request $request)
    {
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $monthlyRevenue = Payment::where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('amount');
        
        $pendingPayments = User::where('role', 'user')
            ->where('registration_status', 'pending_payment')
            ->count();

        $revenueHistory = Payment::where('status', 'paid')
            ->select(DB::raw('MONTHNAME(paid_at) as month'), DB::raw('sum(amount) as total'))
            ->groupBy('month')
            ->orderBy('paid_at')
            ->get();

        return response()->json([
            'overview' => [
                'total_revenue' => $totalRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'pending_count' => $pendingPayments,
            ],
            'revenue_history' => $revenueHistory,
            'recent_payments' => Payment::with('user:id,full_name,email')
                ->latest()
                ->take(10)
                ->get()
        ]);
    }

    /**
     * Get Zoom Classes
     */
    public function zoomClasses(Request $request)
    {
        $classes = ZoomSchedule::with(['creator', 'teachers'])
            ->latest('scheduled_at')
            ->paginate(20);
            
        return response()->json($classes);
    }

    /**
     * Get LMS Materials
     */
    public function materials(Request $request)
    {
        $materials = \App\Models\LearningMaterial::latest()->paginate(20);
        return response()->json($materials);
    }

    public function settings(Request $request)
    {
        $settings = \App\Models\SiteSetting::all()->pluck('value', 'key');
        return response()->json($settings);
    }

    /**
     * Update Site Settings
     */
    public function updateSettings(Request $request)
    {
        $settings = $request->all();
        
        // Handle Logo Removal
        if ($request->remove_logo == '1') {
            \App\Models\SiteSetting::where('key', 'logo_url')->delete();
            \App\Models\SiteSetting::where('key', 'admin_logo')->delete();
        }
        
        // Handle Logo Upload if present in this request (though UI sends it separately, good to have)
        if ($request->hasFile('admin_logo')) {
            $logo = $request->file('admin_logo');
            $name = 'admin_logo_' . time() . '.' . $logo->getClientOriginalExtension();
            $path = 'uploads/settings';
            $destinationPath = public_path($path);
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            
            $logo->move($destinationPath, $name);
            $logoUrl = asset($path . '/' . $name);
            \App\Models\SiteSetting::set('logo_url', $logoUrl, 'admin_identity');
        }

        foreach ($settings as $key => $value) {
            if ($request->hasFile($key) || $key === 'admin_logo') continue;

            // Determine group based on key prefix
            $group = 'general';
            if (str_starts_with($key, 'learning_')) $group = 'learning';
            elseif (str_starts_with($key, 'classes_')) $group = 'classes';
            elseif (str_starts_with($key, 'contact_')) $group = 'contact';
            elseif (str_starts_with($key, 'social_')) $group = 'social';
            elseif (str_starts_with($key, 'footer_')) $group = 'footer';
            elseif (str_starts_with($key, 'hero_')) $group = 'hero';
            elseif (str_starts_with($key, 'admin_')) $group = 'admin_identity';
            elseif (str_starts_with($key, 'register_')) $group = 'register';
            elseif (str_starts_with($key, 'topbar_')) $group = 'topbar';
            elseif (str_starts_with($key, 'nav_')) $group = 'navigation';

            \App\Models\SiteSetting::set($key, $value, $group);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Get All Assignments (Master View)
     */
    public function assignments(Request $request)
    {
        $assignments = Assignment::with(['teacher:id,name', 'submissions'])
            ->withCount('submissions')
            ->latest()
            ->paginate(20);
        return response()->json($assignments);
    }

    /**
     * Get Attendance Analytics
     */
    public function attendanceStats(Request $request)
    {
        $attendance = \App\Models\Attendance::with(['user:id,full_name,name', 'zoomSchedule:id,title'])
            ->latest()
            ->paginate(30);
            
        $stats = [
            'total_present' => \App\Models\Attendance::where('status', 'present')->count(),
            'total_absent' => \App\Models\Attendance::where('status', 'absent')->count(),
        ];

        return response()->json([
            'records' => $attendance,
            'stats' => $stats
        ]);
    }

    /**
     * Update Institute Branding (Logo & Theme)
     */
    public function updateBranding(Request $request)
    {
        $institute = auth()->user()->institute;
        if (!$institute) {
            return response()->json(['message' => 'Institute not found'], 404);
        }

        $validated = $request->validate([
            'logo' => 'nullable|image|max:2048',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'theme_mode' => 'nullable|in:light,dark',
        ]);

        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $name = 'admin_logo_' . time() . '.' . $logo->getClientOriginalExtension();
            $path = 'uploads/settings';
            $destinationPath = public_path($path);
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            
            $logo->move($destinationPath, $name);
            $logoUrl = asset($path . '/' . $name);
            
            // Save to SiteSetting for Frontend Header
            \App\Models\SiteSetting::set('logo_url', $logoUrl, 'admin_identity');
            
            // Also keep in institute for dashboard theme if needed
            $institute->logo_path = $path . '/' . $name;
        } elseif ($request->remove_logo == '1') {
            \App\Models\SiteSetting::where('key', 'logo_url')->delete();
            \App\Models\SiteSetting::where('key', 'admin_logo')->delete();
            $institute->logo_path = null;
        }

        $themeSettings = $institute->theme_settings ?? [];
        if (isset($validated['primary_color'])) {
            $themeSettings['primary_color'] = $validated['primary_color'];
            \App\Models\SiteSetting::set('primary_color', $validated['primary_color'], 'branding');
        }
        if (isset($validated['secondary_color'])) $themeSettings['secondary_color'] = $validated['secondary_color'];
        if (isset($validated['theme_mode'])) $themeSettings['theme_mode'] = $validated['theme_mode'];

        $institute->theme_settings = $themeSettings;
        $institute->save();

        return response()->json([
            'success' => true,
            'message' => 'Branding updated successfully',
            'logo_url' => \App\Models\SiteSetting::get('logo_url'),
            'institute' => $institute
        ]);
    }
}
