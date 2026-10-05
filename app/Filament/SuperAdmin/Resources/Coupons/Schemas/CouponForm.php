<?php

namespace App\Filament\SuperAdmin\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                        Select::make('type')
                            ->options([
                                'fixed' => 'Fixed Amount',
                                'percentage' => 'Percentage',
                            ])
                            ->default('percentage')
                            ->required()
                            ->live(),
                        TextInput::make('value')
                            ->required()
                            ->numeric()
                            ->prefix(fn ($get) => $get('type') === 'fixed' ? '$' : null)
                            ->suffix(fn ($get) => $get('type') === 'percentage' ? '%' : null),
                    ])->columns(3),

                Section::make('Usage Limits & Validity')
                    ->schema([
                        TextInput::make('max_uses')
                            ->numeric()
                            ->label('Max Uses')
                            ->helperText('Leave empty for unlimited'),
                        TextInput::make('used_count')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->label('Times Used'),
                        DateTimePicker::make('starts_at'),
                        DateTimePicker::make('expires_at'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }
}
