<?php
// Absolute fix for api routing
define('LARAVEL_START', microtime(true));

require __DIR__.'/../laravel_api/backend/vendor/autoload.php';
$app = require_once __DIR__.'/../laravel_api/backend/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();

// Force the request URI to include /api if it's missing, 
// so Laravel's api.php routes can match it.
$uri = $request->server->get('REQUEST_URI');
if (strpos($uri, '/api/') !== 0) {
    $request->server->set('REQUEST_URI', '/api' . $uri);
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
