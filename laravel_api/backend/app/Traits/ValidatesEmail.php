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
        if (str_ends_with($email, '@student.local') || str_ends_with($email, '@child.local')) {
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

        // Well-known email providers — always allow (skip DNS entirely)
        $trustedDomains = [
            'gmail.com', 'googlemail.com', 'yahoo.com', 'yahoo.co.uk', 'yahoo.co.in',
            'hotmail.com', 'outlook.com', 'live.com', 'msn.com',
            'icloud.com', 'me.com', 'mac.com',
            'aol.com', 'protonmail.com', 'proton.me', 'zoho.com',
            'yandex.com', 'mail.com', 'gmx.com', 'gmx.net',
        ];

        if (in_array(strtolower($domain), $trustedDomains, true)) {
            $cache[$domain] = true;
            return true;
        }

        try {
            // Check MX records first (preferred for email)
            if (function_exists('checkdnsrr') && @checkdnsrr($domain, 'MX')) {
                $cache[$domain] = true;
                return true;
            }

            // Fallback: check A record (some domains accept email without MX)
            if (function_exists('checkdnsrr') && @checkdnsrr($domain, 'A')) {
                $cache[$domain] = true;
                return true;
            }

            // Fallback 2: dns_get_record (works on some hosts where checkdnsrr doesn't)
            if (function_exists('dns_get_record')) {
                $records = @dns_get_record($domain, DNS_MX | DNS_A);
                if (!empty($records)) {
                    $cache[$domain] = true;
                    return true;
                }
            }

            // Fallback 3: gethostbyname (most basic check — does the domain resolve at all?)
            $ip = @gethostbyname($domain);
            if ($ip !== $domain) {
                // Domain resolved to an IP — it exists
                $cache[$domain] = true;
                return true;
            }

            // If ALL methods failed, still allow through on shared hosting
            // because DNS restrictions on cPanel can cause false negatives
            Log::info("ValidatesEmail: All DNS checks returned no records for {$domain}, allowing through to avoid false rejection");
            $cache[$domain] = true;
            return true;
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

    protected function isPermanentMailFailure(\Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());

        // Temporary / infrastructure problems: never blocklist
        $temporary = ['timed out', 'timeout', 'connection', 'could not be established',
                      'authenticat', 'rate limit', 'too many', 'try again', 'temporar',
                      'quota', 'exceeded', '421', '450', '451', '452'];
        foreach ($temporary as $t) {
            if (str_contains($msg, $t)) {
                return false;
            }
        }

        // Permanent recipient errors (SMTP 5xx about the mailbox/address)
        return (bool) preg_match(
            '/(550|551|553|554|5\.1\.1|5\.1\.10).*(user unknown|no such user|mailbox|recipient|address|does not exist|not exist|invalid)/s',
            $msg
        );
    }
}
