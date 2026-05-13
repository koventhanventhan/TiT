<?php
header('Content-Type: text/plain');
$indexPath = '../../../index.html';

if (file_exists($indexPath)) {
    echo "--- CONTENT OF index.html ---\n";
    echo file_get_contents($indexPath);
} else {
    echo "index.html NOT FOUND";
}
