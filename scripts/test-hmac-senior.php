<?php
/**
 * Test HMAC Senior - Validation unitaire et préparation concurrence.
 * Usage: PK_WP_DIR=/chemin/vers/wp PK_BASE=http://localhost:8092 php scripts/test-hmac-senior.php
 */
$wp_dir = getenv('PK_WP_DIR');
if (!$wp_dir || !file_exists($wp_dir . '/wp-load.php')) {
    die("Erreur: PK_WP_DIR non défini.\n");
}
require_once $wp_dir . '/wp-load.php';

$base = getenv('PK_BASE') ?: 'http://localhost:8092';
$raw_secret = 'senior-test-secret-' . time();
$encoded_secret = base64_encode($raw_secret);
$key_id = 'senior-key-1';

// Configurer WP
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
    'payload' => array('msg' => 'hello')
));

// La route WP interne
$wp_route = '/partikulier/v1/automation-event';
// La route HTTP réelle pour le serveur PHP intégré
$http_route = '/automation-test-bridge.php';

$canonical = "POST\n" . $wp_route . "\n" . $timestamp . "\n" . $body;
$sig = 'sha256=' . hash_hmac('sha256', $canonical, $encoded_secret);

echo "HMAC_CONFIG_OK\n";
echo "BASE=$base\n";
echo "ROUTE=$http_route\n";
echo "EVENT_ID=$event_id\n";
echo "TIMESTAMP=$timestamp\n";
echo "BODY=$body\n";
echo "SIGNATURE=$sig\n";
echo "KEY_ID=$key_id\n";
echo "AUTH_TOKEN=$encoded_secret\n";
