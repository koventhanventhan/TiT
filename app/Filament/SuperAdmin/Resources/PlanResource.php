<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\PlanResource\Pages;
use App\Models\SubscriptionPlan;
use Filament\Forms\Components\KeyValue;
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
                        KeyValue::make('features')
                            ->label('Key Features')
                            ->helperText('List features that will be shown to users'),
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
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'Unlimited' : $state),
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
