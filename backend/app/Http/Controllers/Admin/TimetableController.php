<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\Subject;
use App\Models\User;
use App\Services\ZoomService;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    protected ZoomService $zoom;

    public function __construct(ZoomService $zoom)
    {
        $this->zoom = $zoom;
    }
    public function index()
    {
        $timetables = Timetable::with(['subject', 'teacher'])->orderBy('day_of_week')->orderBy('start_time')->get();
        
        // Group by day for the grid view
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $groupedTimetables = [];
        foreach ($days as $day) {
            $groupedTimetables[$day] = $timetables->where('day_of_week', $day);
        }

        return view('admin.timetable.index', compact('groupedTimetables', 'days'));
    }

    public function create()
    {
        $subjects = Subject::orderBy('name')->get();
        $teachers = User::where('role', 'teacher')->whereNull('deactivated_at')->orderBy('name')->get();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $zoomUsers = $this->zoom->listUsers();
        
        return view('admin.timetable.create', compact('subjects', 'teachers', 'days', 'zoomUsers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|string',
            'start_time' => 'required',
            'duration' => 'required|integer|min:1',
            'grade' => 'required|string',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        $data['institute_id'] = auth()->user()->institute_id;

        Timetable::create($data);

        return redirect()->route('admin.timetables.index')->with('success', 'Timetable slot created successfully.');
    }

    public function edit(Timetable $timetable)
    {
        $subjects = Subject::orderBy('name')->get();
        $teachers = User::where('role', 'teacher')->whereNull('deactivated_at')->orderBy('name')->get();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $zoomUsers = $this->zoom->listUsers();
        
        return view('admin.timetable.edit', compact('timetable', 'subjects', 'teachers', 'days', 'zoomUsers'));
    }

    public function update(Request $request, Timetable $timetable)
    {
        $request->validate([
            'day_of_week' => 'required|string',
            'start_time' => 'required',
            'duration' => 'required|integer|min:1',
            'grade' => 'required|string',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');

        $timetable->update($data);

        return redirect()->route('admin.timetables.index')->with('success', 'Timetable slot updated successfully.');
    }

    public function destroy(Timetable $timetable)
    {
        $timetable->delete();
        return redirect()->route('admin.timetables.index')->with('success', 'Timetable slot deleted successfully.');
    }

    public function toggle(Timetable $timetable)
    {
        $timetable->update(['is_active' => !$timetable->is_active]);
        return back()->with('success', 'Timetable slot status updated.');
    }
}
