<?php

use Illuminate\Support\Facades\Request;

require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$paginator = new \Illuminate\Pagination\LengthAwarePaginator(
    [], 205, 15, 10, ['path' => 'http://127.0.0.1:8000/admin/attendance']
);

$view = view('vendor.pagination.custom', ['paginator' => $paginator, 'elements' => [
    [1 => 'url1', 2 => 'url2'],
    '...',
    [7 => 'url7', 8 => 'url8', 9 => 'url9', 10 => 'url10', 11 => 'url11', 12 => 'url12', 13 => 'url13', 14 => 'url14']
]])->render();

echo $view;
