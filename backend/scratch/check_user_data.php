<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Student;
use App\Models\Payment;

$email = 'niro46869@gmail.com';

echo "=== 1. USERS TABLE CHECK ===\n";
$users = DB::table('users')->where('email', $email)->orWhere('name', $email)->get();
if ($users->isEmpty()) {
    echo "No user found with email/username: $email\n";
    exit;
}

foreach ($users as $u) {
    echo "ID: {$u->id}\n";
    echo "Email: {$u->email}\n";
    echo "Username: {$u->name}\n";
    echo "Full Name: " . ($u->full_name ?? 'NULL') . "\n";
    echo "Phone: " . ($u->phone_number ?? 'NULL') . "\n";
    echo "Status: " . ($u->registration_status ?? 'NULL') . "\n";
    echo "Created At: {$u->created_at}\n";
    echo "-------------------------\n";
}

$userIds = $users->pluck('id')->toArray();

echo "\n=== 2. STUDENTS TABLE CHECK ===\n";
$students = DB::table('students')->whereIn('user_id', $userIds)->get();
if ($students->isEmpty()) {
    echo "No students found for this user.\n";
} else {
    foreach ($students as $s) {
        echo "ID: {$s->id} (User ID: {$s->user_id})\n";
        echo "Full Name: {$s->full_name}\n";
        echo "Grade: {$s->current_grade}\n";
        echo "Medium: {$s->medium}\n";
        echo "Subjects: {$s->selected_subjects}\n";
        echo "Created At: {$s->created_at}\n";
        echo "-------------------------\n";
    }
}

echo "\n=== 3. PAYMENTS TABLE CHECK ===\n";
$payments = DB::table('payments')->whereIn('user_id', $userIds)->get();
if ($payments->isEmpty()) {
    echo "No payments found for this user.\n";
} else {
    foreach ($payments as $p) {
        echo "ID: {$p->id} (User ID: {$p->user_id})\n";
        echo "Amount: {$p->amount}\n";
        echo "Status: {$p->status}\n";
        echo "Method: {$p->payment_method}\n";
        echo "Gateway Ref: {$p->gateway_ref}\n";
        echo "Created At: {$p->created_at}\n";
        echo "-------------------------\n";
    }
}

echo "\n=== 4. ORPHAN CHECK (Students without Parent matching name) ===\n";
$orphanStudents = DB::table('students')->where('full_name', 'like', '%niro%')->whereNotIn('user_id', $userIds)->get();
if ($orphanStudents->isNotEmpty()) {
    foreach ($orphanStudents as $os) {
         echo "Found potential orphan student: ID {$os->id}, Name: {$os->full_name}, User ID: {$os->user_id}\n";
    }
} else {
    echo "No obvious orphan student records found matching name.\n";
}
