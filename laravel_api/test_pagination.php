<?php

use Illuminate\Support\Facades\Request;

require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::create(
        '/admin/attendance', 'GET', ['page' => 10]
    )
);

$content = $response->getContent();

// Extract the pagination container
preg_match('/<div class="pagination-footer.*?<\/div>/s', $content, $matches);
if (isset($matches[0])) {
    echo "PAGINATION EXTRACTED:\n";
    echo $matches[0];
} else {
    echo "PAGINATION NOT FOUND.\n";
    // echo $content;
}

$kernel->terminate($request, $response);
