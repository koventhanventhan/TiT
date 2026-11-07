<?php

namespace App\Console\Commands;

use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Services\DomainChecker;
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

    public function handle(DomainChecker $checker): int
    {
        $instituteInput = $this->argument('institute');
        $institute = is_numeric($instituteInput)
            ? Institute::find((int) $instituteInput)
            : Institute::where('slug', $instituteInput)->first();

        if (!$institute) {
            $this->error("Institute '{$instituteInput}' not found.");

            return self::FAILURE;
        }

        $rawDomain = (string) $this->argument('domain');
        $domain = $checker->normalize($rawDomain);

        $validationError = $checker->validate($domain, $institute->id);
        if ($validationError !== null) {
            $this->error($validationError);

            return self::FAILURE;
        }

        $isPrimary = (bool) $this->option('primary');

        $existing = InstituteDomain::where('domain', $domain)->first();
        if ($existing && (int) $existing->institute_id === (int) $institute->id) {
            if ($isPrimary && !$existing->is_primary) {
                InstituteDomain::where('institute_id', $institute->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);

                $existing->update(['is_primary' => true]);
                $this->info("Domain '{$domain}' updated to PRIMARY for Institute #{$institute->id} ({$institute->name}).");
            } else {
                $this->info("Domain '{$domain}' is already registered for Institute #{$institute->id} ({$institute->name}) [Primary: " . ($existing->is_primary ? 'YES' : 'NO') . '].');
            }

            Cache::forget('tenant_domain:' . $domain);

            return self::SUCCESS;
        }

        if ($isPrimary) {
            InstituteDomain::where('institute_id', $institute->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $domainCount = InstituteDomain::where('institute_id', $institute->id)->count();
        if ($domainCount === 0) {
            $isPrimary = true;
        }

        $domainRecord = InstituteDomain::create([
            'institute_id' => $institute->id,
            'domain' => $domain,
            'is_primary' => $isPrimary,
            'verified_at' => null,
            'verification_token' => bin2hex(random_bytes(16)),
        ]);

        Cache::forget('tenant_domain:' . $domain);
        $this->info("Successfully added domain '{$domain}' to Institute #{$institute->id} ({$institute->name}) [Primary: " . ($domainRecord->is_primary ? 'YES' : 'NO') . '].');

        return self::SUCCESS;
    }
}
