<?php
// Test if assets are accessible via URL
$jsFile = 'assets/index-C1DpLfW1.js';
$url = 'https://titjaffna.lk/' . $jsFile;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "URL: $url\n";
echo "HTTP STATUS: $code\n";

if ($code == 200) {
    echo "SUCCESS: Asset is accessible!";
} else {
    echo "ERROR: Asset is NOT accessible (Status $code)";
}
