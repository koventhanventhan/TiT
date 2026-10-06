<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = \Illuminate\Http\Request::create('/api/payment/details', 'GET');
$user = \App\Models\User::where('role', 'user')->whereNotNull('parent_id')->first();
$request->setUserResolver(function() use ($user) { return $user; });

$controller = $app->make(\App\Http\Controllers\RegistrationController::class);
$response = $controller->getPaymentDetails($request);
echo $response->getContent();
