<?php

namespace App\Filament\SuperAdmin\Resources\Coupons;

use App\Filament\SuperAdmin\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\SuperAdmin\Resources\Coupons\Pages\EditCoupon;
use App\Filament\SuperAdmin\Resources\Coupons\Pages\ListCoupons;
use App\Filament\SuperAdmin\Resources\Coupons\Schemas\CouponForm;
use App\Filament\SuperAdmin\Resources\Coupons\Tables\CouponsTable;
use App\Models\Coupon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return CouponForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CouponsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit' => EditCoupon::route('/{record}/edit'),
        ];
    }
}
