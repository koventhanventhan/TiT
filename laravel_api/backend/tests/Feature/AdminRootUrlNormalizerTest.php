<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdminRootUrlNormalizerTest extends TestCase
{
    public function test_route_url_normalizes_when_request_has_script_prefix(): void
    {
        $server = [
            'SCRIPT_NAME' => '/laravel_api/backend/public/index.php',
            'SCRIPT_FILENAME' => '/var/www/html/laravel_api/backend/public/index.php',
            'PHP_SELF' => '/laravel_api/backend/public/index.php',
            'REQUEST_URI' => '/laravel_api/backend/public/admin/students',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
        ];

        $request = Request::create(
            'http://localhost/laravel_api/backend/public/admin/students',
            'GET',
            [],
            [],
            [],
            $server
        );

        $this->app->instance('request', $request);

        // Trigger the provider boot with this request active
        (new AppServiceProvider($this->app))->boot();

        $generated = route('admin.students.index');
        $this->assertEquals('http://localhost/admin/students', $generated);
    }
}
