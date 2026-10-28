<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Relabel PAID payments that say "online" although the payment gateway never confirmed them
 * (empty transaction_id) – e.g. a pending PayHere checkout that an admin later marked paid
 * after the money was received offline.
 *
 * Dry-run by default; use --apply to write. Idempotent.
 */
class FixOfflinePaymentLabelsCommand extends Command
{
    protected $signature = 'payments:fix-offline-labels
                            {--apply : Actually write the changes (default is a dry-run)}';

    protected $description = 'Relabel PAID payments marked "online" without a gateway transaction id as "offline" (dry-run by default)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $rows = Payment::withoutGlobalScopes()
            ->where('status', 'paid')
            ->whereIn('payment_method', ['online', 'payhere'])
            ->where(function ($q) {
                $q->whereNull('transaction_id')->orWhere('transaction_id', '');
            })
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('Nothing to fix: no paid payment is labelled online without a gateway transaction id.');

            return self::SUCCESS;
        }

        $table = [];
        foreach ($rows as $payment) {
            if ($payment->wasGatewayConfirmed()) {
                continue;
            }

            $oldRef = (string) $payment->gateway_ref;
            $newRef = str_starts_with(strtoupper($oldRef), 'PH_')
                ? 'ADMIN_' . $payment->id . '_' . now()->format('ymdHis')
                : $oldRef;

            $table[] = [
                $payment->id,
                $payment->user_id,
                $payment->year_month,
                $payment->kind ?: 'monthly',
                $payment->payment_method . ' → offline',
                $oldRef !== $newRef ? "{$oldRef} → {$newRef}" : ($oldRef ?: '—'),
            ];

            if ($apply) {
                DB::transaction(function () use ($payment, $newRef, $oldRef) {
                    $meta = (array) ($payment->meta ?? []);
                    $meta['original_method'] = $meta['original_method'] ?? $payment->payment_method;
                    if ($newRef !== $oldRef) {
                        $meta['original_gateway_ref'] = $meta['original_gateway_ref'] ?? $oldRef;
                    }
                    $meta['relabelled_by_command'] = now()->toDateTimeString();

                    $payment->forceFill([
                        'payment_method' => 'offline',
                        'gateway_ref' => $newRef !== '' ? $newRef : $payment->gateway_ref,
                        'meta' => $meta,
                    ])->save();
                });
            }
        }

        $this->table(['Payment', 'Student', 'Month', 'Kind', 'Method (old → new)', 'Gateway ref (old → new)'], $table);

        if ($apply) {
            $this->info(count($table) . ' payment(s) relabelled as offline.');
        } else {
            $this->warn(count($table) . ' payment(s) would be relabelled. Dry-run only – run again with --apply to write the changes.');
        }

        return self::SUCCESS;
    }
}
