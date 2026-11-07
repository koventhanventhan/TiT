<?php

namespace App\Filament\SuperAdmin\Resources\InstituteResource\Pages;

use App\Filament\SuperAdmin\Pages\InstituteIntegrationsOverview;
use App\Filament\SuperAdmin\Resources\InstituteResource;
use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\DomainChecker;
use App\Services\InstituteProvisioner;
use Closure;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

class ViewInstitute extends ViewRecord
{
    protected static string $resource = InstituteResource::class;

    #[Url(as: 'ready')]
    public string $readyToken = '';

    public bool $justProvisioned = false;

    public ?string $oneTimeAdminEmail = null;

    public ?string $oneTimeAdminPassword = null;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.super-admin.resources.institute-resource.pages.view-institute'),
                $this->getRelationManagersContentComponent(),
            ]);
    }

    public static function issueReadyToken(int $instituteId, ?string $adminEmail, ?string $plainPassword): string
    {
        $token = Str::random(40);
        $payload = Crypt::encryptString(json_encode([
            'institute_id' => $instituteId,
            'admin_email' => $adminEmail,
            'plain_password' => $plainPassword,
        ], JSON_THROW_ON_ERROR));

        Cache::put("institute_ready_once_{$token}", $payload, now()->addMinutes(2));

        return $token;
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $readyToken = trim($this->readyToken !== '' ? $this->readyToken : (string) request()->query('ready', ''));
        $this->readyToken = '';
        if ($readyToken !== '') {
            $encrypted = Cache::pull("institute_ready_once_{$readyToken}");
            if (is_string($encrypted) && $encrypted !== '') {
                try {
                    $decoded = json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);
                    if ((int) ($decoded['institute_id'] ?? 0) === (int) $this->getRecord()->getKey()) {
                        $this->justProvisioned = true;
                        $this->oneTimeAdminEmail = $decoded['admin_email'] ?? null;
                        $this->oneTimeAdminPassword = $decoded['plain_password'] ?? null;
                    }
                } catch (\Throwable) {
                    // Corrupted or expired one-time payload; fail closed silently.
                }
            }
        }
    }

    public function getTitle(): string | Htmlable
    {
        if ($this->justProvisioned) {
            return 'Institute ready — ' . $this->getRecord()->name;
        }

        return $this->getRecord()->name . ' — Setup checklist';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit institute'),
        ];
    }

    public function addDomainAction(): Actions\Action
    {
        return Actions\Action::make('addDomain')
            ->label('Add domain')
            ->modalHeading('Add website address')
            ->form([
                TextInput::make('domain')
                    ->label('Website address')
                    ->placeholder('academy.lk')
                    ->required()
                    ->maxLength(253)
                    ->rule(static function (): Closure {
                        return static function (string $attribute, mixed $value, Closure $fail): void {
                            $err = app(DomainChecker::class)->validate((string) $value);
                            if ($err !== null) {
                                $fail($err);
                            }
                        };
                    }),

                Toggle::make('is_primary')
                    ->label('Make primary website address')
                    ->default(true),

                View::make('filament.super-admin.partials.cpanel-domain-instructions')
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                /** @var Institute $institute */
                $institute = $this->getRecord();
                $domain = app(DomainChecker::class)->normalize((string) ($data['domain'] ?? ''));

                DB::transaction(static function () use ($institute, $domain, $data): void {
                    $hasDomains = $institute->domains()->exists();
                    $makePrimary = !$hasDomains || !empty($data['is_primary']);

                    if ($makePrimary) {
                        $institute->domains()->where('is_primary', true)->update(['is_primary' => false]);
                    }

                    InstituteDomain::create([
                        'institute_id' => $institute->id,
                        'domain' => $domain,
                        'is_primary' => $makePrimary,
                        'verified_at' => null,
                        'verification_token' => bin2hex(random_bytes(16)),
                    ]);

                    Cache::forget('tenant_domain:' . $domain);
                });

                $this->record = $institute->fresh(['domains', 'plan']);

                Notification::make()
                    ->title('Website address added')
                    ->body("{$domain} has been connected to {$institute->name}.")
                    ->success()
                    ->send();
            });
    }

    public function checkDnsAction(): Actions\Action
    {
        return Actions\Action::make('checkDns')
            ->label('Check DNS now')
            ->action(function (): void {
                /** @var Institute $institute */
                $institute = $this->getRecord();
                $domainRecord = $institute->domains()->where('is_primary', true)->first()
                    ?? $institute->domains()->first();

                if (!$domainRecord) {
                    Notification::make()
                        ->title('No website address yet')
                        ->body('Add a website address first before checking DNS.')
                        ->warning()
                        ->send();

                    return;
                }

                $result = app(DomainChecker::class)->checkDns($domainRecord->domain, $domainRecord);
                $this->record = $institute->fresh(['domains', 'plan']);

                $notif = Notification::make()
                    ->title($result['badge'])
                    ->body($result['message']);

                match ($result['status']) {
                    DomainChecker::STATUS_POINTS_HERE => $notif->success(),
                    DomainChecker::STATUS_NOT_FOUND => $notif->danger(),
                    default => $notif->warning(),
                };

                $notif->send();
            });
    }

    public function createAdminAction(): Actions\Action
    {
        return Actions\Action::make('createAdmin')
            ->label('Create admin')
            ->modalHeading('Create first administrator')
            ->form([
                TextInput::make('admin_name')
                    ->label('Full name')
                    ->required()
                    ->maxLength(120),

                TextInput::make('admin_email')
                    ->label('E-mail address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->rule(static function (): Closure {
                        return static function (string $attribute, mixed $value, Closure $fail): void {
                            if (InstituteProvisioner::emailExists((string) $value)) {
                                $fail(InstituteProvisioner::DUPLICATE_EMAIL_MESSAGE);
                            }
                        };
                    }),

                TextInput::make('admin_phone')
                    ->label('Phone number')
                    ->tel()
                    ->required()
                    ->maxLength(20),
            ])
            ->action(function (array $data): void {
                /** @var Institute $institute */
                $institute = $this->getRecord();
                $plainPassword = InstituteProvisioner::generateStrongPassword(16);
                $email = strtolower(trim((string) $data['admin_email']));
                $name = trim((string) $data['admin_name']);

                $admin = User::withoutGlobalScopes()->create([
                    'name' => $name,
                    'full_name' => $name,
                    'email' => $email,
                    'phone_number' => trim((string) ($data['admin_phone'] ?? '')),
                    'password' => Hash::make($plainPassword),
                    'role' => 'admin',
                    'institute_id' => $institute->id,
                ]);

                ActivityLogService::log(
                    action: 'institute_admin_created',
                    description: "Created administrator for {$institute->name}",
                    metadata: [
                        'institute_id' => $institute->id,
                        'admin_id' => $admin->id,
                    ],
                    instituteId: $institute->id,
                );

                $this->oneTimeAdminEmail = $admin->email;
                $this->oneTimeAdminPassword = $plainPassword;
                $this->record = $institute->fresh(['domains', 'plan']);

                Notification::make()
                    ->title('Administrator created')
                    ->body('Save the one-time password shown on screen now — it will not be shown again.')
                    ->success()
                    ->send();
            });
    }

    public function resetAdminPasswordAction(): Actions\Action
    {
        return Actions\Action::make('resetAdminPassword')
            ->label('Reset admin password')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Reset administrator password?')
            ->modalDescription('A new strong password will be generated and shown once on this screen. The previous password will stop working immediately.')
            ->action(function (): void {
                /** @var Institute $institute */
                $institute = $this->getRecord();
                $admin = User::withoutGlobalScopes()
                    ->where('institute_id', $institute->id)
                    ->where('role', 'admin')
                    ->orderBy('id')
                    ->first();

                if (!$admin) {
                    Notification::make()
                        ->title('No administrator found')
                        ->body('Create an administrator account first.')
                        ->danger()
                        ->send();

                    return;
                }

                $plainPassword = InstituteProvisioner::generateStrongPassword(16);

                User::withoutEvents(static function () use ($admin, $plainPassword): void {
                    $admin->forceFill([
                        'password' => Hash::make($plainPassword),
                    ])->save();
                });

                ActivityLogService::log(
                    action: 'institute_admin_password_reset',
                    description: "Reset administrator password for {$institute->name}",
                    metadata: [
                        'institute_id' => $institute->id,
                        'admin_id' => $admin->id,
                    ],
                    instituteId: $institute->id,
                );

                $this->oneTimeAdminEmail = $admin->email;
                $this->oneTimeAdminPassword = $plainPassword;

                Notification::make()
                    ->title('Password reset')
                    ->body('Copy the new one-time password shown on screen before leaving.')
                    ->success()
                    ->send();
            });
    }

    public function getIntegrationsOverviewUrlProperty(): string
    {
        return InstituteIntegrationsOverview::getUrl();
    }

    public function getAdminLoginUrlProperty(): string
    {
        /** @var Institute $institute */
        $institute = $this->getRecord();

        return rtrim($institute->primaryUrl(), '/') . '/admin/login';
    }
}
