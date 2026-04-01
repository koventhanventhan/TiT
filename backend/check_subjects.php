<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'koventhanventhhaoon153@gmail.com';
$user = App\Models\User::where('email', $email)->first();

echo "User: " . ($user ? $user->email : "NOT FOUND") . "\n";
echo "Raw Subjects: " . ($user ? $user->selected_subjects : "N/A") . "\n";

if ($user && $user->selected_subjects) {
    try {
        $subjects = $user->selected_subjects;
        if (str_starts_with($subjects, '[')) {
            $selectedSubjects = json_decode($subjects, true);
        } else {
            $selectedSubjects = array_map('trim', explode(',', $subjects));
        }
        
        echo "Parsed Subjects: " . json_encode($selectedSubjects) . "\n";
        
        foreach ($selectedSubjects as $name) {
            $s = App\Models\Subject::where('name', $name)->first();
            if ($s) {
                echo "FOUND: $name -> Price: $s->price\n";
            } else {
                echo "NOT FOUND: $name\n";
                // Try case-insensitive manually
                $s2 = App\Models\Subject::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                if ($s2) {
                    echo "  MATCH FOUND with LOWER(): $s2->name -> Price: $s2->price\n";
                }
            }
        }
        
        $subjectData = App\Models\Subject::whereIn('name', $selectedSubjects)->get(['name', 'price']);
        echo "whereIn count: " . $subjectData->count() . "\n";
        echo "whereIn total: " . $subjectData->sum('price') . "\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
