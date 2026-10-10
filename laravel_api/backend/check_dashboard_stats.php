<?php
require 'c:/xampp/htdocs/New folder/laravel_api/backend/vendor/autoload.php';
$app = require_once 'c:/xampp/htdocs/New folder/laravel_api/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$totalStudents = \App\Models\User::where('role', 'user')->whereNotNull('full_name')->count();
$totalTeachers = \App\Models\User::where('role', 'teacher')->count();
$totalCourses = \App\Models\LearningMaterial::count();
$activeClassesToday = \App\Models\ZoomSchedule::whereDate('scheduled_at', now()->toDateString())->count();
$totalRevenue = \App\Models\Payment::where('status', 'paid')->sum('amount');
$pendingApprovals = \App\Models\User::where('role', 'user')->whereNull('admin_confirmed_at')->count();
$pendingPayments = \App\Models\Payment::where('status', 'pending')->count();

echo json_encode(compact('totalStudents', 'totalTeachers', 'totalCourses', 'activeClassesToday', 'totalRevenue', 'pendingApprovals', 'pendingPayments'));
