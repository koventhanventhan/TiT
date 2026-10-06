<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZoomSchedule;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ZoomService;
use App\Services\ClassNotifier;
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
            'medium' => 'required|in:english,tamil,both',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $data = [
            'title' => $request->title,
            'scheduled_at' => $request->scheduled_at,
            'subject' => $request->subject,
            'grade' => $request->grade,
            'medium' => $request->medium,
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

        // Email-only notification on class creation
        $teacherIds = $request->teacher_ids ?? [];
        $classGrade = $schedule->grade;
        $classMedium = $schedule->medium;
        $classSubject = $schedule->subject;
        $classTitle = $schedule->title;
        $classTime = date('M d, Y @ H:i', strtotime($schedule->scheduled_at));
        $joinUrl = $schedule->join_url;

        dispatch(function () use ($teacherIds, $classGrade, $classMedium, $classSubject, $classTitle, $classTime, $joinUrl) {
            try {
                $notifier = app(\App\Services\ClassNotifier::class);
                $students = $notifier->getMatchingStudents($classGrade, $classMedium, $classSubject);
                $studentIds = $students->pluck('id')->toArray();

                $notifier->sendClassCreatedEmails(
                    $classTitle,
                    $classTime,
                    $classSubject,
                    $classGrade,
                    $joinUrl,
                    $teacherIds,
                    $studentIds
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send class creation emails: ' . $e->getMessage());
            }
        })->afterResponse();

        return redirect()->route('admin.zoom.index')->with('success', 'Zoom class created successfully. Email notifications are being sent.');
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
            'medium' => 'required|in:english,tamil,both',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $data = [
            'title' => $request->title,
            'scheduled_at' => $request->scheduled_at,
            'subject' => $request->subject,
            'grade' => $request->grade,
            'medium' => $request->medium,
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

        // 2. Notify Students using shared logic
        if ($schedule->grade) {
            $classNotifier = app(\App\Services\ClassNotifier::class);
            $students = $classNotifier->getMatchingStudents(
                $schedule->grade,
                $schedule->medium,
                $schedule->subject
            );

            foreach ($students as $student) {
                $this->notifier->notifyUser(
                    $student, 'zoom_reminder', 'tit_zoom_reminder',
                    [$schedule->title, $time],
                    ['class_title' => $schedule->title, 'class_time' => $time]
                );
                $sentCount++;
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
