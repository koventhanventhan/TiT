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

        if (collect($profileSettings)->get('login_alerts') === 'on') {
            // In a real application, you might send an email or a push notification.
            // For now, we'll log it and we could also create a database notification.
            
            Log::info("Login alert for user: {$user->email} at " . now());
            
            // If there's a notification system, we can use it:
            // $user->notify(new \App\Notifications\LoginAlertNotification());
        }
    }
}
