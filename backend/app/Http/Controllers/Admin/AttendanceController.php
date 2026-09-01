<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZoomSchedule;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $schedules = ZoomSchedule::with(['attendances.student', 'teachers'])->latest('scheduled_at')->paginate(15);
        return view('admin.attendance.index', compact('schedules'));
    }

    public function update(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'status' => 'required|in:present,absent',
        ]);

        $att = Attendance::findOrFail($request->attendance_id);
        $att->update([
            'status' => $request->status,
            'source' => 'manual',
            'marked_at' => $request->status === 'present' ? now() : null,
        ]);

        return redirect()->back()->with('success', 'Attendance updated.');
    }
}
