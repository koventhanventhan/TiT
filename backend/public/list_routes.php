<?php
// Route Lister
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;

echo "<h2>API Routes List</h2>";
echo "<table border='1'><tr><th>Method</th><th>URI</th><th>Name</th></tr>";

foreach (Route::getRoutes() as $route) {
    if (str_contains($route->uri(), 'api')) {
        echo "<tr>";
        echo "<td>" . implode('|', $route->methods()) . "</td>";
        echo "<td>" . $route->uri() . "</td>";
        echo "<td>" . $route->getName() . "</td>";
        echo "</tr>";
    }
}
echo "</table>";
