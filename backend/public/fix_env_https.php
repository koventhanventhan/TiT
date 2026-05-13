<?php
// Fix .env file HTTP to HTTPS
$envPath = '../.env';
if (file_exists($envPath)) {
    $content = file_get_contents($envPath);
    
    // Replace http with https for your domain
    $content = str_replace('http://titjaffna.lk', 'https://titjaffna.lk', $content);
    
    // Also make sure session is secure
    if (strpos($content, 'SESSION_SECURE_COOKIE') === false) {
        $content .= "\nSESSION_SECURE_COOKIE=true\n";
    } else {
        $content = str_replace('SESSION_SECURE_COOKIE=false', 'SESSION_SECURE_COOKIE=true', $content);
    }

    if (file_put_contents($envPath, $content)) {
        echo "SUCCESS: .env updated to HTTPS!";
    } else {
        echo "ERROR: Could not update .env";
    }
} else {
    echo ".env not found";
}
