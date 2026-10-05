<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminMessage;
use App\Models\AdminMessageRead;
use Illuminate\Http\Request;

class StudentMessageController extends Controller
{
    /**
     * Get messages for logged-in student (broadcast + individual for this user).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'user' || $user->deactivated_at) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = AdminMessage::where(function ($q) use ($user) {
            $q->where('target_type', 'broadcast')
                ->orWhere(function ($q2) use ($user) {
                    $q2->where('target_type', 'individual')->where('target_user_id', $user->id);
                });
        })
            ->orderByDesc('created_at')
            ->get();

        $readIds = AdminMessageRead::where('user_id', $user->id)->pluck('admin_message_id')->toArray();

        $items = $messages->map(function ($m) use ($readIds) {
            return [
                'id' => $m->id,
                'title' => $m->title,
                'body' => $m->body,
                'target_type' => $m->target_type,
                'created_at' => $m->created_at->toIso8601String(),
                'read' => in_array($m->id, $readIds),
            ];
        });

        return response()->json(['messages' => $items]);
    }

    /**
     * Mark message as read.
     */
    public function markRead(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'user' || $user->deactivated_at) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message = AdminMessage::findOrFail($id);
        $canRead = $message->target_type === 'broadcast'
            || ($message->target_type === 'individual' && (int) $message->target_user_id === (int) $user->id);
        if (!$canRead) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        AdminMessageRead::firstOrCreate(
            ['admin_message_id' => $message->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        return response()->json(['message' => 'Marked as read.']);
    }
}
