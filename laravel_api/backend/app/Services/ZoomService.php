<?php

namespace App\Services;

use App\Exceptions\ZoomNotConfiguredException;
use App\Models\ZoomAccount;
use App\Models\ZoomSchedule;
use App\Support\Integrations;
use App\Support\Redact;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomService
{
    protected string $baseUrl;
    protected ?int $instituteId = null;
    protected ?ZoomAccount $activeAccount = null;

    /**
     * ZoomService constructor.
     * Note: Constructor NEVER captures credentials from global env.
     */
    public function __construct(?int $instituteId = null)
    {
        $this->baseUrl = (string) (config('services.zoom.base_url') ?: 'https://api.zoom.us/v2');
        $this->instituteId = $instituteId;
    }

    /**
     * Clone service scoped to an explicit institute.
     */
    public function forInstitute(int $instituteId): self
    {
        $clone = clone $this;
        $clone->instituteId = $instituteId;
        $clone->activeAccount = null;
        return $clone;
    }

    /**
     * Resolve the active institute ID for this call.
     */
    public function getInstituteId(): int
    {
        return $this->instituteId ?? Tenant::id() ?? 1;
    }

    /**
     * Set the specific Zoom account to use for subsequent API calls.
     */
    public function useAccount(ZoomAccount $account): self
    {
        $this->activeAccount = $account;
        return $this;
    }

    /**
     * Get all usable Zoom accounts for an institute.
     * If an institute has zero configured accounts and is in INTEGRATIONS_ENV_FALLBACK_INSTITUTES,
     * an in-memory virtual account is created using the fallback env credentials.
     *
     * @return Collection<int, ZoomAccount>
     */
    public function getAccountsForInstitute(?int $instituteId = null): Collection
    {
        $instId = $instituteId ?? $this->getInstituteId();

        $accounts = ZoomAccount::withoutGlobalScopes()
            ->where('institute_id', $instId)
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty() && Integrations::allowsEnvFallback($instId)) {
            $envAccountId = config('services.zoom.account_id');
            $envClientId = config('services.zoom.client_id');
            $envClientSecret = config('services.zoom.client_secret');

            if ($envAccountId && $envClientId && $envClientSecret) {
                $virtual = new ZoomAccount([
                    'email' => (string) config('services.zoom.host_email', 'default@tit.lk'),
                    'account_id' => (string) $envAccountId,
                    'client_id' => (string) $envClientId,
                    'client_secret' => (string) $envClientSecret,
                    'max_concurrent' => 10,
                    'is_active' => true,
                    'institute_id' => $instId,
                ]);
                $virtual->id = 0; // Virtual fallback
                $virtual->exists = false;
                return collect([$virtual]);
            }
        }

        return $accounts;
    }

    /**
     * List all available Zoom host emails from our registered accounts for the institute.
     */
    public function listUsers(?int $instituteId = null): array
    {
        return $this->getAccountsForInstitute($instituteId)
            ->map(function ($account) {
                return [
                    'email' => $account->email,
                    'display_name' => $account->email,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Find an available Zoom account for a given timeslot.
     */
    public function getAvailableAccount($startTime, int $durationMinutes = 60, ?int $instituteId = null): ?ZoomAccount
    {
        $start = Carbon::parse($startTime);
        $end = (clone $start)->addMinutes($durationMinutes);

        $accounts = $this->getAccountsForInstitute($instituteId);

        foreach ($accounts as $account) {
            // Virtual accounts (id = 0) have no database schedule overlaps tracked by account id
            if (!$account->id) {
                return $account;
            }

            // Check for overlapping schedules for this specific account
            $overlap = ZoomSchedule::withoutGlobalScopes()
                ->where('zoom_account_id', $account->id)
                ->where(function ($query) use ($start, $end) {
                    $query->where(function ($q) use ($start, $end) {
                        $q->where('scheduled_at', '>=', $start)
                          ->where('scheduled_at', '<', $end);
                    })->orWhere(function ($q) use ($start, $end) {
                        $isSqlite = \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite';
                        if ($isSqlite) {
                            $q->whereRaw("datetime(scheduled_at, '+' || coalesce(duration, 60) || ' minutes') > ?", [$start->toDateTimeString()])
                              ->whereRaw("datetime(scheduled_at, '+' || coalesce(duration, 60) || ' minutes') <= ?", [$end->toDateTimeString()]);
                        } else {
                            $q->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration MINUTE) > ?', [$start->toDateTimeString()])
                              ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration MINUTE) <= ?', [$end->toDateTimeString()]);
                        }
                    })->orWhere(function ($q) use ($start, $end) {
                        $isSqlite = \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite';
                        if ($isSqlite) {
                            $q->where('scheduled_at', '<=', $start)
                              ->whereRaw("datetime(scheduled_at, '+' || coalesce(duration, 60) || ' minutes') >= ?", [$end->toDateTimeString()]);
                        } else {
                            $q->where('scheduled_at', '<=', $start)
                              ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration MINUTE) >= ?', [$end->toDateTimeString()]);
                        }
                    });
                })->exists();

            if (!$overlap) {
                return $account;
            }
        }

        return null;
    }

    /**
     * Invalidate cached token and request a fresh access token (used during connection test).
     */
    public function getFreshAccessToken(?ZoomAccount $account = null): ?string
    {
        $targetAccount = $account ?? $this->activeAccount;
        if ($targetAccount) {
            $instId = (int) ($targetAccount->institute_id ?? $this->getInstituteId());
            $accId = $targetAccount->id ?: 'env';
            Cache::forget("zoom:token:{$instId}:{$accId}");
        }

        return $this->getAccessToken($targetAccount);
    }

    /**
     * Get Zoom OAuth Access Token using Server-to-Server OAuth.
     * Token cache key: zoom:token:{institute}:{account}
     */
    public function getAccessToken(?ZoomAccount $account = null): ?string
    {
        $targetAccount = $account ?? $this->activeAccount;

        if (!$targetAccount) {
            $targetAccount = $this->getAvailableAccount(now());
        }

        if (!$targetAccount || empty($targetAccount->account_id) || empty($targetAccount->client_id) || empty($targetAccount->client_secret)) {
            throw new ZoomNotConfiguredException('Add a Zoom account under Settings → Zoom Accounts');
        }

        $instId = (int) ($targetAccount->institute_id ?? $this->getInstituteId());
        $accId = $targetAccount->id ?: 'env';
        $cacheKey = "zoom:token:{$instId}:{$accId}";

        return Cache::remember($cacheKey, 3500, function () use ($targetAccount, $instId) {
            $response = Http::withBasicAuth((string) $targetAccount->client_id, (string) $targetAccount->client_secret)
                ->timeout(30)
                ->asForm()
                ->post("https://zoom.us/oauth/token?account_id={$targetAccount->account_id}", [
                    'grant_type' => 'account_credentials',
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('Zoom OAuth Failed', [
                'institute_id' => $instId,
                'account_email' => $targetAccount->email ?? 'default',
                'status' => $response->status(),
                'body' => Redact::clean($response->body()),
            ]);

            return null;
        });
    }

    /**
     * Create a Zoom Meeting.
     */
    public function createMeeting(string $title, string $startTime, int $durationMinutes = 60, string $userId = 'me'): ?array
    {
        $account = $this->activeAccount ?? $this->getAvailableAccount($startTime, $durationMinutes);
        if (!$account) {
            throw new ZoomNotConfiguredException('Add a Zoom account under Settings → Zoom Accounts');
        }

        $this->useAccount($account);
        $token = $this->getAccessToken($account);
        if (!$token) {
            return null;
        }

        $payload = [
            'topic' => $title,
            'type' => 2, // Scheduled meeting
            'start_time' => Carbon::parse($startTime)->toIso8601String(),
            'duration' => $durationMinutes,
            'timezone' => config('app.timezone', 'Asia/Colombo'),
            'settings' => [
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'mute_upon_entry' => true,
                'waiting_room' => true,
                'auto_recording' => 'cloud',
            ],
        ];

        $response = Http::withToken($token)
            ->timeout(30)
            ->post("{$this->baseUrl}/users/{$userId}/meetings", $payload);

        if ($response->successful()) {
            return $response->json();
        }

        // Retry once without auto_recording if cloud recording is not supported
        unset($payload['settings']['auto_recording']);
        $response = Http::withToken($token)
            ->timeout(30)
            ->post("{$this->baseUrl}/users/{$userId}/meetings", $payload);

        if ($response->successful()) {
            Log::warning('Zoom meeting created without auto cloud recording');
            return $response->json();
        }

        Log::error('Zoom Meeting Creation Failed', [
            'account' => $this->activeAccount?->email ?? 'default',
            'status' => $response->status(),
            'body' => Redact::clean($response->body()),
        ]);

        return null;
    }

    /**
     * Update an existing Zoom Meeting.
     */
    public function updateMeeting(string $meetingId, array $data): bool
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return false;
        }

        $response = Http::withToken($token)
            ->patch("{$this->baseUrl}/meetings/{$meetingId}", $data);

        if ($response->successful()) {
            return true;
        }

        Log::error('Zoom Meeting Update Failed', [
            'meeting_id' => $meetingId,
            'status' => $response->status(),
            'body' => Redact::clean($response->body()),
        ]);

        return false;
    }

    /**
     * Delete a Zoom Meeting.
     */
    public function deleteMeeting(string $meetingId): bool
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return false;
        }

        $response = Http::withToken($token)
            ->delete("{$this->baseUrl}/meetings/{$meetingId}");

        if ($response->successful()) {
            return true;
        }

        Log::error('Zoom Meeting Deletion Failed', [
            'meeting_id' => $meetingId,
            'status' => $response->status(),
            'body' => Redact::clean($response->body()),
        ]);

        return false;
    }

    /**
     * Get recordings for a specific Zoom Meeting.
     */
    public function getMeetingRecordings(string $meetingId): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/meetings/{$meetingId}/recordings");

        if ($response->successful()) {
            return $response->json();
        }

        if ($response->status() !== 404) {
            Log::error('Zoom Meeting Recordings Fetch Failed', [
                'meeting_id' => $meetingId,
                'status' => $response->status(),
                'body' => Redact::clean($response->body()),
            ]);
        }

        return null;
    }
}
