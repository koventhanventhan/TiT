<?php

require __DIR__ . '/../laravel_api/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../laravel_api/backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Models\RagFaq;
use App\Models\RagMaterial;
use App\Models\RagQueryLog;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

$action = $argv[1] ?? 'status';

$inst = Institute::firstOrCreate(['id' => 1], [
    'name' => 'TiT Primary Institute',
    'slug' => 'tit',
    'status' => 'active',
]);

switch ($action) {
    case 'get_token':
        $user = User::withoutGlobalScopes()->where('role', 'admin')->where('institute_id', 1)->first();
        if (!$user) {
            $user = User::withoutGlobalScopes()->where('role', 'admin')->first();
        }
        echo $user ? $user->createToken('admin-prompt30')->plainTextToken : '';
        break;

    case 'setup_base':
        // Seed some FAQs
        RagFaq::updateOrCreate(
            ['institute_id' => 1, 'question' => 'How can I enroll in a course?'],
            ['answer' => 'You can enroll online through our student registration portal or visit our admissions office.', 'sort_order' => 1, 'is_active' => true]
        );
        RagFaq::updateOrCreate(
            ['institute_id' => 1, 'question' => 'What payment methods do you accept?'],
            ['answer' => 'We accept PayHere online payments, bank transfers, and direct cash payments at our counter.', 'sort_order' => 2, 'is_active' => true]
        );
        RagFaq::updateOrCreate(
            ['institute_id' => 1, 'question' => 'Where can I see class schedules?'],
            ['answer' => 'Class schedules are updated weekly on the student dashboard under the Timetable section.', 'sort_order' => 3, 'is_active' => true]
        );

        // Seed some materials
        RagMaterial::updateOrCreate(
            ['institute_id' => 1, 'title' => 'Biology Syllabus 2026'],
            ['status' => 'ready', 'material_type' => 'pdf', 'file_path' => 'syllabus.pdf']
        );
        RagMaterial::updateOrCreate(
            ['institute_id' => 1, 'title' => 'Chemistry Past Papers Part 1'],
            ['status' => 'ready', 'material_type' => 'pdf', 'file_path' => 'past_papers.pdf']
        );
        RagMaterial::updateOrCreate(
            ['institute_id' => 1, 'title' => 'Physics Mechanics Quick Notes'],
            ['status' => 'failed', 'material_type' => 'pdf', 'file_path' => 'mechanics.pdf', 'failure_reason' => 'OCR parsing timed out. Please click Re-index.']
        );

        // Seed query logs
        for ($i = 0; $i < 5; $i++) {
            RagQueryLog::create([
                'institute_id' => 1,
                'query' => 'What are the chemistry class hours?',
                'had_notes_found' => true,
                'is_thumbs_down' => false,
                'is_safety_blocked' => false,
                'created_at' => now()->subDays(rand(0, 5)),
            ]);
        }

        echo "Base seed applied.\n";
        break;

    case 'state_blocked_gemini':
        // Remove Gemini integration
        InstituteIntegration::withoutGlobalScopes()->where('institute_id', 1)->where('service', 'gemini')->delete();
        SiteSetting::setForInstitute('assistant_public_contact_phone', '+94 11 234 5678', 1, 'general');
        SiteSetting::setForInstitute('assistant_public_contact_email', 'info@tit.lk', 1, 'general');
        $inst->update(['public_assistant_enabled' => false]);
        Cache::flush();
        echo "State: blocked_gemini\n";
        break;

    case 'state_blocked_contacts':
        // Ensure Gemini is present but contacts are empty
        InstituteIntegration::withoutGlobalScopes()->updateOrCreate(
            ['institute_id' => 1, 'service' => 'gemini'],
            ['credentials' => ['api_key' => 'AIzaSyFakeKeyForUiCaptureOnly12345'], 'is_active' => true]
        );
        InstituteIntegration::withoutGlobalScopes()->updateOrCreate(
            ['institute_id' => 1, 'service' => 'recaptcha'],
            ['credentials' => ['site_key' => '6LeIxacZAAAAAFakeKey', 'secret_key' => '6LeIxacZAAAAAFakeSecret'], 'is_active' => true]
        );
        SiteSetting::setForInstitute('assistant_public_contact_phone', '', 1, 'general');
        SiteSetting::setForInstitute('assistant_public_contact_whatsapp', '', 1, 'general');
        SiteSetting::setForInstitute('assistant_public_contact_email', '', 1, 'general');
        SiteSetting::setForInstitute('assistant_public_contact_address', '', 1, 'general');
        $inst->update(['public_assistant_enabled' => false]);
        Cache::flush();
        echo "State: blocked_contacts\n";
        break;

    case 'state_blocked_switch_off':
        // Contacts present, Gemini present, recaptcha present, but institute switch is OFF
        InstituteIntegration::withoutGlobalScopes()->updateOrCreate(
            ['institute_id' => 1, 'service' => 'gemini'],
            ['credentials' => ['api_key' => 'AIzaSyFakeKeyForUiCaptureOnly12345'], 'is_active' => true]
        );
        InstituteIntegration::withoutGlobalScopes()->updateOrCreate(
            ['institute_id' => 1, 'service' => 'recaptcha'],
            ['credentials' => ['site_key' => '6LeIxacZAAAAAFakeKey', 'secret_key' => '6LeIxacZAAAAAFakeSecret'], 'is_active' => true]
        );
        SiteSetting::setForInstitute('assistant_public_contact_phone', '+94 11 234 5678', 1, 'general');
        SiteSetting::setForInstitute('assistant_public_contact_whatsapp', '+94 77 123 4567', 1, 'general');
        SiteSetting::setForInstitute('assistant_public_contact_email', 'info@tit.lk', 1, 'general');
        SiteSetting::setForInstitute('assistant_public_contact_address', '123 Galle Road, Colombo 03', 1, 'general');
        SiteSetting::setForInstitute('assistant_public_contact_hours', 'Mon - Sat: 8:00 AM - 6:00 PM', 1, 'general');
        $inst->update(['public_assistant_enabled' => false]);
        Cache::flush();
        echo "State: blocked_switch_off\n";
        break;

    case 'state_live':
        // Everything configured and institute switch is ON
        InstituteIntegration::withoutGlobalScopes()->updateOrCreate(
            ['institute_id' => 1, 'service' => 'gemini'],
            ['credentials' => ['api_key' => 'AIzaSyFakeKeyForUiCaptureOnly12345'], 'is_active' => true]
        );
        InstituteIntegration::withoutGlobalScopes()->updateOrCreate(
            ['institute_id' => 1, 'service' => 'recaptcha'],
            ['credentials' => ['site_key' => '6LeIxacZAAAAAFakeKey', 'secret_key' => '6LeIxacZAAAAAFakeSecret'], 'is_active' => true]
        );
        SiteSetting::setForInstitute('assistant_public_contact_phone', '+94 11 234 5678', 1, 'general');
        SiteSetting::setForInstitute('assistant_public_contact_whatsapp', '+94 77 123 4567', 1, 'general');
        SiteSetting::setForInstitute('assistant_public_contact_email', 'info@tit.lk', 1, 'general');
        SiteSetting::setForInstitute('assistant_public_contact_address', '123 Galle Road, Colombo 03', 1, 'general');
        SiteSetting::setForInstitute('assistant_public_contact_hours', 'Mon - Sat: 8:00 AM - 6:00 PM', 1, 'general');
        $inst->update(['public_assistant_enabled' => true]);
        Cache::flush();
        echo "State: live\n";
        break;
}

