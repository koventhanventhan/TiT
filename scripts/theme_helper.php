<?php
// Ensure we always connect to the actual local database, even if invoked from phpunit testing environment
putenv('APP_ENV=local');
unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
putenv('DB_CONNECTION=mysql');
unset($_ENV['DB_CONNECTION'], $_SERVER['DB_CONNECTION']);
putenv('DB_DATABASE');
unset($_ENV['DB_DATABASE'], $_SERVER['DB_DATABASE']);

require __DIR__ . '/../laravel_api/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../laravel_api/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$action = $argv[1] ?? '';

if ($action === 'get_token') {
    $user = App\Models\User::where('role', 'admin')->first();
    echo $user->createToken('admin-crawler')->plainTextToken;
    exit(0);
}

if ($action === 'set_preset') {
    $preset = $argv[2] ?? 'royal';
    App\Models\SiteSetting::setForInstitute('theme_preset', $preset, 1, 'appearance');
    Illuminate\Support\Facades\Cache::forget('site_settings_1');
    Illuminate\Support\Facades\Cache::forget('site_settings_all');
    echo "Preset set to {$preset}";
    exit(0);
}

if ($action === 'get_student_id') {
    $student = App\Models\User::where('role', 'user')->first();
    echo $student ? $student->id : 'none';
    exit(0);
}

if ($action === 'seed_crawler_data') {
    if (App\Models\Subject::count() < 2) {
        App\Models\Subject::firstOrCreate(['name' => 'Mathematics', 'category' => 'grade_6_to_11'], ['price' => 600]);
        App\Models\Subject::firstOrCreate(['name' => 'Science', 'category' => 'grade_6_to_11'], ['price' => 650]);
    }
    if (App\Models\Package::count() < 2) {
        App\Models\Package::firstOrCreate(['name' => 'Grade 10 Full Package'], [
            'category' => 'grade_6_to_11',
            'medium' => 'tamil',
            'package_price' => 2500,
            'original_price' => 3000,
            'applicable_grades' => ['10'],
            'is_active' => true,
        ]);
        App\Models\Package::firstOrCreate(['name' => 'Grade 11 Full Package'], [
            'category' => 'grade_6_to_11',
            'medium' => 'tamil',
            'package_price' => 2800,
            'original_price' => 3200,
            'applicable_grades' => ['11'],
            'is_active' => true,
        ]);
    }
    $studentId = 363;
    $paidPayment = App\Models\Payment::where('user_id', $studentId)->where('status', 'paid')->first();
    if (!$paidPayment) {
        App\Models\Payment::create([
            'user_id' => $studentId,
            'amount' => 1500,
            'status' => 'paid',
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'TXN_TEST_1001',
            'paid_at' => now(),
            'year_month' => '2026-03',
            'institute_id' => 1,
        ]);
    }
    $pendingPayment = App\Models\Payment::where('user_id', $studentId)->where('status', 'pending')->first();
    if (!$pendingPayment) {
        App\Models\Payment::create([
            'user_id' => $studentId,
            'amount' => 1500,
            'status' => 'pending',
            'payment_method' => 'cash',
            'year_month' => '2026-04',
            'institute_id' => 1,
        ]);
    }
    echo "Crawler data seeded";
    exit(0);
}

if ($action === 'get_super_admin') {
    $inst1 = App\Models\Institute::firstOrCreate(['id' => 1], [
        'name' => 'TiT Education',
        'slug' => 'tit',
        'status' => 'active',
    ]);

    $sa = App\Models\User::withoutGlobalScopes()
        ->where('role', 'super_admin')
        ->where('institute_id', 1)
        ->whereNull('deleted_at')
        ->first();
    if (!$sa) {
        $sa = App\Models\User::withoutEvents(static function () {
            return App\Models\User::withoutGlobalScopes()->create([
                'name' => 'Super Administrator',
                'email' => 'superadmin_e2e@tit.lk',
                'password' => Illuminate\Support\Facades\Hash::make('SuperAdmin123!'),
                'role' => 'super_admin',
                'institute_id' => 1,
            ]);
        });
    } else {
        App\Models\User::withoutEvents(static function () use ($sa) {
            $sa->update([
                'password' => Illuminate\Support\Facades\Hash::make('SuperAdmin123!'),
            ]);
        });
    }

    $token = $sa->createToken('sa-e2e')->plainTextToken;
    $check = Illuminate\Support\Facades\Hash::check('SuperAdmin123!', $sa->password);
    $attempt = Illuminate\Support\Facades\Auth::attempt(['email' => $sa->email, 'password' => 'SuperAdmin123!']);
    echo json_encode(['email' => $sa->email, 'password' => 'SuperAdmin123!', 'token' => $token, 'hash_ok' => $check, 'attempt_ok' => $attempt, 'role' => $sa->role, 'inst' => $sa->institute_id]);
    exit(0);
}

if ($action === 'setup_e2e_institutes') {
    // 1. Ensure Institute 1 has past expires_at to prove it never expires
    $inst1 = App\Models\Institute::find(1);
    if ($inst1) {
        $inst1->update([
            'expires_at' => now()->subDays(10),
            'status' => 'active',
        ]);
    }

    // 2. Setup Institute B (Horizon Academy) with 1-student limit plan
    $plan1Slot = App\Models\SubscriptionPlan::firstOrCreate(['slug' => 'starter-1slot'], [
        'name' => 'Starter Tier (1 Student)',
        'monthly_price' => 2000,
        'yearly_price' => 20000,
        'max_students' => 1,
        'max_teachers' => 2,
        'features' => ['zoom'],
    ]);
    $plan1Slot->update(['max_students' => 1]);

    $instB = App\Models\Institute::firstOrCreate(['slug' => 'horizon'], [
        'name' => 'Horizon Academy',
        'status' => 'active',
        'subscription_plan_id' => $plan1Slot->id,
        'expires_at' => now()->addDays(30),
    ]);
    $instB->update([
        'subscription_plan_id' => $plan1Slot->id,
        'status' => 'active',
        'expires_at' => now()->addDays(30),
    ]);

    // Ensure domain exists for Institute B
    App\Models\InstituteDomain::firstOrCreate(['domain' => 'horizon.localhost'], [
        'institute_id' => $instB->id,
        'is_primary' => true,
        'verified_at' => now(),
    ]);

    // Create / update Admin for Institute B
    $adminB = App\Models\User::withoutGlobalScopes()
        ->where('institute_id', $instB->id)
        ->where('role', 'admin')
        ->first();
    if (!$adminB) {
        $adminB = App\Models\User::withoutGlobalScopes()->create([
            'name' => 'Horizon Admin',
            'email' => 'admin@horizon.localhost',
            'password' => Illuminate\Support\Facades\Hash::make('AdminPass123!'),
            'role' => 'admin',
            'institute_id' => $instB->id,
        ]);
    }

    // Clean up any test students under Inst B so it has exactly 1 free slot (0 used / 1 limit)
    App\Models\User::withoutGlobalScopes()
        ->where('institute_id', $instB->id)
        ->where('role', 'user')
        ->forceDelete();

    $tokenB = $adminB->createToken('admin-b')->plainTextToken;

    echo json_encode([
        'instB_id' => $instB->id,
        'adminB_token' => $tokenB,
        'adminB_email' => $adminB->email,
    ]);
    exit(0);
}

if ($action === 'set_inst_b_grace') {
    $instB = App\Models\Institute::where('slug', 'horizon')->first();
    if ($instB) {
        // Expired yesterday -> inside grace period (grace is 7 days)
        $instB->update([
            'expires_at' => now()->subDay(),
            'status' => 'active',
        ]);
        echo "Institute B set to grace period (expired yesterday)";
    }
    exit(0);
}

if ($action === 'set_inst_b_expired') {
    $instB = App\Models\Institute::where('slug', 'horizon')->first();
    if ($instB) {
        // Expired 10 days ago -> beyond grace period (7 days)
        $instB->update([
            'expires_at' => now()->subDays(10),
            'status' => 'expired',
        ]);
        echo "Institute B set to expired beyond grace (10 days ago)";
    }
    exit(0);
}

if ($action === 'renew_inst_b') {
    $instB = App\Models\Institute::where('slug', 'horizon')->first();
    if ($instB) {
        $instB->renewSubscription(['duration' => '30_days']);
        echo json_encode([
            'status' => $instB->status,
            'expires_at' => $instB->expires_at->toDateTimeString(),
        ]);
    }
    exit(0);
}

echo "Unknown action";
exit(1);
