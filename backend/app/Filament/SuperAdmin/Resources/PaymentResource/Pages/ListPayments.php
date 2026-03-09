<?php

namespace App\Filament\SuperAdmin\Resources\PaymentResource\Pages;

use App\Filament\SuperAdmin\Resources\PaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;
}
