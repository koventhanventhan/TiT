<?php

namespace App\Console\Commands;

use App\Models\InstituteDomain;
use App\Services\DomainChecker;
use Illuminate\Console\Command;

class InstituteCheckDomainCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'institutes:check-domain
                            {domain : Domain name to diagnose (e.g. abc-tuition.lk)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose DNS, SSL, and routing for an institute domain';

    public function handle(DomainChecker $checker): int
    {
        $rawDomain = (string) $this->argument('domain');
        $domain = $checker->normalize($rawDomain);

        $this->info("Diagnosing domain: {$domain}");

        // 1. Database Mapping Check
        $domainRecord = InstituteDomain::with('institute')->where('domain', $domain)->first();
        if (!$domainRecord) {
            $this->error("[-] Database: Domain '{$domain}' is NOT mapped to any institute.");
            $this->line("    Run: php artisan institutes:add-domain <institute-id> {$domain}");

            return self::FAILURE;
        }

        $institute = $domainRecord->institute;
        $this->info("[+] Database: Mapped to Institute #{$institute->id} ({$institute->name}) [Primary: " . ($domainRecord->is_primary ? 'YES' : 'NO') . '].');

        // 2. DNS Resolution Check via shared DomainChecker
        $dnsResult = $checker->checkDns($domain, $domainRecord);
        $ips = $dnsResult['resolved_ips'];

        if (empty($ips)) {
            $this->error("[-] DNS: No A or CNAME records found for '{$domain}'.");

            return self::FAILURE;
        }

        $this->info('[+] DNS: Resolved to IP(s): ' . implode(', ', $ips));

        if (!$dnsResult['server_ip_configured']) {
            $this->warn('[!] DNS Note: ' . $dnsResult['message']);
        } elseif ($dnsResult['status'] === DomainChecker::STATUS_POINTS_HERE) {
            $this->info('[+] DNS: Points correctly to server IP (' . implode(', ', $dnsResult['matched_ips']) . ').');
        } else {
            $this->warn('[!] DNS Warning: Domain resolves to [' . implode(', ', $ips) . '], but expected server IP(s) are [' . implode(', ', $dnsResult['expected_ips']) . '].');
        }

        // 3. HTTP / SSL Connectivity Check via shared DomainChecker
        $httpResult = $checker->checkHttpAndSsl($domain, $institute);
        if ($httpResult['success']) {
            $this->info("[+] HTTP: Reached portal at {$httpResult['url']} (Status: {$httpResult['status']}).");
            if (!empty($httpResult['institute_name'])) {
                $this->info("[+] Routing: Resolved Institute Name matches: '{$httpResult['institute_name']}'.");
            }
            if ($httpResult['ssl']) {
                $this->info('[+] SSL: HTTPS connection active and valid.');
            } else {
                $this->warn('[!] SSL: Connected over plain HTTP; ensure SSL/HTTPS is configured.');
            }
        } else {
            $this->warn('[!] HTTP/Routing Warning: ' . $httpResult['message']);
        }

        $this->info("\nDomain diagnosis completed for '{$domain}'.");

        return self::SUCCESS;
    }
}
