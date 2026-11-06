<?php

namespace App\Services;

use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\User;

class ProvisionResult
{
    /**
     * @param  array<string, string>  $settings
     */
    public function __construct(
        public readonly Institute $institute,
        public readonly ?InstituteDomain $domain = null,
        public readonly ?User $admin = null,
        public readonly ?string $plainAdminPassword = null,
        public readonly array $settings = [],
    ) {
    }

    /**
     * Prevent accidental leakage of the one-time password in var_dump / print_r / debug logs.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'institute_id' => $this->institute->id,
            'slug' => $this->institute->slug,
            'domain' => $this->domain?->domain,
            'admin_id' => $this->admin?->id,
            'plainAdminPassword' => $this->plainAdminPassword !== null ? '[REDACTED]' : null,
        ];
    }
}
