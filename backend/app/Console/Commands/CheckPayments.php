<?php

namespace App\Console\Commands;

use App\Traits\ValidatesEmail;
use Illuminate\Console\Command;

class CheckPayments extends Command
{
    use ValidatesEmail;

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

        $unpaidParents = \App\Models\User::where('role', 'user')
            ->whereNotNull('full_name')
            ->whereNull('deactivated_at')
            ->get()
            ->filter(function ($user) use ($yearMonth) {
                return !$user->hasPaidForMonth($yearMonth);
            });

        if ($unpaidParents->isEmpty()) {
            $this->info("No unpaid parents found.");
            return;
        }

        $deactivatedList = [];
        $emailsSent = 0;

        foreach ($unpaidParents as $parent) {
            if ($day === 2 || ($day > 2 && $day < 5) || $this->option('force')) {
                // Reminder via NotificationService (WhatsApp + Email fallback)
                $notifier->notifyUser(
                    $parent, 'payment_reminder', 'tit_payment_reminder',
                    [$parent->full_name ?? $parent->name, now()->format('F Y')],
                    ['student_name' => $parent->full_name ?? $parent->name, 'month' => now()->format('F Y'), 'amount' => $parent->calculateMonthlyFee()]
                );
                $this->line("Sent reminder to: " . $parent->email);
                $emailsSent++;

                // Rate limiting: sleep 2 seconds between sends to stay within hourly limit
                if ($emailsSent % 5 === 0) {
                    sleep(2);
                }
            } 
            
            if ($day === 5 || ($day > 5 && $this->option('force'))) {
                // Deactivation Logic
                $parent->update(['deactivated_at' => now()]);
                $deactivatedList[] = ($parent->full_name ?? $parent->name) . " (" . $parent->email . ")";

                $notifier->notifyUser(
                    $parent, 'account_suspended', 'tit_account_suspended',
                    [$parent->full_name ?? $parent->name, now()->format('F Y')],
                    ['student_name' => $parent->full_name ?? $parent->name, 'month' => now()->format('F Y')]
                );
                $this->line("Deactivated and notified: " . $parent->email);
                $emailsSent++;

                // Rate limiting
                if ($emailsSent % 5 === 0) {
                    sleep(2);
                }
            }
        }

        // Notify Admin on Day 5
        if ($day === 5 && !empty($deactivatedList)) {
            $notifier->notifyAdmin(
                'admin_alert', 'titeducation',
                [],
                ['alert_title' => 'Accounts Deactivated', 'alert_message' => count($deactivatedList) . ' accounts were deactivated for non-payment.', 'alert_details' => implode("\n", $deactivatedList)]
            );
            $this->info("Admin notified about deactivations.");
        }

        $this->info("Finished payment automation tasks. ($emailsSent notifications sent)");
    }
}

