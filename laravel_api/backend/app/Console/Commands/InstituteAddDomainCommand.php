<?php

namespace App\Console\Commands;

use App\Models\Institute;
use App\Models\InstituteDomain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class InstituteAddDomainCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'institutes:add-domain
                            {institute : Institute ID or slug}
                            {domain : Domain name to bind (e.g. abc-tuition.lk)}
                            {--primary : Set this domain as the primary domain for the institute}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add and bind a domain to an institute';

    public function handle(): int
    {
        $instituteInput = $this->argument('institute');
        $institute = is_numeric($instituteInput)
            ? Institute::find((int) $instituteInput)
            : Institute::where('slug', $instituteInput)->first();

        if (!$institute) {
            $this->error("Institute '{$instituteInput}' not found.");
            return self::FAILURE;
        }

        $rawDomain = $this->argument('domain');
        $domain = $this->normalizeDomain($rawDomain);

        if ($domain === '') {
            $this->error("Invalid domain '{$rawDomain}'.");
            return self::FAILURE;
        }

        $isPrimary = (bool) $this->option('primary');

        $existing = InstituteDomain::where('domain', $domain)->first();
        if ($existing) {
            if ($existing->institute_id !== $institute->id) {
                $otherInst = $existing->institute;
                $this->error("Domain '{$domain}' is already assigned to Institute #{$existing->institute_id} (" . ($otherInst->name ?? 'Unknown') . ").");
                return self::FAILURE;
            }

            // Already belongs to this institute
            if ($isPrimary && !$existing->is_primary) {
                InstituteDomain::where('institute_id', $institute->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);

                $existing->update(['is_primary' => true]);
                $this->info("Domain '{$domain}' updated to PRIMARY for Institute #{$institute->id} ({$institute->name}).");
            } else {
                $this->info("Domain '{$domain}' is already registered for Institute #{$institute->id} ({$institute->name}) [Primary: " . ($existing->is_primary ? 'YES' : 'NO') . "].");
            }

            Cache::forget('tenant_domain:' . $domain);
            return self::SUCCESS;
        }

        // If setting as primary, clear existing primary for this institute only
        if ($isPrimary) {
            InstituteDomain::where('institute_id', $institute->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        // Check if institute currently has zero domains; if so, default to primary
        $domainCount = InstituteDomain::where('institute_id', $institute->id)->count();
        if ($domainCount === 0) {
            $isPrimary = true;
        }

        $domainRecord = InstituteDomain::create([
            'institute_id' => $institute->id,
            'domain' => $domain,
            'is_primary' => $isPrimary,
            'verified_at' => now(),
            'verification_token' => bin2hex(random_bytes(16)),
        ]);

        Cache::forget('tenant_domain:' . $domain);
        $this->info("Successfully added domain '{$domain}' to Institute #{$institute->id} ({$institute->name}) [Primary: " . ($domainRecord->is_primary ? 'YES' : 'NO') . "].");

        return self::SUCCESS;
    }

    protected function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        // Strip protocol
        $domain = preg_replace('#^https?://#i', '', $domain);
        // Strip path / query
        $parts = explode('/', $domain, 2);
        $domain = $parts[0];
        // Strip port
        $hostParts = explode(':', $domain, 2);
        $domain = $hostParts[0];

        return strtolower(trim($domain));
    }
}
