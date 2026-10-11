<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\EventNotifier;
use Illuminate\Console\Command;

class SendFestivalWishes extends Command
{
    protected $signature = 'alerts:wishes {title : e.g. "Happy Tamil New Year"} {message : Message body}';

    protected $description = 'Manually email festival/greeting wishes to all active students (e.g. php artisan alerts:wishes "Happy Deepavali" "Wishing you light and joy")';

    public function handle()
    {
        $students = User::where('role', 'user')
            ->whereNull('deactivated_at')
            ->whereNotNull('admin_confirmed_at')
            ->get();

        $sent = 0;
        foreach ($students as $student) {
            if (EventNotifier::wishes($student, $this->argument('title'), $this->argument('message'))) {
                $sent++;
                if ($sent % 5 === 0) {
                    sleep(1);
                }
            }
        }

        $this->info("Wishes sent to {$sent} of {$students->count()} students.");
    }
}
