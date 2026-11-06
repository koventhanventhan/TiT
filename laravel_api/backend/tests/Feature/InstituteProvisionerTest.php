<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\SiteSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\InstituteProvisioner;
use App\Services\ProvisionInstituteData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class InstituteProvisionerTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = SubscriptionPlan::create([
            'name' => 'Growth Plan',
            'slug' => 'growth-plan',
            'monthly_price' => 4500,
            'yearly_price' => 45000,
            'max_students' => 250,
            'max_teachers' => 15,
            'features' => ['live_classes', 'study_materials'],
        ]);
    }

    public function test_happy_path_creates_exact_rows_with_neutral_settings_and_no_secret_leaks(): void
    {
        Mail::fake();
        Notification::fake();

        $provisioner = app(InstituteProvisioner::class);

        $result = $provisioner->provision(new ProvisionInstituteData(
            name: 'Horizon Science Academy',
            shortBrandName: 'Horizon Academy',
            subscriptionPlanId: $this->plan->id,
            domain: 'https://WWW.Horizon-Academy.LK:8443/welcome',
            createAdmin: true,
            adminName: 'Nimal Perera',
            adminEmail: 'Nimal@Horizon-Academy.lk',
            adminPhone: '+94771112233',
            contactEmail: 'hello@horizon-academy.lk',
            contactPhone: '+94112223344',
            themePreset: 'ocean',
        ));

        $institute = $result->institute;
        $this->assertSame('Horizon Science Academy', $institute->name);
        $this->assertSame('horizon-science-academy', $institute->slug);
        $this->assertSame('active', $institute->status);
        $this->assertSame($this->plan->id, $institute->subscription_plan_id);
        $this->assertSame('HSA', $institute->index_prefix);
        $this->assertSame('grade', $institute->index_format);
        $this->assertNull($institute->theme_settings);
        $this->assertNotNull($institute->expires_at);
        $this->assertTrue($institute->expires_at->between(now()->addDays(29), now()->addDays(31)));

        // Domain normalized, primary, unverified
        $this->assertNotNull($result->domain);
        $this->assertSame('horizon-academy.lk', $result->domain->domain);
        $this->assertTrue($result->domain->is_primary);
        $this->assertNull($result->domain->verified_at);
        $this->assertDatabaseHas('institute_domains', [
            'institute_id' => $institute->id,
            'domain' => 'horizon-academy.lk',
            'is_primary' => 1,
            'verified_at' => null,
        ]);

        // First admin created with strong >= 14 char password
        $this->assertNotNull($result->admin);
        $this->assertNotNull($result->plainAdminPassword);
        $this->assertGreaterThanOrEqual(14, strlen($result->plainAdminPassword));
        $this->assertSame('admin', $result->admin->role);
        $this->assertSame($institute->id, $result->admin->institute_id);
        $this->assertSame('nimal@horizon-academy.lk', $result->admin->email);
        $this->assertTrue(Hash::check($result->plainAdminPassword, $result->admin->password));

        // Neutral settings written ONLY for this institute ID
        $settings = SiteSetting::withoutGlobalScopes()
            ->where('institute_id', $institute->id)
            ->pluck('value', 'key')
            ->all();

        $this->assertSame('Horizon Academy', $settings['site_name']);
        $this->assertSame('Horizon Academy', $settings['admin_company_name']);
        $this->assertSame('ocean', $settings['theme_preset']);
        $this->assertSame('hello@horizon-academy.lk', $settings['footer_email']);
        $this->assertSame('+94112223344', $settings['footer_phone']);
        $this->assertSame('no', $settings['topbar_show_social']);

        $allSettingsValuesBlob = implode(' | ', array_values($settings));
        $this->assertDoesNotMatchRegularExpression('/\btit\b|titjaffna/i', $allSettingsValuesBlob);
        $this->assertStringNotContainsStringIgnoringCase('jaffna', $allSettingsValuesBlob);
        $this->assertStringNotContainsStringIgnoringCase('edulearns', $allSettingsValuesBlob);
        $this->assertStringNotContainsString('0767206279', $allSettingsValuesBlob);

        $this->assertSame(0, SiteSetting::withoutGlobalScopes()->where('institute_id', '!=', $institute->id)->count());

        // Activity log recorded without secrets
        $log = ActivityLog::withoutGlobalScopes()
            ->where('action', 'institute_provisioned')
            ->where('institute_id', $institute->id)
            ->first();
        $this->assertNotNull($log);
        $this->assertSame('horizon-academy.lk', $log->metadata['domain']);
        $this->assertSame($this->plan->id, $log->metadata['plan_id']);

        // Password NEVER sent via mail or notifications, NEVER stored in activity_logs, notifications, or storage/logs
        Mail::assertNothingSent();
        Notification::assertNothingSent();

        $password = $result->plainAdminPassword;
        $activityDump = json_encode(ActivityLog::withoutGlobalScopes()->get()->toArray(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($password, $activityDump);

        if (Schema::hasTable('notifications')) {
            $notifDump = json_encode(DB::table('notifications')->get()->toArray(), JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString($password, $notifDump);
        }

        $logFiles = File::glob(storage_path('logs/*.log')) ?: [];
        foreach ($logFiles as $logFile) {
            $this->assertStringNotContainsString($password, File::get($logFile), "Plaintext password leaked into {$logFile}");
        }

        // __debugInfo redacts password
        $debugDump = print_r($result, true);
        $this->assertStringNotContainsString($password, $debugDump);
        $this->assertStringContainsString('[REDACTED]', $debugDump);
    }

    public function test_rollback_leaves_zero_rows_on_failure_at_every_step(): void
    {
        $stepsToFail = ['institute', 'domain', 'admin', 'settings'];

        foreach ($stepsToFail as $idx => $step) {
            $provisioner = (new InstituteProvisioner())->observeSteps(function (string $completedStep) use ($step): void {
                if ($completedStep === $step) {
                    throw new RuntimeException("Simulated failure right after {$step}");
                }
            });

            try {
                $provisioner->provision(new ProvisionInstituteData(
                    name: "Rollback Test {$idx}",
                    subscriptionPlanId: $this->plan->id,
                    domain: "rollback-{$idx}.lk",
                    createAdmin: true,
                    adminName: 'Rollback Admin',
                    adminEmail: "rollback{$idx}@example.org",
                    themePreset: 'royal',
                ));
                $this->fail("Expected RuntimeException at step {$step}");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString("Simulated failure right after {$step}", $e->getMessage());
            }

            $this->assertSame(0, Institute::withTrashed()->count(), "Institutes leaked after failure at {$step}");
            $this->assertSame(0, InstituteDomain::count(), "Domains leaked after failure at {$step}");
            $this->assertSame(0, User::withoutGlobalScopes()->count(), "Users leaked after failure at {$step}");
            $this->assertSame(0, SiteSetting::withoutGlobalScopes()->count(), "Settings leaked after failure at {$step}");
            $this->assertSame(0, ActivityLog::withoutGlobalScopes()->count(), "Activity logs leaked after failure at {$step}");
        }
    }

    public function test_duplicate_slug_handling_includes_soft_deleted_institutes(): void
    {
        $provisioner = app(InstituteProvisioner::class);

        $first = $provisioner->provision(new ProvisionInstituteData(
            name: 'Summit College',
            createAdmin: false,
        ));
        $this->assertSame('summit-college', $first->institute->slug);

        // Soft-delete the first institute; slug still exists in DB unique index!
        $first->institute->delete();
        $this->assertSoftDeleted('institutes', ['id' => $first->institute->id]);

        // Auto-slug skips the trashed 'summit-college' and assigns 'summit-college-2'
        $second = $provisioner->provision(new ProvisionInstituteData(
            name: 'Summit College',
            createAdmin: false,
        ));
        $this->assertSame('summit-college-2', $second->institute->slug);

        $third = $provisioner->provision(new ProvisionInstituteData(
            name: 'Summit College',
            createAdmin: false,
        ));
        $this->assertSame('summit-college-3', $third->institute->slug);

        // Explicit slug matching the trashed institute is rejected cleanly
        try {
            $provisioner->provision(new ProvisionInstituteData(
                name: 'Another College',
                slug: 'summit-college',
                createAdmin: false,
            ));
            $this->fail('Expected ValidationException for trashed slug collision');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('slug', $e->errors());
        }
    }

    public function test_auto_index_prefix_never_produces_tit_unless_explicitly_typed(): void
    {
        $provisioner = app(InstituteProvisioner::class);

        $auto = $provisioner->provision(new ProvisionInstituteData(
            name: 'Tamil Information Technology',
            createAdmin: false,
        ));
        $this->assertNotSame('TIT', $auto->institute->index_prefix);

        $explicit = $provisioner->provision(new ProvisionInstituteData(
            name: 'Custom Prefix Institute',
            indexPrefix: 'TIT',
            createAdmin: false,
        ));
        $this->assertSame('TIT', $explicit->institute->index_prefix);
    }

    public function test_duplicate_email_is_rejected_with_exact_message_and_writes_nothing(): void
    {
        $existingInstitute = Institute::create([
            'name' => 'Existing Institute',
            'slug' => 'existing-institute',
            'status' => 'active',
        ]);

        User::withoutGlobalScopes()->create([
            'name' => 'Existing User',
            'email' => 'taken@platform.lk',
            'password' => Hash::make('secret-password-123'),
            'role' => 'user',
            'institute_id' => $existingInstitute->id,
        ]);

        $provisioner = app(InstituteProvisioner::class);

        try {
            $provisioner->provision(new ProvisionInstituteData(
                name: 'New Academy',
                domain: 'new-academy.lk',
                createAdmin: true,
                adminName: 'Admin Candidate',
                adminEmail: 'TAKEN@platform.lk',
            ));
            $this->fail('Expected ValidationException on duplicate email');
        } catch (ValidationException $e) {
            $this->assertSame(
                [InstituteProvisioner::DUPLICATE_EMAIL_MESSAGE],
                $e->errors()['admin_email'] ?? null
            );
        }

        $this->assertSame(1, Institute::withTrashed()->count());
        $this->assertSame(0, InstituteDomain::count());
    }

    public function test_domain_already_owned_by_another_institute_names_owner_and_writes_nothing(): void
    {
        $owner = Institute::create([
            'name' => 'Apex Learning Hub',
            'slug' => 'apex-learning-hub',
            'status' => 'active',
        ]);

        InstituteDomain::create([
            'institute_id' => $owner->id,
            'domain' => 'apexhub.lk',
            'is_primary' => true,
        ]);

        $provisioner = app(InstituteProvisioner::class);

        try {
            $provisioner->provision(new ProvisionInstituteData(
                name: 'Rival Academy',
                domain: 'https://www.apexhub.lk',
                createAdmin: false,
            ));
            $this->fail('Expected ValidationException on owned domain');
        } catch (ValidationException $e) {
            $msg = $e->errors()['domain'][0] ?? '';
            $this->assertStringContainsString('Apex Learning Hub', $msg);
        }

        $this->assertSame(1, Institute::withTrashed()->count());
    }

    public function test_artisan_command_dry_run_happy_path_and_clean_failure_on_rerun(): void
    {
        // 1. Dry run writes zero rows
        $dryExit = Artisan::call('institutes:create', [
            'name' => 'Victory Tuition',
            '--plan' => 'growth-plan',
            '--domain' => 'victory-tuition.lk',
            '--admin-email' => 'admin@victory-tuition.lk',
            '--admin-name' => 'Victory Admin',
            '--preset' => 'emerald',
            '--dry-run' => true,
        ]);
        $this->assertSame(0, $dryExit);
        $this->assertSame(0, Institute::withTrashed()->count());
        $this->assertSame(0, User::withoutGlobalScopes()->count());

        // 2. Real run creates everything and prints one-time password once
        $realExit = Artisan::call('institutes:create', [
            'name' => 'Victory Tuition',
            '--plan' => 'growth-plan',
            '--domain' => 'victory-tuition.lk',
            '--admin-email' => 'admin@victory-tuition.lk',
            '--admin-name' => 'Victory Admin',
            '--preset' => 'emerald',
        ]);
        $output = Artisan::output();
        $this->assertSame(0, $realExit);
        $this->assertStringContainsString('One-time password', $output);
        $this->assertSame(1, Institute::withTrashed()->count());

        // 3. Re-running with the same name/slug fails cleanly (exit code 1) and writes nothing
        $rerunExit = Artisan::call('institutes:create', [
            'name' => 'Victory Tuition',
            '--plan' => 'growth-plan',
        ]);
        $rerunOutput = Artisan::output();
        $this->assertSame(1, $rerunExit);
        $this->assertStringContainsString('slug', strtolower($rerunOutput));
        $this->assertSame(1, Institute::withTrashed()->count());
    }

    public function test_super_admin_api_uses_provisioner_returns_422_on_duplicates_and_sends_no_password_email(): void
    {
        Mail::fake();
        Notification::fake();

        $rootInst = Institute::create([
            'name' => 'Platform Root',
            'slug' => 'platform-root',
            'status' => 'active',
        ]);

        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Super Admin',
            'email' => 'super@platform.lk',
            'password' => Hash::make('SuperSecret123!'),
            'role' => 'super_admin',
            'institute_id' => $rootInst->id,
        ]);

        $res1 = $this->actingAs($superAdmin, 'sanctum')->postJson('/api/super-admin/institutes', [
            'name' => 'Eastern Scholars',
            'subscription_plan_id' => $this->plan->id,
            'admin_name' => 'Eastern Admin',
            'admin_email' => 'admin@eastern-scholars.lk',
        ]);

        $res1->assertStatus(201)
            ->assertJsonPath('slug', 'eastern-scholars');
        $this->assertGreaterThanOrEqual(14, strlen((string) $res1->json('one_time_admin_password')));

        // No welcome email / notification sent with plaintext password
        Mail::assertNothingSent();
        Notification::assertNothingSent();

        // Duplicate name without explicit slug succeeds with -2 suffix instead of 500
        $res2 = $this->actingAs($superAdmin, 'sanctum')->postJson('/api/super-admin/institutes', [
            'name' => 'Eastern Scholars',
            'subscription_plan_id' => $this->plan->id,
        ]);
        $res2->assertStatus(201)
            ->assertJsonPath('slug', 'eastern-scholars-2');

        // Duplicate admin e-mail returns 422 with field error
        $resDupEmail = $this->actingAs($superAdmin, 'sanctum')->postJson('/api/super-admin/institutes', [
            'name' => 'Third Institute',
            'subscription_plan_id' => $this->plan->id,
            'admin_name' => 'Dup Admin',
            'admin_email' => 'admin@eastern-scholars.lk',
        ]);
        $resDupEmail->assertStatus(422)
            ->assertJsonValidationErrors(['admin_email']);
    }
}
