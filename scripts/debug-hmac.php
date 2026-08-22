<?php
$wp_dir = getenv('PK_WP_DIR');
require_once $wp_dir . '/wp-load.php';
$keys = Partikulier_N8n_Security::secret_keys();
echo "KEYS IN DB:\n";
print_r($keys);

$secret_raw = 'senior-real-route-secret-32bytes-12345';
$encoded = base64_encode($secret_raw);
echo "PROVIDED TOKEN: $encoded\n";

foreach ($keys as $id => $secret) {
    echo "Testing against $id: " . (hash_equals($secret, $encoded) ? "YES" : "NO") . "\n";
}
