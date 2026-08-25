<?php

namespace App\Services;

use App\Models\ZoomAccount;
use App\Models\ZoomSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ZoomService
{
    protected string $baseUrl;
    protected ?string $accountId;
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?ZoomAccount $activeAccount = null;

    public function __construct()
    {
        $this->baseUrl = config('services.zoom.base_url', 'https://api.zoom.us/v2');
        // Initial defaults from config
        $this->accountId = config('services.zoom.account_id');
        $this->clientId = config('services.zoom.client_id');
        $this->clientSecret = config('services.zoom.client_secret');
    }

    /**
     * Set the specific Zoom account to use for the next API calls.
     */
    public function useAccount(ZoomAccount $account): self
    {
        $this->activeAccount = $account;
        $this->accountId = $account->account_id;
        $this->clientId = $account->client_id;
        $this->clientSecret = $account->client_secret;
        return $this;
    }

    /**
     * List all available Zoom host emails from our registered accounts.
     */
    public function listUsers(): array
    {
        return ZoomAccount::where('is_active', true)
            ->get(['email'])
            ->map(function ($account) {
                return [
                    'email' => $account->email,
                    'display_name' => $account->email,
                ];
            })
            ->toArray();
    }

    /**
     * Find an available Zoom account for a given timeslot.
     */
    public function getAvailableAccount($startTime, int $durationMinutes = 60): ?ZoomAccount
    {
        $start = Carbon::parse($startTime);
        $end = (clone $start)->addMinutes($durationMinutes);

        // Get all active accounts
        $accounts = ZoomAccount::where('is_active', true)->get();

        foreach ($accounts as $account) {
            // Check for overlapping schedules for this specific account
            $overlap = ZoomSchedule::where('zoom_account_id', $account->id)
                ->where(function ($query) use ($start, $end) {
                    $query->where(function ($q) use ($start, $end) {
                        $q->where('scheduled_at', '>=', $start)
                          ->where('scheduled_at', '<', $end);
                    })->orWhere(function ($q) use ($start, $end) {
                        $q->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration MINUTE) > ?', [$start->toDateTimeString()])
                          ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration MINUTE) <= ?', [$end->toDateTimeString()]);
                    })->orWhere(function ($q) use ($start, $end) {
                        $q->where('scheduled_at', '<=', $start)
                          ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration MINUTE) >= ?', [$end->toDateTimeString()]);
                    });
                })->exists();

            if (!$overlap) {
                return $account;
            }
        }

        return null; // All accounts are busy
    }

    /**
     * Get Zoom OAuth Access Token using Server-to-Server OAuth.
     */
    protected function getAccessToken(): ?string
    {
        $cacheKey = $this->activeAccount 
            ? 'zoom_access_token_' . $this->activeAccount->id 
            : 'zoom_access_token_default';

        return Cache::remember($cacheKey, 3500, function () {
            if (!$this->accountId || !$this->clientId || !$this->clientSecret) {
                Log::error('Zoom API credentials missing.');
                return null;
            }

            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->timeout(30)
                ->asForm()
                ->post("https://zoom.us/oauth/token?account_id={$this->accountId}", [
                    'grant_type' => 'account_credentials',
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('Zoom OAuth Failed', [
                'account_email' => $this->activeAccount?->email ?? 'default',
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        });
    }

    /**
     * Create a Zoom Meeting.
     */
    public function createMeeting(string $title, string $startTime, int $durationMinutes = 60, string $userId = 'me')
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        $response = Http::withToken($token)
            ->timeout(30)
            ->post("{$this->baseUrl}/users/{$userId}/meetings", [
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
                ],
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Zoom Meeting Creation Failed', [
            'account' => $this->activeAccount?->email ?? 'default',
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return null;
    }

    /**
     * Update an existing Zoom Meeting.
     */
    public function updateMeeting(string $meetingId, array $data)
    {
        $token = $this->getAccessToken();
        if (!$token) return false;

        $response = Http::withToken($token)
            ->patch("{$this->baseUrl}/meetings/{$meetingId}", $data);

        if ($response->successful()) {
            return true;
        }

        Log::error('Zoom Meeting Update Failed', [
            'meeting_id' => $meetingId,
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return false;
    }

    /**
     * Delete a Zoom Meeting.
     */
    public function deleteMeeting(string $meetingId)
    {
        $token = $this->getAccessToken();
        if (!$token) return false;

        $response = Http::withToken($token)
            ->delete("{$this->baseUrl}/meetings/{$meetingId}");

        if ($response->successful()) {
            return true;
        }

        Log::error('Zoom Meeting Deletion Failed', [
            'meeting_id' => $meetingId,
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return false;
    }

    /**
     * Get recordings for a specific Zoom Meeting.
     */
    public function getMeetingRecordings(string $meetingId)
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/meetings/{$meetingId}/recordings");

        if ($response->successful()) {
            return $response->json();
        }

        if ($response->status() !== 404) {
            Log::error('Zoom Meeting Recordings Fetch Failed', [
                'meeting_id' => $meetingId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        }

        return null;
    }
}
