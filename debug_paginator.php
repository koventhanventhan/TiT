<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$paginator = new \Illuminate\Pagination\LengthAwarePaginator(
    [], 
    205, 
    15, 
    10, 
    ['path' => 'http://127.0.0.1:8000/admin/attendance']
);

$elements = $paginator->elements();
echo json_encode($elements, JSON_PRETTY_PRINT);
