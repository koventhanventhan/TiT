<?php
// MASTER HTACCESS FIX
$htaccessPath = '../../../.htaccess';

$content = <<<EOT
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # 1. Handle Backend (API, Super-Admin, Livewire)
    RewriteCond %{REQUEST_URI} ^/(api|super-admin|livewire) [NC]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ laravel_api/backend/public/index.php [QSA,L]

    # 2. Handle Frontend (React SPA)
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.html [QSA,L]
</IfModule>
EOT;

if (file_put_contents($htaccessPath, $content)) {
    echo "SUCCESS: Master .htaccess updated! Everything will work now.";
} else {
    echo "ERROR: Could not update .htaccess";
}
