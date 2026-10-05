<?php
// MASTER HTACCESS FIX - VERSION 2
$htaccessPath = '../../../.htaccess';

$content = <<<EOT
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # 1. If it's a real file or folder in the root (like assets, images), serve it
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # 2. Route API and Admin requests to Laravel Backend
    RewriteRule ^(api|super-admin|livewire)(.*)$ laravel_api/backend/public/index.php [QSA,L]

    # 3. Everything else goes to React index.html
    RewriteRule ^ index.html [L]
</IfModule>
EOT;

if (file_put_contents($htaccessPath, $content)) {
    echo "SUCCESS: Master .htaccess updated to V2! This will fix the API 404.";
} else {
    echo "ERROR: Could not update .htaccess";
}
