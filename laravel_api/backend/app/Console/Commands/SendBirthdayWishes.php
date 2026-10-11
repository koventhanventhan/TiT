<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\EventNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendBirthdayWishes extends Command
{
    protected $signature = 'alerts:birthdays';

    protected $description = 'Email birthday wishes to active students whose birthday is today';

    public function handle()
    {
        $today = now();

        $students = User::where('role', 'user')
            ->whereNull('deactivated_at')
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->get();

        $sent = 0;
        foreach ($students as $student) {
            if (!Cache::add("birthday:{$student->id}:{$today->year}", 1, now()->addDays(2))) {
                continue;
            }
            if (EventNotifier::birthday($student)) {
                $sent++;
            }
        }

        $this->info("Birthday wishes sent: {$sent}");
    }
}
