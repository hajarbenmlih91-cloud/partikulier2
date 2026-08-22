<?php
/**
 * Test interne HMAC Senior v6.17.6.
 */
$wp_dir = getenv('PK_WP_DIR') ?: '/home/ubuntu/wp-6172-final';
require_once $wp_dir . '/wp-load.php';

$event_id_base = $argv[1] ?? 'concurrent-event-' . time();
$event_id = 'n8n-' . $event_id_base;
$timestamp = time();
$secret_brut = 'real-secret-brut-123';
$secret_b64 = base64_encode($secret_brut);
$key_id = 'real-key-1';

$request = new WP_REST_Request('POST', '/partikulier/v1/automation-event');
$payload = array(
    'event_id' => $event_id,
    'event_type' => 'whatsapp_inbound',
    'source' => 'n8n',
    'payload' => array('message' => 'Hello')
);
$body = json_encode($payload);
$request->set_body($body);

// Signature HMAC
$canonical = 'POST' . "\n" . '/partikulier/v1/automation-event' . "\n" . $timestamp . "\n" . $body;
$signature = 'sha256=' . hash_hmac('sha256', $canonical, $secret_brut);

$request->set_header('X-Partikulier-Automation', $secret_b64);
$request->set_header('X-Partikulier-Key-Id', $key_id);
$request->set_header('X-Partikulier-Timestamp', $timestamp);
$request->set_header('X-Partikulier-Signature', $signature);
$request->set_header('Content-Type', 'application/json');

$response = rest_do_request($request);
$data = $response->get_data();

echo json_encode($data) . "\n";
