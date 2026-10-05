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

        // Generate a Sanctum token for API calls from JavaScript
        $user = auth()->user();
        $token = $user->createToken('admin-messages')->plainTextToken;

        return view('admin.messages.index', compact('token'));
    }

    public function unreadCount()
    {
        $user = auth()->user();
        
        // Count direct unread messages
        $directUnread = \App\Models\Message::where('receiver_id', $user->id)
            ->where('is_broadcast', false)
            ->whereNull('read_at')
            ->count();

        // Count broadcast unread
        $broadcastMessages = \App\Models\Message::forUser($user)
            ->where('is_broadcast', true)
            ->pluck('id');

        $readBroadcastIds = \App\Models\MessageRead::where('user_id', $user->id)
            ->whereIn('message_id', $broadcastMessages)
            ->pluck('message_id');

        $broadcastUnread = $broadcastMessages->diff($readBroadcastIds)->count();

        return response()->json(['unread_count' => $directUnread + $broadcastUnread]);
    }
}
