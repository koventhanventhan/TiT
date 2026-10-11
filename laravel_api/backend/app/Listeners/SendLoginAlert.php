<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class SendLoginAlert
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        $profileSettings = $user->profile_settings ?? [];
        $alertsEnabled = collect($profileSettings)->get('login_alerts') === 'on';

        $ip = request()->ip() ?? 'Unknown IP';
        $agent = request()->userAgent() ?? 'Unknown Device';
        $deviceKey = 'known_device:' . $user->id . ':' . md5($agent);

        $isNewDevice = !\Illuminate\Support\Facades\Cache::has($deviceKey);

        if ($isNewDevice) {
            \Illuminate\Support\Facades\Cache::put($deviceKey, 1, now()->addDays(60));
        }

        // Send alert if user enabled login alerts OR if it's genuinely a new device
        if ($alertsEnabled || $isNewDevice) {
            Log::info("Login alert: Sending new device login email for {$user->email} from {$ip}");
            try {
                \App\Services\EventNotifier::newDeviceLogin($user, $ip, $agent);
            } catch (\Throwable $e) {
                Log::error("Login alert email failed for {$user->email}: " . $e->getMessage());
            }
        }
    }
}
