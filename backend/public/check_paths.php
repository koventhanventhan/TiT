<?php
// v7 - FINAL API FIX
$apiPath = '../../../api/index.php';
$absoluteBackendPath = '/home/titjaffn/public_html/laravel_api/backend';

$content = <<<EOT
<?php
define('LARAVEL_START', microtime(true));
\$basePath = '$absoluteBackendPath';

require \$basePath.'/vendor/autoload.php';
\$app = require_once \$basePath.'/bootstrap/app.php';

\$kernel = \$app->make(\Illuminate\Contracts\Http\Kernel::class);

// v7 - Global \$_SERVER fix BEFORE capture
if (strpos(\$_SERVER['REQUEST_URI'], '/api/') !== 0) {
    \$_SERVER['REQUEST_URI'] = '/api' . \$_SERVER['REQUEST_URI'];
}
\$_SERVER['REQUEST_URI'] = str_replace('//', '/', \$_SERVER['REQUEST_URI']);

\$request = \Illuminate\Http\Request::capture();
\$response = \$kernel->handle(\$request);
\$response->send();
\$kernel->terminate(\$request, \$response);
EOT;

if (file_put_contents($apiPath, $content)) {
    echo "SUCCESS: api/index.php fixed with v7 logic!";
} else {
    echo "ERROR: Could not update file.";
}
