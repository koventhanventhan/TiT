<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    /**
     * Get System Overview Stats
     */
    public function stats()
    {
        return response()->json([
            'total_institutes' => Institute::count(),
            'active_institutes' => Institute::where('status', 'active')->count(),
            'total_revenue' => \App\Models\Payment::where('status', 'paid')->sum('amount'),
            'total_users' => \App\Models\User::withoutGlobalScope(\App\Scopes\InstituteScope::class)->count(),
            'recent_activity' => \App\Models\ActivityLog::with(['user:id,name', 'institute:id,name'])
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }

    /**
     * Get All Activity Logs
     */
    public function activityLogs()
    {
        return response()->json(
            \App\Models\ActivityLog::with(['user:id,name', 'institute:id,name'])
                ->latest()
                ->paginate(50)
        );
    }

    /**
     * Manage Institutes
     */
    public function institutes()
    {
        $institutes = Institute::with('plan')
            ->withCount([
                'users as students_count' => function ($query) {
                    $query->where('role', 'user');
                },
                'users as teachers_count' => function ($query) {
                    $query->where('role', 'teacher');
                }
            ])
            ->latest()
            ->get();
            
        return response()->json($institutes);
    }

    public function storeInstitute(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'expires_at' => 'nullable|date',
            'admin_name' => 'nullable|string|max:255',
            'admin_email' => 'nullable|email|unique:users,email',
            'admin_phone' => 'nullable|string|max:20',
        ]);

        $institute = Institute::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'subscription_plan_id' => $validated['subscription_plan_id'],
            'status' => 'active',
            'expires_at' => $validated['expires_at'] ?? now()->addYear(),
        ]);

        if (!empty($validated['admin_email'])) {
            $password = Str::random(10);
            $admin = \App\Models\User::create([
                'name' => $validated['admin_name'] ?? 'Admin',
                'full_name' => $validated['admin_name'] ?? 'Admin',
                'email' => $validated['admin_email'],
                'phone_number' => $validated['admin_phone'] ?? null,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'role' => 'admin',
                'institute_id' => $institute->id,
            ]);

            try {
                $notifier = app(\App\Services\NotificationService::class);
                $notifier->notifyUser(
                    $admin, 'welcome', 'tit_welcome',
                    [$admin->name, $admin->email],
                    ['student_name' => $admin->name, 'username' => $admin->email . ' (Password: ' . $password . ')']
                );
            } catch (\Exception $e) {
                \Log::error('Failed to send admin welcome email: ' . $e->getMessage());
            }
        }

        \App\Services\ActivityLogService::log('institute_created', "Created institute: {$institute->name}", ['id' => $institute->id]);

        return response()->json($institute);
    }

    public function updateInstitute(Request $request, Institute $institute)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'subscription_plan_id' => 'sometimes|exists:subscription_plans,id',
            'status' => 'sometimes|in:active,suspended,expired',
            'expires_at' => 'nullable|date',
        ]);

        $institute->update($validated);
        
        \App\Services\ActivityLogService::log('institute_updated', "Updated institute: {$institute->name}", ['id' => $institute->id, 'changes' => $validated]);

        return response()->json($institute);
    }

    /**
     * Manage Subscription Plans
     */
    public function plans()
    {
        return response()->json(SubscriptionPlan::all());
    }

    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'monthly_price' => 'required|numeric',
            'yearly_price' => 'required|numeric',
            'max_students' => 'required|integer',
            'max_teachers' => 'required|integer',
            'features' => 'nullable|array',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'monthly_price' => $validated['monthly_price'],
            'yearly_price' => $validated['yearly_price'],
            'max_students' => $validated['max_students'],
            'max_teachers' => $validated['max_teachers'],
            'features' => $validated['features'] ?? [],
        ]);

        return response()->json($plan);
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'monthly_price' => 'sometimes|numeric',
            'yearly_price' => 'sometimes|numeric',
            'max_students' => 'sometimes|integer',
            'max_teachers' => 'sometimes|integer',
            'features' => 'nullable|array',
        ]);

        $plan->update($validated);
        return response()->json($plan);
    }

    public function impersonate(Institute $institute)
    {
        $admin = \App\Models\User::withoutGlobalScope(\App\Scopes\InstituteScope::class)
            ->where('institute_id', $institute->id)
            ->where('role', 'admin')
            ->first();

        if (!$admin) {
            return response()->json(['message' => 'No admin user found for this institute.'], 404);
        }

        \App\Services\ActivityLogService::log('institute_impersonated', "Impersonated institute admin: {$admin->email}", ['institute_id' => $institute->id]);

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $admin
        ]);
    }

    public function payments(Institute $institute)
    {
        $payments = \App\Models\Payment::with('user:id,name,email,full_name,phone_number')
            ->where('institute_id', $institute->id)
            ->latest()
            ->paginate(50);
            
        return response()->json($payments);
    }

    public function export(Institute $institute)
    {
        $students = \App\Models\User::withoutGlobalScope(\App\Scopes\InstituteScope::class)
            ->where('institute_id', $institute->id)->where('role', 'user')->get();
        $teachers = \App\Models\User::withoutGlobalScope(\App\Scopes\InstituteScope::class)
            ->where('institute_id', $institute->id)->where('role', 'teacher')->get();
        $payments = \App\Models\Payment::where('institute_id', $institute->id)->get();
        
        return response()->json([
            'students' => $students,
            'teachers' => $teachers,
            'payments' => $payments,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->query('q');
        
        if (!$query) {
            return response()->json([]);
        }
        
        $users = \App\Models\User::withoutGlobalScope(\App\Scopes\InstituteScope::class)
            ->with('institute:id,name')
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('full_name', 'like', "%{$query}%")
                  ->orWhere('phone_number', 'like', "%{$query}%");
            })
            ->take(50)
            ->get();
            
        return response()->json($users);
    }
    public function announcements()
    {
        return response()->json(\App\Models\Announcement::latest()->get());
    }

    public function storeAnnouncement(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'is_active' => 'boolean',
        ]);
        
        $announcement = \App\Models\Announcement::create([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'is_active' => $request->has('is_active') ? $validated['is_active'] : true,
        ]);
        
        return response()->json($announcement);
    }
    
    public function activeAnnouncement()
    {
        $announcement = \App\Models\Announcement::where('is_active', true)->latest()->first();
        return response()->json($announcement);
    }
}
