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

    public static function markPaid($target = null, ?string $method = 'online'): ?self
    {
        if ($target instanceof self) {
            $target->update([
                'status' => 'paid',
                'payment_method' => $method ?: $target->payment_method,
                'paid_at' => $target->paid_at ?: now(),
            ]);
            return $target;
        }

        $paymentId = $target instanceof Payment ? $target->id : (is_numeric($target) ? (int)$target : null);
        $payment = $target instanceof Payment ? $target : ($paymentId ? Payment::find($paymentId) : null);

        $topup = null;
        if ($payment) {
            $topupId = $payment->meta['topup_id'] ?? null;
            if ($topupId) {
                $topup = self::find($topupId);
            }
            if (!$topup) {
                $topup = self::where('payment_id', $payment->id)->first();
            }
            if (!$topup && !empty($payment->gateway_ref) && preg_match('/^TOPUP_(\d+)_/', $payment->gateway_ref, $m)) {
                $topup = self::find((int) $m[1]);
            }
            if (!$topup && !empty($payment->gateway_ref) && preg_match('/^TOPUP_ADMIN_(\d+)_/', $payment->gateway_ref, $m)) {
                $topup = self::find((int) $m[1]);
            }
            if (!$topup && !empty($payment->gateway_ref) && preg_match('/^TOPUP_OFF_(\d+)_/', $payment->gateway_ref, $m)) {
                $topup = self::find((int) $m[1]);
            }
            if (!$topup && $payment->user_id && $payment->year_month) {
                $topup = self::where('user_id', $payment->user_id)
                    ->where('year_month', $payment->year_month)
                    ->where('status', 'pending')
                    ->first();
            }
        } elseif (is_numeric($target)) {
            $topup = self::find((int) $target);
        }

        if ($topup) {
            $topup->update([
                'status' => 'paid',
                'payment_id' => $payment ? $payment->id : ($topup->payment_id ?: $paymentId),
                'payment_method' => $method ?: ($payment?->payment_method ?: ($topup->payment_method ?: 'online')),
                'paid_at' => $topup->paid_at ?: now(),
            ]);
            return $topup;
        }

        return null;
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
