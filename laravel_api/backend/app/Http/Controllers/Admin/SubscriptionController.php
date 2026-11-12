<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Services\PlanUsage;
use App\Support\Tenant;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display the institute's subscription plan, validity, limits, and usage.
     */
    public function index(Request $request)
    {
        $instituteId = auth()->user()?->institute_id
            ?: (Tenant::id() ?: 1);

        $institute = Institute::with('plan')->findOrFail($instituteId);
        $summary = PlanUsage::details($institute);

        return view('admin.subscription.index', compact('institute', 'summary'));
    }
}

