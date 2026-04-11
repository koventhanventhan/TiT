<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-payments {--force : Send notifications even if already sent this month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for students who have not paid for the current month and send WhatsApp alerts.';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\WhatsAppService $whatsApp)
    {
        $day = now()->day;
        $yearMonth = now()->format('Y-m');

        if ($day !== 2 && $day !== 5 && !$this->option('force')) {
            $this->info("Today is Day $day. Automated payment checks only run on Day 2 and Day 5.");
            return;
        }

        $this->info("Running payment checks for $yearMonth (Day $day)...");

        $unpaidStudents = \App\Models\User::where('role', 'user')
            ->whereNotNull('full_name')
            ->whereNull('deactivated_at')
            ->get()
            ->filter(function ($user) use ($yearMonth) {
                return !$user->hasPaidForMonth($yearMonth);
            });

        if ($unpaidStudents->isEmpty()) {
            $this->info("No unpaid students found.");
            return;
        }

        $deactivatedList = [];

        foreach ($unpaidStudents as $student) {
            $phone = $student->phone_number;
            if (!$phone) continue;

            if ($day === 2 || ($day > 2 && $day < 5) || $this->option('force')) {
                // Reminder via Template
                $whatsApp->sendTemplate(
                    $phone,
                    'tit_payment_reminder',
                    'en',
                    [$student->full_name ?? $student->name, now()->format('F Y')]
                );
                $this->line("Sent reminder to: " . $student->email);
            } 
            
            if ($day === 5 || ($day > 5 && $this->option('force'))) {
                // Deactivation Logic
                $student->update(['deactivated_at' => now()]);
                $deactivatedList[] = ($student->full_name ?? $student->name) . " (" . $student->email . ")";

                $whatsApp->sendTemplate(
                    $phone,
                    'tit_account_suspended',
                    'en',
                    [$student->full_name ?? $student->name, now()->format('F Y')]
                );
                $this->line("Deactivated and notified: " . $student->email);
            }
        }

        // Notify Admin on Day 5
        if ($day === 5 && !empty($deactivatedList)) {
            $admin = \App\Models\User::whereIn('role', ['admin', 'super_admin'])
                ->whereNotNull('phone_number')
                ->first();

            if ($admin) {
                $whatsApp->sendTemplate(
                    $admin->phone_number,
                    'titeducation',
                    'en'
                );
                $this->info("Notified admin: " . $admin->name);
            }
        }

        $this->info("Finished payment automation tasks.");
    }
}
