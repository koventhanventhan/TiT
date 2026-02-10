<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminMessage;
use App\Models\User;
use Illuminate\Http\Request;

class AdminMessageController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $messages = AdminMessage::with('targetUser')->latest()->paginate(20);
        return view('admin.messages.index', compact('messages'));
    }

    public function create()
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $students = User::where('role', 'user')->whereNull('deactivated_at')->whereNotNull('full_name')->orderBy('full_name')->get();
        return view('admin.messages.create', compact('students'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('admin.login')->with('error', 'Admin access required');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target_type' => 'required|in:broadcast,individual',
            'target_user_ids' => 'required_if:target_type,individual|array',
            'target_user_ids.*' => 'exists:users,id',
        ]);

        if ($request->target_type === 'broadcast') {
            AdminMessage::create([
                'title' => $request->title,
                'body' => $request->body,
                'target_type' => 'broadcast',
                'target_user_id' => null,
            ]);
            return redirect()->route('admin.messages.index')->with('success', 'Broadcast message created.');
        }

        foreach ($request->target_user_ids ?? [] as $userId) {
            AdminMessage::create([
                'title' => $request->title,
                'body' => $request->body,
                'target_type' => 'individual',
                'target_user_id' => $userId,
            ]);
        }
        return redirect()->route('admin.messages.index')->with('success', 'Message(s) sent.');
    }
}
