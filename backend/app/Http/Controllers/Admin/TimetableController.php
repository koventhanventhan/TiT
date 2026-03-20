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
        
        $subjects = Subject::orderBy('name')->get();
        $teachers = User::where('role', 'teacher')->whereNull('deactivated_at')->orderBy('name')->get();
        $zoomUsers = $this->zoom->listUsers();

        // Group by day for the grid view
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $groupedTimetables = [];
        foreach ($days as $day) {
            $groupedTimetables[$day] = $timetables->where('day_of_week', $day);
        }

        // Predefined grades list
        $grades = [
            'Grade 1 / தரம் 1',
            'Grade 2 / தரம் 2',
            'Grade 3 / தரம் 3',
            'Grade 4 / தரம் 4',
            'Grade 5 / தரம் 5',
            'Grade 6 / தரம் 6',
            'Grade 7 / தரம் 7',
            'Grade 8 / தரம் 8',
            'Grade 9 / தரம் 9',
            'Grade 10 / தரம் 10',
            'Grade 11 / தரம் 11',
            'Grade 12 / தரம் 12',
            'Grade 13 / தரம் 13',
        ];

        return view('admin.timetable.index', compact('groupedTimetables', 'days', 'subjects', 'teachers', 'zoomUsers', 'grades'));
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
            'title' => 'required|string|max:255',
            'day_of_week' => 'required|string',
            'start_time' => 'required',
            'duration' => 'required|integer|min:1',
            'grade' => 'required|string',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'zoom_host_email' => 'nullable|email',
            'is_active' => 'nullable',
        ]);

        $startTime = $request->start_time;
        $endTime = date('H:i:s', strtotime($startTime) + ($request->duration * 60));

        // Conflict check for same grade at same time (overlap check)
        $conflict = Timetable::where('day_of_week', $request->day_of_week)
            ->where('grade', $request->grade)
            ->where('is_active', true)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->whereRaw('ADDTIME(start_time, SEC_TO_TIME(duration * 60)) > ?', [$startTime]);
                });
            })
            ->exists();

        if ($conflict) {
            return back()->withInput()->withErrors(['start_time' => 'Time Conflict! This grade already has a class scheduled at this time on ' . $request->day_of_week]);
        }

        $data = $request->except('is_active');
        $data['is_active'] = $request->has('is_active');
        $data['institute_id'] = auth()->user()->institute_id;

        Timetable::create($data);

        // Immediate sync to Zoom
        try {
            \Illuminate\Support\Facades\Artisan::call('zoom:sync-timetable');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Auto-sync failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.timetables.index')->with('success', 'Timetable slot created successfully and synced to Zoom.');
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
            'title' => 'required|string|max:255',
            'day_of_week' => 'required|string',
            'start_time' => 'required',
            'duration' => 'required|integer|min:1',
            'grade' => 'required|string',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'zoom_host_email' => 'nullable|email',
            'is_active' => 'nullable',
        ]);

        $startTime = $request->start_time;
        $endTime = date('H:i:s', strtotime($startTime) + ($request->duration * 60));

        // Conflict check for same grade at same time
        $conflict = Timetable::where('id', '!=', $timetable->id)
            ->where('day_of_week', $request->day_of_week)
            ->where('grade', $request->grade)
            ->where('is_active', true)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->whereRaw('ADDTIME(start_time, SEC_TO_TIME(duration * 60)) > ?', [$startTime]);
                });
            })
            ->exists();

        if ($conflict) {
            return back()->withInput()->withErrors(['grade' => 'This grade already has another class scheduled at this time on ' . $request->day_of_week]);
        }

        $data = $request->except('is_active');
        $data['is_active'] = $request->has('is_active');

        $timetable->update($data);

        // Immediate sync to Zoom
        try {
            \Illuminate\Support\Facades\Artisan::call('zoom:sync-timetable');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Auto-sync failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.timetables.index')->with('success', 'Timetable slot updated successfully and synced to Zoom.');
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

    public function sync()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('zoom:sync-timetable');
            $output = \Illuminate\Support\Facades\Artisan::output();
            return redirect()->route('admin.timetables.index')->with('success', 'Zoom sync completed: ' . $output);
        } catch (\Exception $e) {
            return redirect()->route('admin.timetables.index')->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
}
