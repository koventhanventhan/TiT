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
        return response()->json(Institute::with('plan')->latest()->get());
    }

    public function storeInstitute(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'expires_at' => 'nullable|date',
        ]);

        $institute = Institute::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'subscription_plan_id' => $validated['subscription_plan_id'],
            'status' => 'active',
            'expires_at' => $validated['expires_at'] ?? now()->addYear(),
        ]);

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
}
