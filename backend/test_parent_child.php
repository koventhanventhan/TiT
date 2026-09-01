<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use App\Services\WhatsAppService;

echo "=== TEST 1: Monthly Fee Breakdown ===\n";
// Setup Parent
$parent = User::create([
    'name' => 'Test Parent',
    'username' => 'test_parent_' . time(),
    'full_name' => 'Test Parent',
    'email' => 'parent' . time() . '@test.com',
    'password' => Hash::make('password'),
    'phone_number' => '071234567' . rand(0, 9),
    'role' => 'user'
]);

$student1 = Student::create([
    'user_id' => $parent->id,
    'full_name' => 'Arun',
    'current_grade' => 'Grade 10',
    'selected_subjects' => json_encode(['Maths'])
]);

$student2 = Student::create([
    'user_id' => $parent->id,
    'full_name' => 'Karthik',
    'current_grade' => 'Grade 11',
    'selected_subjects' => json_encode(['Science'])
]);

echo "Parent created: {$parent->email}\n";
echo "Child 1: {$student1->full_name}\n";
echo "Child 2: {$student2->full_name}\n";

$breakdown = $parent->getMonthlyFeeBreakdown();
echo "Breakdown Array:\n";
print_r($breakdown);

echo "\n=== TEST 2: Link Sibling Account ===\n";
// Create another user to act as an old sibling account
$oldSibling = User::create([
    'name' => 'Karthik Old',
    'username' => 'karthik_old_' . time(),
    'full_name' => 'Karthik Old',
    'email' => 'karthik' . time() . '@old.com',
    'password' => Hash::make('oldpassword'),
    'phone_number' => '0799999999',
    'role' => 'user',
    'selected_subjects' => json_encode(['English'])
]);
$oldStudent = Student::create([
    'user_id' => $oldSibling->id,
    'full_name' => 'Karthik Old',
    'current_grade' => 'Grade 10',
    'selected_subjects' => json_encode(['English'])
]);

echo "Old Sibling Created: {$oldSibling->email}\n";

// Link operation
$oldUser = User::where('email', $oldSibling->email)->first();
if ($oldUser && Hash::check('oldpassword', $oldUser->password)) {
    // 1. Move students
    Student::where('user_id', $oldUser->id)->update(['user_id' => $parent->id]);
    
    // 2. Disable old login
    $oldUser->email = 'disabled_' . time() . '_' . $oldUser->email;
    $oldUser->phone_number = 'disabled_' . time() . '_' . $oldUser->phone_number;
    $oldUser->password = Hash::make(bin2hex(random_bytes(16))); // Randomize password
    $oldUser->deactivated_at = now();
    $oldUser->save();
    echo "Successfully linked sibling.\n";
}

$parentStudents = Student::where('user_id', $parent->id)->get();
echo "Parent now has " . $parentStudents->count() . " children.\n";

echo "\n=== TEST 3: Notifications Message Formatting ===\n";
echo "Simulating CheckPayments Command Message:\n";
$subjectNames = $parentStudents->pluck('full_name')->toArray();
$childNamesStr = count($subjectNames) > 0 ? implode(', ', $subjectNames) : 'your child';
$message = "Dear {$parent->full_name},\n\nThis is a gentle reminder that the monthly fee for {$childNamesStr} is due.\nPlease make the payment to ensure uninterrupted access to classes.\n\nThank you,\nTitans Team";
echo $message . "\n";

// Clean up
$parent->delete();
$oldSibling->delete();
$student1->delete();
$student2->delete();
$oldStudent->delete();

echo "\nTests Completed.\n";
