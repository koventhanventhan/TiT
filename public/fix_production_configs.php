<?php
// Config Fixer for Production
$sanctumConfig = '../config/sanctum.php';
$corsConfig = '../config/cors.php';

// Fix Sanctum Domains
if (file_exists($sanctumConfig)) {
    $content = file_get_contents($sanctumConfig);
    $content = str_replace("'localhost:3000'", "'localhost:3000', 'titjaffna.lk', 'www.titjaffna.lk'", $content);
    file_put_contents($sanctumConfig, $content);
    echo "Sanctum config updated.\n";
}

// Fix CORS
if (file_exists($corsConfig)) {
    $content = file_get_contents($corsConfig);
    $content = str_replace("'allowed_origins' => [*]", "'allowed_origins' => ['https://titjaffna.lk', 'https://www.titjaffna.lk']", $content);
    file_put_contents($corsConfig, $content);
    echo "CORS config updated.\n";
}

echo "SUCCESS: Configs aligned with production domain!";
