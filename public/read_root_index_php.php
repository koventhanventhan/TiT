<?php
header('Content-Type: text/plain');
$indexPath = '../../../index.php';

if (file_exists($indexPath)) {
    echo "--- CONTENT OF public_html/index.php ---\n";
    echo file_get_contents($indexPath);
} else {
    echo "index.php NOT FOUND";
}
