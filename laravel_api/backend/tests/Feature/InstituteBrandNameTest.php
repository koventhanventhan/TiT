<?php

namespace Tests\Feature;

use App\Mail\NotificationMail;
use App\Mail\PaymentReceiptMail;
use App\Models\Institute;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\EventNotifier;
use App\Services\PaymentReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstituteBrandNameTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPlan $plan;
    protected Institute $institute1;
    protected Institute $institute2;
    protected User $student1;
    protected User $student2;
    protected Payment $payment1;
    protected Payment $payment2;

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

        $this->institute1 = Institute::create([
            'id' => 1,
            'name' => 'Lenova Main Academy',
            'slug' => 'tit',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'index_prefix' => 'TIT',
            'index_format' => 'grade',
        ]);

        $this->institute2 = Institute::create([
            'id' => 2,
            'name' => 'Northern Horizon',
            'slug' => 'northern-horizon',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'index_prefix' => 'NH',
            'index_format' => 'grade',
        ]);

        $this->student1 = User::create([
            'name' => 'Arun Kumar',
            'full_name' => 'Arun Kumar V',
            'email' => 'arun.kumar@gmail.com',
            'phone_number' => '0771234567',
            'password' => Hash::make('StudentPass!123'),
            'role' => 'user',
            'institute_id' => 1,
            'is_active' => true,
        ]);

        $this->payment1 = Payment::create([
            'user_id' => $this->student1->id,
            'institute_id' => 1,
            'receipt_no' => 'RCP-TIT-001',
            'amount' => 3500,
            'year_month' => '2026-11',
            'payment_method' => 'offline',
            'status' => 'paid',
        ]);

        $this->student2 = User::create([
            'name' => 'Bala Raja',
            'full_name' => 'Bala Raja S',
            'email' => 'bala.raja@gmail.com',
            'phone_number' => '0779876543',
            'password' => Hash::make('StudentPass!123'),
            'role' => 'user',
            'institute_id' => 2,
            'is_active' => true,
        ]);

        $this->payment2 = Payment::create([
            'user_id' => $this->student2->id,
            'institute_id' => 2,
            'receipt_no' => 'RCP-NH-001',
            'amount' => 4000,
            'year_month' => '2026-11',
            'payment_method' => 'offline',
            'status' => 'paid',
        ]);
    }

    public function test_institute_one_named_lenova_without_setting_uses_pre27_branding(): void
    {
        // Ensure no admin_company_name setting exists for Institute 1
        SiteSetting::withoutGlobalScopes()
            ->where('institute_id', 1)
            ->where('key', 'admin_company_name')
            ->delete();

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
        ];

        foreach ($expectedSubjects as $type => $expectedSubject) {
            $mail = new NotificationMail($type, ['institute_id' => 1, 'student_name' => 'Arun']);
            $this->assertSame($expectedSubject, $mail->envelope()->subject, "Subject mismatch for type {$type}");
            $html = $mail->render();
            $this->assertStringContainsString('TiT Education', $html);
            $this->assertStringNotContainsString('Lenova Main Academy', $html);
        }

        // PaymentReceiptMail
        $receiptMail = new PaymentReceiptMail($this->student1, $this->payment1, null, 'RCP-TIT-001', 'November 2026');
        $this->assertSame('Payment Receipt [RCP-TIT-001] - TiT Education', $receiptMail->envelope()->subject);
        $receiptHtml = $receiptMail->render();
        $this->assertStringContainsString('TiT Education', $receiptHtml);
        $this->assertStringContainsString('TiT Education • Jaffna, Sri Lanka', $receiptHtml);
        $this->assertStringNotContainsString('Lenova Main Academy', $receiptHtml);

        // EventNotifier brand helper
        $brand = (new class extends EventNotifier {
            public static function getBrand(?int $id): string {
                return self::brandFor($id);
            }
        })::getBrand(1);
        $this->assertSame('TiT Education', $brand);

        // Receipt PDF wordmark and footers
        config(['mail.from.address' => '']);
        $receiptData = app(PaymentReceiptService::class)->getReceiptData($this->payment1);
        $this->assertSame('TiT', $receiptData['wordmarkLogo']);
        $this->assertSame('INSTITUTE • JAFFNA', $receiptData['wordmarkSub']);
        $this->assertSame('TiT Institute • Jaffna', $receiptData['contactLine']);

        // Admin login title ignores institutes.name and renders TiT Education
        $loginPage = $this->get('/admin/login');
        $loginPage->assertOk();
        $loginPage->assertSee('Admin Login - TiT Education', false);
        $loginPage->assertDontSee('Lenova Main Academy');
    }

    public function test_institute_one_with_admin_company_name_tit_has_same_pre27_branding(): void
    {
        SiteSetting::setForInstitute('admin_company_name', 'TiT', 1, 'admin_identity');

        $mail = new NotificationMail('payment_success', ['institute_id' => 1]);
        $this->assertSame('Payment Confirmed ✅ - TiT Education', $mail->envelope()->subject);
        $this->assertStringContainsString('TiT Education', $mail->render());

        $receiptMail = new PaymentReceiptMail($this->student1, $this->payment1, null, 'RCP-TIT-001', 'November 2026');
        $this->assertSame('Payment Receipt [RCP-TIT-001] - TiT Education', $receiptMail->envelope()->subject);

        $receiptData = app(PaymentReceiptService::class)->getReceiptData($this->payment1);
        $this->assertSame('TiT', $receiptData['wordmarkLogo']);
        $this->assertSame('INSTITUTE • JAFFNA', $receiptData['wordmarkSub']);

        $loginPage = $this->get('/admin/login');
        $loginPage->assertOk();
        $loginPage->assertSee('Admin Login - TiT Education', false);
    }

    public function test_institute_one_with_admin_company_name_oxford_tuition_uses_custom_choice(): void
    {
        SiteSetting::setForInstitute('admin_company_name', 'Oxford Tuition', 1, 'admin_identity');

        $mail = new NotificationMail('payment_success', ['institute_id' => 1]);
        $this->assertSame('Payment Confirmed ✅ - Oxford Tuition', $mail->envelope()->subject);
        $this->assertStringContainsString('Oxford Tuition', $mail->render());

        $receiptMail = new PaymentReceiptMail($this->student1, $this->payment1, null, 'RCP-TIT-001', 'November 2026');
        $this->assertSame('Payment Receipt [RCP-TIT-001] - Oxford Tuition', $receiptMail->envelope()->subject);

        $receiptData = app(PaymentReceiptService::class)->getReceiptData($this->payment1);
        $this->assertSame('Oxford Tuition', $receiptData['wordmarkLogo']);

        $loginPage = $this->get('/admin/login');
        $loginPage->assertOk();
        $loginPage->assertSee('Admin Login - Oxford Tuition', false);
    }

    public function test_institute_two_named_northern_horizon_is_unaffected(): void
    {
        // Without setting, uses institutes.name: "Northern Horizon"
        SiteSetting::withoutGlobalScopes()
            ->where('institute_id', 2)
            ->where('key', 'admin_company_name')
            ->delete();

        $mail = new NotificationMail('payment_success', ['institute_id' => 2]);
        $this->assertSame('Payment Confirmed ✅ - Northern Horizon', $mail->envelope()->subject);
        $this->assertStringContainsString('Northern Horizon', $mail->render());

        $receiptMail = new PaymentReceiptMail($this->student2, $this->payment2, null, 'RCP-NH-001', 'November 2026');
        $this->assertSame('Payment Receipt [RCP-NH-001] - Northern Horizon', $receiptMail->envelope()->subject);

        $receiptData = app(PaymentReceiptService::class)->getReceiptData($this->payment2);
        $this->assertSame('Northern Horizon', $receiptData['wordmarkLogo']);

        // With explicit admin_company_name, uses that
        SiteSetting::setForInstitute('admin_company_name', 'Northern Star Academy', 2, 'admin_identity');
        $mailCustom = new NotificationMail('payment_success', ['institute_id' => 2]);
        $this->assertSame('Payment Confirmed ✅ - Northern Star Academy', $mailCustom->envelope()->subject);

        $receiptDataCustom = app(PaymentReceiptService::class)->getReceiptData($this->payment2);
        $this->assertSame('Northern Star Academy', $receiptDataCustom['wordmarkLogo']);
    }

    public function test_notification_mail_without_institute_id_in_console_context_renders_education(): void
    {
        $mail = new NotificationMail('payment_success', []);
        $this->assertSame('Payment Confirmed ✅ - Education', $mail->envelope()->subject);
        $this->assertStringContainsString('Education', $mail->render());
    }

    public function test_guard_no_new_notification_mail_instantiations_outside_notification_service(): void
    {
        $appPath = realpath(app_path());
        $phpFiles = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($appPath, \FilesystemIterator::SKIP_DOTS)
        );

        $occurrences = [];

        foreach ($phpFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($appPath) + 1));
            $content = file_get_contents($file->getPathname());

            // Match 'new NotificationMail(' outside comments
            $tokens = token_get_all($content);
            $tokenCount = count($tokens);

            for ($i = 0; $i < $tokenCount; $i++) {
                if (is_array($tokens[$i]) && $tokens[$i][0] === T_NEW) {
                    // Check next non-whitespace tokens
                    $j = $i + 1;
                    while ($j < $tokenCount && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                        $j++;
                    }

                    if ($j < $tokenCount && is_array($tokens[$j]) && ($tokens[$j][0] === T_STRING || $tokens[$j][0] === T_NAME_QUALIFIED || $tokens[$j][0] === T_NAME_FULLY_QUALIFIED)) {
                        $className = $tokens[$j][1];
                        if ($className === 'NotificationMail' || str_ends_with($className, '\\NotificationMail')) {
                            $line = $tokens[$i][2];
                            $occurrences[] = "app/{$relativePath}:{$line}";
                        }
                    }
                }
            }
        }

        // Verify that the only occurrence in app/ is in app/Services/NotificationService.php
        $this->assertNotEmpty($occurrences, 'Expected at least one occurrence in NotificationService.php');
        foreach ($occurrences as $loc) {
            $this->assertStringStartsWith(
                'app/Services/NotificationService.php:',
                $loc,
                "Found unexpected 'new NotificationMail(' at {$loc}. All production mails must go through NotificationService::sendEmail"
            );
        }
    }
}
