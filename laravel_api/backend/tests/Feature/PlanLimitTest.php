<?php

namespace Tests\Feature;

use App\Filament\SuperAdmin\Resources\PlanResource\Pages\ListPlans;
use App\Models\Institute;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PlanLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_minus_one_and_null_all_mean_unlimited_and_positive_is_capped(): void
    {
        $planZero = new SubscriptionPlan(['max_students' => 0, 'max_teachers' => 0]);
        $this->assertTrue($planZero->isUnlimited('max_students'));
        $this->assertTrue($planZero->isUnlimited('max_teachers'));
        $this->assertNull($planZero->limitFor('max_students'));
        $this->assertNull($planZero->limitFor('max_teachers'));
        $this->assertSame('Unlimited', $planZero->formattedLimit('max_students'));

        $planMinusOne = new SubscriptionPlan(['max_students' => -1, 'max_teachers' => -1]);
        $this->assertTrue($planMinusOne->isUnlimited('max_students'));
        $this->assertTrue($planMinusOne->isUnlimited('max_teachers'));
        $this->assertNull($planMinusOne->limitFor('max_students'));
        $this->assertNull($planMinusOne->limitFor('max_teachers'));
        $this->assertSame('Unlimited', $planMinusOne->formattedLimit('max_teachers'));

        $planNull = new SubscriptionPlan(['max_students' => null, 'max_teachers' => null]);
        $this->assertTrue($planNull->isUnlimited('max_students'));
        $this->assertTrue($planNull->isUnlimited('max_teachers'));
        $this->assertNull($planNull->limitFor('max_students'));
        $this->assertNull($planNull->limitFor('max_teachers'));

        $planCapped = new SubscriptionPlan(['max_students' => 2, 'max_teachers' => 5]);
        $this->assertFalse($planCapped->isUnlimited('max_students'));
        $this->assertFalse($planCapped->isUnlimited('max_teachers'));
        $this->assertSame(2, $planCapped->limitFor('max_students'));
        $this->assertSame(5, $planCapped->limitFor('max_teachers'));
        $this->assertSame('2', $planCapped->formattedLimit('max_students'));
    }

    public function test_plan_resource_table_shows_unlimited_for_both_zero_and_minus_one(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('super-admin'));

        $root = Institute::create(['name' => 'Root', 'slug' => 'root', 'status' => 'active']);
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Super Admin',
            'email' => 'sa@platform.lk',
            'password' => Hash::make('SecretPass123!'),
            'role' => 'super_admin',
            'institute_id' => $root->id,
        ]);

        $planZero = SubscriptionPlan::create([
            'name' => 'Zero Unlimited',
            'slug' => 'zero-unlimited',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'max_students' => 0,
            'max_teachers' => 0,
        ]);

        $planLegacy = SubscriptionPlan::create([
            'name' => 'Legacy Unlimited',
            'slug' => 'legacy-unlimited',
            'monthly_price' => 9000,
            'yearly_price' => 90000,
            'max_students' => -1,
            'max_teachers' => -1,
        ]);

        $this->actingAs($superAdmin);

        Livewire::test(ListPlans::class)
            ->assertCanSeeTableRecords([$planZero, $planLegacy])
            ->assertSee('Unlimited');
    }

    public function test_unlimited_plans_zero_and_minus_one_do_not_block_teachers_or_students(): void
    {
        Mail::fake();
        Notification::fake();

        foreach ([0, -1] as $idx => $sentinel) {
            $plan = SubscriptionPlan::create([
                'name' => "Unlimited {$idx}",
                'slug' => "unlimited-{$idx}",
                'monthly_price' => 5000,
                'yearly_price' => 50000,
                'max_students' => $sentinel,
                'max_teachers' => $sentinel,
                'features' => ['live_classes'],
            ]);

            $inst = Institute::create([
                'name' => "Inst {$idx}",
                'slug' => "inst-{$idx}",
                'status' => 'active',
                'subscription_plan_id' => $plan->id,
            ]);

            $this->assertTrue(SubscriptionService::hasFeature($inst, 'live_classes'));

            $admin = User::withoutGlobalScopes()->create([
                'name' => "Admin {$idx}",
                'email' => "admin{$idx}@inst.lk",
                'password' => Hash::make('AdminPass123!'),
                'role' => 'admin',
                'institute_id' => $inst->id,
            ]);

            // TeacherController@store succeeds (previously 0 and -1 blocked ALL teachers!)
            $teacherRes = $this->actingAs($admin, 'web')
                ->withHeaders(['X-Institute-Id' => (string) $inst->id])
                ->post(route('admin.teachers.store'), [
                    'name' => "Teacher {$idx}",
                    'email' => "teacher{$idx}@inst.lk",
                    'password' => 'TeacherPass123!',
                    'phone_number' => "077100000{$idx}",
                    'teacher_class' => 'Mathematics',
                ]);
            $teacherRes->assertRedirect();
            $teacherRes->assertSessionHasNoErrors();
            $this->assertDatabaseHas('users', [
                'email' => "teacher{$idx}@inst.lk",
                'institute_id' => $inst->id,
                'role' => 'teacher',
            ]);

            // AuthController@register succeeds
            $studentEmail = "student{$idx}@inst.lk";
            Cache::put('email_verified_' . $studentEmail, true, 600);
            $regRes = $this->withHeaders(['X-Institute-Id' => (string) $inst->id])
                ->postJson('/api/auth/register', [
                    'email' => $studentEmail,
                    'password' => 'StudentPass1!',
                    'password_confirmation' => 'StudentPass1!',
                ]);
            $regRes->assertStatus(201);
        }
    }

    public function test_limit_two_allows_two_and_blocks_third_while_ignoring_other_institutes(): void
    {
        Mail::fake();
        Notification::fake();

        $cappedPlan = SubscriptionPlan::create([
            'name' => 'Micro Plan',
            'slug' => 'micro-plan',
            'monthly_price' => 1500,
            'yearly_price' => 15000,
            'max_students' => 2,
            'max_teachers' => 2,
        ]);

        $instA = Institute::create([
            'name' => 'Institute Alpha',
            'slug' => 'institute-alpha',
            'status' => 'active',
            'subscription_plan_id' => $cappedPlan->id,
        ]);

        $instB = Institute::create([
            'name' => 'Institute Beta',
            'slug' => 'institute-beta',
            'status' => 'active',
            'subscription_plan_id' => $cappedPlan->id,
        ]);

        // Seed 5 students and 5 teachers in Institute B — must NOT count against Institute A!
        for ($i = 1; $i <= 5; $i++) {
            User::withoutGlobalScopes()->create([
                'name' => "Beta Student {$i}",
                'email' => "b_stu_{$i}@beta.lk",
                'password' => Hash::make('Pass1234!'),
                'role' => 'user',
                'institute_id' => $instB->id,
            ]);
            User::withoutGlobalScopes()->create([
                'name' => "Beta Teacher {$i}",
                'email' => "b_tch_{$i}@beta.lk",
                'password' => Hash::make('Pass1234!'),
                'role' => 'teacher',
                'institute_id' => $instB->id,
            ]);
        }

        $adminA = User::withoutGlobalScopes()->create([
            'name' => 'Alpha Admin',
            'email' => 'admin@alpha.lk',
            'password' => Hash::make('AdminPass123!'),
            'role' => 'admin',
            'institute_id' => $instA->id,
        ]);

        // Add 2 teachers in A -> allowed
        for ($t = 1; $t <= 2; $t++) {
            $res = $this->actingAs($adminA, 'web')->post(route('admin.teachers.store'), [
                'name' => "Alpha Teacher {$t}",
                'email' => "a_tch_{$t}@alpha.lk",
                'password' => 'TeacherPass123!',
                'phone_number' => "077200000{$t}",
            ]);
            $res->assertSessionMissing('error');
            $this->assertDatabaseHas('users', ['email' => "a_tch_{$t}@alpha.lk"]);
        }

        // 3rd teacher in A -> blocked with clear limit message
        $res3rdTeacher = $this->actingAs($adminA, 'web')->post(route('admin.teachers.store'), [
            'name' => 'Alpha Teacher 3',
            'email' => 'a_tch_3@alpha.lk',
            'password' => 'TeacherPass123!',
            'phone_number' => '0772000003',
        ]);
        $res3rdTeacher->assertSessionHas('error');
        $this->assertStringContainsString('Teacher limit (2) reached', (string) session('error'));
        $this->assertDatabaseMissing('users', ['email' => 'a_tch_3@alpha.lk']);

        // Add 2 students in A via /api/auth/register -> allowed
        for ($s = 1; $s <= 2; $s++) {
            $email = "a_stu_{$s}@alpha.lk";
            Cache::put('email_verified_' . $email, true, 600);
            $res = $this->withHeaders(['X-Institute-Id' => (string) $instA->id])
                ->postJson('/api/auth/register', [
                    'email' => $email,
                    'password' => 'StudentPass1!',
                    'password_confirmation' => 'StudentPass1!',
                ]);
            $res->assertStatus(201);
        }

        // 3rd student in A via /api/auth/register -> blocked with 403 & clear message
        Cache::put('email_verified_a_stu_3@alpha.lk', true, 600);
        $res3rdStudent = $this->withHeaders(['X-Institute-Id' => (string) $instA->id])
            ->postJson('/api/auth/register', [
                'email' => 'a_stu_3@alpha.lk',
                'password' => 'StudentPass1!',
                'password_confirmation' => 'StudentPass1!',
            ]);
        $res3rdStudent->assertStatus(403);
        $this->assertStringContainsString('Student limit (2) reached', (string) $res3rdStudent->json('message'));
        $this->assertDatabaseMissing('users', ['email' => 'a_stu_3@alpha.lk']);

        // 3rd student in A via RegistrationController@step1 -> also blocked with 403 & clear message
        Cache::put('email_verified_a_stu_step1@alpha.lk', true, 600);
        $resStep1 = $this->withHeaders(['X-Institute-Id' => (string) $instA->id])
            ->postJson('/api/register/step1', [
                'username' => 'a_stu_step1@alpha.lk',
                'full_name' => 'Blocked Student',
                'phone_number' => '0773000003',
                'date_of_birth' => '2010-05-05',
                'gender' => 'male',
                'school_name' => 'Alpha High',
                'medium' => 'english',
                'online_experience' => true,
                'device_used' => 'laptop',
                'current_grade' => 'Grade 10',
                'selected_subjects' => 'Mathematics',
            ]);
        $resStep1->assertStatus(403);
        $this->assertStringContainsString('Student limit (2) reached', (string) $resStep1->json('message'));
    }
}

