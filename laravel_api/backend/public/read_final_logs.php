<?php
header('Content-Type: text/plain');
$logFile = '../../../error_log';

if (file_exists($logFile)) {
    echo "--- LAST 50 LINES OF ERROR LOG ---\n";
    $lines = file($logFile);
    echo implode("", array_slice($lines, -50));
} else {
    echo "error_log NOT FOUND";
}
