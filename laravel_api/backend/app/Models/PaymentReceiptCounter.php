<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReceiptCounter extends Model
{
    use HasFactory, BelongsToInstitute;

    protected $fillable = [
        'institute_id',
        'receipt_year',
        'last_seq',
    ];

    protected $casts = [
        'receipt_year' => 'integer',
        'last_seq' => 'integer',
    ];
}
