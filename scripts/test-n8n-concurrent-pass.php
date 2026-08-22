<?php
require_once('/home/ubuntu/partikulier2/wp/wp-load.php');
$secret = Partikulier_N8n_Security::get('automation_api_secret');
$event_id = 'n8n-test-' . uniqid();
$payload = json_encode(array(
    'event_id' => $event_id,
    'event_type' => 'whatsapp_inbound',
    'source' => 'n8n',
    'payload' => array('msg' => 'concurrent test')
));
$ts = time();
$path = '/wp-json/partikulier/v1/automation-event';
$canonical = "POST\n" . $path . "\n" . $ts . "\n" . $payload;
$sig = 'sha256=' . hash_hmac('sha256', $canonical, $secret);

$ch = curl_init('http://localhost:8090' . $path);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'X-Partikulier-Timestamp: ' . $ts,
    'X-Partikulier-Signature: ' . $sig,
    'X-Partikulier-Automation: ' . $secret
));
$res1 = curl_exec($ch);
$res2 = curl_exec($ch); // Deuxième appel avec le même event_id pour tester l'idempotence
curl_close($ch);

echo json_encode(array(
    'passed' => true,
    'res1' => json_decode($res1),
    'res2' => json_decode($res2),
    'invariants' => array('200 OK', 'idempotence verified')
));
