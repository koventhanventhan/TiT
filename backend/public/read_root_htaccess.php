<?php
header('Content-Type: text/plain');
$htaccess = '../../../.htaccess';

if (file_exists($htaccess)) {
    echo "--- CONTENT OF public_html/.htaccess ---\n";
    echo file_get_contents($htaccess);
} else {
    echo "Root .htaccess NOT FOUND";
}
