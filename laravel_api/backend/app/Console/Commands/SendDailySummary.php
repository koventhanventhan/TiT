<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\User;
use App\Models\ZoomSchedule;
use App\Services\EventNotifier;
use Illuminate\Console\Command;

class SendDailySummary extends Command
{
    protected $signature = 'alerts:daily-summary';

    protected $description = 'Email the admin a daily summary: registrations, payments, pending approvals, classes';

    public function handle()
    {
        $start = now()->startOfDay();

        $newStudents = User::where('role', 'user')->whereNotNull('full_name')
            ->where('created_at', '>=', $start)->count();

        $paid = Payment::where('status', 'paid')->where('paid_at', '>=', $start);
        $paidCount = (clone $paid)->count();
        $paidTotal = (float) (clone $paid)->sum('amount');

        $failed = Payment::where('status', 'failed')->where('updated_at', '>=', $start)->count();

        $pendingApprovals = User::where('role', 'user')->whereNotNull('full_name')
            ->whereNull('admin_confirmed_at')->whereNull('deactivated_at')
            ->where(function ($q) {
                $q->whereNull('registration_status')->orWhere('registration_status', '!=', 'rejected');
            })->count();

        $classesToday = ZoomSchedule::whereBetween('scheduled_at', [$start, now()->endOfDay()])->count();
        $present = Attendance::where('status', 'present')->where('marked_at', '>=', $start)->count();

        $activeStudents = User::where('role', 'user')->whereNull('deactivated_at')
            ->whereNotNull('admin_confirmed_at')->count();

        EventNotifier::toAdmin('daily_summary', [
            'subject_line' => 'Daily Summary - ' . now()->format('M j, Y') . ' - TiT Education',
            'heading'      => 'Daily Summary',
            'icon'         => '📊',
            'color'        => '#4f46e5',
            'message'      => 'Here is what happened today (' . now()->format('l, M j') . ').',
            'details'      => [
                'New registrations'     => $newStudents,
                'Payments received'     => $paidCount . ' (LKR ' . number_format($paidTotal, 2) . ')',
                'Failed payments'       => $failed,
                'Pending approvals'     => $pendingApprovals,
                'Classes today'         => $classesToday,
                'Student attendances'   => $present,
                'Active students'       => $activeStudents,
            ],
        ]);

        $this->info('Daily summary sent.');
    }
}
