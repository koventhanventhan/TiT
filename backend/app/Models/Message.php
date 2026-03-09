<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'receiver_role',
        'subject',
        'body',
        'is_broadcast',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_broadcast' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    /**
     * Scope: get messages relevant to a specific user
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // Direct messages to this user
            $q->where(function ($q2) use ($user) {
                $q2->where('receiver_id', $user->id)
                   ->where('is_broadcast', false);
            });

            // Broadcast to all students (user role = 'user')
            if ($user->role === 'user') {
                $q->orWhere('receiver_role', 'all_students');

                // Broadcast to this user's grade
                if ($user->current_grade) {
                    $q->orWhere('receiver_role', 'grade_' . $user->current_grade);
                }
            }

            // Broadcast to all teachers
            if ($user->role === 'teacher') {
                $q->orWhere('receiver_role', 'all_teachers');
            }

            // Admin sees everything sent to admin role
            if ($user->role === 'admin') {
                $q->orWhere('receiver_role', 'admin');
            }
        });
    }

    /**
     * Check if this message is read by a specific user
     */
    public function isReadBy(User $user): bool
    {
        // If direct message
        if (!$this->is_broadcast) {
            return $this->read_at !== null;
        }

        // If broadcast, check message_reads table
        return $this->reads()->where('user_id', $user->id)->exists();
    }
}
