<?php
$url = 'http://127.0.0.1:8000/api/auth/register';
$data = array(
    'mode' => 'new',
    'email' => 'duplicate_test@example.com',
    'password' => 'password123',
    'full_name' => 'Duplicate Test',
    'role' => 'user'
);

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    )
);
$context  = stream_context_create($options);

// First Call
echo "First Call:\n";
$result = @file_get_contents($url, false, $context);
if ($result === FALSE) {
    echo "Error on first call\n";
} else {
    echo $result . "\n";
}

echo "-----------------\n";

// Second Call (Duplicate)
echo "Second Call (Duplicate):\n";
$result2 = @file_get_contents($url, false, $context);
if ($result2 === FALSE) {
    echo "Error on second call (HTTP 4xx/5xx returned)\n";
    // Get the response content
    echo var_dump($http_response_header);
} else {
    echo $result2 . "\n";
}
