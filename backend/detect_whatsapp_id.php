<?php
$token = 'EAAbD2L018tEBRBaZCjMitrib2DcZChQ4pZBvgRMXlCxps8IxEeqB5tQaHB06FrHWyVAyBJu4yZATgIjbpzEqugdQmmDpVxNIL8ZCGm8pzZAHuwglABo6SPT7UyfNZBzyyEk0HZA2TWxffeEjlDvV0ImImQUSTXixDvZBIwI2DjuXBXhZCldhOodrvRHBgXQJcZA0uL9ZBhVfOGpxT6qeZAyck639YlTZBNZC1VBBQuPOZBTwsoDpLCNL5MLOZCflNG7tGFetwTjgwgn5e5OOUZAIjOPZAssNveDTA4IRkgZD';
$wabaId = '774244258087738'; // From user screenshot

echo "Fetching phone numbers for WABA: $wabaId...\n";
$url = "https://graph.facebook.com/v21.0/$wabaId/phone_numbers";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo "Response Body:\n$response\n";
