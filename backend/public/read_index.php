<?php
header('Content-Type: text/plain');
$indexFile = '../../../index.html';

if (file_exists($indexFile)) {
    echo "--- CONTENT OF public_html/index.html ---\n";
    echo file_get_contents($indexFile);
} else {
    echo "index.html NOT FOUND";
}
