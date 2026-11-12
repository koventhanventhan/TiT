<?php

namespace App\Filament\SuperAdmin\Resources\InstituteResource\Pages;

use App\Filament\SuperAdmin\Resources\InstituteResource;
use App\Models\SubscriptionPlan;
use App\Services\DomainChecker;
use App\Services\InstituteProvisioner;
use App\Services\ProvisionInstituteData;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateInstitute extends CreateRecord
{
    use HasWizard;

    protected static string $resource = InstituteResource::class;

    protected static bool $canCreateAnother = false;

    protected static ?string $title = 'Add new institute';

    /**
     * Latest manual DNS check result for Step 2 ("Check DNS now").
     *
     * @var array<string, mixed>|null
     */
    public ?array $dnsCheckResult = null;

    public string $expiryPresetMode = '30d';

    public bool $indexPrefixManuallyEdited = false;

    protected ?string $readyTokenForRedirect = null;

    /**
     * @return array<Step>
     */
    public function getSteps(): array
    {
        return [
            // Step 1: Institute
            Step::make('Institute')
                ->description('Name, logo & public contact')
                ->schema([
                    TextInput::make('name')
                        ->label('Institute name')
                        ->placeholder('Northern Science Academy')
                        ->required()
                        ->minLength(2)
                        ->maxLength(80)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, ?string $old, Get $get, Set $set): void {
                            $trimmed = trim((string) $state);
                            $currentBrand = trim((string) $get('short_brand_name'));
                            if ($currentBrand === '' || $currentBrand === trim((string) $old)) {
                                $set('short_brand_name', $trimmed);
                            }

                            if (!$this->indexPrefixManuallyEdited && $trimmed !== '') {
                                $set('index_prefix', InstituteProvisioner::deriveIndexPrefix($trimmed));
                            }
                        }),

                    TextInput::make('short_brand_name')
                        ->label('Short brand name')
                        ->placeholder('Defaults to the institute name')
                        ->helperText('Shown in navigation headers, receipts, and outgoing e-mail subjects.')
                        ->maxLength(80)
                        ->live(onBlur: true),

                    FileUpload::make('logo_path')
                        ->label('Institute logo (optional)')
                        ->helperText('PNG, JPG, or WebP image up to 2 MB.')
                        ->image()
                        ->maxSize(2048)
                        ->disk('public')
                        ->directory('institute-logos'),

                    TextInput::make('contact_email')
                        ->label('Public contact e-mail (optional)')
                        ->placeholder('office@academy.lk')
                        ->helperText('Shown on the institute public website footer and contact details.')
                        ->email()
                        ->maxLength(255),

                    TextInput::make('contact_phone')
                        ->label('Public contact phone (optional)')
                        ->placeholder('077 123 4567')
                        ->helperText('Shown on the institute public website if provided.')
                        ->tel()
                        ->maxLength(30),
                ]),

            // Step 2: Website address
            Step::make('Website address')
                ->description('Domain & DNS instructions')
                ->schema([
                    Checkbox::make('defer_domain')
                        ->label("I'll add it later")
                        ->helperText('You can still sign in locally or add the website address later from the setup checklist.')
                        ->default(false)
                        ->live()
                        ->afterStateUpdated(function (bool $state): void {
                            if ($state) {
                                $this->dnsCheckResult = null;
                                $this->resetValidation('data.domain');
                            }
                        }),

                    TextInput::make('domain')
                        ->label('Website address')
                        ->placeholder('academy.lk')
                        ->helperText('Enter only the domain name (for example, academy.lk). Scheme and www are stripped automatically.')
                        ->maxLength(253)
                        ->live(onBlur: true)
                        ->disabled(fn (Get $get): bool => (bool) $get('defer_domain'))
                        ->required(fn (Get $get): bool => !(bool) $get('defer_domain'))
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $this->dnsCheckResult = null;
                            if ($state !== null && trim($state) !== '') {
                                $set('domain', app(DomainChecker::class)->normalize($state));
                            }
                        })
                        ->rule(fn (Get $get): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            if ((bool) $get('defer_domain')) {
                                return;
                            }

                            $trimmed = trim((string) $value);
                            if ($trimmed === '') {
                                $fail("Enter a website address or tick \"I'll add it later\".");

                                return;
                            }

                            $err = app(DomainChecker::class)->validate($trimmed);
                            if ($err !== null) {
                                $fail($err);
                            }
                        }),

                    View::make('filament.super-admin.partials.wizard-dns-check')
                        ->columnSpanFull(),

                    View::make('filament.super-admin.partials.cpanel-domain-instructions')
                        ->viewData(fn (Get $get): array => [
                            'domain' => $get('domain'),
                        ])
                        ->columnSpanFull(),
                ]),

            // Step 3: Plan
            Step::make('Plan')
                ->description('Limits & validity period')
                ->schema([
                    Select::make('subscription_plan_id')
                        ->label('Subscription plan')
                        ->options(fn (): array => SubscriptionPlan::query()->orderBy('monthly_price')->pluck('name', 'id')->all())
                        ->default(fn (): ?int => SubscriptionPlan::query()->orderBy('monthly_price')->value('id'))
                        ->required()
                        ->live(),

                    View::make('filament.super-admin.partials.wizard-plan-summary')
                        ->columnSpanFull(),

                    DateTimePicker::make('expires_at')
                        ->label('Access valid until')
                        ->helperText('Defaults to a 30-day trial from today. Use the quick buttons above or choose a custom date.')
                        ->default(fn (): string => now()->addDays(30)->format('Y-m-d H:i:s'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (): void {
                            $this->expiryPresetMode = 'custom';
                        }),
                ]),

            // Step 4: First admin
            Step::make('First admin')
                ->description('Initial administrator account')
                ->schema([
                    Checkbox::make('create_admin')
                        ->label('Create the first admin now')
                        ->helperText('Recommended. A strong one-time password will be generated automatically and shown once on the confirmation screen.')
                        ->default(true)
                        ->live(),

                    TextInput::make('admin_name')
                        ->label('Full name')
                        ->placeholder('Principal or coordinator name')
                        ->maxLength(120)
                        ->visible(fn (Get $get): bool => (bool) $get('create_admin'))
                        ->required(fn (Get $get): bool => (bool) $get('create_admin')),

                    TextInput::make('admin_email')
                        ->label('E-mail address')
                        ->placeholder('admin@academy.lk')
                        ->email()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->visible(fn (Get $get): bool => (bool) $get('create_admin'))
                        ->required(fn (Get $get): bool => (bool) $get('create_admin'))
                        ->rule(fn (Get $get): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            if (!(bool) $get('create_admin')) {
                                return;
                            }

                            if (InstituteProvisioner::emailExists((string) $value)) {
                                $fail(InstituteProvisioner::DUPLICATE_EMAIL_MESSAGE);
                            }
                        }),

                    TextInput::make('admin_phone')
                        ->label('Phone number')
                        ->placeholder('077 123 4567')
                        ->tel()
                        ->maxLength(20)
                        ->visible(fn (Get $get): bool => (bool) $get('create_admin'))
                        ->required(fn (Get $get): bool => (bool) $get('create_admin')),
                ]),

            // Step 5: Look & feel
            Step::make('Look & feel')
                ->description('Theme & student ID format')
                ->schema([
                    Hidden::make('theme_preset')
                        ->default('royal')
                        ->required()
                        ->rule(Rule::in(['royal', 'ocean', 'emerald'])),

                    View::make('filament.super-admin.partials.wizard-preset-cards')
                        ->columnSpanFull(),

                    TextInput::make('index_prefix')
                        ->label('Student ID prefix')
                        ->helperText('2 to 6 letters or digits placed at the start of every student ID.')
                        ->default('INS')
                        ->required()
                        ->minLength(2)
                        ->maxLength(6)
                        ->regex('/^[A-Za-z0-9]{2,6}$/')
                        ->validationMessages([
                            'regex' => 'Use 2 to 6 letters or numbers only (no spaces or symbols).',
                        ])
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $this->indexPrefixManuallyEdited = true;
                            if ($state !== null) {
                                $set('index_prefix', strtoupper(trim($state)));
                            }
                        }),

                    Radio::make('index_format')
                        ->label('Student ID numbering style')
                        ->options([
                            'grade' => 'By grade (for example, ABC/08/001)',
                            'batch' => 'By admission year (for example, ABC/' . date('y') . '/1001)',
                        ])
                        ->default('grade')
                        ->required()
                        ->live(),

                    View::make('filament.super-admin.partials.wizard-index-preview')
                        ->columnSpanFull(),
                ]),

            // Step 6: Review & create
            Step::make('Review & create')
                ->description('Confirm & create institute')
                ->schema([
                    View::make('filament.super-admin.partials.wizard-review-summary')
                        ->columnSpanFull(),
                ]),
        ];
    }

    public function checkWizardDomainDns(): void
    {
        $rawDomain = trim((string) data_get($this->data, 'domain', ''));
        if ($rawDomain === '') {
            Notification::make()
                ->title('Enter a website address first')
                ->body('Type the website address above before running the DNS check.')
                ->warning()
                ->send();

            return;
        }

        $checker = app(DomainChecker::class);
        $validationError = $checker->validate($rawDomain);
        if ($validationError !== null) {
            $this->dnsCheckResult = null;
            Notification::make()
                ->title('Invalid website address')
                ->body($validationError)
                ->danger()
                ->send();

            return;
        }

        $result = $checker->checkDns($rawDomain);
        $this->data['domain'] = $result['domain'];
        $this->dnsCheckResult = $result;

        $notif = Notification::make()
            ->title($result['badge'])
            ->body($result['message']);

        match ($result['status']) {
            DomainChecker::STATUS_POINTS_HERE => $notif->success(),
            DomainChecker::STATUS_NOT_FOUND => $notif->danger(),
            default => $notif->warning(),
        };

        $notif->send();
    }

    public function applyExpiryPreset(string $preset): void
    {
        $this->expiryPresetMode = $preset;

        if ($preset === '30d') {
            $this->data['expires_at'] = now()->addDays(30)->format('Y-m-d H:i:s');
        } elseif ($preset === '1y') {
            $this->data['expires_at'] = now()->addYear()->format('Y-m-d H:i:s');
        }
    }

    public function selectThemePreset(string $preset): void
    {
        $normalized = strtolower(trim($preset));
        if (!in_array($normalized, ['royal', 'ocean', 'emerald'], true)) {
            return;
        }

        $this->data['theme_preset'] = $normalized;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Create institute');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Institute created';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $deferDomain = (bool) ($data['defer_domain'] ?? false);
        $domain = (!$deferDomain && !empty($data['domain'])) ? trim((string) $data['domain']) : null;

        $createAdmin = (bool) ($data['create_admin'] ?? true);
        $expiresAt = !empty($data['expires_at'])
            ? Carbon::parse((string) $data['expires_at'])
            : now()->addDays(30);

        $dto = new ProvisionInstituteData(
            name: trim((string) ($data['name'] ?? '')),
            shortBrandName: !empty($data['short_brand_name']) ? trim((string) $data['short_brand_name']) : null,
            slug: null,
            logoPath: !empty($data['logo_path']) ? (string) $data['logo_path'] : null,
            subscriptionPlanId: !empty($data['subscription_plan_id']) ? (int) $data['subscription_plan_id'] : null,
            expiresAt: $expiresAt,
            indexPrefix: !empty($data['index_prefix']) ? strtoupper(trim((string) $data['index_prefix'])) : null,
            indexFormat: in_array(($data['index_format'] ?? 'grade'), ['grade', 'batch'], true) ? (string) $data['index_format'] : 'grade',
            domain: $domain,
            createAdmin: $createAdmin,
            adminName: $createAdmin ? trim((string) ($data['admin_name'] ?? '')) : null,
            adminEmail: $createAdmin ? trim((string) ($data['admin_email'] ?? '')) : null,
            adminPhone: $createAdmin ? trim((string) ($data['admin_phone'] ?? '')) : null,
            contactEmail: !empty($data['contact_email']) ? trim((string) $data['contact_email']) : null,
            contactPhone: !empty($data['contact_phone']) ? trim((string) $data['contact_phone']) : null,
            themePreset: !empty($data['theme_preset']) ? strtolower(trim((string) $data['theme_preset'])) : 'royal',
        );

        try {
            $result = app(InstituteProvisioner::class)->provision($dto);
        } catch (ValidationException $e) {
            $prefixed = [];
            foreach ($e->errors() as $field => $messages) {
                $prefixed[str_starts_with($field, 'data.') ? $field : "data.{$field}"] = $messages;
            }

            throw ValidationException::withMessages($prefixed);
        }

        if (
            $result->domain !== null
            && is_array($this->dnsCheckResult)
            && ($this->dnsCheckResult['domain'] ?? null) === $result->domain->domain
            && !empty($this->dnsCheckResult['verified'])
        ) {
            $result->domain->forceFill(['verified_at' => now()])->save();
        }

        $this->readyTokenForRedirect = ViewInstitute::issueReadyToken(
            instituteId: $result->institute->id,
            adminEmail: $result->admin?->email,
            plainPassword: $result->plainAdminPassword,
        );

        return $result->institute;
    }

    protected function getRedirectUrl(): string
    {
        $params = ['record' => $this->getRecord()];
        if ($this->readyTokenForRedirect !== null && $this->readyTokenForRedirect !== '') {
            $params['ready'] = $this->readyTokenForRedirect;
        }

        return static::getResource()::getUrl('view', $params);
    }
}
