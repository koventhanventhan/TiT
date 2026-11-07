<?php

namespace App\Services;

use App\Models\Institute;
use App\Models\InstituteDomain;
use Closure;
use Illuminate\Support\Facades\Http;

class DomainChecker
{
    public const STATUS_NOT_CHECKED = 'not_checked';
    public const STATUS_POINTS_HERE = 'points_to_this_server';
    public const STATUS_POINTS_ELSEWHERE = 'points_elsewhere';
    public const STATUS_NOT_FOUND = 'not_found';

    /**
     * @param  (Closure(string): list<string>)|null  $dnsResolver
     * @param  (Closure(string, ?Institute): array<string, mixed>)|null  $httpChecker
     */
    public function __construct(
        protected ?Closure $dnsResolver = null,
        protected ?Closure $httpChecker = null,
    ) {
    }

    /**
     * Normalize a user-supplied URL or hostname into a bare lowercase domain:
     * strips scheme, path, query, fragment, port, and leading "www.".
     */
    public function normalize(string $rawDomain): string
    {
        $domain = strtolower(trim($rawDomain));
        $domain = preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', $domain) ?? $domain;
        $domain = explode('/', $domain, 2)[0];
        $domain = explode('?', $domain, 2)[0];
        $domain = explode('#', $domain, 2)[0];
        $domain = preg_replace('/:\d+$/', '', trim($domain)) ?? $domain;
        $domain = preg_replace('/^www\./i', '', trim($domain)) ?? $domain;

        return trim($domain);
    }

    /**
     * Validate format, reserved hosts, platform hosts, and ownership in institute_domains.
     * Returns null when valid, or a plain-English error message when invalid.
     */
    public function validate(string $rawOrNormalizedDomain, ?int $allowSameInstituteId = null): ?string
    {
        $domain = $this->normalize($rawOrNormalizedDomain);

        if ($domain === '') {
            return 'Please enter a valid website address (for example, academy.lk).';
        }

        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return 'Please enter a domain name, not an IP address.';
        }

        if (
            $domain === 'localhost'
            || str_ends_with($domain, '.localhost')
            || $domain === 'test'
            || str_ends_with($domain, '.test')
        ) {
            return 'Local addresses (localhost or .test) cannot be used as an institute website address.';
        }

        $platformHosts = $this->normalizedPlatformHosts();
        if (in_array($domain, $platformHosts, true)) {
            return 'This website address belongs to the platform itself and cannot be assigned to an institute.';
        }

        if (!str_contains($domain, '.') || strlen($domain) > 253) {
            return 'Please enter a full domain name with its extension (for example, academy.lk).';
        }

        $labels = explode('.', $domain);
        foreach ($labels as $label) {
            if ($label === '' || strlen($label) > 63 || !preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $label)) {
                return 'Each part of the domain may only use letters, numbers, and hyphens, and cannot start or end with a hyphen.';
            }
        }

        $tld = (string) end($labels);
        if (!preg_match('/^[a-z]{2,63}$/', $tld)) {
            return 'The domain extension must be at least 2 letters (for example, .lk or .com).';
        }

        $existing = InstituteDomain::with(['institute' => static fn ($q) => $q->withTrashed()])
            ->whereIn('domain', [$domain, 'www.' . $domain])
            ->first();

        if ($existing) {
            if ($allowSameInstituteId !== null && (int) $existing->institute_id === $allowSameInstituteId) {
                return null;
            }

            $ownerName = $existing->institute?->name ?? 'Unknown';

            return "Domain '{$domain}' is already assigned to Institute #{$existing->institute_id} ({$ownerName}).";
        }

        return null;
    }

    /**
     * Resolve DNS A records for a domain and compare against config('tenant.server_ips').
     * Never throws; if DNS points to this server and $domainRecord is given, marks verified_at = now().
     *
     * @return array{
     *   domain: string,
     *   status: string,
     *   badge: string,
     *   color: string,
     *   resolved_ips: list<string>,
     *   expected_ips: list<string>,
     *   matched_ips: list<string>,
     *   server_ip_configured: bool,
     *   verified: bool,
     *   message: string
     * }
     */
    public function checkDns(string $rawDomain, ?InstituteDomain $domainRecord = null): array
    {
        $domain = $this->normalize($rawDomain);
        $expectedIps = $this->configuredServerIps();
        $serverIpConfigured = !empty($expectedIps);

        if ($domain === '') {
            return [
                'domain' => '',
                'status' => self::STATUS_NOT_CHECKED,
                'badge' => self::badgeLabel(self::STATUS_NOT_CHECKED),
                'color' => 'gray',
                'resolved_ips' => [],
                'expected_ips' => $expectedIps,
                'matched_ips' => [],
                'server_ip_configured' => $serverIpConfigured,
                'verified' => false,
                'message' => 'Enter a website address first before checking DNS.',
            ];
        }

        $resolvedIps = $this->resolveDnsIps($domain);

        if (!$serverIpConfigured) {
            $resolvedPart = empty($resolvedIps)
                ? 'No A record was found yet.'
                : 'Resolved A record(s): ' . implode(', ', $resolvedIps) . '.';

            return [
                'domain' => $domain,
                'status' => self::STATUS_NOT_CHECKED,
                'badge' => self::badgeLabel(self::STATUS_NOT_CHECKED),
                'color' => 'gray',
                'resolved_ips' => $resolvedIps,
                'expected_ips' => [],
                'matched_ips' => [],
                'server_ip_configured' => false,
                'verified' => false,
                'message' => "Server IP is not configured yet — ask the platform administrator to set TENANT_SERVER_IPS in the environment settings. {$resolvedPart}",
            ];
        }

        if (empty($resolvedIps)) {
            return [
                'domain' => $domain,
                'status' => self::STATUS_NOT_FOUND,
                'badge' => self::badgeLabel(self::STATUS_NOT_FOUND),
                'color' => 'danger',
                'resolved_ips' => [],
                'expected_ips' => $expectedIps,
                'matched_ips' => [],
                'server_ip_configured' => true,
                'verified' => false,
                'message' => "No DNS A record was found for {$domain}. Point its A record to " . implode(' or ', $expectedIps) . '.',
            ];
        }

        $matchedIps = array_values(array_intersect($resolvedIps, $expectedIps));

        if (!empty($matchedIps)) {
            if ($domainRecord !== null && $domainRecord->verified_at === null) {
                $domainRecord->forceFill(['verified_at' => now()])->save();
            }

            return [
                'domain' => $domain,
                'status' => self::STATUS_POINTS_HERE,
                'badge' => self::badgeLabel(self::STATUS_POINTS_HERE),
                'color' => 'success',
                'resolved_ips' => $resolvedIps,
                'expected_ips' => $expectedIps,
                'matched_ips' => $matchedIps,
                'server_ip_configured' => true,
                'verified' => true,
                'message' => "{$domain} points to this server (" . implode(', ', $matchedIps) . ').',
            ];
        }

        return [
            'domain' => $domain,
            'status' => self::STATUS_POINTS_ELSEWHERE,
            'badge' => self::badgeLabel(self::STATUS_POINTS_ELSEWHERE),
            'color' => 'warning',
            'resolved_ips' => $resolvedIps,
            'expected_ips' => $expectedIps,
            'matched_ips' => [],
            'server_ip_configured' => true,
            'verified' => false,
            'message' => "{$domain} currently points to " . implode(', ', $resolvedIps) . ' instead of this server (' . implode(', ', $expectedIps) . ').',
        ];
    }

    /**
     * Resolve A records for $domain using injected resolver, test config mock, or PHP DNS lookup.
     *
     * @return list<string>
     */
    public function resolveDnsIps(string $domain): array
    {
        if ($this->dnsResolver !== null) {
            $ips = ($this->dnsResolver)($domain);

            return array_values(array_unique(array_filter(array_map('strval', $ips))));
        }

        $mocked = config('tenant.mock_dns.' . $domain);
        if ($mocked !== null) {
            return array_values(array_unique(array_map('strval', (array) $mocked)));
        }

        if (app()->environment('testing')) {
            return ['127.0.0.1'];
        }

        $records = @dns_get_record($domain, DNS_A);
        $ips = [];
        if (is_array($records)) {
            foreach ($records as $record) {
                if (!empty($record['ip'])) {
                    $ips[] = (string) $record['ip'];
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
     * Check HTTP/HTTPS reachability and tenant routing for $domain.
     *
     * @return array<string, mixed>
     */
    public function checkHttpAndSsl(string $domain, ?Institute $institute = null): array
    {
        $domain = $this->normalize($domain);

        if ($this->httpChecker !== null) {
            return ($this->httpChecker)($domain, $institute);
        }

        $mockedHttp = config('tenant.mock_http.' . $domain);
        if ($mockedHttp !== null) {
            return $mockedHttp;
        }

        if (app()->environment('testing')) {
            return [
                'success' => true,
                'status' => 200,
                'url' => "https://{$domain}/api/settings",
                'institute_name' => $institute?->name,
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

    /**
     * @return list<string>
     */
    public function configuredServerIps(): array
    {
        return array_values(array_filter(array_map(
            static fn ($ip): string => trim((string) $ip),
            (array) config('tenant.server_ips', [])
        )));
    }

    /**
     * @return list<string>
     */
    public function normalizedPlatformHosts(): array
    {
        $hosts = [];
        foreach ((array) config('tenant.platform_hosts', []) as $host) {
            $norm = $this->normalize((string) $host);
            if ($norm !== '') {
                $hosts[] = $norm;
            }
        }

        return array_values(array_unique($hosts));
    }

    public static function badgeLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_POINTS_HERE => 'Points to this server',
            self::STATUS_POINTS_ELSEWHERE => 'Points elsewhere',
            self::STATUS_NOT_FOUND => 'Not found',
            default => 'Not checked',
        };
    }

    /**
     * Plain-language DNS & cPanel setup steps for the platform owner.
     *
     * @return array{server_ips: list<string>, document_root: string, steps: list<string>, manual_note: string}
     */
    public function cpanelInstructions(?string $domain = null): array
    {
        $ips = $this->configuredServerIps();
        $ipDisplay = !empty($ips)
            ? implode(' / ', $ips)
            : 'your server IP (not configured yet — set TENANT_SERVER_IPS)';
        $target = $domain !== null && $domain !== '' ? $domain : 'your-domain.lk';
        $docRoot = '/home/titjaffn/public_html';

        return [
            'server_ips' => $ips,
            'document_root' => $docRoot,
            'steps' => [
                "At the domain registrar for {$target}, point both the root (@) and www A records to {$ipDisplay}.",
                "In cPanel, add {$target} as an Alias / Parked domain whose document root is the SAME folder as titjaffna.lk ({$docRoot}) — NOT a separate application folder.",
                'Wait a few minutes for cPanel AutoSSL to issue the HTTPS certificate automatically.',
                'Click "Check DNS now" here to confirm the domain points to this server.',
            ],
            'manual_note' => 'Step 2 is performed manually by the platform owner inside cPanel; this screen does not modify cPanel automatically.',
        ];
    }
}

