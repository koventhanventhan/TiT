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
        $yearMonth = now()->format('Y-m');
        $this->info("Checking payments for $yearMonth...");

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

        foreach ($unpaidStudents as $student) {
            $phone = $student->phone_number;
            if ($phone) {
                $message = "Dear " . ($student->full_name ?? $student->name) . ",\n\n" .
                    "This is an automated reminder from " . config('app.name') . ".\n" .
                    "We noticed that your payment for " . now()->format('F Y') . " is still outstanding.\n" .
                    "Please complete your payment to continue accessing your Zoom classes and materials.\n\n" .
                    "If you have already paid, please ignore this message or contact admin.";
                
                $whatsApp->send($phone, $message);
                $this->line("Sent alert to: " . $student->email);
            }
        }

        $this->info("Finished sending payment alerts.");
    }
}
