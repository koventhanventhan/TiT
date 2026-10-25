<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use App\Services\GeminiTranslationService;

class SubjectController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }
        $subjects = Subject::orderBy('category')->orderBy('name')->paginate(15);
        return view('admin.subjects.index', compact('subjects'));
    }

    public function store(Request $request, GeminiTranslationService $translator)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|in:grade_1_to_2,grade_3,grade_4,grade_5,grade_6_to_9,grade_10_to_11,arts_stream,bio_maths_stream',
            'medium' => 'required|in:english,tamil,both',
        ]);

        // Check for medium overlap: "both" conflicts with "english" and "tamil"
        $mediumCheck = $request->medium === 'both'
            ? ['english', 'tamil', 'both']
            : [$request->medium, 'both'];

        $exists = Subject::where('name', $request->name)
            ->where('category', $request->category)
            ->whereIn('medium', $mediumCheck)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'This subject already exists in the selected category. A subject with medium "both" covers English and Tamil.');
        }

        $name_ta = $translator->translate($request->name, 'ta');
        $name_si = $translator->translate($request->name, 'si');

        Subject::create([
            'name' => $request->name,
            'name_ta' => $name_ta,
            'name_si' => $name_si,
            'price' => $request->price,
            'category' => $request->category,
            'medium' => $request->medium
        ]);

        return redirect()->back()->with('success', 'Subject added successfully with translations');
    }

    public function update(Request $request, $id, GeminiTranslationService $translator)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $subject = Subject::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|in:grade_1_to_2,grade_3,grade_4,grade_5,grade_6_to_9,grade_10_to_11,arts_stream,bio_maths_stream',
            'medium' => 'required|in:english,tamil,both',
        ]);

        // Check for medium overlap: "both" conflicts with "english" and "tamil"
        $mediumCheck = $request->medium === 'both'
            ? ['english', 'tamil', 'both']
            : [$request->medium, 'both'];

        $exists = Subject::where('name', $request->name)
            ->where('category', $request->category)
            ->whereIn('medium', $mediumCheck)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'This subject already exists in the selected category. A subject with medium "both" covers English and Tamil.');
        }

        $data = $request->only(['name', 'price', 'category', 'medium']);

        // Only translate if name is changed or translations are missing
        if ($subject->name !== $request->name || !$subject->name_ta || !$subject->name_si) {
            $data['name_ta'] = $translator->translate($request->name, 'ta');
            $data['name_si'] = $translator->translate($request->name, 'si');
        }

        $subject->update($data);

        return redirect()->back()->with('success', 'Subject updated successfully with translations');
    }

    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $subject = Subject::findOrFail($id);
        $subject->delete();

        return redirect()->back()->with('success', 'Subject deleted successfully');
    }

    /**
     * API for frontend to get subjects grouped by category with prices.
     * Supports optional ?medium=english|tamil filter for student-specific views.
     */
    public function getPrices(Request $request)
    {
        $query = Subject::orderBy('name');

        // Filter by medium if provided (student views)
        $medium = $request->query('medium');
        if ($medium && in_array($medium, ['english', 'tamil'])) {
            $query->where(function($q) use ($medium) {
                $q->where('medium', $medium)
                  ->orWhere('medium', 'both');
            });
        }

        $subjects = $query->get();
        $grouped = $subjects->groupBy('category')->map(function ($items) {
            return $items->map(function ($item) {
                return [
                    'name' => $item->name,
                    'name_ta' => $item->name_ta,
                    'name_si' => $item->name_si,
                    'price' => $item->price,
                    'medium' => $item->medium,
                ];
            })->values();
        });
        return response()->json($grouped);
    }
}
