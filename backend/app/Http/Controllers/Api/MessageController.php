<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;
use App\Events\NewMessageEvent;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * GET /api/messages — Inbox for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $messages = Message::forUser($user)
            ->with(['sender:id,name,full_name,role,email'])
            ->orderByDesc('created_at')
            ->paginate(30);

        // Get read IDs for broadcast messages
        $broadcastReadIds = MessageRead::where('user_id', $user->id)
            ->pluck('message_id')
            ->toArray();

        $items = $messages->getCollection()->map(function ($msg) use ($user, $broadcastReadIds) {
            $isRead = $msg->is_broadcast
                ? in_array($msg->id, $broadcastReadIds)
                : ($msg->read_at !== null);

            return [
                'id' => $msg->id,
                'subject' => $msg->subject,
                'body' => $msg->body,
                'sender' => [
                    'id' => $msg->sender->id,
                    'name' => $msg->sender->full_name ?? $msg->sender->name,
                    'role' => $msg->sender->role,
                ],
                'is_broadcast' => $msg->is_broadcast,
                'receiver_role' => $msg->receiver_role,
                'is_read' => $isRead,
                'created_at' => $msg->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'messages' => $items,
            'current_page' => $messages->currentPage(),
            'last_page' => $messages->lastPage(),
            'total' => $messages->total(),
        ]);
    }

    /**
     * GET /api/messages/{id} — View a single message
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $message = Message::with(['sender:id,name,full_name,role,email'])->findOrFail($id);

        // Check permission: user must be part of the conversation
        if (!$this->canUserSeeMessage($user, $message)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'message' => [
                'id' => $message->id,
                'subject' => $message->subject,
                'body' => $message->body,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->full_name ?? $message->sender->name,
                    'role' => $message->sender->role,
                ],
                'is_broadcast' => $message->is_broadcast,
                'receiver_role' => $message->receiver_role,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/messages — Send a new message
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'recipient_type' => 'required|in:individual,all_teachers,all_students,grade,admin',
            'recipient_id' => 'required_if:recipient_type,individual|nullable|exists:users,id',
            'grade' => 'required_if:recipient_type,grade|nullable|string',
        ]);

        $recipientType = $request->recipient_type;

        // Validate permissions based on sender's role
        if (!$this->canSend($user, $recipientType, $request->recipient_id)) {
            return response()->json(['message' => 'You are not allowed to send to this recipient.'], 403);
        }

        if ($recipientType === 'individual') {
            // Send to a single user
            $message = Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $request->recipient_id,
                'subject' => $request->subject,
                'body' => $request->body,
                'is_broadcast' => false,
            ]);

            // Broadcast via Pusher
            try { event(new NewMessageEvent($message)); } catch (\Exception $e) {}

            return response()->json(['message' => 'Message sent.', 'id' => $message->id]);
        }

        // Broadcast message
        $receiverRole = $recipientType;
        if ($recipientType === 'grade') {
            $receiverRole = 'grade_' . $request->grade;
        }

        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_role' => $receiverRole,
            'subject' => $request->subject,
            'body' => $request->body,
            'is_broadcast' => true,
        ]);

        // Broadcast via Pusher
        try { event(new NewMessageEvent($message)); } catch (\Exception $e) {}

        return response()->json(['message' => 'Broadcast sent.', 'id' => $message->id]);
    }

    /**
     * POST /api/messages/{id}/read — Mark message as read
     */
    public function markRead(Request $request, $id)
    {
        $user = $request->user();
        $message = Message::findOrFail($id);

        if (!$this->canUserSeeMessage($user, $message)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($message->is_broadcast) {
            MessageRead::firstOrCreate(
                ['message_id' => $message->id, 'user_id' => $user->id],
                ['read_at' => now()]
            );
        } else {
            if ($message->receiver_id === $user->id && !$message->read_at) {
                $message->update(['read_at' => now()]);
            }
        }

        return response()->json(['message' => 'Marked as read.']);
    }

    /**
     * GET /api/messages/recipients — Available recipients for the sender
     */
    public function recipients(Request $request)
    {
        $user = $request->user();
        $options = [];

        if ($user->role === 'admin') {
            $options['broadcast'] = [
                ['value' => 'all_teachers', 'label' => 'All Teachers'],
                ['value' => 'all_students', 'label' => 'All Students'],
            ];

            // Grade-based
            $grades = User::where('role', 'user')
                ->whereNotNull('current_grade')
                ->whereNull('deactivated_at')
                ->distinct()
                ->pluck('current_grade')
                ->sort()
                ->values();

            foreach ($grades as $grade) {
                $options['broadcast'][] = ['value' => 'grade_' . $grade, 'label' => 'Grade ' . $grade . ' Students'];
            }

            // Individual teachers
            $options['teachers'] = User::where('role', 'teacher')
                ->whereNull('deactivated_at')
                ->select('id', 'name', 'full_name', 'email')
                ->orderBy('name')
                ->get()
                ->map(fn($t) => ['id' => $t->id, 'name' => $t->full_name ?? $t->name, 'email' => $t->email]);

            // Individual students
            $options['students'] = User::where('role', 'user')
                ->whereNull('deactivated_at')
                ->select('id', 'name', 'full_name', 'email', 'current_grade')
                ->orderBy('full_name')
                ->get()
                ->map(fn($s) => ['id' => $s->id, 'name' => $s->full_name ?? $s->name, 'email' => $s->email, 'grade' => $s->current_grade]);

        } elseif ($user->role === 'teacher') {
            $options['broadcast'] = [
                ['value' => 'admin', 'label' => 'Admin'],
            ];

            // Grade-based
            $grades = User::where('role', 'user')
                ->whereNotNull('current_grade')
                ->whereNull('deactivated_at')
                ->distinct()
                ->pluck('current_grade')
                ->sort()
                ->values();

            foreach ($grades as $grade) {
                $options['broadcast'][] = ['value' => 'grade_' . $grade, 'label' => 'Grade ' . $grade . ' Students'];
            }

            // Individual students
            $options['students'] = User::where('role', 'user')
                ->whereNull('deactivated_at')
                ->select('id', 'name', 'full_name', 'email', 'current_grade')
                ->orderBy('full_name')
                ->get()
                ->map(fn($s) => ['id' => $s->id, 'name' => $s->full_name ?? $s->name, 'email' => $s->email, 'grade' => $s->current_grade]);

        } elseif ($user->role === 'user') {
            // Students can send to admin and teachers
            $options['admins'] = User::where('role', 'admin')
                ->whereNull('deactivated_at')
                ->select('id', 'name', 'full_name', 'email')
                ->orderBy('name')
                ->get()
                ->map(fn($a) => ['id' => $a->id, 'name' => $a->full_name ?? $a->name, 'email' => $a->email]);

            $options['teachers'] = User::where('role', 'teacher')
                ->whereNull('deactivated_at')
                ->select('id', 'name', 'full_name', 'email')
                ->orderBy('name')
                ->get()
                ->map(fn($t) => ['id' => $t->id, 'name' => $t->full_name ?? $t->name, 'email' => $t->email]);
        }

        return response()->json($options);
    }

    /**
     * GET /api/messages/unread-count — Unread message count
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();

        // Count direct unread messages
        $directUnread = Message::where('receiver_id', $user->id)
            ->where('is_broadcast', false)
            ->whereNull('read_at')
            ->count();

        // Count broadcast unread
        $broadcastMessages = Message::forUser($user)
            ->where('is_broadcast', true)
            ->pluck('id');

        $readBroadcastIds = MessageRead::where('user_id', $user->id)
            ->whereIn('message_id', $broadcastMessages)
            ->pluck('message_id');

        $broadcastUnread = $broadcastMessages->diff($readBroadcastIds)->count();

        return response()->json(['unread_count' => $directUnread + $broadcastUnread]);
    }

    /**
     * GET /api/messages/sent — Messages sent by the current user
     */
    public function sent(Request $request)
    {
        $user = $request->user();

        $messages = Message::where('sender_id', $user->id)
            ->with(['receiver:id,name,full_name,role,email'])
            ->orderByDesc('created_at')
            ->paginate(30);

        $items = $messages->getCollection()->map(function ($msg) {
            return [
                'id' => $msg->id,
                'subject' => $msg->subject,
                'body' => $msg->body,
                'receiver' => $msg->receiver ? [
                    'id' => $msg->receiver->id,
                    'name' => $msg->receiver->full_name ?? $msg->receiver->name,
                    'role' => $msg->receiver->role,
                ] : null,
                'is_broadcast' => $msg->is_broadcast,
                'receiver_role' => $msg->receiver_role,
                'created_at' => $msg->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'messages' => $items,
            'current_page' => $messages->currentPage(),
            'last_page' => $messages->lastPage(),
            'total' => $messages->total(),
        ]);
    }

    // ---- Private helpers ----

    private function canUserSeeMessage(User $user, Message $message): bool
    {
        // Sender can always see their own messages
        if ($message->sender_id === $user->id) return true;
        // Direct recipient
        if ($message->receiver_id === $user->id) return true;
        // Broadcast to role
        if ($message->is_broadcast) {
            if ($message->receiver_role === 'all_students' && $user->role === 'user') return true;
            if ($message->receiver_role === 'all_teachers' && $user->role === 'teacher') return true;
            if ($message->receiver_role === 'admin' && $user->role === 'admin') return true;
            if ($user->role === 'user' && $user->current_grade && $message->receiver_role === 'grade_' . $user->current_grade) return true;
        }
        // Admin can see everything
        if ($user->role === 'admin') return true;
        return false;
    }

    private function canSend(User $sender, string $recipientType, ?int $recipientId): bool
    {
        if ($sender->role === 'admin') {
            // Admin can send to anyone
            return true;
        }

        if ($sender->role === 'teacher') {
            // Teacher → admin, individual student, grade broadcast
            if ($recipientType === 'admin') return true;
            if ($recipientType === 'grade') return true;
            if ($recipientType === 'individual') {
                $recipient = User::find($recipientId);
                return $recipient && in_array($recipient->role, ['user', 'admin']);
            }
            return false;
        }

        if ($sender->role === 'user') {
            // Student → admin or teacher only (individual)
            if ($recipientType === 'individual') {
                $recipient = User::find($recipientId);
                return $recipient && in_array($recipient->role, ['admin', 'teacher']);
            }
            return false;
        }

        return false;
    }
}
