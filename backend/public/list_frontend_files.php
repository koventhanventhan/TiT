<?php
header('Content-Type: text/plain');
$dir = '../../../'; // This should be public_html

if (is_dir($dir)) {
    echo "--- LISTING public_html (Frontend) ---\n";
    print_r(scandir($dir));
} else {
    echo "public_html NOT FOUND at $dir";
}
