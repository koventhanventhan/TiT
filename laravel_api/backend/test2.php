<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('role', 'user')->whereNotNull('parent_id')->first();
if ($user) {
    // Replicate getPaymentDetails logic
    $subjects = $user->selected_subjects;
    $selectedSubjects = [];
    if (is_array($subjects)) {
        $selectedSubjects = $subjects;
    } elseif (is_string($subjects)) {
        $trimmedSubjects = trim($subjects);
        try {
            if (str_starts_with($trimmedSubjects, '[')) {
                $selectedSubjects = json_decode($trimmedSubjects, true) ?? [];
            } else {
                $selectedSubjects = array_map('trim', explode(',', $trimmedSubjects));
            }
        } catch (\Exception $e) {
            $selectedSubjects = array_map('trim', explode(',', $subjects));
        }
    }
    
    // IF the decoded JSON was actually another string (double decode):
    if (is_string($selectedSubjects)) {
        echo "IT WAS A STRING!\n";
        $selectedSubjects = json_decode($selectedSubjects, true);
    }
    
    $selectedSubjects = array_unique(array_filter($selectedSubjects));
    var_dump($selectedSubjects);
    
    $category = $user->getSubjectCategory();
    $subjectData = \App\Models\Subject::when($category, function($query) use ($category) {
        return $query->where('category', $category);
    })->whereIn('name', $selectedSubjects)->get(['name', 'price']);
    
    echo "SUBJECT DATA COUNT: " . $subjectData->count() . "\n";
    if ($subjectData->count() == 0) {
        echo "No subjects matched. Category: $category\n";
    }
} else {
    echo 'No sibling found';
}
