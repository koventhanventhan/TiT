<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use App\Services\GeminiTranslationService;

class PackageController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        
        $packages = Package::orderBy('category')->orderBy('name')->paginate(15);
        return view('admin.packages.index', compact('packages'));
    }

    public function store(Request $request, GeminiTranslationService $translator)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'medium' => 'required|in:english,tamil,both',
            'package_price' => 'required|numeric|min:0',
            'original_price' => 'required|numeric|min:0',
            'addon_price' => 'nullable|numeric|min:0',
            'type' => 'required|string',
            'applicable_grades' => 'required|array',
        ]);

        $name_ta = $translator->translate($request->name, 'ta');
        $name_si = $translator->translate($request->name, 'si');

        Package::create([
            'name' => $request->name,
            'name_ta' => $name_ta,
            'name_si' => $name_si,
            'category' => $request->category,
            'medium' => $request->medium,
            'package_price' => $request->package_price,
            'original_price' => $request->original_price,
            'addon_price' => $request->addon_price,
            'type' => $request->type,
            'applicable_grades' => $request->applicable_grades,
            'is_active' => $request->has('is_active'),
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Package added successfully');
    }

    public function update(Request $request, $id, GeminiTranslationService $translator)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $package = Package::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'medium' => 'required|in:english,tamil,both',
            'package_price' => 'required|numeric|min:0',
            'original_price' => 'required|numeric|min:0',
            'addon_price' => 'nullable|numeric|min:0',
            'type' => 'required|string',
            'applicable_grades' => 'required|array',
        ]);

        $data = $request->except(['_token', '_method']);
        
        // Handle checkbox
        $data['is_active'] = $request->has('is_active');

        // Only translate if name is changed
        if ($package->name !== $request->name || !$package->name_ta || !$package->name_si) {
            $data['name_ta'] = $translator->translate($request->name, 'ta');
            $data['name_si'] = $translator->translate($request->name, 'si');
        }

        $package->update($data);

        return redirect()->back()->with('success', 'Package updated successfully');
    }

    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $package = Package::findOrFail($id);
        $package->delete();

        return redirect()->back()->with('success', 'Package deleted successfully');
    }

    /**
     * API for frontend to get active packages
     */
    public function getActivePackages(Request $request)
    {
        $query = Package::where('is_active', true)->orderBy('package_price');

        // Filter by medium if provided
        $medium = $request->query('medium');
        if ($medium && in_array($medium, ['english', 'tamil'])) {
            $query->where(function($q) use ($medium) {
                $q->where('medium', $medium)
                  ->orWhere('medium', 'both');
            });
        }

        $packages = $query->get();
        return response()->json($packages);
    }
}
