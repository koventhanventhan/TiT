<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\PlanResource\Pages;
use App\Models\SubscriptionPlan;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';
    protected static string | \UnitEnum | null $navigationGroup = 'Plans & Subscriptions';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                        TextInput::make('slug')
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->unique(SubscriptionPlan::class, 'slug', ignoreRecord: true),
                        TextInput::make('monthly_price')
                            ->numeric()
                            ->prefix('LKR')
                            ->required(),
                        TextInput::make('yearly_price')
                            ->numeric()
                            ->prefix('LKR')
                            ->required(),
                    ])->columns(2),

                Section::make('Limits & Features')
                    ->schema([
                        TextInput::make('max_students')
                            ->numeric()
                            ->default(0)
                            ->helperText('Use 0 for unlimited'),
                        TextInput::make('max_teachers')
                            ->numeric()
                            ->default(0)
                            ->helperText('Use 0 for unlimited'),
                        CheckboxList::make('features')
                            ->label('Plan Features')
                            ->options(config('plans.features', [
                                'zoom' => 'Zoom Classes',
                                'ai_assistant' => 'AI Learning Assistant',
                                'whatsapp' => 'WhatsApp Messaging',
                                'custom_domain' => 'Custom Domain',
                                'online_payments' => 'Online Payments (PayHere)',
                                'advanced_reports' => 'Advanced Reports',
                            ]))
                            ->columns(2)
                            ->afterStateHydrated(function ($component, $state) {
                                if (!is_array($state)) {
                                    $component->state([]);
                                    return;
                                }
                                $legacyMap = config('plans.legacy_map', []);
                                $canonicalOptions = array_keys(config('plans.features', []));
                                $mapped = [];
                                foreach ($state as $k => $v) {
                                    $item = is_int($k) ? $v : $k;
                                    if (in_array($item, $canonicalOptions, true)) {
                                        $mapped[] = $item;
                                    } elseif (array_key_exists($item, $legacyMap)) {
                                        if ($legacyMap[$item] !== null) {
                                            $mapped[] = $legacyMap[$item];
                                        }
                                    }
                                }
                                $component->state(array_values(array_unique($mapped)));
                            })
                            ->dehydrateStateUsing(function ($state, $record) {
                                $canonicalOptions = array_keys(config('plans.features', []));
                                $cleaned = array_values(array_intersect((array) $state, $canonicalOptions));
                                if ($record && is_array($record->features)) {
                                    $legacyMap = config('plans.legacy_map', []);
                                    foreach ($record->features as $raw) {
                                        if (!in_array($raw, $canonicalOptions, true) && !array_key_exists($raw, $legacyMap)) {
                                            $cleaned[] = $raw;
                                        }
                                    }
                                }
                                return array_values(array_unique($cleaned));
                            }),
                        Placeholder::make('legacy_features')
                            ->label('Other Legacy Features (Preserved)')
                            ->content(function ($record) {
                                if (!$record || !is_array($record->features)) {
                                    return 'None';
                                }
                                $canonicalOptions = array_keys(config('plans.features', []));
                                $legacyMap = config('plans.legacy_map', []);
                                $unknown = [];
                                foreach ($record->features as $raw) {
                                    if (!in_array($raw, $canonicalOptions, true) && !array_key_exists($raw, $legacyMap)) {
                                        $unknown[] = $raw;
                                    }
                                }
                                return empty($unknown) ? 'None' : implode(', ', $unknown);
                            })
                            ->visible(function ($record) {
                                if (!$record || !is_array($record->features)) {
                                    return false;
                                }
                                $canonicalOptions = array_keys(config('plans.features', []));
                                $legacyMap = config('plans.legacy_map', []);
                                foreach ($record->features as $raw) {
                                    if (!in_array($raw, $canonicalOptions, true) && !array_key_exists($raw, $legacyMap)) {
                                        return true;
                                    }
                                }
                                return false;
                            }),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('monthly_price')
                    ->money('LKR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('yearly_price')
                    ->money('LKR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_students')
                    ->label('Students Limit')
                    ->formatStateUsing(fn ($state, SubscriptionPlan $record): string => $record->formattedLimit('max_students')),
                Tables\Columns\TextColumn::make('max_teachers')
                    ->label('Teachers Limit')
                    ->formatStateUsing(fn ($state, SubscriptionPlan $record): string => $record->formattedLimit('max_teachers')),
                Tables\Columns\TextColumn::make('institutes_count')
                    ->counts('institutes')
                    ->label('Active Institutes'),
            ])
            ->filters([])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
