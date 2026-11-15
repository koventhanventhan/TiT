<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Institute;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PublicAssistantStatusService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAssistantMariaDbTest extends TestCase
{
    private string $targetDatabase = 'tit_rag_test_round6';
    private array $cleanupInstituteIds = [];
    private array $cleanupUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.mysql.database' => $this->targetDatabase]);
        DB::purge('mysql');

        try {
            DB::connection('mysql')->getPdo();
            DB::setDefaultConnection('mysql');
        } catch (\Throwable $e) {
            $this->markTestSkipped("Dedicated test MariaDB ({$this->targetDatabase}) is not reachable: " . $e->getMessage());
        }

        // Run real migrations on MariaDB test database
        Artisan::call('migrate', ['--database' => 'mysql', '--force' => true]);
    }

    protected function tearDown(): void
    {
        try {
            if (!empty($this->cleanupInstituteIds)) {
                DB::connection('mysql')->table('site_settings')->whereIn('institute_id', $this->cleanupInstituteIds)->delete();
                DB::connection('mysql')->table('faqs')->whereIn('institute_id', $this->cleanupInstituteIds)->delete();
                DB::connection('mysql')->table('institutes')->whereIn('id', $this->cleanupInstituteIds)->delete();
            }
            if (!empty($this->cleanupUserIds)) {
                DB::connection('mysql')->table('users')->whereIn('id', $this->cleanupUserIds)->delete();
            }
        } catch (\Throwable $e) {
            // Ignore cleanup failure
        } finally {
            DB::setDefaultConnection('sqlite');
        }

        parent::tearDown();
    }

    /**
     * Test contact details storage, lengths, and tenant isolation on MariaDB.
     */
    public function test_contact_details_and_faqs_storage_on_mariadb(): void
    {
        $instA = Institute::on('mysql')->create([
            'name' => 'MariaDB Institute Alpha',
            'slug' => 'mariadb-inst-alpha',
            'status' => 'active',
            'public_assistant_enabled' => false,
            'rag_enabled' => false,
        ]);
        $this->cleanupInstituteIds[] = $instA->id;

        $instB = Institute::on('mysql')->create([
            'name' => 'MariaDB Institute Beta',
            'slug' => 'mariadb-inst-beta',
            'status' => 'active',
            'public_assistant_enabled' => false,
            'rag_enabled' => false,
        ]);
        $this->cleanupInstituteIds[] = $instB->id;

        // 1. Store contacts for Institute A on MariaDB
        SiteSetting::setForInstitute('assistant_public_contact_phone', '+94 77 999 8888', $instA->id, 'assistant_public_contact');
        SiteSetting::setForInstitute('assistant_public_contact_email', 'alpha_maria@test.lk', $instA->id, 'assistant_public_contact');
        SiteSetting::setForInstitute('assistant_public_contact_address', '45 Temple Road, Nallur, Jaffna, Sri Lanka', $instA->id, 'assistant_public_contact');

        // Store contacts for Institute B on MariaDB
        SiteSetting::setForInstitute('assistant_public_contact_phone', '+94 71 222 3333', $instB->id, 'assistant_public_contact');
        SiteSetting::setForInstitute('assistant_public_contact_email', 'beta_maria@test.lk', $instB->id, 'assistant_public_contact');

        // Verify MariaDB isolation
        $this->assertSame('+94 77 999 8888', SiteSetting::getForInstitute('assistant_public_contact_phone', $instA->id));
        $this->assertSame('+94 71 222 3333', SiteSetting::getForInstitute('assistant_public_contact_phone', $instB->id));

        // 2. FAQ creation and unicode text support on MariaDB
        $faq = Faq::on('mysql')->create([
            'institute_id' => $instA->id,
            'question' => 'வகுப்புகள் எப்போது ஆரம்பமாகும்?', // Tamil unicode question
            'answer' => 'வகுப்புகள் திங்கட்கிழமை காலை 8 மணிக்கு ஆரம்பமாகும்.',
            'language' => 'ta',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $fetchedFaq = Faq::on('mysql')->find($faq->id);
        $this->assertNotNull($fetchedFaq);
        $this->assertSame('வகுப்புகள் எப்போது ஆரம்பமாகும்?', $fetchedFaq->question);

        // 3. Shared predicate safety check on MariaDB
        $service = app(PublicAssistantStatusService::class);
        $missingA = $service->getMissingContactFields($instA->id);
        $this->assertEmpty($missingA);

        $missingB = $service->getMissingContactFields($instB->id);
        // Inst B has phone and email, but neither address nor whatsapp? Wait, phone is present, email is present
        $this->assertEmpty($missingB);
    }
}

