<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZoomSchedule;
use App\Models\User;
use Illuminate\Http\Request;

class ZoomScheduleController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $schedules = ZoomSchedule::with(['creator', 'teachers'])->latest('scheduled_at')->paginate(15);
        return view('admin.zoom.index', compact('schedules'));
    }

    public function create()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $teachers = User::where('role', 'teacher')->whereNull('deactivated_at')->orderBy('name')->get();
        return view('admin.zoom.create', compact('teachers'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'zoom_link' => 'required|string|url',
            'scheduled_at' => 'required|date',
            'subject' => 'nullable|string|max:100',
            'grade' => 'nullable|string|max:50',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $schedule = ZoomSchedule::create([
            'title' => $request->title,
            'zoom_link' => $request->zoom_link,
            'scheduled_at' => $request->scheduled_at,
            'subject' => $request->subject,
            'grade' => $request->grade,
            'created_by' => auth()->id(),
        ]);

        if ($request->filled('teacher_ids')) {
            $schedule->teachers()->sync($request->teacher_ids);
        }

        return redirect()->route('admin.zoom.index')->with('success', 'Zoom class created.');
    }

    public function edit($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $schedule = ZoomSchedule::findOrFail($id);
        $teachers = User::where('role', 'teacher')->whereNull('deactivated_at')->orderBy('name')->get();
        return view('admin.zoom.edit', compact('schedule', 'teachers'));
    }

    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $schedule = ZoomSchedule::findOrFail($id);
        $request->validate([
            'title' => 'required|string|max:255',
            'zoom_link' => 'required|string|url',
            'scheduled_at' => 'required|date',
            'subject' => 'nullable|string|max:100',
            'grade' => 'nullable|string|max:50',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $schedule->update([
            'title' => $request->title,
            'zoom_link' => $request->zoom_link,
            'scheduled_at' => $request->scheduled_at,
            'subject' => $request->subject,
            'grade' => $request->grade,
        ]);

        $schedule->teachers()->sync($request->teacher_ids ?? []);

        return redirect()->route('admin.zoom.index')->with('success', 'Zoom class updated.');
    }

    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        ZoomSchedule::findOrFail($id)->delete();
        return redirect()->route('admin.zoom.index')->with('success', 'Zoom class deleted.');
    }
}
