<?php
$payload = json_encode([
    'name' => 'rfid.scanned',
    'channels' => ['rfid-scanner'],
    'data' => json_encode(['test' => 123])
]);
$ch = curl_init('http://127.0.0.1:8080/apps/smartklon-app/events');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer smartklon-secret-local' // Not correct, pusher uses signature
]);
$res = curl_exec($ch);
echo "Result: " . $res . "\n";
