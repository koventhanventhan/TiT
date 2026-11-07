<?php

namespace Tests\Feature;

use App\Mail\NotificationMail;
use App\Mail\PaymentReceiptMail;
use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\EventNotifier;
use App\Services\InstituteProvisioner;
use App\Services\PaymentReceiptService;
use App\Services\ProvisionInstituteData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NeutralTenantTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = SubscriptionPlan::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'monthly_price' => 5000,
            'yearly_price' => 50000,
            'max_students' => 0,
            'max_teachers' => 0,
        ]);
    }

    public function test_institute_one_mail_subjects_receipt_footer_and_index_prefix_stay_unchanged(): void
    {
        $inst1 = Institute::withoutGlobalScopes()->find(1);
        if (! $inst1) {
            $inst1 = Institute::create([
                'id' => 1,
                'name' => 'TiT Institute',
                'slug' => 'tit',
                'status' => 'active',
                'subscription_plan_id' => $this->plan->id,
                'index_prefix' => 'TIT',
                'index_format' => 'grade',
            ]);
        } else {
            $inst1->update([
                'name' => 'TiT Institute',
                'index_prefix' => 'TIT',
            ]);
        }

        // SyncSettingsSeeder sets admin_company_name = 'TiT' on institute 1
        SiteSetting::setForInstitute('admin_company_name', 'TiT', 1, 'admin_identity');

        $expectedSubjects = [
            'general_update'           => 'Update - TiT Education',
            'welcome'                  => 'Welcome to TiT Education! 🎓',
            'payment_reminder'         => 'Payment Reminder - TiT Education',
            'payment_request_received' => 'Offline Payment Request Received 📋 - TiT Education',
            'payment_success'          => 'Payment Confirmed ✅ - TiT Education',
            'admin_approved'           => 'Account Approved! 🎉 - TiT Education',
            'account_deactivated'      => 'Account Deactivated - TiT Education',
            'account_suspended'        => 'Account Suspended - TiT Education',
            'account_reactivated'      => 'Account Reactivated ✅ - TiT Education',
            'zoom_reminder'            => 'Zoom Class Reminder 📹 - TiT Education',
            'admin_alert'              => 'Admin Alert - TiT Education',
            'class_created'            => 'New Class Scheduled - TiT Education',
            'custom_unknown_type'      => 'Notification - TiT Education',
        ];

        foreach ($expectedSubjects as $type => $expectedSubject) {
            $mail = new NotificationMail($type, ['institute_id' => 1, 'student_name' => 'Arun']);
            $this->assertSame($expectedSubject, $mail->envelope()->subject, "Subject mismatch for type {$type}");
            $html = $mail->render();
            $this->assertStringContainsString('TiT Education', $html);
        }

        $student1 = User::withoutGlobalScopes()->create([
            'name' => 'Kavitha',
            'full_name' => 'Kavitha R',
            'email' => 'kavitha.inst1@gmail.com',
            'phone_number' => '0771112233',
            'password' => Hash::make('SecretPass!123'),
            'role' => 'user',
            'institute_id' => 1,
        ]);

        $payment1 = Payment::withoutGlobalScopes()->create([
            'user_id' => $student1->id,
            'institute_id' => 1,
            'amount' => 2500,
            'year_month' => '2026-11',
            'payment_method' => 'offline',
            'status' => 'paid',
        ]);

        $receiptMail1 = new PaymentReceiptMail($student1, $payment1, null, 'RCP-TIT-001', 'November 2026');
        $this->assertSame('Payment Receipt [RCP-TIT-001] - TiT Education', $receiptMail1->envelope()->subject);
        $this->assertStringContainsString('TiT Education • Jaffna, Sri Lanka', $receiptMail1->render());

        // Clear mail.from.address and footer contact settings to verify Institute #1's historical receipt fallback
        config(['mail.from.address' => '']);
        $receiptData1 = app(PaymentReceiptService::class)->getReceiptData($payment1);
        $this->assertSame('TiT Institute • Jaffna', $receiptData1['contactLine']);

        // Even if index_prefix is null in memory, Institute #1 still resolves 'TIT'
        $this->assertSame('TIT', $inst1->getIndexPrefix());
        $inst1NullPrefix = new Institute(['name' => 'TiT Institute', 'index_prefix' => null]);
        $inst1NullPrefix->id = 1;
        $this->assertSame('TIT', $inst1NullPrefix->getIndexPrefix());
    }

    public function test_new_institute_mails_receipts_and_index_prefix_contain_zero_tit_or_jaffna_strings(): void
    {
        Mail::fake();

        Institute::withoutGlobalScopes()->find(1) ?? Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'index_prefix' => 'TIT',
            'index_format' => 'grade',
        ]);

        $result = app(InstituteProvisioner::class)->provision(new ProvisionInstituteData(
            name: 'Northern Star Academy',
            subscriptionPlanId: $this->plan->id,
            domain: 'northernstar.lk',
            createAdmin: true,
            adminName: 'Nimal Perera',
            adminEmail: 'admin@northernstar.lk',
            adminPhone: '0778889900',
            shortBrandName: 'Northern Star',
            themePreset: 'ocean',
        ));

        $instB = $result->institute;
        $this->assertNotSame(1, $instB->id);
        $this->assertSame('NSA', $instB->getIndexPrefix());

        // Even if index_prefix is cleared to null/empty on a non-1 institute, getIndexPrefix derives from name, never TIT
        $instBBlank = new Institute(['name' => 'Southern Wisdom College', 'index_prefix' => '']);
        $instBBlank->id = $instB->id;
        $this->assertSame('SWC', $instBBlank->getIndexPrefix());
        $this->assertNotSame('TIT', $instBBlank->getIndexPrefix());

        // Even a 3-word T-I-T name on a non-1 institute never auto-derives 'TIT'
        $instTitInitials = new Institute(['name' => 'Tamil Island Tutors', 'index_prefix' => null]);
        $instTitInitials->id = 99;
        $this->assertNotSame('TIT', $instTitInitials->getIndexPrefix());

        $types = [
            'general_update',
            'welcome',
            'payment_reminder',
            'payment_request_received',
            'payment_success',
            'admin_approved',
            'account_deactivated',
            'account_suspended',
            'account_reactivated',
            'zoom_reminder',
            'admin_alert',
            'class_created',
        ];

        foreach ($types as $type) {
            $mail = new NotificationMail($type, ['institute_id' => $instB->id, 'student_name' => 'Siva']);
            $subject = $mail->envelope()->subject;
            $html = $mail->render();

            $this->assertStringContainsString('Northern Star', $subject, "Expected tenant brand in {$type} subject");
            $this->assertStringNotContainsStringIgnoringCase('tit', $subject);
            $this->assertStringNotContainsStringIgnoringCase('jaffna', $subject);
            $this->assertStringContainsString('Northern Star', $html);
            $this->assertStringNotContainsString('TiT', $html);
            $this->assertStringNotContainsStringIgnoringCase('jaffna', $html);
        }

        // Fallback when admin_company_name is removed -> institutes.name -> 'Education'
        SiteSetting::withoutGlobalScopes()
            ->where('institute_id', $instB->id)
            ->where('key', 'admin_company_name')
            ->delete();
        $this->assertSame('Northern Star Academy', Institute::mailBrandName($instB->id));
        $this->assertSame('Education', Institute::mailBrandName(999999));

        // Restore short brand name
        SiteSetting::setForInstitute('admin_company_name', 'Northern Star', $instB->id, 'admin_identity');

        $studentB = User::withoutGlobalScopes()->create([
            'name' => 'Meena',
            'full_name' => 'Meena K',
            'email' => 'meena.student@gmail.com',
            'phone_number' => '0774445566',
            'password' => Hash::make('SecretPass!123'),
            'role' => 'user',
            'institute_id' => $instB->id,
        ]);

        $paymentB = Payment::withoutGlobalScopes()->create([
            'user_id' => $studentB->id,
            'institute_id' => $instB->id,
            'amount' => 3200,
            'year_month' => '2026-11',
            'payment_method' => 'offline',
            'status' => 'paid',
        ]);

        $receiptMailB = new PaymentReceiptMail($studentB, $paymentB, null, 'RCP-NSA-001', 'November 2026');
        $this->assertSame('Payment Receipt [RCP-NSA-001] - Northern Star', $receiptMailB->envelope()->subject);
        $receiptMailHtml = $receiptMailB->render();
        $this->assertStringContainsString('Northern Star', $receiptMailHtml);
        $this->assertStringNotContainsString('TiT', $receiptMailHtml);
        $this->assertStringNotContainsStringIgnoringCase('jaffna', $receiptMailHtml);

        $receiptService = app(PaymentReceiptService::class);
        $this->assertNull($receiptService->getLogoBase64($paymentB));
        $receiptDataB = $receiptService->getReceiptData($paymentB);
        $this->assertSame('Northern Star', $receiptDataB['contactLine']);
        $receiptPdfHtml = view('pdf.receipt', $receiptDataB)->render();
        $this->assertStringContainsString('Northern Star', $receiptPdfHtml);
        $this->assertStringNotContainsString('TiT', $receiptPdfHtml);
        $this->assertStringNotContainsStringIgnoringCase('jaffna', $receiptPdfHtml);

        // EventNotifier uses Institute B's brand when notifying B's student
        \App\Support\Tenant::run($instB->id, function () use ($studentB): void {
            EventNotifier::approvalPendingStudent($studentB);
        });

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mailable): bool {
            $subject = $mailable->envelope()->subject;
            return $subject === 'Your Registration Is Under Review - Northern Star'
                && ! str_contains(strtolower($subject), 'tit')
                && ! str_contains(strtolower($subject), 'jaffna');
        });
    }

    public function test_api_settings_never_leaks_another_institutes_settings_even_when_empty_or_super_admin(): void
    {
        $instA = Institute::withoutGlobalScopes()->find(1) ?? Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'index_prefix' => 'TIT',
            'index_format' => 'grade',
        ]);

        InstituteDomain::updateOrCreate(
            ['domain' => 'tit-primary.lk'],
            ['institute_id' => $instA->id, 'is_primary' => true, 'verified_at' => now()]
        );

        // Seed distinctive Institute A values
        $distinctiveA = [
            'site_name' => ['TiT Online Education Jaffna', 'general'],
            'admin_company_name' => ['TiT', 'admin_identity'],
            'hero_title' => ['DISTINCTIVE_A_HERO_TITLE', 'hero'],
            'hero_description' => ['Join TiT Online Education in Jaffna today.', 'hero'],
            'footer_logo_text' => ['TiT Jaffna', 'footer'],
            'footer_copyright' => ['© 2025 TiT Online Education (PVT) Ltd.', 'footer'],
            'footer_email' => ['info@edulearns.lk', 'footer'],
            'footer_phone' => ['0767206279', 'footer'],
            'social_facebook' => ['https://facebook.com/titjaffna', 'social'],
            'logo_url' => ['https://titjaffna.lk/uploads/tit-distinctive-logo.png', 'general'],
        ];
        foreach ($distinctiveA as $key => [$val, $group]) {
            SiteSetting::setForInstitute($key, $val, $instA->id, $group);
        }

        // Provision Institute B via InstituteProvisioner
        $resultB = app(InstituteProvisioner::class)->provision(new ProvisionInstituteData(
            name: 'Hill Country Academy',
            subscriptionPlanId: $this->plan->id,
            domain: 'hillcountry.lk',
            createAdmin: false,
            themePreset: 'emerald',
        ));

        $respB = $this->getJson('http://hillcountry.lk/api/settings');
        $respB->assertOk();
        $rawJsonB = $respB->getContent();

        $this->assertSame('Hill Country Academy', $respB->json('site_name'));
        $this->assertSame('emerald', $respB->json('theme_preset'));

        $this->assertStringNotContainsString('TiT', $rawJsonB);
        $this->assertDoesNotMatchRegularExpression('/\btit\b/i', $rawJsonB);

        foreach ([
            'Jaffna',
            'titjaffna',
            'edulearns.lk',
            '0767206279',
            'DISTINCTIVE_A_HERO_TITLE',
            'tit-distinctive-logo.png',
        ] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase(
                $forbidden,
                $rawJsonB,
                "Institute A value [{$forbidden}] leaked into Institute B /api/settings"
            );
        }

        // Also test Finding 7's exact bare-row scenario: Institute C with ZERO rows in site_settings,
        // both as guest AND while authenticated as super_admin (where InstituteScope does not filter SiteSetting::all()).
        $instC = Institute::create([
            'name' => 'Eastern Horizon College',
            'slug' => 'eastern-horizon',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'index_prefix' => 'EHC',
            'index_format' => 'grade',
        ]);
        InstituteDomain::create([
            'institute_id' => $instC->id,
            'domain' => 'easternhorizon.lk',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        $this->assertSame(
            0,
            SiteSetting::withoutGlobalScopes()->where('institute_id', $instC->id)->count()
        );

        $respCGuest = $this->getJson('http://easternhorizon.lk/api/settings');
        $respCGuest->assertOk();
        $this->assertSame('Eastern Horizon College', $respCGuest->json('site_name'));
        $this->assertStringNotContainsStringIgnoringCase('edulearns.lk', $respCGuest->getContent());
        $this->assertStringNotContainsStringIgnoringCase('DISTINCTIVE_A_HERO_TITLE', $respCGuest->getContent());
        $this->assertStringNotContainsStringIgnoringCase('Hill Country Academy', $respCGuest->getContent());

        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Owner',
            'full_name' => 'Platform Owner',
            'email' => 'owner@platform.lk',
            'phone_number' => '0770000001',
            'password' => Hash::make('OwnerPass!12345'),
            'role' => 'super_admin',
            'institute_id' => null,
        ]);

        $respCSuper = $this->actingAs($superAdmin)->getJson('http://easternhorizon.lk/api/settings');
        $respCSuper->assertOk();
        $this->assertSame('Eastern Horizon College', $respCSuper->json('site_name'));
        $this->assertStringNotContainsStringIgnoringCase('edulearns.lk', $respCSuper->getContent());
        $this->assertStringNotContainsStringIgnoringCase('0767206279', $respCSuper->getContent());
        $this->assertStringNotContainsStringIgnoringCase('DISTINCTIVE_A_HERO_TITLE', $respCSuper->getContent());
    }
}

