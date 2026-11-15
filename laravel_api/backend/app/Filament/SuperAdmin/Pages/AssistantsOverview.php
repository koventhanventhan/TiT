<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\SuperAdmin\Resources\InstituteResource;
use App\Models\Institute;
use App\Models\StudentAiQuery;
use App\Services\ActivityLogService;
use App\Services\PublicAssistantStatusService;
use App\Services\PublicKnowledgeService;
use App\Support\Integrations;
use Filament\Pages\Page;

class AssistantsOverview extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cpu-chip';
    protected static string | \UnitEnum | null $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = 'AI Assistants Overview';
    protected static ?string $title = 'AI Assistants Overview';
    protected static ?int $navigationSort = 102;

    protected string $view = 'filament.super-admin.pages.assistants-overview';

    public ?int $pendingTurnOffInstituteId = null;
    public ?string $feedbackMessage = null;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()?->role === 'super_admin';
    }

    /**
     * Request confirmation to turn off public assistant for an institute.
     */
    public function requestTurnOff(int $instituteId): void
    {
        if (!self::canAccess()) {
            abort(403);
        }

        $this->pendingTurnOffInstituteId = $instituteId;
    }

    /**
     * Cancel pending turn-off action.
     */
    public function cancelTurnOff(): void
    {
        $this->pendingTurnOffInstituteId = null;
    }

    /**
     * Confirm turning off public assistant for the selected institute.
     */
    public function confirmTurnOff(int $instituteId): void
    {
        if (!self::canAccess()) {
            abort(403);
        }

        $institute = Institute::withoutGlobalScopes()->findOrFail($instituteId);
        $institute->update(['public_assistant_enabled' => false]);

        ActivityLogService::log(
            'assistant_public_toggle',
            "Super-admin turned off public assistant for institute #{$instituteId} ({$institute->name})",
            ['state' => 'off', 'public_assistant_enabled' => false, 'turned_off_by' => 'super_admin'],
            $instituteId
        );

        app(PublicKnowledgeService::class)->clearCache($instituteId);

        $this->pendingTurnOffInstituteId = null;
        $this->feedbackMessage = "Public assistant successfully turned off for Institute #{$institute->id} ({$institute->name}).";
    }

    /**
     * Platform-level switches.
     *
     * @return array<string, bool>
     */
    public function getPlatformSwitchesProperty(): array
    {
        return [
            'rag_public_enabled' => filter_var(config('rag.public_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'rag_enabled' => filter_var(config('rag.enabled', false), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Matrix of all institutes with AI assistant statuses.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getInstitutesMatrixProperty(): array
    {
        $statusService = app(PublicAssistantStatusService::class);
        $institutes = Institute::withoutGlobalScopes()->orderBy('id')->get();

        $rows = [];
        foreach ($institutes as $institute) {
            $instId = (int) $institute->id;
            $integrationsSummary = Integrations::for($instId)->statusSummary();

            // Gemini status
            $geminiItem = $integrationsSummary['gemini'] ?? null;
            $geminiSource = $geminiItem['source'] ?? 'none';
            $geminiDisplay = match ($geminiSource) {
                'db' => 'db',
                'env' => 'env',
                default => 'missing',
            };

            // reCAPTCHA status
            $recaptchaItem = $integrationsSummary['recaptcha'] ?? null;
            $recaptchaSource = $recaptchaItem['source'] ?? 'none';
            $recaptchaDisplay = match ($recaptchaSource) {
                'db' => 'db',
                'env' => 'env',
                default => 'missing',
            };

            // Plan allows AI
            $planAllowsAi = $institute->hasFeature('ai_assistant');

            // Contact details check
            $missingContacts = $statusService->getMissingContactFields($instId);
            $contactsPresent = empty($missingContacts);

            // Last 7 days query count
            $queriesCount = StudentAiQuery::withoutGlobalScopes()
                ->where('institute_id', $instId)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            $openUrl = InstituteResource::getUrl('view', ['record' => $instId]);

            $rows[] = [
                'id' => $instId,
                'name' => (string) $institute->name,
                'slug' => (string) $institute->slug,
                'primary_domain' => $institute->primaryDomainHost(),
                'gemini' => $geminiDisplay,
                'recaptcha' => $recaptchaDisplay,
                'plan_allows_ai' => $planAllowsAi,
                'contacts_present' => $contactsPresent,
                'missing_contacts' => $missingContacts,
                'public_assistant_enabled' => (bool) $institute->public_assistant_enabled,
                'queries_count' => $queriesCount,
                'open_url' => $openUrl,
            ];
        }

        return $rows;
    }
}
