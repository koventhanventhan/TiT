<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectTopup extends Model
{
    use HasFactory;

    protected $table = 'subject_topups';

    protected $fillable = [
        'institute_id',
        'user_id',
        'year_month',
        'added_subjects',
        'previous_subjects',
        'amount',
        'status',
        'payment_method',
        'payment_id',
        'paid_at',
    ];

    protected $casts = [
        'added_subjects' => 'array',
        'previous_subjects' => 'array',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForMonth($query, ?string $yearMonth = null)
    {
        $ym = $yearMonth ?: now()->format('Y-m');
        return $query->where('year_month', $ym);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function markPaid(?int $paymentId = null, ?string $method = null): void
    {
        $this->update([
            'status' => 'paid',
            'payment_id' => $paymentId ?: $this->payment_id,
            'payment_method' => $method ?: $this->payment_method,
            'paid_at' => now(),
        ]);
    }

    public function markCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function markSuperseded(): void
    {
        $this->update(['status' => 'superseded']);
    }
}
