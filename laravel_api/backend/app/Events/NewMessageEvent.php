<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageData;

    public function __construct(public Message $message)
    {
        $this->messageData = [
            'id' => $message->id,
            'subject' => $message->subject,
            'body' => substr($message->body, 0, 100),
            'sender_name' => $message->sender->name ?? $message->sender->full_name ?? 'Unknown',
            'sender_role' => $message->sender->role,
            'is_broadcast' => $message->is_broadcast,
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     * For direct messages: broadcast to the receiver's private channel
     * For broadcasts: broadcast to a role-based channel
     */
    public function broadcastOn(): array
    {
        $channels = [];

        if (!$this->message->is_broadcast && $this->message->receiver_id) {
            // Direct message → private channel of receiver
            $channels[] = new PrivateChannel('user.' . $this->message->receiver_id);
        } else {
            // Broadcast → role-based channel
            $channels[] = new Channel('messages.' . $this->message->receiver_role);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'new.message';
    }

    public function broadcastWith(): array
    {
        return $this->messageData;
    }
}
