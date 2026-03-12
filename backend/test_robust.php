<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $user = \App\Models\User::find(120);
    $zoom = \App\Models\ZoomSchedule::find(14); // ICT Grade 7
    
    echo "---TESTING_ROBUST_MATCH---\n";
    if ($user && $zoom) {
        $selected = $user->selected_subjects;
        $selectedArr = is_array($selected) ? $selected : (json_decode($selected, true) ?: explode(',', (string)$selected));
        
        $classSub = trim($zoom->subject);
        echo "CLASS_SUB: [" . $classSub . "]\n";
        echo "STUDENT_SUBS: " . json_encode($selectedArr, JSON_UNESCAPED_UNICODE) . "\n";
        
        $match = false;
        foreach ($selectedArr as $s) {
            $s = trim($s);
            // Robust match: exact OR contains
            if ($s === $classSub || stripos($s, $classSub) !== false || stripos($classSub, $s) !== false) {
                echo "MATCH FOUND: [" . $s . "] with [" . $classSub . "]\n";
                $match = true;
                break;
            }
        }
        echo "FINAL_RESULT: " . ($match ? 'SUCCESS' : 'FAILURE') . "\n";
    }
    echo "---END_TEST---\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
