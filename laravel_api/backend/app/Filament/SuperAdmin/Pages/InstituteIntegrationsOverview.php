<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Support\Integrations;
use Filament\Pages\Page;

class InstituteIntegrationsOverview extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-key';
    protected static string | \UnitEnum | null $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = 'Institute Integrations';
    protected static ?string $title = 'Institute Integrations Overview';
    protected static ?int $navigationSort = 101;

    protected string $view = 'filament.super-admin.pages.institute-integrations-overview';

    public ?string $pendingClearKey = null;
    public ?string $feedbackMessage = null;

    public function requestClear(int $instituteId, string $provider): void
    {
        if (!self::canAccess()) {
            abort(403);
        }

        $this->pendingClearKey = "{$instituteId}:{$provider}";
    }

    public function cancelClear(): void
    {
        $this->pendingClearKey = null;
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()?->role === 'super_admin';
    }

    /**
     * Build a strictly read-only, secret-free matrix of all institutes and their 3 provider statuses.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getInstitutesMatrixProperty(): array
    {
        $institutes = Institute::withoutGlobalScopes()
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($institutes as $institute) {
            $summary = Integrations::for((int) $institute->id)->statusSummary();
            $providers = [];

            foreach (InstituteIntegration::PROVIDERS as $provider) {
                $item = $summary[$provider] ?? [
                    'status' => InstituteIntegration::STATUS_UNCONFIGURED,
                    'source' => 'none',
                    'last_verified_at' => null,
                    'last_error' => null,
                ];

                $displayStatus = ($item['source'] ?? 'none') === 'env'
                    ? 'using platform fallback'
                    : (string) ($item['status'] ?? 'unconfigured');

                $providers[$provider] = [
                    'status' => $displayStatus,
                    'source' => $item['source'] ?? 'none',
                    'has_db_credentials' => ($item['source'] ?? 'none') === 'db',
                    'last_verified_at' => $item['last_verified_at'] ?? null,
                    'last_error' => $item['last_error'] ?? null,
                ];
            }

            $rows[] = [
                'id' => (int) $institute->id,
                'name' => (string) $institute->name,
                'slug' => (string) $institute->slug,
                'primary_domain' => $institute->primaryDomainHost(),
                'providers' => $providers,
            ];
        }

        return $rows;
    }

    public function clearProviderCredentials(int $instituteId, string $provider): void
    {
        if (!self::canAccess()) {
            abort(403);
        }

        if (!in_array($provider, InstituteIntegration::PROVIDERS, true)) {
            return;
        }

        $integration = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where('provider', $provider)
            ->first();

        if ($integration && $integration->status !== InstituteIntegration::STATUS_UNCONFIGURED) {
            $integration->clearCredentials();
            $this->pendingClearKey = null;
            $this->feedbackMessage = ucfirst($provider) . " credentials cleared for institute #{$instituteId}.";
        }
    }
}
