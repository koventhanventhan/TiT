<?php require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$result = App\Models\ExamResult::first();
echo "INDEX: '" . $result->index_no . "'\n";
echo "TERM: '" . $result->term . "'\n";
echo "GRADE: '" . $result->grade . "'\n";
