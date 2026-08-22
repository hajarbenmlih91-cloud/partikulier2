<?php
$wp_dir = getenv('PK_WP_DIR');
require_once $wp_dir . '/wp-load.php';
$keys = Partikulier_N8n_Security::secret_keys();
print_r($keys);
$provided = 'senior-real-route-secret';
$encoded = base64_encode($provided);
echo "Provided: $encoded\n";
foreach ($keys as $id => $secret) {
    echo "Testing against $id: " . (hash_equals($secret, $encoded) ? "YES" : "NO") . "\n";
}
