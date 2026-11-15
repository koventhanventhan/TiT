<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAssistantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $instA;
    protected Institute $instB;
    protected User $adminA;
    protected User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instA = Institute::create([
            'name' => 'Institute Alpha',
            'slug' => 'institute-alpha',
            'status' => 'active',
            'public_assistant_enabled' => false,
            'rag_enabled' => false,
        ]);

        $this->instB = Institute::create([
            'name' => 'Institute Beta',
            'slug' => 'institute-beta',
            'status' => 'active',
            'public_assistant_enabled' => false,
            'rag_enabled' => false,
        ]);

        $this->adminA = User::factory()->create([
            'name' => 'Admin Alpha',
            'email' => 'admin@alpha.lk',
            'role' => 'admin',
            'institute_id' => $this->instA->id,
        ]);

        $this->adminB = User::factory()->create([
            'name' => 'Admin Beta',
            'email' => 'admin@beta.lk',
            'role' => 'admin',
            'institute_id' => $this->instB->id,
        ]);
    }

    /**
     * Admin of Institute B cannot read or change Institute A's contact details.
     */
    public function test_admin_b_cannot_read_or_overwrite_admin_a_contact_settings(): void
    {
        // 1. Seed Institute A contact details
        SiteSetting::setForInstitute('assistant_public_contact_phone', '+94 77 111 1111', $this->instA->id, 'assistant_public_contact');
        SiteSetting::setForInstitute('assistant_public_contact_email', 'alpha@inst.lk', $this->instA->id, 'assistant_public_contact');
        SiteSetting::setForInstitute('assistant_public_contact_address', '10 Alpha Lane', $this->instA->id, 'assistant_public_contact');

        // Verify Institute B sees empty contacts initially
        $this->actingAs($this->adminB, 'web');
        $viewRes = $this->get('/admin/assistant');
        $viewRes->assertOk();
        $viewRes->assertDontSee('+94 77 111 1111');
        $viewRes->assertDontSee('alpha@inst.lk');
        $viewRes->assertDontSee('10 Alpha Lane');

        // Verify via API endpoint as well
        $apiGetRes = $this->actingAs($this->adminB, 'sanctum')->getJson('/api/admin/rag/contact-settings');
        $apiGetRes->assertOk();
        $this->assertSame('', $apiGetRes->json('phone'));
        $this->assertSame('', $apiGetRes->json('email'));
        $this->assertSame('', $apiGetRes->json('address'));

        // 2. Admin B submits their own contact details
        $this->actingAs($this->adminB, 'web');
        $postRes = $this->post('/admin/assistant/contacts', [
            'phone' => '+94 77 222 2222',
            'email' => 'beta@inst.lk',
            'address' => '20 Beta Road',
        ]);
        $postRes->assertSessionHasNoErrors();

        // 3. Verify Institute A's contacts are 100% untouched
        $this->assertSame('+94 77 111 1111', SiteSetting::getForInstitute('assistant_public_contact_phone', $this->instA->id));
        $this->assertSame('alpha@inst.lk', SiteSetting::getForInstitute('assistant_public_contact_email', $this->instA->id));
        $this->assertSame('10 Alpha Lane', SiteSetting::getForInstitute('assistant_public_contact_address', $this->instA->id));

        // 4. Verify Institute B's contacts are saved under B's institute_id
        $this->assertSame('+94 77 222 2222', SiteSetting::getForInstitute('assistant_public_contact_phone', $this->instB->id));
        $this->assertSame('beta@inst.lk', SiteSetting::getForInstitute('assistant_public_contact_email', $this->instB->id));
        $this->assertSame('20 Beta Road', SiteSetting::getForInstitute('assistant_public_contact_address', $this->instB->id));
    }

    /**
     * Admin of Institute B cannot read, update or delete Institute A's FAQs.
     */
    public function test_admin_b_cannot_read_update_or_delete_institute_a_faqs(): void
    {
        $faqA = Faq::create([
            'institute_id' => $this->instA->id,
            'question' => 'How to apply for Alpha classes?',
            'answer' => 'Visit alpha.lk/apply.',
            'language' => 'en',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // 1. Admin B does not see Alpha's FAQ on the Blade page
        $this->actingAs($this->adminB, 'web');
        $bladeRes = $this->get('/admin/assistant');
        $bladeRes->assertOk();
        $bladeRes->assertDontSee('How to apply for Alpha classes?');

        // 2. Admin B does not see Alpha's FAQ in API listing
        $apiList = $this->actingAs($this->adminB, 'sanctum')->getJson('/api/admin/faqs');
        $apiList->assertOk();
        $this->assertEmpty($apiList->json('data'));

        // 3. Admin B cannot update Alpha's FAQ by guessing id (Blade)
        $this->actingAs($this->adminB, 'web');
        $updateRes = $this->put("/admin/assistant/faqs/{$faqA->id}", [
            'question' => 'Hacked Question?',
            'answer' => 'Hacked Answer',
            'language' => 'en',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $updateRes->assertNotFound();
        $this->assertSame('How to apply for Alpha classes?', $faqA->fresh()->question);

        // 4. Admin B cannot update Alpha's FAQ by guessing id (API)
        $apiUpdate = $this->actingAs($this->adminB, 'sanctum')->putJson("/api/admin/faqs/{$faqA->id}", [
            'question' => 'Hacked via API?',
            'answer' => 'Hacked answer via API',
        ]);
        $apiUpdate->assertNotFound();
        $this->assertSame('How to apply for Alpha classes?', $faqA->fresh()->question);

        // 5. Admin B cannot delete Alpha's FAQ by guessing id (Blade)
        $deleteRes = $this->delete("/admin/assistant/faqs/{$faqA->id}");
        $deleteRes->assertNotFound();
        $this->assertDatabaseHas('faqs', ['id' => $faqA->id]);

        // 6. Admin B cannot delete Alpha's FAQ by guessing id (API)
        $apiDelete = $this->actingAs($this->adminB, 'sanctum')->deleteJson("/api/admin/faqs/{$faqA->id}");
        $apiDelete->assertNotFound();
        $this->assertDatabaseHas('faqs', ['id' => $faqA->id]);
    }

    /**
     * Admin of Institute B cannot re-index Institute A's learning material.
     */
    public function test_admin_b_cannot_reindex_institute_a_learning_material(): void
    {
        $matA = LearningMaterial::create([
            'institute_id' => $this->instA->id,
            'title' => 'Alpha Physics Revision',
            'type' => 'note',
            'file_path' => 'materials/alpha_phys.pdf',
        ]);
        $matA->updateQuietly(['rag_status' => 'failed', 'rag_error' => 'OCR failure']);

        // 1. Admin B does not see Alpha's failed material on the Blade page
        $this->actingAs($this->adminB, 'web');
        $bladeRes = $this->get('/admin/assistant');
        $bladeRes->assertOk();
        $bladeRes->assertDontSee('Alpha Physics Revision');

        // 2. Admin B cannot trigger re-indexing by guessing ID (Blade)
        $reindexBladeRes = $this->post("/admin/assistant/materials/{$matA->id}/reindex");
        $reindexBladeRes->assertNotFound();

        // 3. Admin B cannot trigger re-indexing by guessing ID (API)
        $apiReindex = $this->actingAs($this->adminB, 'sanctum')->postJson("/api/admin/rag/materials/{$matA->id}/reindex");
        $apiReindex->assertNotFound();

        // Verify status remains failed and was not queued
        $this->assertSame('failed', $matA->fresh()->rag_status);
    }

    /**
     * Proves SiteSetting::get and SiteSetting::set are strictly tenant-isolated.
     */
    public function test_site_setting_get_and_set_isolation_between_institutes(): void
    {
        $this->actingAs($this->adminA, 'web');
        SiteSetting::set('test_setting_key', 'Value For Alpha', 'general');

        $this->actingAs($this->adminB, 'web');
        SiteSetting::set('test_setting_key', 'Value For Beta', 'general');

        // When authenticated as admin A, SiteSetting::get returns Alpha value
        $this->actingAs($this->adminA, 'web');
        $this->assertSame('Value For Alpha', SiteSetting::get('test_setting_key'));

        // When authenticated as admin B, SiteSetting::get returns Beta value
        $this->actingAs($this->adminB, 'web');
        $this->assertSame('Value For Beta', SiteSetting::get('test_setting_key'));
    }
}

