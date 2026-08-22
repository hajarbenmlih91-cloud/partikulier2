<?php
$wp_dir = getenv('PK_WP_DIR');
require_once $wp_dir . '/wp-load.php';

$secret = 'internal-test-secret';
$encoded_secret = base64_encode($secret);
$key_id = 'internal-key-1';

update_option('pk_n8n_settings', array(
    'automation_api_secret' => $encoded_secret,
    'hmac_mode' => 'enforce',
    'active_key_id' => $key_id
));

$event_id = 'n8n-evt-internal';
$timestamp = time();
$body = json_encode(array('event_id' => $event_id));
$path = '/partikulier/v1/automation-event';
$canonical = "POST\n" . $path . "\n" . $timestamp . "\n" . $body;
$sig = 'sha256=' . hash_hmac('sha256', $canonical, $encoded_secret);

// Simuler une requête REST
$request = new WP_REST_Request('POST', $path);
$request->set_header('x-partikulier-automation', $encoded_secret);
$request->set_header('x-partikulier-timestamp', $timestamp);
$request->set_header('x-partikulier-key-id', $key_id);
$request->set_header('x-partikulier-signature', $sig);
$request->set_body($body);

$result = Partikulier_N8n_Security::check_automation_secret($request);
if ($result === true) {
    echo "INTERNAL_HMAC_PASS\n";
} else {
    echo "INTERNAL_HMAC_FAIL: " . (is_wp_error($result) ? $result->get_error_message() : 'Unknown error') . "\n";
}
