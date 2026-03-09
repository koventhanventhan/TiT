<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ZoomService
{
    protected string $baseUrl;
    protected ?string $accountId;
    protected ?string $clientId;
    protected ?string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.zoom.base_url', 'https://api.zoom.us/v2');
        $this->accountId = config('services.zoom.account_id');
        $this->clientId = config('services.zoom.client_id');
        $this->clientSecret = config('services.zoom.client_secret');
    }

    /**
     * Get Zoom OAuth Access Token using Server-to-Server OAuth.
     */
    protected function getAccessToken(): ?string
    {
        return Cache::remember('zoom_access_token', 3500, function () {
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
                'start_time' => $startTime, // ISO 8601 format
                'duration' => $durationMinutes,
                'timezone' => config('app.timezone', 'UTC'),
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
     * List all users in the Zoom account.
     */
    public function listUsers()
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/users");

        if ($response->successful()) {
            return $response->json('users');
        }

        return [];
    }
}
