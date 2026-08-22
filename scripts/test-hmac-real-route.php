<?php
/**
 * Test HMAC Senior v6.17.5.
 */
$wp_dir = getenv('PK_WP_DIR');
require_once $wp_dir . '/wp-load.php';

$base = getenv('PK_BASE') ?: 'http://localhost:8094';
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
    'payload' => array('msg' => 'real route test')
));

// La route REST WordPress interne
$path = '/partikulier/v1/automation-event';
$canonical = "POST\n" . $path . "\n" . $timestamp . "\n" . $body;
$sig = 'sha256=' . hash_hmac('sha256', $canonical, $secret_raw);

echo "BASE=$base\n";
echo "ROUTE=/wp-json$path\n";
echo "TIMESTAMP=$timestamp\n";
echo "BODY=$body\n";
echo "SIGNATURE=$sig\n";
echo "KEY_ID=$key_id\n";
echo "AUTH_TOKEN=$encoded_secret\n";
