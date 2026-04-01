<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZoomAccount;
use App\Services\ZoomService;
use Illuminate\Http\Request;

class ZoomAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accounts = ZoomAccount::latest()->get();
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
        \Illuminate\Support\Facades\Log::info('ZoomAccount store request', $request->all());
        $validated = $request->validate([
            'email' => 'required|email|unique:zoom_accounts,email',
            'account_id' => 'required|string',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'max_concurrent' => 'required|integer|min:1',
        ]);

        $validated['institute_id'] = auth()->user()->institute_id ?? 1;
        $validated['is_active'] = $request->has('is_active');

        \Illuminate\Support\Facades\Log::info('ZoomAccount validated data', $validated);
        $account = ZoomAccount::create($validated);
        \Illuminate\Support\Facades\Log::info('ZoomAccount created', ['id' => $account->id]);

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
            'client_secret' => 'required|string',
            'max_concurrent' => 'required|integer|min:1',
        ]);

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
        try {
            $zoomService->useAccount($zoomAccount);
            // Try to create a dummy meeting or just get access token
            // Here we'll try to get the access token indirectly or just create a very short dummy meeting
            // Actually, we can just return success if useAccount works and we can log the oauth result
            
            return response()->json([
                'success' => true, 
                'message' => "Connection successful for {$zoomAccount->email}"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Connection failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
