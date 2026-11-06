<?php

namespace Tests\Feature;

use App\Mail\NotificationMail;
use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\WhatsAppService;
use App\Support\InstituteMail;
use App\Support\Tenant;
use App\Support\WhatsAppSendResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\NullTransport;
use Tests\TestCase;

class TenantQueuedProbeMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public static ?int $observedTenantIdInWorker = null;
    public static ?string $observedDefaultMailerInWorker = null;

    public function __construct(public string $studentName = 'Beta Learner') {}

    public function envelope(): Envelope
    {
        self::$observedTenantIdInWorker = Tenant::id();
        self::$observedDefaultMailerInWorker = (string) config('mail.default');

        return new Envelope(
            subject: 'Welcome Queue Probe - ' . $this->studentName,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Hello ' . e($this->studentName) . '</p>',
        );
    }
}

class PerInstituteMessagingAndQueueTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $instOne;
    protected Institute $instituteA;
    protected Institute $instituteB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instOne = Institute::create([
            'name' => 'Platform Default Institute',
            'slug' => 'platform-default',
            'status' => 'active',
        ]);

        $this->instituteA = Institute::create([
            'name' => 'Institute Alpha',
            'slug' => 'institute-alpha',
            'index_prefix' => 'ALP',
            'status' => 'active',
        ]);

        $this->instituteB = Institute::create([
            'name' => 'Institute Beta',
            'slug' => 'institute-beta',
            'index_prefix' => 'BET',
            'status' => 'active',
        ]);

        config([
            'integrations.env_fallback_institutes' => [$this->instOne->id],
            'integrations.mail_platform_fallback' => true,
            'services.meta_whatsapp.token' => 'ENV_GLOBAL_WA_TOKEN_0000',
            'services.meta_whatsapp.phone_number_id' => '999000111',
            'services.meta_whatsapp.api_version' => 'v21.0',
        ]);
    }

    public function test_whatsapp_uses_per_institute_credentials_and_template_overrides(): void
    {
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instituteA->id,
            'provider' => InstituteIntegration::PROVIDER_WHATSAPP,
            'credentials' => ['access_token' => 'EAAG_ALPHA_SECRET_TOKEN_1111'],
            'settings' => [
                'phone_number_id' => '111222333444',
                'api_version' => 'v21.0',
                'templates' => [
                    'welcome' => 'alpha_welcome_custom',
                ],
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instituteB->id,
            'provider' => InstituteIntegration::PROVIDER_WHATSAPP,
            'credentials' => ['access_token' => 'EAAG_BETA_SECRET_TOKEN_2222'],
            'settings' => [
                'phone_number_id' => '555666777888',
                'api_version' => 'v22.0',
                'templates' => [
                    'welcome' => 'beta_welcome_v2',
                ],
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        $recorded = [];
        Http::fake(function ($request) use (&$recorded) {
            $recorded[] = [
                'url' => $request->url(),
                'auth' => $request->header('Authorization')[0] ?? '',
                'body' => $request->data(),
            ];
            return Http::response(['messages' => [['id' => 'wamid.test.' . count($recorded)]]], 200);
        });

        $resA = WhatsAppService::forInstitute($this->instituteA->id)
            ->sendTemplateResult('0771234567', 'tit_welcome', 'en', ['Nimal', 'ALP/10/001']);
        $resB = WhatsAppService::forInstitute($this->instituteB->id)
            ->sendTemplateResult('0777654321', 'welcome', 'en', ['Kamal', 'BET/10/001']);

        $this->assertTrue($resA->isSent());
        $this->assertTrue($resB->isSent());
        $this->assertCount(2, $recorded);

        $this->assertSame('https://graph.facebook.com/v21.0/111222333444/messages', $recorded[0]['url']);
        $this->assertSame('Bearer EAAG_ALPHA_SECRET_TOKEN_1111', $recorded[0]['auth']);
        $this->assertSame('alpha_welcome_custom', $recorded[0]['body']['template']['name']);

        $this->assertSame('https://graph.facebook.com/v22.0/555666777888/messages', $recorded[1]['url']);
        $this->assertSame('Bearer EAAG_BETA_SECRET_TOKEN_2222', $recorded[1]['auth']);
        $this->assertSame('beta_welcome_v2', $recorded[1]['body']['template']['name']);

        // Both DB rows transitioned to verified
        $this->assertSame(
            InstituteIntegration::STATUS_VERIFIED,
            InstituteIntegration::withoutGlobalScopes()
                ->where('institute_id', $this->instituteB->id)
                ->where('provider', InstituteIntegration::PROVIDER_WHATSAPP)
                ->value('status')
        );
    }

    public function test_unconfigured_whatsapp_returns_typed_skipped_result_and_falls_back_to_institute_email(): void
    {
        Mail::fake();
        Http::fake();

        SiteSetting::withoutGlobalScopes()->create([
            'institute_id' => $this->instituteB->id,
            'key' => 'contact_email',
            'value' => 'desk@beta.academy.lk',
            'type' => 'text',
            'group' => 'contact',
        ]);

        // Institute B has NO WhatsApp row and is NOT in INTEGRATIONS_ENV_FALLBACK_INSTITUTES
        $result = WhatsAppService::forInstitute($this->instituteB->id)
            ->sendTemplateResult('0779998887', 'tit_welcome', 'en', ['Suresh']);

        $this->assertInstanceOf(WhatsAppSendResult::class, $result);
        $this->assertTrue($result->isSkipped());
        $this->assertFalse($result->isSent());
        $this->assertFalse($result->isFailed());
        Http::assertNothingSent();

        // NotificationService must NOT treat skipped WhatsApp as a fatal error and MUST still send email
        config(['services.whatsapp.enabled' => true]);
        $studentB = User::factory()->create([
            'institute_id' => $this->instituteB->id,
            'role' => 'user',
            'full_name' => 'Suresh Perera',
            'email' => 'suresh.perera@gmail.com',
            'phone_number' => '0779998887',
        ]);

        $sent = Tenant::run($this->instituteB, function () use ($studentB) {
            return app(NotificationService::class)->notifyUser(
                $studentB,
                'welcome',
                'tit_welcome',
                ['Suresh Perera'],
                ['student_name' => 'Suresh Perera']
            );
        });

        $this->assertTrue($sent);
        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) {
            return $mail->hasTo('suresh.perera@gmail.com')
                && $mail->mailer === 'institute_' . $this->instituteB->id
                && ($mail->from[0]['name'] ?? null) === 'Institute Beta'
                && ($mail->replyTo[0]['address'] ?? null) === 'desk@beta.academy.lk';
        });
    }

    public function test_should_queue_mailable_dispatched_under_institute_b_and_processed_by_queue_work_once_uses_institute_b_smtp_and_from_name(): void
    {
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instituteB->id,
            'provider' => InstituteIntegration::PROVIDER_MAIL,
            'credentials' => [
                'password' => 'BETA_SMTP_SECRET_PASS_7788',
            ],
            'settings' => [
                'host' => 'smtp.beta.academy.lk',
                'port' => 465,
                'encryption' => 'ssl',
                'username' => 'smtp-user@beta.academy.lk',
                'from_address' => 'noreply@beta.academy.lk',
                'from_name' => 'Institute Beta Official',
                'reply_to' => 'support@beta.academy.lk',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        config(['queue.default' => 'database']);
        TenantQueuedProbeMailable::$observedTenantIdInWorker = null;
        TenantQueuedProbeMailable::$observedDefaultMailerInWorker = null;

        // 1. Dispatch a ShouldQueue mailable while Institute B is the current tenant
        Tenant::run($this->instituteB, function () {
            $dispatched = InstituteMail::to('learner@gmail.com')->send(new TenantQueuedProbeMailable('Kavindi'));
            $this->assertTrue($dispatched);
        });

        // Verify job was persisted to database queue with institute_id embedded in payload
        $jobRow = DB::table('jobs')->first();
        $this->assertNotNull($jobRow, 'Expected ShouldQueue mailable to be stored in jobs table.');
        $decodedPayload = json_decode($jobRow->payload, true);
        $this->assertSame($this->instituteB->id, $decodedPayload['institute_id'] ?? null);

        // 2. Simulate cold queue worker process with NO HTTP request and NO pre-populated tenant or runtime mailer config
        Tenant::set(null);
        $mailerName = 'institute_' . $this->instituteB->id;
        config(["mail.mailers.{$mailerName}" => null]);
        $this->assertNull(Tenant::id());

        // 3. Process the queued job via `queue:work database --once`
        $exitCode = Artisan::call('queue:work', [
            'connection' => 'database',
            '--once' => true,
            '--queue' => 'default',
        ]);
        $this->assertSame(0, $exitCode);
        $this->assertSame(0, DB::table('jobs')->count(), 'Queue job should have been processed.');
        $this->assertSame(0, DB::table('failed_jobs')->count(), 'Queue job should not have failed.');

        // 4. Assert worker restored Tenant::id() to Institute B during processing and cleared it back to null afterwards
        $this->assertSame($this->instituteB->id, TenantQueuedProbeMailable::$observedTenantIdInWorker);
        $this->assertSame($mailerName, TenantQueuedProbeMailable::$observedDefaultMailerInWorker);
        $this->assertNull(Tenant::id(), 'Tenant must be cleared after JobProcessed.');

        // 5. Inspect resolved runtime mailer configuration for Institute B
        $resolvedMailerConfig = config("mail.mailers.{$mailerName}");
        $this->assertIsArray($resolvedMailerConfig);
        $this->assertSame('smtp.beta.academy.lk', $resolvedMailerConfig['host']);
        $this->assertSame(465, $resolvedMailerConfig['port']);
        $this->assertSame('ssl', $resolvedMailerConfig['encryption']);
        $this->assertSame('smtps', $resolvedMailerConfig['scheme']);
        $this->assertSame('smtp-user@beta.academy.lk', $resolvedMailerConfig['username']);
        $this->assertSame('BETA_SMTP_SECRET_PASS_7788', $resolvedMailerConfig['password']);
        $this->assertSame('noreply@beta.academy.lk', $resolvedMailerConfig['from']['address']);
        $this->assertSame('Institute Beta Official', $resolvedMailerConfig['from']['name']);
        $this->assertSame('support@beta.academy.lk', $resolvedMailerConfig['reply_to']['address']);

        // 6. Inspect the actual message delivered to the array transport
        $symfonyTransport = Mail::mailer($mailerName)->getSymfonyTransport();
        $this->assertNotInstanceOf(NullTransport::class, $symfonyTransport);
        $sentMessages = $symfonyTransport->messages();
        $this->assertCount(1, $sentMessages);

        $rawEmail = $sentMessages[0]->getOriginalMessage();
        $fromHeader = $rawEmail->getFrom();
        $replyToHeader = $rawEmail->getReplyTo();
        $this->assertSame('noreply@beta.academy.lk', $fromHeader[0]->getAddress());
        $this->assertSame('Institute Beta Official', $fromHeader[0]->getName());
        $this->assertSame('support@beta.academy.lk', $replyToHeader[0]->getAddress());
    }

    public function test_smtp_and_whatsapp_failures_record_sanitised_failing_status_without_breaking_payment_flow(): void
    {
        $waTokenSecret = 'EAAG_SECRET_FAIL_TOKEN_8899';
        $smtpPassSecret = 'SMTP_SUPER_SECRET_PASS_9988';
        $phMerchant = 'MERCHANT_BETA_555';
        $phSecret = 'PAYHERE_BETA_SECRET_6666';

        $waRow = InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instituteB->id,
            'provider' => InstituteIntegration::PROVIDER_WHATSAPP,
            'credentials' => ['access_token' => $waTokenSecret],
            'settings' => [
                'phone_number_id' => '444555666',
                'api_version' => 'v21.0',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        $mailRow = InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instituteB->id,
            'provider' => InstituteIntegration::PROVIDER_MAIL,
            'credentials' => ['password' => $smtpPassSecret],
            'settings' => [
                'host' => '127.0.0.1',
                'port' => 1, // Unreachable port -> immediate connection refused TransportException
                'encryption' => 'none',
                'username' => 'broken@beta.academy.lk',
                'from_address' => 'noreply@beta.academy.lk',
                'from_name' => 'Institute Beta',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instituteB->id,
            'provider' => InstituteIntegration::PROVIDER_PAYHERE,
            'credentials' => ['merchant_secret' => $phSecret],
            'settings' => [
                'merchant_id' => $phMerchant,
                'is_sandbox' => true,
                'currency' => 'LKR',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        // Simulate Meta Cloud API returning 401 echoing back the secret token in error text
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => "Invalid OAuth access token {$waTokenSecret} rejected by Meta",
                ],
            ], 401),
        ]);

        // Force real SMTP transport so connection to 127.0.0.1:1 fails with TransportException
        config([
            'integrations.testing_real_smtp_transport' => true,
            'services.whatsapp.enabled' => true,
        ]);

        try {
            $studentB = User::factory()->create([
                'institute_id' => $this->instituteB->id,
                'role' => 'user',
                'full_name' => 'Resilient Student',
                'email' => 'resilient.student@gmail.com',
                'phone_number' => '0771112233',
                'registration_status' => 'pending_payment',
            ]);

            $paymentB = Payment::withoutGlobalScopes()->create([
                'user_id' => $studentB->id,
                'institute_id' => $this->instituteB->id,
                'year_month' => '2026-11',
                'amount' => 1800.00,
                'status' => 'pending',
                'payment_method' => 'online',
                'gateway_ref' => 'PH_RESILIENT_001',
                'meta' => ['currency' => 'LKR'],
            ]);

            $amountFmt = '1800.00';
            $md5sig = strtoupper(md5(
                $phMerchant . 'PH_RESILIENT_001' . $amountFmt . 'LKR2' . strtoupper(md5($phSecret))
            ));

            // Business flow: PayHere webhook confirms payment while BOTH WhatsApp and SMTP fail!
            $resp = $this->post('/api/payhere/notify', [
                'merchant_id' => $phMerchant,
                'order_id' => 'PH_RESILIENT_001',
                'payment_id' => 'TX_RES_01',
                'payhere_amount' => $amountFmt,
                'payhere_currency' => 'LKR',
                'status_code' => 2,
                'md5sig' => $md5sig,
            ]);

            // Business flow MUST still succeed!
            $resp->assertStatus(200);
            $this->assertSame('paid', $paymentB->fresh()->status);
            $this->assertSame('approved', $studentB->fresh()->registration_status);
            $this->assertNotNull($paymentB->fresh()->receipt_no);

            // Both integration rows recorded status = failing and sanitised last_error WITHOUT secrets
            $waFresh = $waRow->fresh();
            $this->assertSame(InstituteIntegration::STATUS_FAILING, $waFresh->status);
            $this->assertNotEmpty($waFresh->last_error);
            $this->assertStringNotContainsString($waTokenSecret, (string) $waFresh->last_error);

            $mailFresh = $mailRow->fresh();
            $this->assertSame(InstituteIntegration::STATUS_FAILING, $mailFresh->status);
            $this->assertNotEmpty($mailFresh->last_error);
            $this->assertStringNotContainsString($smtpPassSecret, (string) $mailFresh->last_error);
        } finally {
            config(['integrations.testing_real_smtp_transport' => false]);
        }
    }
}
