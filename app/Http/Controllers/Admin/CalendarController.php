<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function index()
    {
        $events = CalendarEvent::where('user_id', Auth::id())->get();
        return response()->json($events->map(function($e) {
            return [
                'id' => $e->id,
                'title' => $e->title,
                'start' => $e->event_date->format('Y-m-d') . ($e->start_time ? 'T'.$e->start_time : ''),
                'className' => 'bg-'.$e->type,
                'extendedProps' => [
                    'type' => $e->type,
                    'priority' => $e->priority,
                    'location' => $e->location,
                ]
            ];
        }));
    }

    public function getCategoryCounts()
    {
        $counts = CalendarEvent::where('user_id', Auth::id())
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');
            
        return response()->json($counts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'priority' => 'required|string',
            'event_date' => 'required|date',
            'start_time' => 'nullable|string',
            'duration' => 'nullable|string',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
            'attendees' => 'nullable|string',
            'reminders' => 'nullable|array',
            'is_recurring' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();

        $event = CalendarEvent::create($validated);

        return response()->json(['success' => true, 'event' => $event]);
    }

    public function update(Request $request, $id)
    {
        $event = CalendarEvent::where('user_id', Auth::id())->findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string',
            'priority' => 'sometimes|required|string',
            'event_date' => 'sometimes|required|date',
            'start_time' => 'nullable|string',
            'duration' => 'nullable|string',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
            'attendees' => 'nullable|string',
            'reminders' => 'nullable|array',
            'is_recurring' => 'boolean',
        ]);

        $event->update($validated);

        return response()->json(['success' => true, 'event' => $event]);
    }

    public function destroy($id)
    {
        $event = CalendarEvent::where('user_id', Auth::id())->findOrFail($id);
        $event->delete();

        return response()->json(['success' => true]);
    }
}
