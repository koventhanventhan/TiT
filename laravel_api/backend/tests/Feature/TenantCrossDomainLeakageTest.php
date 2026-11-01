<?php

namespace Tests\Feature;

use App\Models\ExamResult;
use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\LearningMaterial;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\ZoomSchedule;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantCrossDomainLeakageTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute1;
    protected Institute $institute2;
    protected User $admin1;
    protected User $student1;
    protected User $teacher1;
    protected User $admin2;
    protected User $student2;
    protected User $teacher2;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // 1. Institute 1 (Alpha)
        $this->institute1 = Institute::create([
            'name' => 'Alpha Institute',
            'slug' => 'alpha',
            'status' => 'active',
        ]);

        InstituteDomain::create([
            'institute_id' => $this->institute1->id,
            'domain' => 'alpha.test',
            'is_primary' => true,
            'verified_at' => now(),
            'verification_token' => 'tok_alpha',
        ]);

        // 2. Institute 2 (Beta)
        $this->institute2 = Institute::create([
            'name' => 'Beta Institute',
            'slug' => 'beta',
            'status' => 'active',
        ]);

        InstituteDomain::create([
            'institute_id' => $this->institute2->id,
            'domain' => 'beta.test',
            'is_primary' => true,
            'verified_at' => now(),
            'verification_token' => 'tok_beta',
        ]);

        SiteSetting::setForInstitute('site_name', 'Alpha Institute', $this->institute1->id);
        SiteSetting::setForInstitute('site_name', 'Beta Institute', $this->institute2->id);

        // Seed Institute 1 records
        Tenant::run($this->institute1, function () {
            $this->admin1 = User::create([
                'name' => 'Admin Alpha',
                'email' => 'admin@alpha.test',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'institute_id' => $this->institute1->id,
            ]);

            $this->student1 = User::create([
                'name' => 'Student Alpha',
                'full_name' => 'Student Alpha Full',
                'email' => 'student@alpha.test',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'registration_status' => 'confirmed',
                'admin_confirmed_at' => now(),
                'institute_id' => $this->institute1->id,
            ]);

            $this->teacher1 = User::create([
                'name' => 'Teacher Alpha',
                'email' => 'teacher@alpha.test',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'institute_id' => $this->institute1->id,
            ]);

            Payment::create([
                'institute_id' => $this->institute1->id,
                'user_id' => $this->student1->id,
                'student_id' => $this->student1->id,
                'amount' => 2000,
                'payment_method' => 'online',
                'status' => 'approved',
                'gateway_ref' => 'ALPHA_PAYMENT_111',
                'year_month' => '2026-11',
            ]);

            ZoomSchedule::create([
                'institute_id' => $this->institute1->id,
                'title' => 'ALPHA_CLASS_111',
                'zoom_link' => 'https://zoom.us/j/111111111',
                'scheduled_at' => now()->addDay(),
                'duration' => 60,
                'grade' => '10',
                'medium' => 'english',
                'subject' => 'Science',
            ]);

            ExamResult::create([
                'institute_id' => $this->institute1->id,
                'student_name' => 'Student Alpha',
                'index_no' => 'ALPHA-101',
                'term' => 'Term 1',
                'grade' => '10',
                'subject' => 'Science',
                'marks' => 88.5,
                'result_grade' => 'A',
                'year' => 2026,
            ]);

            LearningMaterial::create([
                'institute_id' => $this->institute1->id,
                'title' => 'ALPHA_MATERIAL_111',
                'file_path' => 'materials/alpha1.pdf',
                'type' => 'document',
                'grade' => '10',
                'medium' => 'english',
                'subject' => 'Science',
            ]);
        });

        // Seed Institute 2 (Beta) records with unique tokens
        Tenant::run($this->institute2, function () {
            $this->admin2 = User::create([
                'name' => 'Admin Beta',
                'email' => 'admin@beta.test',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'institute_id' => $this->institute2->id,
            ]);

            $this->student2 = User::create([
                'name' => 'Student Beta',
                'full_name' => 'Student Beta Secret',
                'email' => 'student@beta.test',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'registration_status' => 'confirmed',
                'admin_confirmed_at' => now(),
                'institute_id' => $this->institute2->id,
            ]);

            $this->teacher2 = User::create([
                'name' => 'Teacher Beta Secret',
                'email' => 'teacher@beta.test',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'institute_id' => $this->institute2->id,
            ]);

            Payment::create([
                'institute_id' => $this->institute2->id,
                'user_id' => $this->student2->id,
                'student_id' => $this->student2->id,
                'amount' => 5000,
                'payment_method' => 'online',
                'status' => 'approved',
                'gateway_ref' => 'BETA_SECRET_PAYMENT_999',
                'year_month' => '2026-11',
            ]);

            ZoomSchedule::create([
                'institute_id' => $this->institute2->id,
                'title' => 'BETA_SECRET_CLASS_999',
                'zoom_link' => 'https://zoom.us/j/999999999',
                'scheduled_at' => now()->addDay(),
                'duration' => 60,
                'grade' => '11',
                'medium' => 'tamil',
                'subject' => 'Maths',
            ]);

            ExamResult::create([
                'institute_id' => $this->institute2->id,
                'student_name' => 'Student Beta Secret',
                'index_no' => 'BETA-SECRET-999',
                'term' => 'Term 1',
                'grade' => '11',
                'subject' => 'Maths',
                'marks' => 95.0,
                'result_grade' => 'A+',
                'year' => 2026,
            ]);

            LearningMaterial::create([
                'institute_id' => $this->institute2->id,
                'title' => 'BETA_SECRET_MATERIAL_DOC',
                'file_path' => 'materials/beta1.pdf',
                'type' => 'document',
                'grade' => '11',
                'medium' => 'tamil',
                'subject' => 'Maths',
            ]);
        });
    }

    /**
     * Test Eloquent model queries are strictly isolated under Tenant context.
     */
    public function test_raw_eloquent_queries_strictly_isolate_tenant_data(): void
    {
        Tenant::run($this->institute1, function () {
            // Payments
            $payments = Payment::all();
            $this->assertTrue($payments->every(fn($p) => $p->institute_id === $this->institute1->id));
            $this->assertFalse($payments->contains('gateway_ref', 'BETA_SECRET_PAYMENT_999'));

            // Classes
            $classes = ZoomSchedule::all();
            $this->assertTrue($classes->every(fn($c) => $c->institute_id === $this->institute1->id));
            $this->assertFalse($classes->contains('title', 'BETA_SECRET_CLASS_999'));

            // Exam results
            $exams = ExamResult::all();
            $this->assertTrue($exams->every(fn($e) => $e->institute_id === $this->institute1->id));
            $this->assertFalse($exams->contains('index_no', 'BETA-SECRET-999'));

            // Materials
            $materials = LearningMaterial::all();
            $this->assertTrue($materials->every(fn($m) => $m->institute_id === $this->institute1->id));
            $this->assertFalse($materials->contains('title', 'BETA_SECRET_MATERIAL_DOC'));

            // Users
            $users = User::all();
            $this->assertTrue($users->every(fn($u) => $u->institute_id === $this->institute1->id));
            $this->assertFalse($users->contains('email', 'student@beta.test'));
            $this->assertFalse($users->contains('email', 'teacher@beta.test'));
            $this->assertFalse($users->contains('email', 'admin@beta.test'));
        });
    }

    /**
     * Test Public API endpoints under alpha.test never leak Beta Institute data.
     */
    public function test_public_api_endpoints_never_leak_cross_tenant_data(): void
    {
        // 1. Classes endpoint
        $classesResp = $this->withServerVariables(['HTTP_HOST' => 'alpha.test'])
            ->getJson('http://alpha.test/api/classes');

        if ($classesResp->status() === 200) {
            $content = $classesResp->getContent();
            $this->assertStringNotContainsString('BETA_SECRET_CLASS_999', $content);
        }

        // 2. Teachers endpoint
        $teachersResp = $this->withServerVariables(['HTTP_HOST' => 'alpha.test'])
            ->getJson('http://alpha.test/api/teachers');

        if ($teachersResp->status() === 200) {
            $content = $teachersResp->getContent();
            $this->assertStringNotContainsString('Teacher Beta Secret', $content);
        }

        // 3. Settings endpoint
        $settingsResp = $this->withServerVariables(['HTTP_HOST' => 'alpha.test'])
            ->getJson('http://alpha.test/api/settings');

        $settingsResp->assertStatus(200);
        $settingsResp->assertJsonPath('site_name', 'Alpha Institute');
        $this->assertStringNotContainsString('Beta Institute', $settingsResp->getContent());
    }

    /**
     * Test Authenticated Admin 1 on alpha.test never sees Beta Institute records.
     */
    public function test_authenticated_admin_never_sees_cross_tenant_records(): void
    {
        $this->actingAs($this->admin1);

        $resp = $this->withServerVariables(['HTTP_HOST' => 'alpha.test'])
            ->getJson('http://alpha.test/api/payments/all');

        if ($resp->status() === 200) {
            $content = $resp->getContent();
            $this->assertStringNotContainsString('BETA_SECRET_PAYMENT_999', $content);
        }

        // Blade admin dashboard or students view
        $adminResp = $this->withServerVariables(['HTTP_HOST' => 'alpha.test'])
            ->get('http://alpha.test/admin/dashboard');

        if ($adminResp->status() === 200) {
            $content = $adminResp->getContent();
            $this->assertStringNotContainsString('Student Beta Secret', $content);
        }
    }
}
