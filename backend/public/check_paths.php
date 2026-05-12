<?php
// Re-using check_paths.php to deliver the FINAL fix
$apiPath = '../../../api/index.php';
$absoluteBackendPath = '/home/titjaffn/public_html/laravel_api/backend';

$content = <<<EOT
<?php
define('LARAVEL_START', microtime(true));

\$basePath = '$absoluteBackendPath';

require \$basePath.'/vendor/autoload.php';
\$app = require_once \$basePath.'/bootstrap/app.php';

\$kernel = \$app->make(\Illuminate\Contracts\Http\Kernel::class);

\$request = \Illuminate\Http\Request::capture();

// v6 - Smart URI fix
\$uri = \$_SERVER['REQUEST_URI'];
if (strpos(\$uri, '/api/') !== 0) {
    \$uri = '/api' . \$uri;
}

// Ensure there's no double /api/api/
\$uri = str_replace('/api/api/', '/api/', \$uri);
\$request->server->set('REQUEST_URI', \$uri);

\$response = \$kernel->handle(\$request);
\$response->send();
\$kernel->terminate(\$request, \$response);
EOT;

if (file_put_contents($apiPath, $content)) {
    echo "SUCCESS: api/index.php updated via check_paths.php!";
} else {
    echo "ERROR: Could not update file.";
}

