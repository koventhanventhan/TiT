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
        // Check if user is admin or super_admin
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'super_admin'], true)) {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $user = auth()->user();
        $instituteId = $user->institute_id;
        $isSuperAdmin = $user->role === 'super_admin';

        // Base scoped queries for strict tenant isolation
        $studentQuery = User::where('role', 'user')->whereNotNull('full_name');
        $teacherQuery = User::where('role', 'teacher');
        $courseQuery = \App\Models\LearningMaterial::query();
        $activeClassesQuery = \App\Models\ZoomSchedule::whereDate('scheduled_at', now()->toDateString());
        $revenueQuery = \App\Models\Payment::where('status', 'paid');
        $currentYearMonth = now()->format('Y-m');
        $pendingPaymentsQuery = \App\Models\Payment::where('status', 'pending')->where('year_month', $currentYearMonth);
        $upcomingClassesQuery = \App\Models\ZoomSchedule::where('scheduled_at', '>=', now())->orderBy('scheduled_at', 'asc');

        if (!$isSuperAdmin && $instituteId) {
            $studentQuery->where('institute_id', $instituteId);
            $teacherQuery->where('institute_id', $instituteId);
            $courseQuery->where('institute_id', $instituteId);
            $activeClassesQuery->where('institute_id', $instituteId);
            $revenueQuery->where('institute_id', $instituteId);
            $pendingPaymentsQuery->where('institute_id', $instituteId);
            $upcomingClassesQuery->where('institute_id', $instituteId);
        }
        
        // Summary Card Stats
        $totalStudents = (clone $studentQuery)->count();
        $totalTeachers = (clone $teacherQuery)->count();
        $totalCourses = (clone $courseQuery)->count();
        $activeClassesToday = (clone $activeClassesQuery)->count();
        $totalRevenue = (clone $revenueQuery)->sum('amount');
        
        // Recent Student Registrations
        $recentStudents = (clone $studentQuery)
            ->latest()
            ->take(8)
            ->get();
            
        // Upcoming Classes
        $upcomingClasses = (clone $upcomingClassesQuery)
            ->take(5)
            ->get();
            
        // Registration Stats (Last 7 Days) for Chart in Asia/Colombo timezone
        $tz = 'Asia/Colombo';
        $nowColombo = now($tz);
        $startDateUtc = $nowColombo->copy()->subDays(6)->startOfDay()->setTimezone('UTC');

        $rawRegistrations = (clone $studentQuery)
            ->where('created_at', '>=', $startDateUtc)
            ->get(['created_at'])
            ->groupBy(function ($u) use ($tz) {
                return $u->created_at ? $u->created_at->setTimezone($tz)->format('Y-m-d') : null;
            })
            ->map(fn($group) => $group->count());

        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $nowColombo->copy()->subDays($i);
            $dateKey = $day->format('Y-m-d');
            $chartLabels[] = $day->format('M d');
            $chartData[] = $rawRegistrations->get($dateKey, 0);
        }

        // Pending Approvals & Payments (Notifications)
        $pendingApprovals = (clone $studentQuery)->whereNull('admin_confirmed_at')->count();
        $pendingPayments = (clone $pendingPaymentsQuery)->count();
        $pendingTopups = (clone $pendingPaymentsQuery)->where('kind', 'topup')->count();
        $pendingMonthly = $pendingPayments - $pendingTopups;

        // Academic Breakdown by Grade & Stream
        $academicBreakdown = [
            'grade_12' => (clone $studentQuery)->where(fn($q) => $q->where('current_grade', '12')->orWhere('current_grade', 'Grade 12'))->count(),
            'grade_13' => (clone $studentQuery)->where(fn($q) => $q->where('current_grade', '13')->orWhere('current_grade', 'Grade 13'))->count(),
            'arts' => (clone $studentQuery)->where('stream', 'arts')->count(),
            'bio_maths' => (clone $studentQuery)->whereIn('stream', ['bio_maths', 'science', 'maths', 'biology'])->count(),
        ];

        return view('admin.dashboard', compact(
            'totalStudents', 
            'totalTeachers', 
            'totalCourses',
            'activeClassesToday',
            'totalRevenue',
            'recentStudents',
            'upcomingClasses',
            'chartLabels',
            'chartData',
            'pendingApprovals',
            'pendingPayments',
            'pendingMonthly',
            'pendingTopups',
            'academicBreakdown'
        ));
    }

    /**
     * Global search across students, teachers, subjects, zoom classes
     */
    public function search(Request $request)
    {
        try {
            $q = $request->get('q', '');
            if (strlen($q) < 1) {
                return response()->json(['results' => []]);
            }

            $results = [];

            // Search Students
            $students = User::where('role', 'user')
                ->where(function($query) use ($q) {
                    $query->where('full_name', 'LIKE', "%{$q}%")
                          ->orWhere('name', 'LIKE', "%{$q}%")
                          ->orWhere('username', 'LIKE', "%{$q}%")
                          ->orWhere('email', 'LIKE', "%{$q}%")
                          ->orWhere('phone_number', 'LIKE', "%{$q}%");
                })
                ->limit(5)
                ->get();

            foreach ($students as $s) {
                $results[] = [
                    'type' => 'student',
                    'icon' => '🎓',
                    'name' => $s->full_name ?: $s->name ?: $s->username,
                    'desc' => "Grade: " . ($s->current_grade ?: 'N/A') . " • " . ($s->phone_number ?: $s->email),
                    'url'  => route('admin.students.edit', $s->id),
                ];
            }

            // Search Teachers
            $teachers = User::where('role', 'teacher')
                ->where(function($query) use ($q) {
                    $query->where('name', 'LIKE', "%{$q}%")
                          ->orWhere('username', 'LIKE', "%{$q}%")
                          ->orWhere('email', 'LIKE', "%{$q}%")
                          ->orWhere('phone_number', 'LIKE', "%{$q}%");
                })
                ->limit(5)
                ->get();

            foreach ($teachers as $t) {
                $results[] = [
                    'type' => 'teacher',
                    'icon' => '👨‍🏫',
                    'name' => $t->name ?: $t->username,
                    'desc' => $t->phone_number ?: $t->email,
                    'url'  => route('admin.teachers.edit', $t->id),
                ];
            }

            // Search Payments
            try {
                $payments = \App\Models\Payment::with('user')
                    ->where('transaction_id', 'LIKE', "%{$q}%")
                    ->orWhere('gateway_ref', 'LIKE', "%{$q}%")
                    ->orWhereHas('user', function($query) use ($q) {
                        $query->where('full_name', 'LIKE', "%{$q}%")
                              ->orWhere('name', 'LIKE', "%{$q}%");
                    })
                    ->limit(5)
                    ->get();

                foreach ($payments as $p) {
                    $results[] = [
                        'type' => 'payment',
                        'icon' => '💳',
                        'name' => "Payment of LKR " . number_format($p->amount, 2),
                        'desc' => ($p->user ? $p->user->full_name : 'Unknown') . " • " . ($p->transaction_id ?: $p->gateway_ref),
                        'url'  => route('admin.students.index'), // Link to students for now as there is no specific payment detail page
                    ];
                }
            } catch (\Exception $e) { }

            // Search Subjects
            try {
                $subjects = \App\Models\Subject::where('name', 'LIKE', "%{$q}%")
                    ->limit(5)
                    ->get();

                foreach ($subjects as $sub) {
                    $catLabels = [
                        'grade_1_to_5' => 'Grade 1–5',
                        'grade_6_to_11' => 'Grade 6–11',
                        'arts_stream' => 'A/L Arts',
                        'bio_maths_stream' => 'A/L Bio & Maths',
                    ];
                    $results[] = [
                        'type' => 'subject',
                        'icon' => '📚',
                        'name' => $sub->name,
                        'desc' => ($catLabels[$sub->category] ?? $sub->category) . " • LKR " . number_format($sub->price, 2),
                        'url'  => route('admin.subjects.index'),
                    ];
                }
            } catch (\Exception $e) { }

            // Search Zoom Classes
            try {
                $zoom = \App\Models\ZoomSchedule::where('topic', 'LIKE', "%{$q}%")
                    ->limit(5)
                    ->get();

                foreach ($zoom as $z) {
                    $results[] = [
                        'type' => 'zoom',
                        'icon' => '📹',
                        'name' => $z->topic,
                        'desc' => $z->scheduled_at ? \Carbon\Carbon::parse($z->scheduled_at)->format('M d, Y h:i A') : '',
                        'url'  => route('admin.zoom.edit', $z->id),
                    ];
                }
            } catch (\Exception $e) { }

            // Search Admin Messages
            try {
                $messages = \App\Models\AdminMessage::where('title', 'LIKE', "%{$q}%")
                    ->limit(5)
                    ->get();

                foreach ($messages as $m) {
                    $results[] = [
                        'type' => 'message',
                        'icon' => '✉️',
                        'name' => $m->title,
                        'desc' => \Illuminate\Support\Str::limit($m->body, 50),
                        'url'  => route('admin.messages.index'),
                    ];
                }
            } catch (\Exception $e) { }

            return response()->json(['results' => $results, 'query' => $q]);
        } catch (\Exception $e) {
            return response()->json(['results' => [], 'error' => $e->getMessage()], 200);
        }
    }
}
