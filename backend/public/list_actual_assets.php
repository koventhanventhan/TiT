<?php
header('Content-Type: text/plain');
$assetsDir = '../../../assets';

if (is_dir($assetsDir)) {
    echo "--- LISTING public_html/assets/ ---\n";
    print_r(scandir($assetsDir));
} else {
    echo "assets/ folder NOT FOUND at $assetsDir";
}
