<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Clean up dirty data per (user_id, year_month):
     * - If paid exists & pending exists -> cancel those pending rows.
     * - If multiple pending exist & no paid -> keep newest pending, cancel others.
     * Idempotent, chunked, non-destructive.
     */
    public function up(): void
    {
        $processedGroups = [];
        $cancelledCount = 0;

        DB::table('payments')
            ->where('status', 'pending')
            ->orderBy('id')
            ->chunkById(100, function ($pendingRows) use (&$cancelledCount, &$processedGroups) {
                foreach ($pendingRows as $pending) {
                    $key = $pending->user_id . '_' . $pending->year_month;
                    if (isset($processedGroups[$key])) {
                        continue;
                    }
                    $processedGroups[$key] = true;

                    $allForMonth = DB::table('payments')
                        ->where('user_id', $pending->user_id)
                        ->where('year_month', $pending->year_month)
                        ->orderByDesc('id')
                        ->get();

                    $hasPaid = $allForMonth->contains(fn($p) => $p->status === 'paid');
                    $pendingList = $allForMonth->where('status', 'pending');

                    if ($pendingList->isEmpty()) {
                        continue;
                    }

                    if ($hasPaid) {
                        $toCancelIds = $pendingList->pluck('id')->all();
                        $cancelledCount += DB::table('payments')
                            ->whereIn('id', $toCancelIds)
                            ->update(['status' => 'cancelled']);
                    } elseif ($pendingList->count() > 1) {
                        $toCancelIds = $pendingList->slice(1)->pluck('id')->all();
                        $cancelledCount += DB::table('payments')
                            ->whereIn('id', $toCancelIds)
                            ->update(['status' => 'cancelled']);
                    }
                }
            });

        Log::info("Cleaned up duplicate pending payments migration: cancelled {$cancelledCount} rows.");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: cancelling dirty duplicate pending rows is non-reversible history preservation
    }
};
