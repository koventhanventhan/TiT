<?php

namespace App\Filament\SuperAdmin\Resources\Coupons\Pages;

use App\Filament\SuperAdmin\Resources\Coupons\CouponResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;
}
