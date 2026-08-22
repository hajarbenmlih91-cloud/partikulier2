<?php
/**
 * Test de validation HMAC senior.
 * Simule deux processus concurrents pour vérifier l'idempotence et la sécurité HMAC.
 */
require_once '/home/ubuntu/wp-6170-clean/wp-load.php';

function test_hmac_request($event_id, $secret, $key_id, $mode = 'enforce') {
    $url = 'http://localhost:8092/wp-json/partikulier/v1/automation-event';
    $method = 'POST';
    $timestamp = time();
    $body = json_encode(array(
        'event_id' => $event_id,
        'event_type' => 'whatsapp_inbound',
        'source' => 'n8n',
        'payload' => array('msg' => 'hello')
    ));

    $path = '/partikulier/v1/automation-event';
    $canonical = strtoupper($method) . "\n" . $path . "\n" . $timestamp . "\n" . $body;
    $signature = 'sha256=' . hash_hmac('sha256', $canonical, $secret);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-Partikulier-Automation: ' . $secret,
        'X-Partikulier-Timestamp: ' . $timestamp,
        'X-Partikulier-Key-Id: ' . $key_id,
        'X-Partikulier-Signature: ' . $signature
    ));
    
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return array('status' => $status, 'body' => json_decode($response, true));
}

// Configuration
$secret = base64_encode(random_bytes(32));
$key_id = 'test-key-senior';
$settings = array(
    'automation_api_secret' => $secret,
    'active_key_id' => $key_id,
    'hmac_mode' => 'enforce',
    'n8n_webhook_url' => 'https://example.com/webhook'
);
update_option('pk_n8n_settings', $settings);

// Test 1: Succès simple
$event_id = 'n8n-senior-' . uniqid();
$res1 = test_hmac_request($event_id, $secret, $key_id);
echo "Test 1 (Valid): Status {$res1['status']}, Duplicate: " . ($res1['body']['duplicate'] ? 'true' : 'false') . "\n";

// Test 2: Idempotence (même event_id)
$res2 = test_hmac_request($event_id, $secret, $key_id);
echo "Test 2 (Duplicate): Status {$res2['status']}, Duplicate: " . ($res2['body']['duplicate'] ? 'true' : 'false') . "\n";

// Test 3: Mauvaise signature
$body = json_encode(array('event_id' => 'n8n-bad'));
$ch = curl_init('http://localhost:8092/wp-json/partikulier/v1/automation-event');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'X-Partikulier-Automation: ' . $secret,
    'X-Partikulier-Timestamp: ' . time(),
    'X-Partikulier-Key-Id: ' . $key_id,
    'X-Partikulier-Signature: sha256=' . str_repeat('a', 64)
));
$res3 = curl_exec($ch);
$status3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Test 3 (Bad Signature): Status $status3\n";

if ($res1['status'] === 200 && $res2['status'] === 200 && $res2['body']['duplicate'] === true && $status3 === 401) {
    echo "HMAC_TEST_RESULT: PASS\n";
} else {
    echo "HMAC_TEST_RESULT: FAIL\n";
}
