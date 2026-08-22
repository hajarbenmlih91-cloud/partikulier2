<?php
/**
 * Test HMAC & Idempotence Senior v6.17.5 via simulation interne (Body corrigé).
 */
$wp_dir = getenv('PK_WP_DIR');
require_once $wp_dir . '/wp-load.php';

$secret_raw = 'senior-real-route-secret-32bytes-12345';
$encoded_secret = base64_encode($secret_raw);
$key_id = 'real-key-1';

update_option('pk_n8n_settings', array(
    'automation_api_secret' => $encoded_secret,
    'hmac_mode' => 'enforce',
    'active_key_id' => $key_id
));

$event_id = 'n8n-evt-' . uniqid();
$timestamp = time();
$body = json_encode(array(
    'event_id' => $event_id,
    'event_type' => 'whatsapp_inbound',
    'source' => 'n8n',
    'payload' => array('msg' => 'senior test')
));
$path = '/partikulier/v1/automation-event';
$canonical = "POST\n" . $path . "\n" . $timestamp . "\n" . $body;
$sig = 'sha256=' . hash_hmac('sha256', $canonical, $secret_raw);

// Simuler 2 appels REST
for ($i = 1; $i <= 2; $i++) {
    $request = new WP_REST_Request('POST', $path);
    $request->set_header('x-partikulier-automation', $encoded_secret);
    $request->set_header('x-partikulier-key-id', $key_id);
    $request->set_header('x-partikulier-timestamp', (string)$timestamp);
    $request->set_header('x-partikulier-signature', $sig);
    $request->set_body($body);
    $request->set_header('Content-Type', 'application/json');
    
    $response = rest_do_request($request);
    $data = $response->get_data();
    echo "Appel $i: " . json_encode($data) . "\n";
}
