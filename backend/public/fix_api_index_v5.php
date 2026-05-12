<?php
// v5 - Absolute path fix for api routing
$apiPath = '../../../api/index.php';
$absoluteBackendPath = '/home/titjaffn/public_html/laravel_api/backend';

$content = <<<EOT
<?php
define('LARAVEL_START', microtime(true));

\$basePath = '$absoluteBackendPath';

if (!file_exists(\$basePath.'/vendor/autoload.php')) {
    die("Error: Vendor folder not found at \$basePath");
}

require \$basePath.'/vendor/autoload.php';
\$app = require_once \$basePath.'/bootstrap/app.php';

\$kernel = \$app->make(\Illuminate\Contracts\Http\Kernel::class);

\$request = \Illuminate\Http\Request::capture();

// Manual URI fix to ensure /api/ prefix matches Laravel routes
\$uri = \$_SERVER['REQUEST_URI'];
if (strpos(\$uri, '/api/') !== 0) {
    \$request->server->set('REQUEST_URI', '/api' . \$uri);
}

\$response = \$kernel->handle(\$request);
\$response->send();
\$kernel->terminate(\$request, \$response);
EOT;

if (file_put_contents($apiPath, $content)) {
    echo "SUCCESS: api/index.php updated with absolute paths!";
} else {
    echo "ERROR: Could not update file.";
}
