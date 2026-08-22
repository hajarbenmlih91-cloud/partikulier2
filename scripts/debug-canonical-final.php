<?php
$wp_dir = getenv('PK_WP_DIR');
require_once $wp_dir . '/wp-load.php';

$token = 'c2VuaW9yLXJlYWwtcm91dGUtc2VjcmV0LTMyYnl0ZXMtMTIzNDU=';
$ts = time();
$kid = 'real-key-1';
$body = json_encode(['event_id' => 'test-id']);

$request = new WP_REST_Request('POST', '/partikulier/v1/automation-event');
$request->set_header('x-partikulier-automation', $token);
$request->set_header('x-partikulier-timestamp', (string)$ts);
$request->set_header('x-partikulier-key-id', $kid);
$request->set_body($body);

// Simuler la signature attendue
$path = $request->get_route();
$canonical = strtoupper($request->get_method()) . "\n" . $path . "\n" . $ts . "\n" . $body;
echo "CANONICAL_WP_BASE64: " . base64_encode($canonical) . "\n";
