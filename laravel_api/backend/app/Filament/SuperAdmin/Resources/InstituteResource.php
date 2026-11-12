<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\InstituteResource\Pages;
use App\Models\Institute;
use Closure;
use DomainException;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class InstituteResource extends Resource
{
    protected static ?string $model = Institute::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';
    protected static string | \UnitEnum | null $navigationGroup = 'Institute Management';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Institute details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Institute name')
                            ->required()
                            ->minLength(2)
                            ->maxLength(80)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, ?string $state, ?Institute $record) => $record === null ? $set('slug', Str::slug((string) $state)) : null),

                        TextInput::make('slug')
                            ->label('Short URL identifier')
                            ->required()
                            ->maxLength(240)
                            ->rule(static function (?Institute $record): Closure {
                                return static function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                    $normalized = Str::slug(trim((string) $value));
                                    if ($normalized === '' || $normalized !== trim((string) $value)) {
                                        $fail('Use lowercase letters, numbers, and hyphens only.');

                                        return;
                                    }

                                    $exists = Institute::withTrashed()
                                        ->where('slug', $normalized)
                                        ->when($record !== null, static fn ($q) => $q->whereKeyNot($record->getKey()))
                                        ->exists();

                                    if ($exists) {
                                        $fail('This URL identifier is already used by another institute (including deleted ones).');
                                    }
                                };
                            }),

                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->maxSize(2048)
                            ->disk('public')
                            ->directory('institute-logos'),

                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'expired' => 'Expired',
                            ])
                            ->default('active')
                            ->disabled(static fn (?Institute $record): bool => $record !== null && (int) $record->id === 1)
                            ->dehydrated()
                            ->required(),
                    ])->columns(2),

                Section::make('Plan & student numbering')
                    ->schema([
                        Select::make('subscription_plan_id')
                            ->label('Plan')
                            ->relationship('plan', 'name')
                            ->preload()
                            ->searchable(),

                        DateTimePicker::make('expires_at')
                            ->label('Expiry date'),

                        TextInput::make('index_prefix')
                            ->label('Student ID prefix')
                            ->minLength(2)
                            ->maxLength(6)
                            ->regex('/^[A-Za-z0-9]{2,6}$/'),

                        Select::make('index_format')
                            ->label('Student ID format')
                            ->options([
                                'grade' => 'By grade (for example, ABC/08/001)',
                                'batch' => 'By batch year (for example, ABC/26/1001)',
                            ])
                            ->default('grade'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query->with(['domains', 'plan']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Institute')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'danger',
                        'expired' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('setup_badges')
                    ->label('Setup')
                    ->badge()
                    ->getStateUsing(static fn (Institute $record): array => $record->setupBadges())
                    ->color(static fn (string $state): string => match (true) {
                        $state === 'Domain: verified',
                        $state === 'Admin: yes',
                        $state === 'Integrations: 3/3' => 'success',
                        $state === 'Domain: unverified',
                        $state === 'Integrations: 1/3',
                        $state === 'Integrations: 2/3' => 'warning',
                        $state === 'Domain: none',
                        $state === 'Admin: no' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->default('No plan'),

                Tables\Columns\TextColumn::make('students_count')
                    ->counts('students')
                    ->label('Students')
                    ->sortable(),

                Tables\Columns\TextColumn::make('teachers_count')
                    ->counts('teachers')
                    ->label('Teachers')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->badge()
                    ->color(fn (Institute $record): string => match (true) {
                        (int) $record->id === 1 => 'gray',
                        $record->isExpired() => 'danger',
                        $record->expires_at !== null && now()->diffInDays($record->expires_at, false) <= 14 && now()->lte($record->expires_at) => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'expired' => 'Expired',
                    ]),

                Tables\Filters\SelectFilter::make('subscription_plan_id')
                    ->label('Plan')
                    ->relationship('plan', 'name'),

                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('id', '!=', 1)
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<', now())
                    ),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring in 30 days')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('id', '!=', 1)
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '>=', now())
                        ->where('expires_at', '<=', now()->addDays(30))
                    ),

                Tables\Filters\Filter::make('over_limit')
                    ->label('Over limit')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereHas('plan', fn ($p) => $p->where('max_students', '>', 0))
                        ->whereRaw('(SELECT count(*) FROM users WHERE users.institute_id = institutes.id AND users.role = \'user\' AND users.deleted_at IS NULL) > (SELECT max_students FROM subscription_plans WHERE subscription_plans.id = institutes.subscription_plan_id)')
                    ),
            ])
            ->actions([
                Actions\ViewAction::make()
                    ->label('Checklist'),

                static::getRenewAction(),

                Actions\EditAction::make(),

                Actions\Action::make('suspend')
                    ->label(static fn (Institute $record): string => (!$record->canBeDeletedSafely() && (int) $record->id !== 1) ? 'Suspend instead' : 'Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(static fn (Institute $record): string => "Suspend {$record->name}?")
                    ->modalDescription(static fn (Institute $record): string => "Suspending {$record->name} immediately blocks sign-ins for its administrators, teachers, and students until reactivated.")
                    ->hidden(static fn (Institute $record): bool => (int) $record->id === 1 || $record->status !== 'active')
                    ->action(static function (Institute $record): void {
                        try {
                            $record->update(['status' => 'suspended']);

                            Notification::make()
                                ->title("{$record->name} suspended")
                                ->success()
                                ->send();
                        } catch (DomainException $e) {
                            Notification::make()
                                ->title('Cannot suspend institute')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(static fn (Institute $record): string => "Reactivate {$record->name}?")
                    ->modalDescription(static fn (Institute $record): string => "Restore access for {$record->name}?")
                    ->visible(static fn (Institute $record): bool => (int) $record->id !== 1 && $record->status !== 'active')
                    ->action(static function (Institute $record): void {
                        $record->update(['status' => 'active']);

                        Notification::make()
                            ->title("{$record->name} activated")
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('delete_institute')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->hidden(static fn (Institute $record): bool => !$record->canBeDeletedSafely())
                    ->requiresConfirmation()
                    ->modalHeading(static fn (Institute $record): string => "Delete {$record->name}?")
                    ->modalDescription(static fn (Institute $record): string => "This institute has 0 students and 0 payments. Type \"{$record->name}\" below to confirm removal.")
                    ->form(static fn (Institute $record): array => [
                        TextInput::make('confirm_name')
                            ->label("Type \"{$record->name}\" to confirm")
                            ->required()
                            ->rule(static function () use ($record): Closure {
                                return static function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                    if (trim((string) $value) !== $record->name) {
                                        $fail("Please type \"{$record->name}\" exactly to confirm deletion.");
                                    }
                                };
                            }),
                    ])
                    ->action(static function (Institute $record): void {
                        try {
                            $name = $record->name;
                            $record->delete();

                            Notification::make()
                                ->title("{$name} deleted")
                                ->success()
                                ->send();
                        } catch (DomainException $e) {
                            Notification::make()
                                ->title('Cannot delete institute')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRenewAction(): Actions\Action
    {
        return Actions\Action::make('renew')
            ->label('Renew')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->modalHeading(static fn (Institute $record): string => "Renew subscription — {$record->name}")
            ->modalDescription(static fn (Institute $record): string => "Extend {$record->name}'s subscription from its current expiry or today.")
            ->form([
                Select::make('duration')
                    ->label('Extension')
                    ->options([
                        '30_days' => '+30 days',
                        '1_year'  => '+1 year',
                        'custom'  => 'Custom date',
                    ])
                    ->default('30_days')
                    ->required()
                    ->live(),

                DateTimePicker::make('custom_expires_at')
                    ->label('Custom expiry date')
                    ->visible(fn ($get) => $get('duration') === 'custom')
                    ->required(fn ($get) => $get('duration') === 'custom'),

                Select::make('subscription_plan_id')
                    ->label('Change plan (optional)')
                    ->options(static fn (): array => \App\Models\SubscriptionPlan::orderBy('id')->pluck('name', 'id')->all())
                    ->placeholder('Keep current plan'),

                TextInput::make('note')
                    ->label('Note (optional)')
                    ->placeholder('Payment reference, invoice number, or reason')
                    ->maxLength(255),
            ])
            ->action(static function (Institute $record, array $data): void {
                $record->renewSubscription($data);

                Notification::make()
                    ->title('Subscription renewed')
                    ->body("{$record->name} subscription renewed until " . $record->expires_at->format('M d, Y'))
                    ->success()
                    ->send();
            });
    }

    public static function getRelations(): array
    {
        return [
            InstituteResource\RelationManagers\DomainsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstitutes::route('/'),
            'create' => Pages\CreateInstitute::route('/create'),
            'view' => Pages\ViewInstitute::route('/{record}'),
            'edit' => Pages\EditInstitute::route('/{record}/edit'),
        ];
    }
}
