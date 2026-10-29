<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HtaccessDocumentationTest extends TestCase
{
    public function test_public_web_routes_first_segments_match_documented_htaccess(): void
    {
        // 1. Locate and read docs/DEPLOYMENT.md
        $docPath = base_path('../../docs/DEPLOYMENT.md');
        if (!file_exists($docPath)) {
            $docPath = base_path('../docs/DEPLOYMENT.md');
        }
        $this->assertFileExists($docPath, 'docs/DEPLOYMENT.md must exist.');

        $docContent = file_get_contents($docPath);

        // 2. Extract RewriteCond %{REQUEST_URI} line and regex
        $this->assertMatchesRegularExpression(
            '/RewriteCond\s+%{REQUEST_URI}\s+([^\s]+)/',
            $docContent,
            'docs/DEPLOYMENT.md must document the RewriteCond %{REQUEST_URI} regex.'
        );

        preg_match('/RewriteCond\s+%{REQUEST_URI}\s+([^\s]+)/', $docContent, $matches);
        $regexPattern = $matches[1];

        // Ensure receipts is documented
        $this->assertStringContainsString('receipts', $regexPattern, 'Documented .htaccess regex must include receipts.');

        // 3. Read routes/web.php and find their registered routes in the router
        $webPhpPath = base_path('routes/web.php');
        $this->assertFileExists($webPhpPath);
        $webPhpContent = file_get_contents($webPhpPath);

        // Extract route URI definitions from routes/web.php
        preg_match_all("/Route::(?:get|post|put|patch|delete|any|match)\s*\(\s*['\"]([^'\"]+)['\"]/i", $webPhpContent, $uriMatches);
        $definedUris = $uriMatches[1] ?? [];
        $this->assertNotEmpty($definedUris, 'routes/web.php should contain route definitions.');

        $routerRoutes = Route::getRoutes();
        $testedSegments = [];

        foreach ($definedUris as $definedUri) {
            $normalizedUri = trim($definedUri, '/');
            if ($normalizedUri === '') {
                // Skip root '/'
                continue;
            }

            // Find matching route in Route::getRoutes()
            $matchingRoute = null;
            foreach ($routerRoutes as $route) {
                if (trim($route->uri(), '/') === $normalizedUri) {
                    $matchingRoute = $route;
                    break;
                }
            }

            $this->assertNotNull($matchingRoute, "Route '{$definedUri}' from routes/web.php must be registered in the router.");

            // Verify route belongs to web middleware group
            $middleware = $matchingRoute->gatherMiddleware();
            $this->assertTrue(
                in_array('web', $middleware, true),
                "Route '{$definedUri}' must belong to the 'web' middleware group."
            );

            // Take the first path segment
            $segments = explode('/', $normalizedUri);
            $firstSegment = $segments[0];
            $testedSegments[$firstSegment] = $definedUri;

            // Assert segment matches the documented .htaccess regex
            $path = '/' . $firstSegment;
            $matched = preg_match('#' . $regexPattern . '#i', $path);

            $this->assertEquals(
                1,
                $matched,
                "First path segment '{$firstSegment}' from web route '{$definedUri}' is missing from documented .htaccess regex [{$regexPattern}] in docs/DEPLOYMENT.md."
            );
        }

        $this->assertArrayHasKey('receipts', $testedSegments, "Expected 'receipts' route to be evaluated.");
        $this->assertArrayHasKey('sitemap.xml', $testedSegments, "Expected 'sitemap.xml' route to be evaluated.");
    }
}
