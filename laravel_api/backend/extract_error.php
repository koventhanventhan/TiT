<?php
$lines = file('storage/logs/laravel.log');
$lastErrorJson = '';
foreach ($lines as $line) {
    if (strpos($line, 'Meta WhatsApp template failed to send') !== false) {
        $startPos = strpos($line, '{');
        if ($startPos !== false) {
            $lastErrorJson = substr($line, $startPos);
        }
    }
}
file_put_contents('meta_error.json', $lastErrorJson);
echo "Dumped to meta_error.json";
