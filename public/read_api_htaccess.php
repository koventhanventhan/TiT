<?php
header('Content-Type: text/plain');
$apiHtaccess = '../../../api/.htaccess';

if (file_exists($apiHtaccess)) {
    echo "--- CONTENT OF public_html/api/.htaccess ---\n";
    echo file_get_contents($apiHtaccess);
} else {
    echo "api/.htaccess NOT FOUND";
}
