<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Student;
use App\Models\ZoomSchedule;
use App\Services\NotificationService;
use Carbon\Carbon;

echo "--- 1. Testing Legacy Data Migration ---\n";
$legacyUser = User::where('email', 'karthik@legacy.com')->first();
echo "Legacy User ID: {$legacyUser->id}, Name: {$legacyUser->full_name}\n";
echo "Legacy User Students Count: " . $legacyUser->students()->count() . "\n";
$legacyStudent = $legacyUser->students()->first();
echo "Migrated Student Name: {$legacyStudent->full_name}, Grade: {$legacyStudent->current_grade}\n";
echo "----------------------------------------\n\n";

echo "--- 2. Testing Dummy Parent + 2 Children & Breakdown ---\n";
// Create a Dummy Parent
$parent = User::create([
    'name' => 'parent_test2',
    'email' => 'parent2@test.com',
    'password' => \Hash::make('password123'),
    'role' => 'user',
    'full_name' => 'Test Parent',
    'phone_number' => '0779998888',
    'registration_status' => 'approved',
]);

// Create 2 children
$child1 = $parent->students()->create([
    'full_name' => 'Child Arun',
    'first_name' => 'Arun',
    'current_grade' => 'Grade 5',
    'selected_subjects' => 'Maths',
]);

$child2 = $parent->students()->create([
    'full_name' => 'Child Karthik',
    'first_name' => 'Karthik',
    'current_grade' => 'Grade 4',
    'selected_subjects' => 'Science',
]);

echo "Created Parent ID {$parent->id} with 2 children.\n";
// The fee calculation uses Subject prices. Let's create dummy subjects.
\App\Models\Subject::firstOrCreate(['name' => 'Maths', 'price' => 500]);
\App\Models\Subject::firstOrCreate(['name' => 'Science', 'price' => 600]);

$breakdown = $parent->getMonthlyFeeBreakdown();
echo "Total Fee: " . $breakdown['total'] . "\n";
echo "Breakdown Array:\n";
print_r($breakdown['breakdown']);
echo "----------------------------------------\n\n";

echo "--- 3. Testing SendZoomReminders ---\n";
// Create a ZoomSchedule for Grade 5 Maths
$schedule = ZoomSchedule::create([
    'title' => 'Maths Revision Class',
    'grade' => 'Grade 5',
    'subject' => 'Maths',
    'scheduled_at' => Carbon::now()->addMinutes(10), 'zoom_link' => 'https://zoom.us/j/123456789', 'zoom_meeting_id' => '123456789', // Starts in 10 mins
]);

echo "Created Zoom Schedule ID {$schedule->id} for Grade 5 Maths.\n";

// We can just call the handle method logic directly or run the artisan command.
echo "Running php artisan zoom:send-reminders...\n";
$exitCode = \Artisan::call('zoom:send-reminders');
echo \Artisan::output();
echo "----------------------------------------\n\n";

echo "--- 4. Testing 'Link Sibling Account' Backend Logic ---\n";
// Simulate merging legacy_karthik into parent_test2
echo "Moving legacy_karthik's student record to parent_test2...\n";
$legacyStudent->update(['user_id' => $parent->id]);

// Deactivate old legacy login
$legacyUser->update(['deactivated_at' => now(), 'email' => 'merged_' . $legacyUser->email]);
echo "Old Legacy User Deactivated At: " . $legacyUser->deactivated_at . "\n";
echo "Parent's updated children count: " . $parent->students()->count() . "\n";

$breakdownAfterMerge = $parent->getMonthlyFeeBreakdown();
echo "Total Fee After Merge: " . $breakdownAfterMerge['total'] . "\n";
print_r($breakdownAfterMerge['breakdown']);
echo "----------------------------------------\n\n";
