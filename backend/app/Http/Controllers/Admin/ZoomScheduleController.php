<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZoomSchedule;
use App\Models\User;
use App\Services\WhatsAppService;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZoomScheduleController extends Controller
{
    protected WhatsAppService $whatsApp;
    protected ZoomService $zoom;

    public function __construct(WhatsAppService $whatsApp, ZoomService $zoom)
    {
        $this->whatsApp = $whatsApp;
        $this->zoom = $zoom;
    }

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
            if ($teacher->phone_number) {
                $link = $schedule->start_url ?: $schedule->zoom_link;
                $msg = "Hello {$teacher->name},\n\nYou have a new Zoom class: *{$schedule->title}*\nTime: {$schedule->scheduled_at}\n\nStart Link: {$link}";
                if ($schedule->password) {
                    $msg .= "\nPassword: {$schedule->password}";
                }
                $this->whatsApp->send($teacher->phone_number, $msg);
            }
        }

        // Notify Students in the same grade
        if ($schedule->grade) {
            $students = User::where('role', 'user')
                ->where('current_grade', $schedule->grade)
                ->whereNull('deactivated_at')
                ->get();

            foreach ($students as $student) {
                // Filter by selected subjects: Only send if the student has selected this schedule's subject
                $selected = $student->selected_subjects;
                $classSubject = trim($schedule->subject);
                
                if (!empty($classSubject)) {
                    $selectedArr = is_array($selected) ? $selected : (json_decode($selected, true) ?: explode(',', (string)$selected));
                    $selectedArr = array_map('trim', (array)$selectedArr);
                    
                    if (!in_array($classSubject, $selectedArr)) {
                        continue;
                    }
                }

                if ($student->phone_number) {
                    $link = $schedule->join_url ?: $schedule->zoom_link;
                    $msg = "Hello {$student->name},\n\nNew Zoom class scheduled: *{$schedule->title}*\nTime: {$schedule->scheduled_at}\n\nJoin Link: {$link}";
                    if ($schedule->password) {
                        $msg .= "\nPassword: {$schedule->password}";
                    }
                    $this->whatsApp->send($student->phone_number, $msg);
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
            if ($teacher->phone_number) {
                $teacherMsg = "👨‍🏫 *Hi Teacher {$teacher->name},*\n\n" .
                             "You have a new Zoom class scheduled.\n\n" .
                             "📚 *Subject:* " . ($schedule->subject ?? 'General') . "\n" .
                             "📝 *Title:* {$schedule->title}\n" .
                             "⏰ *Time:* {$time}\n\n" .
                             "🚀 *Start Class (via Dashboard):* {$baseUrl}/teacher/schedule\n" .
                             "🔗 *Direct Zoom Link:* {$schedule->zoom_link}\n\n" .
                             "Please be ready 5 minutes before the start.";
                $this->whatsApp->send($teacher->phone_number, $teacherMsg);
                $sentCount++;
            }
        }

        // 2. Notify Students of the relevant grade
        if ($schedule->grade) {
            $students = User::where('role', 'user')
                ->where('current_grade', $schedule->grade)
                ->whereNull('deactivated_at')
                ->get();

            foreach ($students as $student) {
                // Filter by selected subjects: Only send if the student has selected this schedule's subject
                $selected = $student->selected_subjects;
                $classSubject = trim($schedule->subject);
                
                if (!empty($classSubject)) {
                    $selectedArr = is_array($selected) ? $selected : (json_decode($selected, true) ?: explode(',', (string)$selected));
                    $selectedArr = array_map('trim', (array)$selectedArr);
                    
                    if (!in_array($classSubject, $selectedArr)) {
                        continue;
                    }
                }

                if ($student->phone_number) {
                    $studentMsg = "👋 *Hello " . ($student->full_name ?? $student->name) . ",*\n\n" .
                                 "You have a new Zoom class scheduled today!\n\n" .
                                 "📚 *Subject:* " . ($schedule->subject ?? 'General') . "\n" .
                                 "📝 *Title:* {$schedule->title}\n" .
                                 "⏰ *Time:* {$time}\n\n" .
                                 "🎓 *Join via Dashboard (To mark Attendance):* {$baseUrl}/student/zoom\n" .
                                 "🔗 *Direct Zoom Link:* {$schedule->zoom_link}\n\n" .
                                 "Happy learning!";
                    $this->whatsApp->send($student->phone_number, $studentMsg);
                    $sentCount++;
                }
            }
        }

        return redirect()->route('admin.zoom.index')->with('success', "Notifications sent to {$sentCount} contacts.");
    }
}
