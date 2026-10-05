<?php
// Create index.php in public_html/api/ to bootstrap Laravel
$path = '../../../api/index.php';

$content = <<<EOT
<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

define('LARAVEL_START', microtime(true));

// Correct paths for your cPanel setup
require __DIR__.'/../laravel_api/backend/vendor/autoload.php';
\$app = require_once __DIR__.'/../laravel_api/backend/bootstrap/app.php';

\$kernel = \$app->make(Illuminate\Contracts\Http\Kernel::class);

\$response = \$kernel->handle(
    \$request = Illuminate\Http\Request::capture()
);

\$response->send();

\$kernel->terminate(\$request, \$response);
EOT;

if (file_put_contents($path, $content)) {
    echo "SUCCESS: api/index.php created and linked to Laravel!";
} else {
    echo "ERROR: Could not create file.";
}
