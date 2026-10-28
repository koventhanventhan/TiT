<?php

namespace App\Console\Commands;

use App\Models\AdvancePaymentRequest;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Read-only report of advance payment requests.
 * Prints pending requests with student id, institute id, whether the student is visible,
 * months, amount, age – so the owner can see orphan/other-institute rows from the cPanel terminal.
 */
class AdvancePaymentsReportCommand extends Command
{
    protected $signature = 'advance-payments:report
                            {--status=pending : Filter by request status (default: pending, use "all" for all statuses)}
                            {--institute= : Check visibility against a specific institute ID}';

    protected $description = 'Report advance payment requests with student, institute, visibility, months, amount, and age';

    public function handle(): int
    {
        $status = $this->option('status');
        $targetInstitute = $this->option('institute') !== null ? (int) $this->option('institute') : null;

        $query = AdvancePaymentRequest::withoutGlobalScopes()->orderBy('id', 'desc');
        if ($status && strtolower($status) !== 'all') {
            $query->where('status', strtolower($status));
        }

        $requests = $query->get();

        if ($requests->isEmpty()) {
            $this->info("No advance payment requests found" . ($status ? " with status '{$status}'" : "") . ".");
            return self::SUCCESS;
        }

        $userIds = $requests->pluck('user_id')->unique()->filter()->values();
        $users = User::withoutGlobalScopes()->whereIn('id', $userIds)->get()->keyBy('id');

        $rows = [];
        $orphanCount = 0;
        $totalAmount = 0.0;

        foreach ($requests as $req) {
            $user = $users->get($req->user_id);
            $totalAmount += (float) ($req->amount ?? 0);

            if (!$user) {
                $orphanCount++;
                $instituteText = 'None (Missing)';
                $visibleText = 'No (orphan)';
                $studentName = 'Missing User';
            } else {
                $instId = $user->institute_id;
                $instituteText = $instId !== null ? (string) $instId : 'None';

                if ($targetInstitute !== null) {
                    $visibleText = ((int) $instId === $targetInstitute) ? 'Yes' : 'No (other inst)';
                } else {
                    $visibleText = $instId !== null ? "Yes (Inst #{$instId})" : 'No (no inst)';
                }

                $studentName = $user->full_name ?? $user->name ?? "User #{$user->id}";
            }

            $ageText = $req->created_at
                ? $req->created_at->diffForHumans(null, true, true) . ' ago (' . $req->created_at->format('Y-m-d H:i') . ')'
                : 'N/A';

            $rows[] = [
                $req->id,
                $req->user_id,
                $studentName,
                $instituteText,
                $visibleText,
                $req->months_count ?? 1,
                number_format((float) ($req->amount ?? 0), 2),
                $ageText,
                $req->status,
            ];
        }

        $this->table(
            ['Req ID', 'Student ID', 'Student Name', 'Institute ID', 'Student Visible?', 'Months', 'Amount (LKR)', 'Age', 'Status'],
            $rows
        );

        $this->newLine();
        $this->info(sprintf(
            "Total Requests: %d | Total Amount: LKR %s | Orphan/Missing Students: %d",
            $requests->count(),
            number_format($totalAmount, 2),
            $orphanCount
        ));

        if ($targetInstitute !== null) {
            $visibleCount = collect($rows)->filter(fn ($r) => str_starts_with($r[4], 'Yes'))->count();
            $this->info("Visible to Institute #{$targetInstitute}: {$visibleCount} | Hidden/Other: " . ($requests->count() - $visibleCount));
        }

        return self::SUCCESS;
    }
}
