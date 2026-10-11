<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\EventNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendApprovalReminders extends Command
{
    protected $signature = 'alerts:pending-approvals';

    protected $description = 'Remind admin (daily digest) and waiting students about registrations pending approval for a long time';

    public function handle()
    {
        $pending = User::where('role', 'user')
            ->whereNotNull('full_name')
            ->whereNull('admin_confirmed_at')
            ->whereNull('deactivated_at')
            ->where(function ($q) {
                $q->whereNull('registration_status')
                  ->orWhereNotIn('registration_status', ['rejected', 'confirmed', 'approved']);
            })
            ->where('created_at', '<=', now()->subHours(24))
            ->orderBy('created_at')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No long-pending registrations.');
            return;
        }

        // 1. Admin digest
        $lines = $pending->take(25)->map(function ($u) {
            return ($u->full_name ?? $u->name) . ' - Grade ' . ($u->current_grade ?? '?') . ' - waiting ' . $u->created_at->diffForHumans(null, true);
        })->all();

        EventNotifier::toAdmin('pending_approvals', [
            'subject_line' => $pending->count() . ' Registration(s) Waiting for Approval',
            'heading'      => 'Pending Approvals',
            'icon'         => '⏳',
            'color'        => '#f59e0b',
            'message'      => $pending->count() . ' student registration(s) have been waiting for more than 24 hours.',
            'lines'        => $lines,
            'footer_note'  => $pending->count() > 25 ? 'Showing the oldest 25 only.' : null,
        ]);

        // 2. Reassure students waiting more than 48h (once a week at most)
        $sent = 0;
        foreach ($pending->filter(fn($u) => $u->created_at->lte(now()->subHours(48))) as $user) {
            if (Cache::add('approval_wait:' . $user->id, 1, now()->addDays(7)) && EventNotifier::approvalPendingStudent($user)) {
                $sent++;
            }
        }

        $this->info("Admin digest sent. Student reminders: {$sent}");
    }
}
