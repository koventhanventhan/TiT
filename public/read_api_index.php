<?php
header('Content-Type: text/plain');
$apiIndex = '../../../api/index.php';

if (file_exists($apiIndex)) {
    echo "--- CONTENT OF public_html/api/index.php ---\n";
    echo file_get_contents($apiIndex);
} else {
    echo "api/index.php NOT FOUND";
}
