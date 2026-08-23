<?php
/** Configure l'auth HMAC de la recette sans écrire le secret dans Git. */
$dir = getenv('PK_WP_DIR');
if (!$dir) { fwrite(STDERR, "PK_WP_DIR manquant\n"); exit(2); }
require_once rtrim($dir, '/') . '/wp-load.php';
$secret = getenv('PARTIKULIER_N8N_SECRET');
if (!$secret) { fwrite(STDERR, "PARTIKULIER_N8N_SECRET manquant\n"); exit(2); }
update_option('pk_n8n_settings', array(
    'automation_api_secret' => $secret,
    'hmac_mode' => 'enforce',
    'active_key_id' => 'env',
), false);
global $wpdb;
$table = Partikulier_Automation_Bridge::events_table();
$wpdb->query("TRUNCATE TABLE {$table}");
echo "HMAC_FIXTURE_READY=1\n";
