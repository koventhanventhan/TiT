<?php
// Read .htaccess files to debug routing
header('Content-Type: text/plain');

$files = [
    '../../.htaccess', // Root public_html
    '../.htaccess',    // backend folder
    './.htaccess',     // public folder
];

foreach ($files as $file) {
    echo "--- FILE: $file ---\n";
    if (file_exists($file)) {
        echo file_get_contents($file);
    } else {
        echo "File not found.";
    }
    echo "\n\n";
}
