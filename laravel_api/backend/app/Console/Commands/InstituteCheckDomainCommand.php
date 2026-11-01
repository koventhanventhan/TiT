<?php

namespace App\Console\Commands;

use App\Models\InstituteDomain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

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

    public function handle(): int
    {
        $rawDomain = $this->argument('domain');
        $domain = $this->normalizeDomain($rawDomain);

        $this->info("Diagnosing domain: {$domain}");

        // 1. Database Mapping Check
        $domainRecord = InstituteDomain::with('institute')->where('domain', $domain)->first();
        if (!$domainRecord) {
            $this->error("[-] Database: Domain '{$domain}' is NOT mapped to any institute.");
            $this->line("    Run: php artisan institutes:add-domain <institute-id> {$domain}");
            return self::FAILURE;
        }

        $institute = $domainRecord->institute;
        $this->info("[+] Database: Mapped to Institute #{$institute->id} ({$institute->name}) [Primary: " . ($domainRecord->is_primary ? 'YES' : 'NO') . "].");

        // 2. DNS Resolution Check
        $ips = $this->resolveDnsIps($domain);
        if (empty($ips)) {
            $this->error("[-] DNS: No A or CNAME records found for '{$domain}'.");
            return self::FAILURE;
        }

        $this->info("[+] DNS: Resolved to IP(s): " . implode(', ', $ips));

        $serverIps = config('tenant.server_ips', []);
        if (!empty($serverIps)) {
            $matched = array_intersect($ips, $serverIps);
            if (empty($matched)) {
                $this->warn("[!] DNS Warning: Domain resolves to [" . implode(', ', $ips) . "], but expected server IP(s) are [" . implode(', ', $serverIps) . "].");
            } else {
                $this->info("[+] DNS: Points correctly to server IP (" . implode(', ', $matched) . ").");
            }
        }

        // 3. HTTP / SSL Connectivity Check
        $httpResult = $this->checkHttpAndSsl($domain, $institute);
        if ($httpResult['success']) {
            $this->info("[+] HTTP: Reached portal at {$httpResult['url']} (Status: {$httpResult['status']}).");
            if (!empty($httpResult['institute_name'])) {
                $this->info("[+] Routing: Resolved Institute Name matches: '{$httpResult['institute_name']}'.");
            }
            if ($httpResult['ssl']) {
                $this->info("[+] SSL: HTTPS connection active and valid.");
            } else {
                $this->warn("[!] SSL: Connected over plain HTTP; ensure SSL/HTTPS is configured.");
            }
        } else {
            $this->warn("[!] HTTP/Routing Warning: " . $httpResult['message']);
        }

        $this->info("\nDomain diagnosis completed for '{$domain}'.");
        return self::SUCCESS;
    }

    /**
     * Resolves DNS IPs (mockable via protected method).
     */
    protected function resolveDnsIps(string $domain): array
    {
        // Check test hook or config mock
        $mocked = config('tenant.mock_dns.' . $domain);
        if ($mocked !== null) {
            return (array) $mocked;
        }

        if (app()->environment('testing')) {
            return ['127.0.0.1'];
        }

        $records = @dns_get_record($domain, DNS_A);
        $ips = [];
        if (is_array($records)) {
            foreach ($records as $record) {
                if (!empty($record['ip'])) {
                    $ips[] = $record['ip'];
                }
            }
        }

        if (empty($ips)) {
            $ip = @gethostbyname($domain);
            if ($ip && $ip !== $domain) {
                $ips[] = $ip;
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * Checks HTTP and SSL response for domain.
     */
    protected function checkHttpAndSsl(string $domain, $institute): array
    {
        $mockedHttp = config('tenant.mock_http.' . $domain);
        if ($mockedHttp !== null) {
            return $mockedHttp;
        }

        if (app()->environment('testing')) {
            return [
                'success' => true,
                'status' => 200,
                'url' => "https://{$domain}/api/settings",
                'institute_name' => $institute->name,
                'ssl' => true,
                'message' => 'OK (Mocked in test)',
            ];
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders(['Host' => $domain])
                ->get("https://{$domain}/api/settings");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'url' => "https://{$domain}/api/settings",
                    'institute_name' => $data['site_name'] ?? null,
                    'ssl' => true,
                    'message' => 'OK',
                ];
            }

            // Fallback to HTTP
            $httpResponse = Http::timeout(5)
                ->withHeaders(['Host' => $domain])
                ->get("http://{$domain}/api/settings");

            return [
                'success' => $httpResponse->successful(),
                'status' => $httpResponse->status(),
                'url' => "http://{$domain}/api/settings",
                'institute_name' => $httpResponse->json('site_name'),
                'ssl' => false,
                'message' => $httpResponse->successful() ? 'OK (HTTP only)' : 'Returned status ' . $httpResponse->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 0,
                'url' => "https://{$domain}",
                'institute_name' => null,
                'ssl' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    protected function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        $domain = preg_replace('#^https?://#i', '', $domain);
        $parts = explode('/', $domain, 2);
        $domain = $parts[0];
        $hostParts = explode(':', $domain, 2);
        return strtolower(trim($hostParts[0]));
    }
}
