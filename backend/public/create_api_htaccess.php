<?php
// Create .htaccess in public_html/api/ to allow static files
$path = '../../../api/.htaccess';

$content = <<<EOT
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # 1. If the file or folder exists, serve it directly
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # 2. Otherwise, send everything to index.php (Laravel)
    RewriteRule ^ index.php [L]
</IfModule>
EOT;

if (file_put_contents($path, $content)) {
    echo "SUCCESS: api/.htaccess created!";
} else {
    echo "ERROR: Could not create file.";
}
