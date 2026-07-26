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
    protected NotificationService $notifier;
    protected ZoomService $zoom;

    public function __construct(NotificationService $notifier, ZoomService $zoom)
    {
        $this->notifier = $notifier;
        $this->zoom = $zoom;
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
            $meeting = $this->zoom->createMeeting($request->title, date('Y-m-d\TH:i:s', strtotime($request->scheduled_at)));
            if ($meeting) {
                $data['meeting_id'] = $meeting['id'];
                $data['zoom_link'] = $meeting['join_url'];
                $data['start_url'] = $meeting['start_url'];
                $data['join_url'] = $meeting['join_url'];
                $data['password'] = $meeting['password'] ?? null;
            } else {
                Log::error('Automated Zoom meeting creation failed');
                return redirect()->back()->withInput()->with('error', 'Failed to create automated Zoom meeting. Please provide a link manually or try again.');
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

        // Fetch the schedule with relations for notification
        $schedule->load('teachers');

        // Notify Teachers
        foreach ($schedule->teachers as $teacher) {
            $this->notifier->notifyUser(
                $teacher, 'zoom_reminder', 'tit_zoom_reminder',
                [$schedule->title, $schedule->scheduled_at->format('H:i')],
                ['class_title' => $schedule->title, 'class_time' => $schedule->scheduled_at->format('H:i')]
            );
        }

        // Notify Students in the same grade
        if ($schedule->grade) {
            $students = User::where('role', 'user')
                ->whereNull('deactivated_at')
                ->get();

            preg_match('/(\d+)/', $schedule->grade, $classMatch);
            $classRef = isset($classMatch[1]) ? $classMatch[1] : strtoupper(trim($schedule->grade));

            foreach ($students as $student) {
                // 1. Grade Match
                $userGrade = $student->current_grade;
                if (!$userGrade) continue;

                preg_match('/(\d+)/', $userGrade, $userMatch);
                $userRef = isset($userMatch[1]) ? $userMatch[1] : strtoupper(trim($userGrade));

                if ($userRef !== $classRef) {
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
                        [$schedule->title, $schedule->scheduled_at->format('H:i')],
                        ['class_title' => $schedule->title, 'class_time' => $schedule->scheduled_at->format('H:i')]
                    );
                }
            }
        }

        return redirect()->route('admin.zoom.index')->with('success', 'Zoom class created and notifications sent.');
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
        if ($schedule->meeting_id) {
            $this->zoom->updateMeeting($schedule->meeting_id, [
                'topic' => $request->title,
                'start_time' => date('Y-m-d\TH:i:s', strtotime($request->scheduled_at)),
            ]);
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
        if ($schedule->meeting_id) {
            $this->zoom->deleteMeeting($schedule->meeting_id);
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
            if ($schedule->meeting_id) {
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
