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
    $message = 'Please complete your payment for next month.';
    $sent = 0;
    foreach ($students as $student) {
        if ($whatsApp->send($student->phone_number, $message)) {
            $sent++;
        }
    }
    $this->info("Month-end payment reminder sent to {$sent} students.");
})->purpose('Send month-end payment reminder to confirmed students via WhatsApp');

Schedule::command('reminders:month-end-payment')->dailyAt('09:00');
