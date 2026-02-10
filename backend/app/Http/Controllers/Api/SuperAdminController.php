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
        ]);
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
        ]);

        $institute = Institute::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'subscription_plan_id' => $validated['subscription_plan_id'],
            'status' => 'active',
        ]);

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
        ]);

        $plan = SubscriptionPlan::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'monthly_price' => $validated['monthly_price'],
            'yearly_price' => $validated['yearly_price'],
            'max_students' => $validated['max_students'],
            'max_teachers' => $validated['max_teachers'],
        ]);

        return response()->json($plan);
    }
}
