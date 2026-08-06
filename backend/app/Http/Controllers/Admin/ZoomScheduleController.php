<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZoomSchedule;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZoomScheduleController extends Controller
{
    protected ?NotificationService $notifier = null;
    protected ?ZoomService $zoom = null;

    public function __construct()
    {
        try {
            $this->notifier = app(NotificationService::class);
        } catch (\Exception $e) {
            Log::warning('NotificationService could not be initialized: ' . $e->getMessage());
        }
        try {
            $this->zoom = app(ZoomService::class);
        } catch (\Exception $e) {
            Log::warning('ZoomService could not be initialized: ' . $e->getMessage());
        }
    }

    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $schedules = ZoomSchedule::with(['creator', 'teachers'])
            ->where('scheduled_at', '>=', now()->subDay()) // Only show classes from today onwards by default
            ->orderBy('scheduled_at', 'asc')
            ->paginate(15);
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
        Log::info('ZoomScheduleController@store started', $request->all());

        if (auth()->user()->role !== 'admin') {
            Log::warning('Permission denied for Zoom session creation');
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'zoom_link' => 'nullable|string|url',
            'scheduled_at' => 'required|date',
            'subject' => 'nullable|string|max:100',
            'grade' => 'nullable|string|max:50',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $data = [
            'title' => $request->title,
            'scheduled_at' => $request->scheduled_at,
            'subject' => $request->subject,
            'grade' => $request->grade,
            'created_by' => auth()->id(),
        ];

        Log::info('Prepared data for ZoomSchedule', $data);

        // Auto-create Zoom meeting if no link provided
        if (!$request->filled('zoom_link')) {
            $meeting = null;
            try {
                if ($this->zoom) {
                    $meeting = $this->zoom->createMeeting($request->title, date('Y-m-d\TH:i:s', strtotime($request->scheduled_at)));
                }
            } catch (\Exception $e) {
                Log::error('Zoom meeting creation exception: ' . $e->getMessage());
            }

            if ($meeting) {
                $data['meeting_id'] = $meeting['id'];
                $data['zoom_link'] = $meeting['join_url'];
                $data['start_url'] = $meeting['start_url'];
                $data['join_url'] = $meeting['join_url'];
                $data['password'] = $meeting['password'] ?? null;
            } else {
                Log::warning('Zoom meeting could not be created automatically. Saving class without Zoom link.');
                // Still save the class even without zoom — admin can add the link later
            }
        } else {
            $data['zoom_link'] = $request->zoom_link;
        }

        Log::info('Creating ZoomSchedule record');
        $schedule = ZoomSchedule::create($data);
        Log::info('ZoomSchedule record created', ['id' => $schedule->id]);

        if ($request->filled('teacher_ids')) {
            $schedule->teachers()->sync($request->teacher_ids);
        }

        // No immediate notification on class creation.
        // The cron job (zoom:send-reminders) will automatically notify
        // teachers and students ~45 minutes before the class starts.

        return redirect()->route('admin.zoom.index')->with('success', 'Zoom class created successfully. Reminders will be sent automatically before the class starts.');
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
            'zoom_link' => 'nullable|string|url',
            'scheduled_at' => 'required|date',
            'subject' => 'nullable|string|max:100',
            'grade' => 'nullable|string|max:50',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $data = [
            'title' => $request->title,
            'scheduled_at' => $request->scheduled_at,
            'subject' => $request->subject,
            'grade' => $request->grade,
        ];

        if ($request->filled('zoom_link')) {
            $data['zoom_link'] = $request->zoom_link;
        }

        // Update Zoom meeting if it was auto-created
        if ($schedule->meeting_id && $this->zoom) {
            try {
                $this->zoom->updateMeeting($schedule->meeting_id, [
                    'topic' => $request->title,
                    'start_time' => date('Y-m-d\TH:i:s', strtotime($request->scheduled_at)),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to update Zoom meeting: ' . $e->getMessage());
            }
        }

        $schedule->update($data);

        $schedule->teachers()->sync($request->teacher_ids ?? []);

        return redirect()->route('admin.zoom.index')->with('success', 'Zoom class updated.');
    }

    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $schedule = ZoomSchedule::findOrFail($id);
        
        // Delete Zoom meeting if it was auto-created
        if ($schedule->meeting_id && $this->zoom) {
            try {
                $this->zoom->deleteMeeting($schedule->meeting_id);
            } catch (\Exception $e) {
                Log::error('Failed to delete Zoom meeting from API: ' . $e->getMessage());
            }
        }

        $schedule->delete();
        return redirect()->route('admin.zoom.index')->with('success', 'Zoom class deleted.');
    }

    public function notify($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $schedule = ZoomSchedule::with(['teachers'])->findOrFail($id);
        $time = $schedule->scheduled_at->format('M d, Y @ H:i');
        
        $sentCount = 0;
        $baseUrl = config('app.url');

        if (!$this->notifier) {
            return redirect()->route('admin.zoom.index')->with('error', 'Notification service is not available.');
        }

        // 1. Notify Assigned Teachers
        foreach ($schedule->teachers as $teacher) {
            $this->notifier->notifyUser(
                $teacher, 'zoom_reminder', 'tit_zoom_reminder',
                [$schedule->title, $time],
                ['class_title' => $schedule->title, 'class_time' => $time]
            );
            $sentCount++;
        }

        // 2. Notify Students of the relevant grade
        if ($schedule->grade) {
            $students = User::where('role', 'user')
                ->whereNull('deactivated_at')
                ->get();

            preg_match('/(\d+)/', $schedule->grade, $classMatch);
            $classNum = $classMatch[1] ?? null;

            foreach ($students as $student) {
                // 1. Grade Match
                $userGrade = $student->current_grade;
                if (!$userGrade) continue;

                preg_match('/(\d+)/', $userGrade, $userMatch);
                $userNum = $userMatch[1] ?? null;

                if ($userNum === null || $classNum === null || $userNum !== $classNum) {
                    continue;
                }

                // 2. Filter by selected subjects (Robust substring match)
                $selected = $student->selected_subjects;
                $classSubject = trim($schedule->subject);
                
                if (!empty($classSubject)) {
                    $selectedArr = is_array($selected) ? $selected : (json_decode($selected, true) ?: explode(',', (string)$selected));
                    $selectedArr = array_map('trim', (array)$selectedArr);
                    
                    $subjectMatch = false;
                    foreach ($selectedArr as $studentSub) {
                        $studentSub = trim($studentSub);
                        if ($studentSub === $classSubject || 
                            stripos($studentSub, $classSubject) !== false || 
                            stripos($classSubject, $studentSub) !== false) {
                            $subjectMatch = true;
                            break;
                        }
                    }
                    if (!$subjectMatch) {
                        continue;
                    }
                }

                if ($student->phone_number || ($student->email && !str_ends_with($student->email, '@student.local'))) {
                    $this->notifier->notifyUser(
                        $student, 'zoom_reminder', 'tit_zoom_reminder',
                        [$schedule->title, $time],
                        ['class_title' => $schedule->title, 'class_time' => $time]
                    );
                    $sentCount++;
                }
            }
        }

        return redirect()->route('admin.zoom.index')->with('success', "Notifications sent to {$sentCount} contacts.");
    }

    public function bulkDelete(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request->validate(['ids' => 'required|string']);
        $ids = explode(',', $request->ids);
        
        $schedules = ZoomSchedule::whereIn('id', $ids)->get();
        foreach ($schedules as $schedule) {
            if ($schedule->meeting_id && $this->zoom) {
                try {
                    $this->zoom->deleteMeeting($schedule->meeting_id);
                } catch (\Exception $e) {
                    Log::error('Failed to delete Zoom meeting from Zoom API: ' . $e->getMessage());
                }
            }
            $schedule->delete();
        }

        return redirect()->back()->with('success', count($ids) . ' zoom classes deleted successfully.');
    }
}
