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

    /**
     * Options offered in the "Received by" select of every admin Mark Paid dialog.
     * Stored in payments.meta.received_by (no schema change).
     */
    public const RECEIVED_BY_OPTIONS = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'other' => 'Other',
    ];

    /**
     * ONE rule for "the payment gateway really confirmed this payment":
     * only payhereNotify / a trusted callback store a non-empty transaction_id.
     * A pending row that merely carries payment_method='online' and a PH_… gateway_ref
     * (created when the student pressed "Pay online") is NOT confirmed.
     */
    public function wasGatewayConfirmed(): bool
    {
        return trim((string) $this->transaction_id) !== '';
    }

    /**
     * The method that may be shown to humans (receipt, admin screens).
     * 'online'/'payhere' is only returned when the gateway confirmed the payment;
     * otherwise it is reported as 'offline'. Other methods are returned unchanged.
     */
    public function displayMethod(): string
    {
        $method = strtolower(trim((string) $this->payment_method));

        if (in_array($method, ['online', 'payhere'], true)) {
            return $this->wasGatewayConfirmed() ? 'online' : 'offline';
        }

        return $method;
    }

    /**
     * Attributes to merge into the update when an ADMIN marks this payment as paid
     * (row Mark Paid, student-page Mark Paid, Confirm + Mark Paid, markMonthPaid,
     * top-up Mark Paid, advance approval, edit form turning a row to paid).
     *
     * Not gateway-confirmed => payment_method becomes 'offline', the previous value is
     * kept in meta.original_method and a PH_… gateway_ref is replaced by a neutral
     * ADMIN_<id>_<ymdHis> reference (the old one is kept in meta.original_gateway_ref).
     * Gateway-confirmed rows keep their method and references untouched.
     *
     * @return array<string,mixed> attributes for ->update()
     */
    public function adminPaidAttributes(?string $receivedBy = null, ?int $adminId = null): array
    {
        $meta = (array) ($this->meta ?? []);
        $attrs = [];

        if (!$this->wasGatewayConfirmed()) {
            $old = strtolower(trim((string) $this->payment_method));
            $new = in_array($old, ['bank', 'bank_transfer', 'cash'], true) ? $old : 'offline';
            if ($old !== '' && $old !== $new && !isset($meta['original_method'])) {
                $meta['original_method'] = $old;
            }
            $attrs['payment_method'] = $new;

            $ref = (string) $this->gateway_ref;
            if ($ref !== '' && str_starts_with(strtoupper($ref), 'PH_')) {
                $meta['original_gateway_ref'] = $meta['original_gateway_ref'] ?? $ref;
                $attrs['gateway_ref'] = 'ADMIN_' . ($this->id ?: 'NEW') . '_' . now()->format('ymdHis');
            }

            $receivedBy = strtolower(trim((string) $receivedBy));
            if ($receivedBy !== '') {
                $meta['received_by'] = array_key_exists($receivedBy, self::RECEIVED_BY_OPTIONS) ? $receivedBy : 'other';
            }
            if ($this->status !== 'paid') {
                if ($adminId) {
                    $meta['marked_paid_by'] = $adminId;
                }
                $meta['marked_paid_at'] = now()->toDateTimeString();
            }
        }

        $attrs['meta'] = $meta === [] ? $this->meta : $meta;

        return $attrs;
    }

    /**
     * Human label for meta.received_by, or null when not set / not applicable.
     */
    public function receivedByLabel(): ?string
    {
        $key = $this->meta['received_by'] ?? null;

        return $key ? (self::RECEIVED_BY_OPTIONS[$key] ?? 'Other') : null;
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
