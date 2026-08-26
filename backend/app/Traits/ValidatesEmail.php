<?php

namespace App\Traits;

use App\Models\EmailBounce;
use Illuminate\Support\Facades\Log;

/**
 * Trait ValidatesEmail
 *
 * Provides email validation methods including DNS/MX record checking
 * and bounce tracking to prevent sending emails to invalid addresses.
 */
trait ValidatesEmail
{
    /**
     * Check if an email address is valid for sending.
     *
     * Performs the following checks:
     * 1. Not empty/null
     * 2. Not a @student.local placeholder
     * 3. Passes PHP's FILTER_VALIDATE_EMAIL
     * 4. Domain has valid MX or A DNS records
     * 5. Not on the bounce blocklist (2+ bounces)
     *
     * @param string|null $email
     * @return bool
     */
    protected function isValidEmailForSending(?string $email): bool
    {
        // 1. Not empty
        if (empty($email)) {
            return false;
        }

        // 2. Not a placeholder address
        if (str_ends_with($email, '@student.local')) {
            return false;
        }

        // 3. Basic format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::debug("ValidatesEmail: Invalid format — {$email}");
            return false;
        }

        // 4. DNS/MX record check — verify the domain actually exists
        $domain = substr(strrchr($email, '@'), 1);
        if ($domain && !$this->domainHasMailRecords($domain)) {
            Log::info("ValidatesEmail: No MX/A records for domain — {$email}");
            return false;
        }

        // 5. Check bounce blocklist
        if ($this->isEmailBounced($email)) {
            Log::info("ValidatesEmail: Email is on bounce blocklist — {$email}");
            return false;
        }

        return true;
    }

    /**
     * Check if a domain has MX or A DNS records.
     * Falls back to A record check if no MX records exist.
     *
     * Results are cached in-memory for the duration of the request
     * to avoid redundant DNS lookups in batch operations.
     *
     * @param string $domain
     * @return bool
     */
    protected function domainHasMailRecords(string $domain): bool
    {
        // In-memory cache for this request (avoids repeated DNS lookups in batch sends)
        static $cache = [];

        if (isset($cache[$domain])) {
            return $cache[$domain];
        }

        try {
            // Check MX records first (preferred for email)
            if (checkdnsrr($domain, 'MX')) {
                $cache[$domain] = true;
                return true;
            }

            // Fallback: check A record (some domains accept email without MX)
            if (checkdnsrr($domain, 'A')) {
                $cache[$domain] = true;
                return true;
            }

            $cache[$domain] = false;
            return false;
        } catch (\Throwable $e) {
            // If DNS check fails (e.g., network issue), allow the email through
            // to avoid blocking legitimate emails due to transient DNS failures
            Log::warning("ValidatesEmail: DNS check failed for {$domain}: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Check if an email address has been bounced 2+ times.
     *
     * @param string $email
     * @return bool
     */
    protected function isEmailBounced(string $email): bool
    {
        try {
            return EmailBounce::where('email', strtolower($email))
                ->where('bounce_count', '>=', EmailBounce::BOUNCE_BLOCK_THRESHOLD)
                ->exists();
        } catch (\Throwable $e) {
            // If the table doesn't exist yet (pre-migration), allow through
            Log::warning("ValidatesEmail: Bounce check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record an email bounce. After 2 bounces, the email will be blocklisted.
     *
     * @param string $email
     * @return void
     */
    protected function markEmailAsBounced(string $email): void
    {
        try {
            $bounce = EmailBounce::firstOrCreate(
                ['email' => strtolower($email)],
                ['bounce_count' => 0]
            );

            $bounce->increment('bounce_count');
            $bounce->update(['last_bounced_at' => now()]);

            Log::warning("ValidatesEmail: Recorded bounce #{$bounce->bounce_count} for {$email}");

            if ($bounce->bounce_count >= EmailBounce::BOUNCE_BLOCK_THRESHOLD) {
                Log::error("ValidatesEmail: Email permanently blocklisted — {$email} (bounce count: {$bounce->bounce_count})");
            }
        } catch (\Throwable $e) {
            Log::warning("ValidatesEmail: Failed to record bounce for {$email}: " . $e->getMessage());
        }
    }
}
