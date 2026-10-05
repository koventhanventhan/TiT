<?php
/**
 * cPanel Setup Script - Upload this to /home/titjaffn/public_html/laravel_api/
 * Then run: php cpanel_setup.php
 * After running, DELETE this file for security!
 */

echo "=== cPanel Setup Script ===\n\n";

// 1. Write correct .htaccess for public_html
$htaccess = <<<'HTACCESS'
# php -- BEGIN cPanel-generated handler, do not edit
# Set the "ea-php84" package as the default "PHP" programming language.
<IfModule mime_module>
  AddHandler application/x-httpd-ea-php84___lsphp .php .php8 .phtml
</IfModule>
# php -- END cPanel-generated handler, do not edit

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Route /api requests to Laravel
    RewriteCond %{REQUEST_URI} ^/api(/.*)?$
    RewriteRule ^api(/.*)?$ laravel_api/public/index.php [QSA,L]

    # Route /sanctum requests to Laravel
    RewriteCond %{REQUEST_URI} ^/sanctum(/.*)?$
    RewriteRule ^sanctum(/.*)?$ laravel_api/public/index.php [QSA,L]

    # Route /admin requests to Laravel (Filament admin panel)
    RewriteCond %{REQUEST_URI} ^/admin(/.*)?$
    RewriteRule ^admin(/.*)?$ laravel_api/public/index.php [QSA,L]

    # Don't rewrite existing files/directories
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d

    # SPA fallback - all other requests go to React
    RewriteRule ^(.*)$ /index.html [L]
</IfModule>
HTACCESS;

$publicHtmlPath = dirname(__DIR__);
$htaccessPath = $publicHtmlPath . '/.htaccess';

if (file_put_contents($htaccessPath, $htaccess)) {
    echo "✅ .htaccess written to: $htaccessPath\n";
} else {
    echo "❌ Failed to write .htaccess to: $htaccessPath\n";
}

// 2. Create api/ directory with index.php proxy (backup approach)
$apiDir = $publicHtmlPath . '/api';
if (!is_dir($apiDir)) {
    mkdir($apiDir, 0755, true);
    echo "✅ Created /api/ directory\n";
}

$apiIndex = <<<'APIINDEX'
<?php
/**
 * API Proxy - Routes all /api/* requests to Laravel
 */

// Set the correct REQUEST_URI for Laravel
$_SERVER['SCRIPT_NAME'] = '/api/index.php';

// Bootstrap Laravel
require __DIR__ . '/../laravel_api/public/index.php';
APIINDEX;

if (file_put_contents($apiDir . '/index.php', $apiIndex)) {
    echo "✅ API index.php proxy written\n";
} else {
    echo "❌ Failed to write API index.php\n";
}

// 3. Create .htaccess inside /api/ directory
$apiHtaccess = <<<'APIHTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /api/
    
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [QSA,L]
</IfModule>
APIHTACCESS;

if (file_put_contents($apiDir . '/.htaccess', $apiHtaccess)) {
    echo "✅ API .htaccess written\n";
} else {
    echo "❌ Failed to write API .htaccess\n";
}

// 4. Verify setup
echo "\n=== Verification ===\n";
echo "public_html path: $publicHtmlPath\n";
echo ".htaccess exists: " . (file_exists($htaccessPath) ? 'YES' : 'NO') . "\n";
echo "api/index.php exists: " . (file_exists($apiDir . '/index.php') ? 'YES' : 'NO') . "\n";
echo "api/.htaccess exists: " . (file_exists($apiDir . '/.htaccess') ? 'YES' : 'NO') . "\n";
echo "Laravel artisan exists: " . (file_exists(__DIR__ . '/artisan') ? 'YES' : 'NO') . "\n";
echo "Laravel .env exists: " . (file_exists(__DIR__ . '/.env') ? 'YES' : 'NO') . "\n";

echo "\n=== .htaccess content ===\n";
echo file_get_contents($htaccessPath);

echo "\n\n🎉 Setup complete! Now test: curl http://162.214.204.205/api/settings\n";
echo "⚠️  DELETE this file after setup: rm cpanel_setup.php\n";
