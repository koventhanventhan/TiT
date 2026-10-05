<?php
header('Content-Type: text/plain');
$jsFile = '../../../assets/index-C1DpLfW1.js';

if (file_exists($jsFile)) {
    $content = file_get_contents($jsFile);
    echo "Checking for 'localhost' in JS...\n";
    if (strpos($content, 'localhost') !== false) {
        echo "FOUND! Your JS still contains 'localhost'. You must rebuild your React app with the correct API URL.\n";
        // Show context
        preg_match_all('/http:\/\/localhost[^\s"\']+/', $content, $matches);
        print_r($matches[0]);
    } else {
        echo "No 'localhost' found. Good.\n";
    }
    
    echo "\nChecking for 'api' base URL...\n";
    // Find things like /api/auth/login to see if they are relative
    if (strpos($content, '/api/auth/login') !== false) {
        echo "Found relative API paths. This should work if the base URL is correct.\n";
    }
} else {
    echo "JS file not found.";
}
