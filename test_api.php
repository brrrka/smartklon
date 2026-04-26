<?php
$ch = curl_init('http://127.0.0.1:8000/api/rfid/scan');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['epc' => 'NEW-TAG-' . rand(1000, 9999)]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
echo "HTTP Status: " . $info['http_code'] . "\n";
echo "Response: " . $response . "\n";
