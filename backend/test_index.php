<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::where('email', 'uduyyyymy@gmail.com')->first();
$controller = new \App\Http\Controllers\Api\StudentZoomController(new \App\Services\WhatsAppService());
$request = new \Illuminate\Http\Request();
$request->setUserResolver(function() use ($user) { return $user; });

echo "--- TESTING INDEX METHOD ---\n";
try {
    $response = $controller->index($request);
    echo "Staus Code: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}
echo "--- END TEST ---\n";
