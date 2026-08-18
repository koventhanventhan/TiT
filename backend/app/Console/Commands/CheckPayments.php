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
    public function handle(\App\Services\NotificationService $notifier)
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
            if ($day === 2 || ($day > 2 && $day < 5) || $this->option('force')) {
                // Reminder via NotificationService (WhatsApp + Email fallback)
                $notifier->notifyUser(
                    $student, 'payment_reminder', 'tit_payment_reminder',
                    [$student->full_name ?? $student->name, now()->format('F Y')],
                    ['student_name' => $student->full_name ?? $student->name, 'month' => now()->format('F Y'), 'amount' => $student->calculateMonthlyFee()]
                );
                $this->line("Sent reminder to: " . $student->email);
            } 
            
            if ($day === 5 || ($day > 5 && $this->option('force'))) {
                // Deactivation Logic
                $student->update(['deactivated_at' => now()]);
                $deactivatedList[] = ($student->full_name ?? $student->name) . " (" . $student->email . ")";

                $notifier->notifyUser(
                    $student, 'account_suspended', 'tit_account_suspended',
                    [$student->full_name ?? $student->name, now()->format('F Y')],
                    ['student_name' => $student->full_name ?? $student->name, 'month' => now()->format('F Y')]
                );
                $this->line("Deactivated and notified: " . $student->email);
            }
        }

        // Notify Admin on Day 5
        if ($day === 5 && !empty($deactivatedList)) {
            $notifier->notifyAdmin(
                'admin_alert', 'titeducation',
                [],
                ['alert_title' => 'Students Deactivated', 'alert_message' => count($deactivatedList) . ' students were deactivated for non-payment.', 'alert_details' => implode("\n", $deactivatedList)]
            );
            $this->info("Admin notified about deactivations.");
        }

        $this->info("Finished payment automation tasks.");
    }
}
