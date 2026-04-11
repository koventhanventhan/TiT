<?php
$lines = file('storage/logs/laravel.log');
$errors = [];
foreach ($lines as $line) {
    if (strpos($line, 'local.ERROR') !== false && strpos($line, 'Meta WhatsApp template failed to send') !== false) {
        $errors[] = trim($line);
    }
}
$last = end($errors);
if ($last) {
    echo "LAST ERROR:\n";
    // Try to extract JSON
    $startPos = strpos($last, '{');
    if ($startPos !== false) {
        $jsonStr = substr($last, $startPos);
        $data = json_decode($jsonStr, true);
        if ($data) {
            print_r($data);
        } else {
            echo $jsonStr;
        }
    } else {
        echo $last;
    }
} else {
    echo "No errors found.";
}
