<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\PlanResource\Pages;
use App\Models\SubscriptionPlan;
use Filament\Actions;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';
    protected static string | \UnitEnum | null $navigationGroup = 'Subscription & Billing';
    protected static ?string $navigationLabel = 'Plans';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Filament\Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])->columns(2),

                Section::make('Pricing')
                    ->schema([
                        TextInput::make('monthly_price')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        TextInput::make('yearly_price')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                    ])->columns(2),

                Section::make('Limits')
                    ->schema([
                        TextInput::make('max_students')
                            ->numeric()
                            ->default(-1)
                            ->helperText('-1 for unlimited'),

                        TextInput::make('max_teachers')
                            ->numeric()
                            ->default(-1)
                            ->helperText('-1 for unlimited'),
                    ])->columns(2),

                Section::make('Features')
                    ->schema([
                        KeyValue::make('features')
                            ->label('Feature Flags')
                            ->helperText('Key-value pairs of features included in this plan'),
                    ]),
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
                    ->label('Monthly')
                    ->money('usd')
                    ->sortable(),

                Tables\Columns\TextColumn::make('yearly_price')
                    ->label('Yearly')
                    ->money('usd')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_students')
                    ->label('Max Students')
                    ->formatStateUsing(fn ($state) => $state == -1 ? 'Unlimited' : $state),

                Tables\Columns\TextColumn::make('max_teachers')
                    ->label('Max Teachers')
                    ->formatStateUsing(fn ($state) => $state == -1 ? 'Unlimited' : $state),

                Tables\Columns\TextColumn::make('institutes_count')
                    ->counts('institutes')
                    ->label('Institutes')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
