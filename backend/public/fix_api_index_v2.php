<?php
// Smarter index.php for api subfolder
define('LARAVEL_START', microtime(true));

require __DIR__.'/../laravel_api/backend/vendor/autoload.php';
$app = require_once __DIR__.'/../laravel_api/backend/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Strip /api from the beginning of the URI if it exists
$request = Illuminate\Http\Request::capture();
$uri = $request->getRequestUri();
if (strpos($uri, '/api/') === 0) {
    $newUri = substr($uri, 4); // Remove '/api'
    $request->server->set('REQUEST_URI', $newUri);
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
