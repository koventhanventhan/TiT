<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\EventNotifier;
use App\Support\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendBirthdayWishes extends Command
{
    protected $signature = 'alerts:birthdays';

    protected $description = 'Send birthday wishes to students at 07:00';

    public function handle()
    {
        $today = now();

        Tenant::each(function ($institute) use ($today) {
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

            $this->info("Institute {$institute->name}: Birthday wishes sent: {$sent}");
        });
    }
}
