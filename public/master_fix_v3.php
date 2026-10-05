<?php
// MASTER HTACCESS FIX - VERSION 3 (Laravel Blade + React Frontend)
$htaccessPath = '../../../.htaccess';

$content = <<<EOT
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # 1. Real files/folders
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # 2. Route Laravel Blade Pages (Admin, Student, Teacher, API, Super-Admin)
    RewriteCond %{REQUEST_URI} ^/(admin|student|teacher|api|super-admin|livewire|super-panel) [NC]
    RewriteRule ^(.*)$ laravel_api/backend/public/index.php [QSA,L]

    # 3. Everything else (Frontend) goes to React
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.html [L]
</IfModule>
EOT;

if (file_put_contents($htaccessPath, $content)) {
    echo "SUCCESS: Master .htaccess updated for Laravel Dashboards!";
} else {
    echo "ERROR: Could not update .htaccess";
}
