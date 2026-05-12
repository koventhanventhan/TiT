<?php
header('Content-Type: text/plain');
$apiDir = '../../../api';

if (is_dir($apiDir)) {
    echo "--- LISTING public_html/api/ ---\n";
    print_r(scandir($apiDir));
    
    if (is_dir("$apiDir/vendor")) {
        echo "\n--- LISTING public_html/api/vendor/ ---\n";
        print_r(scandir("$apiDir/vendor"));
    } else {
        echo "\nvendor folder NOT FOUND in api/";
    }
} else {
    echo "api/ folder NOT FOUND at $apiDir";
}
