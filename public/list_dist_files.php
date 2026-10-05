<?php
header('Content-Type: text/plain');
$distDir = '../../../dist';

if (is_dir($distDir)) {
    echo "--- LISTING public_html/dist/ ---\n";
    print_r(scandir($distDir));
} else {
    echo "dist/ folder NOT FOUND at $distDir";
}
