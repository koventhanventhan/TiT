<?php
header('Content-Type: text/plain');
$envFile = '../.env';

if (file_exists($envFile)) {
    $content = file_get_contents($envFile);
    // Hide sensitive keys but show URLs and Configs
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
        if (strpos($line, 'APP_URL') === 0 || strpos($line, 'SESSION_') === 0 || strpos($line, 'SANCTUM_') === 0) {
            echo $line . "\n";
        }
    }
} else {
    echo ".env NOT FOUND";
}
