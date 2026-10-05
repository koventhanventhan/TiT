<?php
// v4 - Robust API fix
define('LARAVEL_START', microtime(true));

$basePath = __DIR__.'/../laravel_api/backend';

if (!file_exists($basePath.'/vendor/autoload.php')) {
    die("Error: Vendor folder not found at $basePath");
}

require $basePath.'/vendor/autoload.php';
$app = require_once $basePath.'/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

$request = \Illuminate\Http\Request::capture();

// Manual URI fix to ensure /api/ prefix matches Laravel routes
$uri = $_SERVER['REQUEST_URI'];
if (strpos($uri, '/api/') !== 0) {
    // If we are hitting https://titjaffna.lk/api/auth/login 
    // we want Laravel to see /api/auth/login
    $request->server->set('REQUEST_URI', '/api' . $uri);
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
