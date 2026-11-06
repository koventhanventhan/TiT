<?php

namespace App\Services;

use Carbon\CarbonInterface;

class ProvisionInstituteData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $shortBrandName = null,
        public readonly ?string $slug = null,
        public readonly ?string $logoPath = null,
        public readonly ?int $subscriptionPlanId = null,
        public readonly ?CarbonInterface $expiresAt = null,
        public readonly ?string $indexPrefix = null,
        public readonly string $indexFormat = 'grade',
        public readonly ?string $domain = null,
        public readonly bool $createAdmin = true,
        public readonly ?string $adminName = null,
        public readonly ?string $adminEmail = null,
        public readonly ?string $adminPhone = null,
        public readonly ?string $contactEmail = null,
        public readonly ?string $contactPhone = null,
        public readonly string $themePreset = 'royal',
    ) {
    }
}
