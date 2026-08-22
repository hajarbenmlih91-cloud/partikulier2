<?php
$wp_dir = getenv('PK_WP_DIR');
require_once $wp_dir . '/wp-load.php';

$cmd = "PK_WP_DIR=$wp_dir PK_BASE=http://localhost:8093 php scripts/test-hmac-real-route.php";
$VARS = shell_exec($cmd);
preg_match('/AUTH_TOKEN=(.*)/', $VARS, $m1);
preg_match('/TIMESTAMP=(.*)/', $VARS, $m2);
preg_match('/KEY_ID=(.*)/', $VARS, $m3);
preg_match('/SIGNATURE=(.*)/', $VARS, $m4);
preg_match('/BODY=(.*)/', $VARS, $m5);

$token = trim($m1[1] ?? '');
$ts = trim($m2[1] ?? '');
$kid = trim($m3[1] ?? '');
$sig = trim($m4[1] ?? '');
$body = trim($m5[1] ?? '');

$request = new WP_REST_Request('POST', '/partikulier/v1/automation-event');
$request->set_header('x-partikulier-automation', $token);
$request->set_header('x-partikulier-timestamp', $ts);
$request->set_header('x-partikulier-key-id', $kid);
$request->set_header('x-partikulier-signature', $sig);
$request->set_body($body);

$result = Partikulier_N8n_Security::check_automation_secret($request);
if ($result === true) {
    echo "SIMULATION_PASS\n";
} else {
    echo "SIMULATION_FAIL: " . (is_wp_error($result) ? $result->get_error_message() : 'Unknown error') . "\n";
}
