<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\BelongsToInstitute;

class Payment extends Model
{
    use HasFactory, BelongsToInstitute;

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'payment_method',
        'transaction_id',
        'paid_at',
        'institute_id',
        'year_month',
        'gateway_ref',
        'receipt_no',
        'receipt_sent_at',
        'receipt_last_error',
        'receipt_attempted_at',
        'kind',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'receipt_sent_at' => 'datetime',
            'receipt_attempted_at' => 'datetime',
            'amount' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjectTopup()
    {
        return $this->hasOne(SubjectTopup::class, 'payment_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function scopeMonthly($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('kind')->orWhereIn('kind', ['monthly', 'advance']);
        });
    }

    public function scopeTopup($query)
    {
        return $query->where('kind', 'topup');
    }
}
