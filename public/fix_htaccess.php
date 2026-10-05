<?php
// Script to fix root .htaccess for Livewire/Filament routing
$htaccessPath = '../../../.htaccess'; // Root public_html/.htaccess

$content = <<<EOT
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # 1. Route Livewire assets and updates to backend
    RewriteRule ^livewire/(.*)$ laravel_api/backend/public/livewire/$1 [L]
    
    # 2. Route Filament assets to backend
    RewriteRule ^filament/(.*)$ laravel_api/backend/public/filament/$1 [L]

    # 3. Handle existing React routing (keep your existing rules below this)
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.html [L]
</IfModule>
EOT;

if (file_put_contents($htaccessPath, $content)) {
    echo "SUCCESS: Root .htaccess updated!";
} else {
    echo "ERROR: Could not write to .htaccess. Please check permissions.";
}
