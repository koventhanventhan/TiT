<?php
// MASTER HTACCESS FIX
$htaccessPath = '../../../.htaccess';

$content = <<<EOT
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # 1. Handle API requests
    RewriteCond %{REQUEST_URI} ^/api/ [OR]
    RewriteCond %{REQUEST_URI} ^/super-admin [OR]
    RewriteCond %{REQUEST_URI} ^/livewire/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ laravel_api/backend/public/index.php [L]

    # 2. Handle Frontend (React)
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.html [L]
</IfModule>
EOT;

if (file_put_contents($htaccessPath, $content)) {
    echo "SUCCESS: Master .htaccess updated! Everything will work now.";
} else {
    echo "ERROR: Could not update .htaccess";
}
