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
                // Reminder Logic
                $message = "Dear " . ($student->full_name ?? $student->name) . ",\n\n" .
                    "This is a reminder from " . config('app.name') . ".\n" .
                    "Your payment for " . now()->format('F Y') . " is due.\n" .
                    "Please pay by the 5th to avoid automatic deactivation of your account.\n\n" .
                    "If you have already paid, please ignore this message.";
                
                $whatsApp->send($phone, $message);
                $this->line("Sent reminder to: " . $student->email);
            } 
            
            if ($day === 5 || ($day > 5 && $this->option('force'))) {
                // Deactivation Logic
                $student->update(['deactivated_at' => now()]);
                $deactivatedList[] = ($student->full_name ?? $student->name) . " (" . $student->email . ")";

                $message = "Dear " . ($student->full_name ?? $student->name) . ",\n\n" .
                    "Your account at " . config('app.name') . " has been deactivated due to non-payment for " . now()->format('F Y') . ".\n" .
                    "To reactivate your account, please complete your payment and contact Admin.";
                
                $whatsApp->send($phone, $message);
                $this->line("Deactivated and notified: " . $student->email);
            }
        }

        // Notify Admin on Day 5
        if ($day === 5 && !empty($deactivatedList)) {
            $admin = \App\Models\User::whereIn('role', ['admin', 'super_admin'])
                ->whereNotNull('phone_number')
                ->first();

            if ($admin) {
                $adminMessage = "Admin Notice: Payment Deactivations for " . now()->format('F Y') . "\n\n" .
                    "The following " . count($deactivatedList) . " students have been deactivated:\n" .
                    implode("\n", $deactivatedList);
                
                $whatsApp->send($admin->phone_number, $adminMessage);
                $this->info("Notified admin: " . $admin->name);
            }
        }

        $this->info("Finished payment automation tasks.");
    }
}
