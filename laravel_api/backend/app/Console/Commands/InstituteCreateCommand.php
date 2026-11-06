<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPlan;
use App\Services\InstituteProvisioner;
use App\Services\ProvisionInstituteData;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InstituteCreateCommand extends Command
{
    protected $signature = 'institutes:create
                            {name : Display name of the new institute (2-80 characters)}
                            {--slug= : Explicit URL slug (defaults to slugified name)}
                            {--plan= : Subscription plan ID or slug}
                            {--domain= : Primary domain (for example, academy.lk)}
                            {--admin-email= : First administrator e-mail address}
                            {--admin-name= : First administrator full name}
                            {--admin-phone= : First administrator phone number}
                            {--preset=royal : Theme preset (royal, ocean, emerald)}
                            {--dry-run : Validate all inputs and preview without writing to the database}';

    protected $description = 'Provision a new institute with neutral settings, optional domain, and optional first admin';

    public function handle(InstituteProvisioner $provisioner): int
    {
        $name = (string) $this->argument('name');
        $slugOption = $this->option('slug');
        $slug = ($slugOption !== null && trim((string) $slugOption) !== '')
            ? trim((string) $slugOption)
            : (Str::slug(trim($name)) ?: 'institute');

        $planId = null;
        $planInput = $this->option('plan');
        if ($planInput !== null && trim((string) $planInput) !== '') {
            $planStr = trim((string) $planInput);
            $plan = is_numeric($planStr)
                ? SubscriptionPlan::find((int) $planStr)
                : SubscriptionPlan::where('slug', strtolower($planStr))
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($planStr)])
                    ->first();

            if (!$plan) {
                $this->error("Subscription plan '{$planStr}' was not found.");

                return self::FAILURE;
            }
            $planId = $plan->id;
        }

        $adminEmail = $this->option('admin-email') !== null && trim((string) $this->option('admin-email')) !== ''
            ? trim((string) $this->option('admin-email'))
            : null;
        $adminName = $this->option('admin-name') !== null && trim((string) $this->option('admin-name')) !== ''
            ? trim((string) $this->option('admin-name'))
            : ($adminEmail !== null ? trim($name) . ' Admin' : null);
        $adminPhone = $this->option('admin-phone') !== null && trim((string) $this->option('admin-phone')) !== ''
            ? trim((string) $this->option('admin-phone'))
            : null;
        $domain = $this->option('domain') !== null && trim((string) $this->option('domain')) !== ''
            ? trim((string) $this->option('domain'))
            : null;
        $preset = $this->option('preset') !== null && trim((string) $this->option('preset')) !== ''
            ? trim((string) $this->option('preset'))
            : 'royal';

        $data = new ProvisionInstituteData(
            name: $name,
            shortBrandName: $name,
            slug: $slug,
            subscriptionPlanId: $planId,
            domain: $domain,
            createAdmin: $adminEmail !== null,
            adminName: $adminName,
            adminEmail: $adminEmail,
            adminPhone: $adminPhone,
            themePreset: $preset,
        );

        try {
            if ($this->option('dry-run')) {
                $validated = $provisioner->validateData($data);

                $this->info('[DRY RUN] Validation passed. No database changes were made.');
                $this->line('  Name:         ' . $validated['name']);
                $this->line('  Slug:         ' . $validated['slug']);
                $this->line('  Index prefix: ' . $validated['index_prefix'] . ' (' . $validated['index_format'] . ')');
                $this->line('  Theme preset: ' . $validated['theme_preset']);
                $this->line('  Plan:         ' . ($validated['plan_name'] ?? 'None'));
                $this->line('  Domain:       ' . ($validated['domain'] ?? 'None (add later)'));
                $this->line('  First admin:  ' . ($validated['create_admin'] ? "{$validated['admin_name']} <{$validated['admin_email']}>" : 'Skipped'));

                return self::SUCCESS;
            }

            $result = $provisioner->provision($data);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->error("[{$field}] {$message}");
                }
            }

            return self::FAILURE;
        }

        $this->info("Provisioned institute #{$result->institute->id}: {$result->institute->name} (slug: {$result->institute->slug})");
        if ($result->domain) {
            $this->line("  Domain:    {$result->domain->domain} (primary, unverified)");
        }
        $this->line('  Login URL: ' . rtrim($result->institute->primaryUrl(), '/') . '/admin/login');

        if ($result->admin && $result->plainAdminPassword !== null) {
            $this->line("  Admin:     {$result->admin->email}");
            $this->warn('  One-time password (save now — never stored or shown again): ' . $result->plainAdminPassword);
        }

        return self::SUCCESS;
    }
}
