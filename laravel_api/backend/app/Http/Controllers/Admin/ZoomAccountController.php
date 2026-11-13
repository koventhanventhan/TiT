<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Models\ZoomAccount;
use App\Services\ZoomService;
use App\Support\Integrations;
use App\Support\Redact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accounts = ZoomAccount::latest()->paginate(15);
        return view('admin.zoom-accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.zoom-accounts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('ZoomAccount store request', Redact::array($request->all()));
        $validated = $request->validate([
            'email' => 'required|email|unique:zoom_accounts,email',
            'account_id' => 'required|string',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'max_concurrent' => 'required|integer|min:1',
        ]);

        $validated['institute_id'] = auth()->user()->institute_id ?? 1;
        $validated['is_active'] = $request->has('is_active');

        Log::info('ZoomAccount validated data', Redact::array($validated));
        $account = ZoomAccount::create($validated);
        Log::info('ZoomAccount created', ['id' => $account->id]);

        return redirect()->route('admin.zoom-accounts.index')
            ->with('success', 'Zoom account added successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ZoomAccount $zoomAccount)
    {
        return view('admin.zoom-accounts.edit', compact('zoomAccount'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ZoomAccount $zoomAccount)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:zoom_accounts,email,' . $zoomAccount->id,
            'account_id' => 'required|string',
            'client_id' => 'required|string',
            'client_secret' => 'nullable|string',
            'max_concurrent' => 'required|integer|min:1',
        ]);

        if (empty($validated['client_secret'])) {
            unset($validated['client_secret']);
        }

        $validated['is_active'] = $request->has('is_active');

        $zoomAccount->update($validated);

        return redirect()->route('admin.zoom-accounts.index')
            ->with('success', 'Zoom account updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ZoomAccount $zoomAccount)
    {
        $zoomAccount->delete();

        return redirect()->route('admin.zoom-accounts.index')
            ->with('success', 'Zoom account deleted successfully.');
    }

    /**
     * Test connection for a Zoom account.
     */
    public function testConnection(ZoomAccount $zoomAccount, ZoomService $zoomService)
    {
        $institute = Institute::find($zoomAccount->institute_id);
        if ($institute && !$institute->hasFeature('zoom')) {
            return response()->json([
                'success' => false,
                'message' => 'Zoom integration is not included in your current subscription plan.',
            ], 403);
        }

        $currentUser = auth()->user();
        if ($currentUser && $currentUser->role !== 'super_admin' && (int) $zoomAccount->institute_id !== (int) $currentUser->institute_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized account access.',
            ], 403);
        }

        try {
            $token = $zoomService->useAccount($zoomAccount)->getFreshAccessToken($zoomAccount);
            if (!$token) {
                throw new \RuntimeException('Failed to obtain Zoom access token with provided credentials.');
            }

            $res = Http::withToken($token)
                ->timeout(15)
                ->get('https://api.zoom.us/v2/users/me');

            if (!$res->successful()) {
                $err = $res->json('message') ?: ('Zoom API returned HTTP ' . $res->status());
                throw new \RuntimeException($err);
            }

            Integrations::for((int) $zoomAccount->institute_id)->updateStatus(
                InstituteIntegration::PROVIDER_ZOOM,
                InstituteIntegration::STATUS_VERIFIED
            );

            return response()->json([
                'success' => true,
                'message' => "Connection verified successfully for {$zoomAccount->email}.",
            ]);
        } catch (\Throwable $e) {
            $cleanReason = Redact::clean($e->getMessage());

            Integrations::for((int) $zoomAccount->institute_id)->updateStatus(
                InstituteIntegration::PROVIDER_ZOOM,
                InstituteIntegration::STATUS_FAILING,
                $cleanReason
            );

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $cleanReason,
            ], 422);
        }
    }
}
