<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|in:grade_1_to_5,grade_6_to_11,arts_stream,bio_maths_stream',
        ]);

        $exists = Subject::where('name', $request->name)
            ->where('category', $request->category)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'already add panijachu intha subject endu ok');
        }

        Subject::create($request->only(['name', 'price', 'category']));

        return redirect()->back()->with('success', 'Subject added successfully');
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $subject = Subject::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|in:grade_1_to_5,grade_6_to_11,arts_stream,bio_maths_stream',
        ]);

        $exists = Subject::where('name', $request->name)
            ->where('category', $request->category)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'already add panijachu intha subject endu ok');
        }

        $subject->update($request->only(['name', 'price', 'category']));

        return redirect()->back()->with('success', 'Subject updated successfully');
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
     * API for frontend to get subjects grouped by category with prices
     */
    public function getPrices()
    {
        $subjects = Subject::orderBy('name')->get();
        $grouped = $subjects->groupBy('category')->map(function ($items) {
            return $items->map(function ($item) {
                return ['name' => $item->name, 'price' => $item->price];
            })->values();
        });
        return response()->json($grouped);
    }
}
