<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\User;
use App\Services\WhatsAppService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reminders:month-end-payment', function () {
    $now = now();
    if ($now->day !== $now->copy()->endOfMonth()->day) {
        $this->info('Skipped: not the last day of month.');
        return;
    }
    $whatsApp = app(WhatsAppService::class);
    $students = User::where('role', 'user')
        ->whereNull('deactivated_at')
        ->whereNotNull('admin_confirmed_at')
        ->whereNotNull('phone_number')
        ->get();
    $sent = 0;
    foreach ($students as $student) {
        if ($whatsApp->sendTemplate(
            $student->phone_number,
            'tit_payment_reminder',
            'en',
            [$student->full_name ?? $student->name, now()->addMonth()->format('F Y')]
        )) {
            $sent++;
        }
    }
    $this->info("Month-end payment reminder sent to {$sent} students.");
})->purpose('Send month-end payment reminder to confirmed students via WhatsApp');

// Runs a scheduled task; if it throws or exits non-zero, the admin gets an email alert.
$guard = function (string $label, callable $task) {
    return function () use ($label, $task) {
        try {
            $result = $task();
            if (is_int($result) && $result !== 0) {
                throw new \RuntimeException("Command exited with code {$result}");
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Scheduled job [{$label}] failed: " . $e->getMessage());
            \App\Services\EventNotifier::jobFailed($label, $e);
        }
    };
};

Schedule::call($guard('app:check-payments', fn() => Artisan::call('app:check-payments')))->dailyAt('09:00');
Schedule::call($guard('zoom:sync-timetable', fn() => Artisan::call('zoom:sync-timetable')))->dailyAt('00:00');
Schedule::call($guard('zoom:send-reminders', fn() => Artisan::call('zoom:send-reminders')))->everyMinute();
Schedule::call($guard('zoom:fetch-recordings', fn() => Artisan::call('zoom:fetch-recordings')))->everyFifteenMinutes();

Schedule::call(function () {
    \App\Models\ZoomSchedule::where('scheduled_at', '<', now()->subDays(10))->delete();
})->dailyAt('01:00');

Schedule::call($guard('promotions:auto-run', fn() => Artisan::call('promotions:auto-run')))->dailyAt('01:30');

// Clean up zoom recordings older than 1 week from the database
Schedule::call(function () {
    \App\Models\LearningMaterial::where('type', 'recording')
        ->whereNotNull('zoom_schedule_id') // Only auto-fetched recordings
        ->where('created_at', '<', now()->subDays(7))
        ->delete();
})->dailyAt('02:00');

// ── Email alerts ──
// Absent alerts + low attendance warnings (after classes finish)
Schedule::call($guard('alerts:absent', fn() => Artisan::call('alerts:absent')))->everyFifteenMinutes()->when(fn () => filter_var(config('services.absent_alerts_enabled'), FILTER_VALIDATE_BOOLEAN));
// Birthday wishes in the morning
Schedule::call($guard('alerts:birthdays', fn() => Artisan::call('alerts:birthdays')))->dailyAt('07:00');
// Pending approval digest/reminders
Schedule::call($guard('alerts:pending-approvals', fn() => Artisan::call('alerts:pending-approvals')))->dailyAt('10:00');
// Daily summary for the admin
Schedule::call($guard('alerts:daily-summary', fn() => Artisan::call('alerts:daily-summary')))->dailyAt('20:00');


