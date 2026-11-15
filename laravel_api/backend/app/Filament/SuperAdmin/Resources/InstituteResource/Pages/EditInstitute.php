<?php

namespace App\Filament\SuperAdmin\Resources\InstituteResource\Pages;

use App\Filament\SuperAdmin\Resources\InstituteResource;
use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Services\ActivityLogService;
use Closure;
use DomainException;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInstitute extends EditRecord
{
    protected static string $resource = InstituteResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Institute $record */
        $record = $this->getRecord();

        $recaptcha = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $record->id)
            ->where('provider', InstituteIntegration::PROVIDER_RECAPTCHA)
            ->first();
        if ($recaptcha) {
            $data['recaptcha_site_key'] = $recaptcha->settings['site_key'] ?? '';
            $data['has_recaptcha_secret'] = !empty($recaptcha->credentials['secret_key']);
        }

        $google = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $record->id)
            ->where('provider', InstituteIntegration::PROVIDER_GOOGLE)
            ->first();
        if ($google) {
            $data['google_client_id'] = $google->settings['client_id'] ?? '';
            $data['has_google_secret'] = !empty($google->credentials['client_secret']);
        }

        $domain = $record->primaryDomainHost();
        $data['google_redirect_uri'] = 'https://' . $domain . '/api/auth/google/callback';

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Institute $record */
        $record = $this->getRecord();
        $rawState = $this->form->getRawState();

        // 1. reCAPTCHA
        $siteKey = trim((string) ($rawState['recaptcha_site_key'] ?? ''));
        $secretKey = trim((string) ($rawState['recaptcha_secret_key'] ?? ''));

        if ($siteKey !== '' || $secretKey !== '') {
            $row = InstituteIntegration::withoutGlobalScopes()->firstOrNew([
                'institute_id' => $record->id,
                'provider' => InstituteIntegration::PROVIDER_RECAPTCHA,
            ]);

            $existingCreds = is_array($row->credentials) ? $row->credentials : [];
            $existingSettings = is_array($row->settings) ? $row->settings : [];

            $finalSecret = $secretKey !== '' ? $secretKey : ($existingCreds['secret_key'] ?? '');
            $row->credentials = array_filter(['secret_key' => $finalSecret]);
            $row->settings = array_merge($existingSettings, [
                'site_key' => $siteKey,
                'score_threshold' => (float) ($existingSettings['score_threshold'] ?? 0.5),
            ]);
            $row->status = ($finalSecret !== '' && $siteKey !== '')
                ? InstituteIntegration::STATUS_CONFIGURED
                : InstituteIntegration::STATUS_UNCONFIGURED;
            $row->save();

            ActivityLogService::log(
                'platform_integration_updated',
                "Updated platform-managed reCAPTCHA for institute #{$record->id}",
                ['provider' => 'recaptcha', 'site_key' => $siteKey],
                $record->id
            );
        }

        // 2. Google OAuth
        $clientId = trim((string) ($rawState['google_client_id'] ?? ''));
        $clientSecret = trim((string) ($rawState['google_client_secret'] ?? ''));

        if ($clientId !== '' || $clientSecret !== '') {
            $row = InstituteIntegration::withoutGlobalScopes()->firstOrNew([
                'institute_id' => $record->id,
                'provider' => InstituteIntegration::PROVIDER_GOOGLE,
            ]);

            $existingCreds = is_array($row->credentials) ? $row->credentials : [];
            $existingSettings = is_array($row->settings) ? $row->settings : [];

            $finalSecret = $clientSecret !== '' ? $clientSecret : ($existingCreds['client_secret'] ?? '');
            $domain = $record->primaryDomainHost();
            $row->credentials = array_filter(['client_secret' => $finalSecret]);
            $row->settings = array_merge($existingSettings, [
                'client_id' => $clientId,
                'redirect_uri' => 'https://' . $domain . '/api/auth/google/callback',
            ]);
            $row->status = ($finalSecret !== '' && $clientId !== '')
                ? InstituteIntegration::STATUS_CONFIGURED
                : InstituteIntegration::STATUS_UNCONFIGURED;
            $row->save();

            ActivityLogService::log(
                'platform_integration_updated',
                "Updated platform-managed Google OAuth for institute #{$record->id}",
                ['provider' => 'google', 'client_id' => $clientId],
                $record->id
            );
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Setup checklist'),

            Actions\Action::make('suspend')
                ->label(fn (): string => (!$this->getRecord()->canBeDeletedSafely() && (int) $this->getRecord()->id !== 1) ? 'Suspend instead' : 'Suspend')
                ->icon('heroicon-o-no-symbol')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(fn (): string => "Suspend {$this->getRecord()->name}?")
                ->modalDescription(fn (): string => "Suspending {$this->getRecord()->name} blocks sign-ins for all its users until reactivated.")
                ->hidden(fn (): bool => (int) $this->getRecord()->id === 1 || $this->getRecord()->status !== 'active')
                ->action(function (): void {
                    /** @var Institute $record */
                    $record = $this->getRecord();
                    try {
                        $record->update(['status' => 'suspended']);
                        $this->refreshFormData(['status']);

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

            Actions\Action::make('delete_institute')
                ->label('Delete')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->hidden(fn (): bool => !$this->getRecord()->canBeDeletedSafely())
                ->requiresConfirmation()
                ->modalHeading(fn (): string => "Delete {$this->getRecord()->name}?")
                ->modalDescription(fn (): string => "Type \"{$this->getRecord()->name}\" below to confirm deletion.")
                ->form(fn (): array => [
                    TextInput::make('confirm_name')
                        ->label("Type \"{$this->getRecord()->name}\" to confirm")
                        ->required()
                        ->rule(function (): Closure {
                            $expected = $this->getRecord()->name;

                            return static function (string $attribute, mixed $value, Closure $fail) use ($expected): void {
                                if (trim((string) $value) !== $expected) {
                                    $fail("Please type \"{$expected}\" exactly to confirm deletion.");
                                }
                            };
                        }),
                ])
                ->action(function () {
                    /** @var Institute $record */
                    $record = $this->getRecord();
                    try {
                        $name = $record->name;
                        $record->delete();

                        Notification::make()
                            ->title("{$name} deleted")
                            ->success()
                            ->send();

                        return redirect()->to(InstituteResource::getUrl('index'));
                    } catch (DomainException $e) {
                        Notification::make()
                            ->title('Cannot delete institute')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
