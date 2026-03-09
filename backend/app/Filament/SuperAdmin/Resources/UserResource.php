<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\UserResource\Pages;
use App\Models\Institute;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static string | \UnitEnum | null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->disabled(),
                        TextInput::make('email')
                            ->disabled(),
                        TextInput::make('role')
                            ->disabled(),
                        TextInput::make('phone_number')
                            ->disabled(),
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

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'teacher' => 'info',
                        'user' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('institute.name')
                    ->label('Institute')
                    ->default('N/A')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->state(fn (User $record) => $record->deactivated_at === null)
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                        'teacher' => 'Teacher',
                        'user' => 'Student',
                    ]),

                Tables\Filters\SelectFilter::make('institute_id')
                    ->label('Institute')
                    ->options(fn () => Institute::pluck('name', 'id')->toArray()),

                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active Status')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('deactivated_at'),
                        false: fn (Builder $query) => $query->whereNotNull('deactivated_at'),
                    ),
            ])
            ->actions([
                Actions\ViewAction::make(),

                Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->deactivated_at === null && $record->role !== 'super_admin')
                    ->action(fn (User $record) => $record->update(['deactivated_at' => now()])),

                Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->deactivated_at !== null)
                    ->action(fn (User $record) => $record->update(['deactivated_at' => null])),

                Actions\Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-finger-print')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => auth()->user()->canImpersonate() && $record->canBeImpersonated())
                    ->action(fn (User $record) => auth()->user()->impersonate($record)),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
