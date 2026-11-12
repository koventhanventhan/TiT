<?php

namespace Tests\Feature;

use App\Filament\SuperAdmin\Resources\InstituteResource;
use App\Filament\SuperAdmin\Widgets\StatsOverviewWidget;
use App\Mail\SubscriptionExpiryDigestMail;
use App\Mail\SubscriptionExpiryReminderMail;
use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\InstituteSubscriptionNotice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InstituteRenewalAndReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Primary Institute 1
        Institute::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'TiT Platform Institute',
                'slug' => 'tit',
                'status' => 'active',
                'is_active' => true,
            ]
        );
    }

    /**
     * Helper to create an institute with plan and admin.
     */
    protected function createInstituteWithAdmin(string $name, ?Carbon $expiresAt = null, ?SubscriptionPlan $plan = null): array
    {
        if (!$plan) {
            $plan = SubscriptionPlan::create([
                'name' => 'Standard Plan',
                'slug' => 'standard-' . uniqid(),
                'monthly_price' => 5000,
                'yearly_price' => 50000,
                'max_students' => 50,
                'max_teachers' => 5,
                'features' => ['zoom'],
            ]);
        }

        $institute = Institute::create([
            'name' => $name,
            'slug' => 'inst-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
            'subscription_plan_id' => $plan->id,
            'expires_at' => $expiresAt,
        ]);

        $admin = User::create([
            'name' => "Admin {$name}",
            'email' => "admin_" . uniqid() . "@example.com",
            'password' => Hash::make('Password@123'),
            'role' => 'admin',
            'institute_id' => $institute->id,
        ]);

        return [$institute, $admin, $plan];
    }

    public function test_reminder_schedule_triggers_at_each_offset(): void
    {
        Mail::fake();

        $offsets = [
            14 => '14_days',
            7  => '7_days',
            3  => '3_days',
            1  => '1_day',
            0  => 'today',
        ];

        $targetExpiry = Carbon::create(2026, 12, 1, 10, 0, 0);
        [$institute, $admin] = $this->createInstituteWithAdmin('Expiry Test Academy', $targetExpiry);

        foreach ($offsets as $daysBefore => $expectedKind) {
            // Set current time to $daysBefore days before targetExpiry
            $testNow = $targetExpiry->copy()->subDays($daysBefore)->startOfDay()->addHours(9)->addMinutes(30);
            Carbon::setTestNow($testNow);

            Artisan::call('institutes:expiry-reminders');

            // Assert notice was recorded in database
            $this->assertTrue(
                InstituteSubscriptionNotice::where('institute_id', $institute->id)
                    ->where('kind', $expectedKind)
                    ->whereDate('expires_on', $targetExpiry->toDateString())
                    ->exists(),
                "Expected notice for {$expectedKind} was not recorded."
            );

            // Assert email was sent to admin
            Mail::assertSent(SubscriptionExpiryReminderMail::class, function (SubscriptionExpiryReminderMail $mail) use ($admin) {
                return $mail->hasTo($admin->email);
            });
        }

        Carbon::setTestNow(); // Reset time
    }

    public function test_reminder_command_is_idempotent(): void
    {
        Mail::fake();

        $targetExpiry = Carbon::create(2026, 12, 1, 10, 0, 0);
        [$institute, $admin] = $this->createInstituteWithAdmin('Idempotent Academy', $targetExpiry);

        // 7 days before
        Carbon::setTestNow(Carbon::create(2026, 11, 24, 9, 30, 0));

        // First run
        Artisan::call('institutes:expiry-reminders');
        $this->assertEquals(1, InstituteSubscriptionNotice::where('institute_id', $institute->id)->count());

        Mail::fake(); // Reset fake to count subsequent sends

        // Second run on the exact same day
        Artisan::call('institutes:expiry-reminders');

        // No new emails sent
        Mail::assertNothingSent();
        $this->assertEquals(1, InstituteSubscriptionNotice::where('institute_id', $institute->id)->count());

        Carbon::setTestNow();
    }

    public function test_institute_1_is_never_sent_expiry_reminders(): void
    {
        Mail::fake();

        $inst1 = Institute::find(1);
        $inst1->update([
            'expires_at' => Carbon::create(2026, 12, 1, 10, 0, 0),
        ]);

        User::create([
            'name' => 'Institute 1 Admin',
            'email' => 'admin_inst1@example.com',
            'password' => Hash::make('Password@123'),
            'role' => 'admin',
            'institute_id' => 1,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 11, 24, 9, 30, 0)); // 7 days before

        Artisan::call('institutes:expiry-reminders');

        // Assert no notice for institute 1
        $this->assertDatabaseMissing('institute_subscription_notices', [
            'institute_id' => 1,
        ]);

        Mail::assertNotSent(SubscriptionExpiryReminderMail::class, function (SubscriptionExpiryReminderMail $mail) {
            return $mail->hasTo('admin_inst1@example.com');
        });

        Carbon::setTestNow();
    }

    public function test_institute_without_admin_email_is_skipped_gracefully(): void
    {
        Mail::fake();

        $targetExpiry = Carbon::create(2026, 12, 1, 10, 0, 0);
        $institute = Institute::create([
            'name' => 'No Admin Institute',
            'slug' => 'no-admin-' . uniqid(),
            'status' => 'active',
            'expires_at' => $targetExpiry,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 11, 24, 9, 30, 0)); // 7 days before

        $exitCode = Artisan::call('institutes:expiry-reminders');
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseMissing('institute_subscription_notices', [
            'institute_id' => $institute->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_dry_run_writes_nothing_to_database_and_sends_no_mail(): void
    {
        Mail::fake();

        $targetExpiry = Carbon::create(2026, 12, 1, 10, 0, 0);
        [$institute, $admin] = $this->createInstituteWithAdmin('Dry Run Institute', $targetExpiry);

        Carbon::setTestNow(Carbon::create(2026, 11, 24, 9, 30, 0)); // 7 days before

        Artisan::call('institutes:expiry-reminders', ['--dry-run' => true]);

        // Notice table should be empty
        $this->assertEquals(0, InstituteSubscriptionNotice::count());

        // No mail sent
        Mail::assertNothingSent();

        Carbon::setTestNow();
    }

    public function test_platform_owner_receives_digest_when_configured(): void
    {
        Mail::fake();
        config(['tenant.platform_contact_email' => 'owner@platform.com']);

        $targetExpiry = Carbon::create(2026, 12, 1, 10, 0, 0);
        [$institute, $admin] = $this->createInstituteWithAdmin('Digest Institute', $targetExpiry);

        Carbon::setTestNow(Carbon::create(2026, 11, 24, 9, 30, 0)); // 7 days before

        Artisan::call('institutes:expiry-reminders');

        Mail::assertSent(SubscriptionExpiryDigestMail::class, function (SubscriptionExpiryDigestMail $mail) {
            return $mail->hasTo('owner@platform.com');
        });

        Carbon::setTestNow();
    }

    public function test_renewal_math_past_expiry_extends_from_now(): void
    {
        $testNow = Carbon::create(2026, 11, 20, 12, 0, 0);
        Carbon::setTestNow($testNow);

        // Expired 10 days ago (2026-11-10)
        $pastExpiry = $testNow->copy()->subDays(10);
        [$institute] = $this->createInstituteWithAdmin('Past Expired Institute', $pastExpiry);
        $institute->update(['status' => 'expired']);

        // Renew +30 days directly via method
        $institute->renewSubscription(['duration' => '30_days']);
        $institute->refresh();

        // Must extend from now -> now + 30 days = 2026-12-20 12:00:00
        $this->assertEquals('active', $institute->status);
        $this->assertEquals($testNow->copy()->addDays(30)->toDateTimeString(), $institute->expires_at->toDateTimeString());

        // Verify activity log was recorded
        $this->assertDatabaseHas('activity_logs', [
            'institute_id' => $institute->id,
            'action' => 'institute_subscription_renewed',
        ]);

        // Verify Filament renew action is properly registered on InstituteResource
        $action = InstituteResource::getRenewAction();
        $this->assertEquals('renew', $action->getName());

        Carbon::setTestNow();
    }

    public function test_renewal_math_future_expiry_extends_from_expires_at(): void
    {
        $testNow = Carbon::create(2026, 11, 20, 12, 0, 0);
        Carbon::setTestNow($testNow);

        // Expires in 15 days (2026-12-05)
        $futureExpiry = $testNow->copy()->addDays(15);
        [$institute] = $this->createInstituteWithAdmin('Future Expiry Institute', $futureExpiry);

        // Renew +30 days
        $institute->renewSubscription(['duration' => '30_days']);
        $institute->refresh();

        // Must extend from expires_at -> 2026-12-05 + 30 days = 2027-01-04
        $expectedExpiry = $futureExpiry->copy()->addDays(30);
        $this->assertEquals($expectedExpiry->toDateTimeString(), $institute->expires_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_renewal_supports_one_year_custom_date_and_plan_change(): void
    {
        $testNow = Carbon::create(2026, 11, 20, 12, 0, 0);
        Carbon::setTestNow($testNow);

        [$institute, $admin, $oldPlan] = $this->createInstituteWithAdmin('Upgrade Institute', $testNow);

        $newPlan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-' . uniqid(),
            'monthly_price' => 15000,
            'yearly_price' => 150000,
            'max_students' => 500,
            'max_teachers' => 50,
            'features' => ['zoom', 'ai_assistant', 'whatsapp'],
        ]);

        // Renew +1 year and change plan
        $institute->renewSubscription([
            'duration' => '1_year',
            'subscription_plan_id' => $newPlan->id,
            'note' => 'Upgraded via annual contract',
        ]);
        $institute->refresh();

        $this->assertEquals($testNow->copy()->addYear()->toDateTimeString(), $institute->expires_at->toDateTimeString());
        $this->assertEquals($newPlan->id, $institute->subscription_plan_id);

        // Custom date renewal
        $customDate = Carbon::create(2028, 6, 30, 23, 59, 59);
        $institute->renewSubscription([
            'duration' => 'custom',
            'custom_expires_at' => $customDate->toDateTimeString(),
        ]);
        $institute->refresh();

        $this->assertEquals($customDate->toDateTimeString(), $institute->expires_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_stats_overview_widget_includes_accurate_expiry_and_limit_counts(): void
    {
        $testNow = Carbon::create(2026, 11, 20, 12, 0, 0);
        Carbon::setTestNow($testNow);

        // Expiring in 10 days
        $this->createInstituteWithAdmin('Expiring Soon', $testNow->copy()->addDays(10));

        // Already expired
        $this->createInstituteWithAdmin('Already Expired', $testNow->copy()->subDays(5));

        // Over limit institute
        $limitedPlan = SubscriptionPlan::create([
            'name' => 'Small Plan',
            'slug' => 'small-' . uniqid(),
            'monthly_price' => 1000,
            'yearly_price' => 10000,
            'max_students' => 2,
            'max_teachers' => 2,
        ]);
        [$overLimitInst] = $this->createInstituteWithAdmin('Over Limit Inst', $testNow->copy()->addDays(100), $limitedPlan);

        // Add 3 students to exceed max_students of 2
        for ($i = 0; $i < 3; $i++) {
            User::create([
                'name' => "Student {$i}",
                'email' => "student_{$i}_" . uniqid() . "@example.com",
                'password' => Hash::make('Password@123'),
                'role' => 'user',
                'institute_id' => $overLimitInst->id,
            ]);
        }

        // Invoke protected getStats on widget via reflection
        $widget = new class extends StatsOverviewWidget {
            public function testStats(): array
            {
                return $this->getStats();
            }
        };

        $stats = $widget->testStats();

        $statLabels = array_map(fn ($s) => $s->getLabel(), $stats);
        $this->assertContains('Expiring in 30 days', $statLabels);
        $this->assertContains('Expired', $statLabels);
        $this->assertContains('Over Limit', $statLabels);

        // Find specific stats
        foreach ($stats as $stat) {
            if ($stat->getLabel() === 'Expiring in 30 days') {
                $this->assertGreaterThanOrEqual(1, $stat->getValue());
            }
            if ($stat->getLabel() === 'Expired') {
                $this->assertGreaterThanOrEqual(1, $stat->getValue());
            }
            if ($stat->getLabel() === 'Over Limit') {
                $this->assertGreaterThanOrEqual(1, $stat->getValue());
            }
        }

        Carbon::setTestNow();
    }
}
