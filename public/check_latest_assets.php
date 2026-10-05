<?php
header('Content-Type: text/plain');
$assetsDir = '../../../assets';

if (is_dir($assetsDir)) {
    echo "--- CURRENT ASSETS ON CPANEL ---\n";
    print_r(scandir($assetsDir));
} else {
    echo "assets folder NOT FOUND";
}
