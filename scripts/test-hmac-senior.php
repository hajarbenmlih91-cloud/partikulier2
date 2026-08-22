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
$secret = 'senior-test-secret-' . time();
$key_id = 'senior-key-1';

// Configurer WP
update_option('pk_n8n_settings', array(
    'automation_api_secret' => base64_encode($secret),
    'hmac_mode' => 'enforce',
    'active_key_id' => $key_id
));

$event_id = 'evt_' . uniqid();
$timestamp = time();
$body = json_encode(array('event' => 'test', 'id' => $event_id));
$route = '/partikulier/v1/automation-event';
$canonical = 'POST' . $route . $timestamp . $body;
$sig = hash_hmac('sha256', $canonical, $secret);

echo "HMAC_CONFIG_OK\n";
echo "BASE=$base\n";
echo "ROUTE=$route\n";
echo "EVENT_ID=$event_id\n";
echo "TIMESTAMP=$timestamp\n";
echo "BODY=$body\n";
echo "SIGNATURE=$sig\n";
echo "KEY_ID=$key_id\n";
