<?php
// Test important API endpoints
$endpoints = [
    'api/settings',
    'api/subjects/prices'
];

foreach ($endpoints as $ep) {
    $url = 'https://titjaffna.lk/' . $ep;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Endpoint: $ep\n";
    echo "Status: $code\n";
    echo "Response Snippet: " . substr($response, 0, 100) . "...\n\n";
}
