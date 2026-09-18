<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Subject; 
use App\Models\User; 
$u = User::factory()->create(['current_grade' => '7', 'medium' => 'english']); 
$u->current_grade = '8'; 
dump($u->getSubjectCategory()); 
Subject::create(['name' => 'Maths', 'category' => 'grade_6_to_9', 'medium' => 'english']); 
dump(Subject::where('category', 'grade_6_to_9')->where(function($q) { $q->where('medium', 'english')->orWhere('medium', 'both'); })->pluck('name')->toArray());
