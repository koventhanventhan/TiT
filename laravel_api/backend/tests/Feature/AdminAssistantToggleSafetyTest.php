<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AdminAssistantToggleSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'name' => 'Safety Institute',
            'slug' => 'safety-inst',
            'status' => 'active',
            'public_assistant_enabled' => false,
            'rag_enabled' => false,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Safety Admin',
            'email' => 'admin@safety.lk',
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        InstituteIntegration::withoutGlobalScopes()->updateOrCreate(
            ['institute_id' => $this->institute->id, 'provider' => InstituteIntegration::PROVIDER_GEMINI],
            ['credentials' => ['api_key' => 'AIzaSySAFETY_GEMINI_KEY'], 'status' => InstituteIntegration::STATUS_CONFIGURED]
        );
    }

    /**
     * Turning ON with missing phone and WhatsApp is refused with exact missing item.
     */
    public function test_turning_on_with_missing_phone_and_whatsapp_is_refused_with_exact_missing_item(): void
    {
        // Provide only email/address; phone & whatsapp are completely empty
        SiteSetting::setForInstitute('assistant_public_contact_email', 'help@safety.lk', $this->institute->id, 'assistant_public_contact');
        SiteSetting::setForInstitute('assistant_public_contact_address', '10 Safe Rd', $this->institute->id, 'assistant_public_contact');
        SiteSetting::withoutGlobalScopes()->where('institute_id', $this->institute->id)->whereIn('key', [
            'assistant_public_contact_phone',
            'assistant_public_contact_whatsapp',
        ])->delete();

        // 1. Blade toggle POST
        $this->actingAs($this->admin, 'web');
        $bladeRes = $this->post('/admin/assistant/toggle-public', ['enabled' => 1]);
        $bladeRes->assertSessionHas('error');
        $this->assertStringContainsString('phone or WhatsApp', session('error'));
        $this->assertFalse((bool) $this->institute->fresh()->public_assistant_enabled);

        // 2. API toggle POST
        $apiRes = $this->actingAs($this->admin, 'sanctum')->postJson('/api/admin/rag/toggle-assistant', [
            'public_assistant_enabled' => true,
        ]);
        $apiRes->assertStatus(422);
        $this->assertStringContainsString('phone or WhatsApp', $apiRes->json('message'));
        $this->assertFalse((bool) $this->institute->fresh()->public_assistant_enabled);

        // 3. Artisan command
        $code = Artisan::call('rag:public', ['id' => $this->institute->id, 'state' => 'on']);
        $this->assertSame(1, $code);
        $this->assertStringContainsString('phone or WhatsApp', Artisan::output());
        $this->assertFalse((bool) $this->institute->fresh()->public_assistant_enabled);
    }

    /**
     * Turning ON with missing email and address is refused with exact missing item.
     */
    public function test_turning_on_with_missing_email_and_address_is_refused_with_exact_missing_item(): void
    {
        // Provide phone; email & address are empty
        SiteSetting::setForInstitute('assistant_public_contact_phone', '+94 77 111 2222', $this->institute->id, 'assistant_public_contact');
        SiteSetting::withoutGlobalScopes()->where('institute_id', $this->institute->id)->whereIn('key', [
            'assistant_public_contact_email',
            'assistant_public_contact_address',
        ])->delete();

        $this->actingAs($this->admin, 'web');
        $bladeRes = $this->post('/admin/assistant/toggle-public', ['enabled' => 1]);
        $bladeRes->assertSessionHas('error');
        $this->assertStringContainsString('email or address', session('error'));
        $this->assertFalse((bool) $this->institute->fresh()->public_assistant_enabled);
    }

    /**
     * In production environment, missing reCAPTCHA is refused with exact missing item.
     */
    public function test_turning_on_without_recaptcha_in_production_is_refused(): void
    {
        config(['app.env' => 'production']);
        InstituteIntegration::withoutGlobalScopes()->where('institute_id', $this->institute->id)->where('provider', InstituteIntegration::PROVIDER_RECAPTCHA)->delete();

        // Configure contacts so only reCAPTCHA fails
        SiteSetting::setForInstitute('assistant_public_contact_phone', '+94 77 111 2222', $this->institute->id, 'assistant_public_contact');
        SiteSetting::setForInstitute('assistant_public_contact_email', 'contact@safety.lk', $this->institute->id, 'assistant_public_contact');

        $this->actingAs($this->admin, 'web');
        $bladeRes = $this->post('/admin/assistant/toggle-public', ['enabled' => 1]);
        $bladeRes->assertSessionHas('error');
        $this->assertStringContainsString('Google reCAPTCHA', session('error'));
        $this->assertFalse((bool) $this->institute->fresh()->public_assistant_enabled);
    }

    /**
     * Turning OFF always works without refusal and writes an ActivityLog entry.
     */
    public function test_turning_off_always_works_and_logs_activity(): void
    {
        $this->institute->update(['public_assistant_enabled' => true]);
        $this->assertTrue((bool) $this->institute->fresh()->public_assistant_enabled);

        // Turn OFF via Blade
        $this->actingAs($this->admin, 'web');
        $res = $this->post('/admin/assistant/toggle-public', ['enabled' => 0]);
        $res->assertSessionHas('success');
        $this->assertFalse((bool) $this->institute->fresh()->public_assistant_enabled);

        // Verify ActivityLog entry was written
        $log = ActivityLog::where('institute_id', $this->institute->id)
            ->where('action', 'assistant_public_toggle')
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('off', $log->metadata['state']);
    }
}

