<?php
$conn = @new mysqli('127.0.0.1', 'root', '', 'titjaffn_titjaffnas');
if ($conn->connect_error) {
    echo 'FAIL: ' . $conn->connect_error . "\n";
} else {
    $result = $conn->query('SHOW TABLES');
    echo 'OK: Connected to DB. Tables: ' . $result->num_rows . "\n";
    $conn->close();
}
