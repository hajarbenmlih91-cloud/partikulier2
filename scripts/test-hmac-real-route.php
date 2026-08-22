<?php
/**
 * Test HMAC sur la vraie route REST WordPress corrigé.
 */
$wp_dir = getenv('PK_WP_DIR');
require_once $wp_dir . '/wp-load.php';

$base = getenv('PK_BASE') ?: 'http://localhost:8092';
$secret = 'senior-real-route-secret';
$encoded_secret = base64_encode($secret);
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

$wp_route = '/wp-json/partikulier/v1/automation-event';
$canonical = "POST\n" . '/partikulier/v1/automation-event' . "\n" . $timestamp . "\n" . $body;
echo "CANONICAL_CLIENT: " . str_replace("
", '[NL]', $canonical) . "
";
$sig = 'sha256=' . hash_hmac('sha256', $canonical, $encoded_secret);

echo "BASE=$base\n";
echo "ROUTE=$wp_route\n";
echo "TIMESTAMP=$timestamp\n";
echo "BODY=$body\n";
echo "SIGNATURE=$sig\n";
echo "KEY_ID=$key_id\n";
echo "AUTH_TOKEN=$encoded_secret\n";
