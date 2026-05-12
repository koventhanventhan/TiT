<?php
// Read the last 50 lines of Laravel log
$logFile = '../storage/logs/laravel.log';
header('Content-Type: text/plain');

if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -100);
    echo implode("", $lastLines);
} else {
    echo "Log file not found at $logFile";
}
