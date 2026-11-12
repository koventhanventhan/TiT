<?php

namespace App\Filament\SuperAdmin\Resources\InstituteResource\RelationManagers;

use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\User;
use App\Services\DomainChecker;
use Closure;
use DomainException;
use Filament\Actions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    protected static ?string $title = 'Website addresses';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('domain')
                    ->label('Website address')
                    ->placeholder('academy.lk')
                    ->required()
                    ->maxLength(253)
                    ->rule(static function (): Closure {
                        return static function (string $attribute, mixed $value, Closure $fail): void {
                            $error = app(DomainChecker::class)->validate((string) $value);
                            if ($error !== null) {
                                $fail($error);
                            }
                        };
                    }),

                Toggle::make('is_primary')
                    ->label('Make primary website address')
                    ->helperText('Used as the main sign-in address for this institute.')
                    ->default(false),

                View::make('filament.super-admin.partials.cpanel-domain-instructions')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('domain')
            ->columns([
                Tables\Columns\TextColumn::make('domain')
                    ->label('Website address')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('is_primary')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(static fn (bool $state): string => $state ? 'Primary' : 'Alias')
                    ->color(static fn (bool $state): string => $state ? 'primary' : 'gray'),

                Tables\Columns\TextColumn::make('verified_at')
                    ->label('DNS status')
                    ->badge()
                    ->getStateUsing(static fn (InstituteDomain $record): string => $record->verified_at ? 'Verified' : 'Unverified')
                    ->color(static fn (string $state): string => $state === 'Verified' ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('M d, Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Actions\CreateAction::make('add_domain')
                    ->label('Add website address')
                    ->modalHeading('Add website address')
                    ->before(function (Actions\CreateAction $action) {
                        /** @var Institute $owner */
                        $owner = $this->getOwnerRecord();
                        if ($owner->domains()->exists() && !$owner->hasFeature('custom_domain')) {
                            Notification::make()
                                ->title('Feature not in plan')
                                ->body('This institute plan does not include the Custom Domain feature. Please upgrade the plan first.')
                                ->danger()
                                ->send();
                            $action->halt();
                        }
                    })
                    ->using(function (array $data): InstituteDomain {
                        /** @var Institute $owner */
                        $owner = $this->getOwnerRecord();
                        $checker = app(DomainChecker::class);
                        $domain = $checker->normalize((string) ($data['domain'] ?? ''));

                        return DB::transaction(static function () use ($owner, $domain, $data): InstituteDomain {
                            $hasExisting = $owner->domains()->exists();
                            $makePrimary = !$hasExisting || !empty($data['is_primary']);

                            if ($makePrimary) {
                                $owner->domains()->where('is_primary', true)->update(['is_primary' => false]);
                            }

                            $created = InstituteDomain::create([
                                'institute_id' => $owner->id,
                                'domain' => $domain,
                                'is_primary' => $makePrimary,
                                'verified_at' => null,
                                'verification_token' => bin2hex(random_bytes(16)),
                            ]);

                            Cache::forget('tenant_domain:' . $domain);

                            return $created;
                        });
                    }),
            ])
            ->actions([
                Actions\Action::make('verify_dns')
                    ->label('Check DNS now')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (InstituteDomain $record): void {
                        $result = app(DomainChecker::class)->checkDns($record->domain, $record);

                        $notification = Notification::make()
                            ->title($result['badge'])
                            ->body($result['message']);

                        match ($result['status']) {
                            DomainChecker::STATUS_POINTS_HERE => $notification->success(),
                            DomainChecker::STATUS_NOT_FOUND => $notification->danger(),
                            default => $notification->warning(),
                        };

                        $notification->send();
                    }),

                Actions\Action::make('make_primary')
                    ->label('Make primary')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(static fn (InstituteDomain $record): bool => !$record->is_primary)
                    ->action(function (InstituteDomain $record): void {
                        DB::transaction(static function () use ($record): void {
                            InstituteDomain::where('institute_id', $record->institute_id)
                                ->where('id', '!=', $record->id)
                                ->update(['is_primary' => false]);

                            $record->update(['is_primary' => true]);
                        });

                        Notification::make()
                            ->title('Primary address updated')
                            ->body("{$record->domain} is now the primary website address.")
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('remove_domain')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->hidden(static function (InstituteDomain $record): bool {
                        if ((int) $record->institute_id === 1) {
                            return InstituteDomain::where('institute_id', 1)->count() <= 1;
                        }

                        return false;
                    })
                    ->disabled(static function (InstituteDomain $record): bool {
                        $currentHost = app(DomainChecker::class)->normalize((string) request()->getHost());

                        return $currentHost !== '' && $currentHost === strtolower($record->domain);
                    })
                    ->tooltip(static function (InstituteDomain $record): ?string {
                        $currentHost = app(DomainChecker::class)->normalize((string) request()->getHost());
                        if ($currentHost !== '' && $currentHost === strtolower($record->domain)) {
                            return 'Cannot remove the website address currently open in your browser.';
                        }

                        return null;
                    })
                    ->requiresConfirmation()
                    ->modalHeading(static fn (InstituteDomain $record): string => "Remove {$record->domain}?")
                    ->modalDescription(function (InstituteDomain $record): string {
                        /** @var Institute $owner */
                        $owner = $this->getOwnerRecord();
                        $isLast = $owner->domains()->count() <= 1;
                        $userCount = User::withoutGlobalScopes()->where('institute_id', $owner->id)->count();

                        if ($isLast && $userCount > 0) {
                            return "Warning: {$record->domain} is the last website address for {$owner->name}, which has {$userCount} user account(s). Removing it means no one can open this institute by website address until a new one is added.";
                        }

                        return "Are you sure you want to disconnect {$record->domain} from {$owner->name}?";
                    })
                    ->form(function (): array {
                        /** @var Institute $owner */
                        $owner = $this->getOwnerRecord();
                        $isLast = $owner->domains()->count() <= 1;
                        $userCount = User::withoutGlobalScopes()->where('institute_id', $owner->id)->count();

                        if ($isLast && $userCount > 0) {
                            return [
                                Checkbox::make('confirm_last_domain_removal')
                                    ->label("I understand {$owner->name} will be unreachable until another website address is added")
                                    ->accepted()
                                    ->validationMessages([
                                        'accepted' => 'Please confirm that you understand the impact before removing the last website address.',
                                    ]),
                            ];
                        }

                        return [];
                    })
                    ->action(function (InstituteDomain $record): void {
                        try {
                            $domainName = $record->domain;
                            $record->delete();

                            Notification::make()
                                ->title('Website address removed')
                                ->body("{$domainName} has been disconnected.")
                                ->success()
                                ->send();
                        } catch (DomainException $e) {
                            Notification::make()
                                ->title('Cannot remove website address')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}

