<?php
// MASTER HTACCESS FIX - VERSION 4 (Laravel Admin + React Student/Teacher)
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

    # 2. Route Laravel Backend ONLY (Admin Blade, API, Super-Panel, Livewire)
    RewriteCond %{REQUEST_URI} ^/(admin|api|super-panel|livewire) [NC]
    RewriteRule ^(.*)$ laravel_api/backend/public/index.php [QSA,L]

    # 3. Everything else (Frontend, Student Dashboard, Teacher Dashboard) goes to React
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.html [L]
</IfModule>
EOT;

if (file_put_contents($htaccessPath, $content)) {
    echo "SUCCESS: Master .htaccess updated! Student/Teacher Dashboards moved back to React.";
} else {
    echo "ERROR: Could not update .htaccess";
}
