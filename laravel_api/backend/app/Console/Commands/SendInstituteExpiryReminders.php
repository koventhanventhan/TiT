<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiryDigestMail;
use App\Mail\SubscriptionExpiryReminderMail;
use App\Models\Institute;
use App\Models\InstituteSubscriptionNotice;
use App\Models\User;
use App\Support\InstituteMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInstituteExpiryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'institutes:expiry-reminders {--dry-run : List what would be sent without sending or recording}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send subscription expiry reminders to institute admins and a platform digest';

    /**
     * Day offsets before expiry when reminders are triggered.
     *
     * @var array<int, string>
     */
    protected array $triggerOffsets = [
        14 => '14_days',
        7  => '7_days',
        3  => '3_days',
        1  => '1_day',
        0  => 'today',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $today = now()->startOfDay();

        $institutes = Institute::withoutGlobalScopes()
            ->where('id', '!=', 1)
            ->whereNotNull('expires_at')
            ->get();

        $sentReminders = [];
        $noticesSentCount = 0;

        foreach ($institutes as $institute) {
            $expiresDate = $institute->expires_at->copy()->startOfDay();
            $daysUntil = (int) $today->diffInDays($expiresDate, false);

            if (!isset($this->triggerOffsets[$daysUntil])) {
                continue;
            }

            $kind = $this->triggerOffsets[$daysUntil];
            $expiresOn = $institute->expires_at->toDateString();

            // Idempotency check: have we already recorded this notice?
            $alreadySent = InstituteSubscriptionNotice::where('institute_id', $institute->id)
                ->where('kind', $kind)
                ->whereDate('expires_on', $expiresOn)
                ->exists();

            if ($alreadySent) {
                $this->line("Notice [{$kind}] for institute {$institute->id} ({$institute->name}) on {$expiresOn} already sent, skipping.");
                continue;
            }

            // Find institute administrator
            $admin = User::withoutGlobalScopes()
                ->where('institute_id', $institute->id)
                ->where('role', 'admin')
                ->orderBy('id')
                ->first();

            if (!$admin || empty(trim((string) $admin->email))) {
                Log::warning("institutes:expiry-reminders: Skipping institute {$institute->id} ({$institute->name}) - no administrator email found.");
                $this->warn("Skipped institute {$institute->id} ({$institute->name}): no admin email found.");
                continue;
            }

            $brand = Institute::mailBrandName($institute->id);

            if ($daysUntil === 0) {
                $subject = "Your Subscription Expires Today - {$brand}";
                $heading = "Subscription Expires Today";
                $messageText = "Your subscription for {$brand} expires today ({$expiresOn}). To ensure uninterrupted access for your students and teachers, please contact the platform owner to renew your plan.";
            } else {
                $unit = $daysUntil === 1 ? 'day' : 'days';
                $subject = "Subscription Reminder: {$daysUntil} {$unit} remaining - {$brand}";
                $heading = "Subscription Expiring Soon";
                $messageText = "Your subscription for {$brand} will expire in {$daysUntil} {$unit} on {$expiresOn}. Please contact the platform owner to renew your plan.";
            }

            if ($isDryRun) {
                $this->info("[DRY RUN] Would send {$kind} reminder to {$admin->email} for {$institute->name} (expires {$expiresOn}).");
            } else {
                $mailable = new SubscriptionExpiryReminderMail(
                    subjectLine: $subject,
                    heading: $heading,
                    messageText: $messageText,
                    instituteId: $institute->id,
                    details: [
                        'Institute'  => $institute->name,
                        'Expires On' => $expiresOn,
                        'Days Left'  => (string) $daysUntil,
                    ],
                );

                // Send via InstituteMail with platform SMTP fallback
                $sent = InstituteMail::to($admin->email, $institute->id)->send($mailable);
                if (!$sent) {
                    Mail::to($admin->email)->send($mailable);
                }

                // Record notice for idempotency
                InstituteSubscriptionNotice::create([
                    'institute_id' => $institute->id,
                    'kind'         => $kind,
                    'expires_on'   => $expiresOn,
                    'sent_at'      => now(),
                ]);

                $this->info("Sent {$kind} reminder to {$admin->email} for {$institute->name}.");
            }

            $sentReminders[] = [
                'institute_id' => $institute->id,
                'institute'    => $institute->name,
                'admin_email'  => $admin->email,
                'kind'         => $kind,
                'expires_on'   => $expiresOn,
                'days_until'   => $daysUntil,
            ];
            $noticesSentCount++;
        }

        // Send digest to platform owner if any reminders were processed
        $platformOwnerEmail = config('tenant.platform_contact_email');
        if (!empty($platformOwnerEmail) && !empty($sentReminders)) {
            $digestLines = [];
            foreach ($sentReminders as $r) {
                $digestLines[] = "• {$r['institute']} (ID: {$r['institute_id']}): {$r['kind']} reminder sent to {$r['admin_email']} (expires {$r['expires_on']})";
            }

            if ($isDryRun) {
                $this->info("[DRY RUN] Would send digest of " . count($sentReminders) . " notice(s) to platform owner: {$platformOwnerEmail}");
            } else {
                $digestMailable = new SubscriptionExpiryDigestMail(
                    subjectLine: "Subscription Expiry Digest - " . count($sentReminders) . " Notice(s) Sent",
                    heading: 'Subscription Expiry Digest',
                    messageText: "The daily subscription expiry reminder scan processed the following notices:\n\n" . implode("\n", $digestLines),
                );

                Mail::to($platformOwnerEmail)->send($digestMailable);
                $this->info("Sent digest to platform owner ({$platformOwnerEmail}).");
            }
        }

        $mode = $isDryRun ? '[DRY RUN] ' : '';
        $this->info("{$mode}Finished: {$noticesSentCount} reminder notice(s) processed.");

        return Command::SUCCESS;
    }
}
