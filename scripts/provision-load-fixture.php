<?php
declare(strict_types=1);

$wpDir = getenv('PK_WP_DIR') ?: '';
$target = max(1, (int) (getenv('PK_MIN_LISTINGS') ?: '1000'));
if ($wpDir === '' || !is_file($wpDir . '/wp-load.php')) {
    fwrite(STDERR, "PK_WP_DIR doit pointer vers WordPress\n");
    exit(2);
}
require $wpDir . '/wp-load.php';

$current = (int) wp_count_posts('properties')->publish;
$created = 0;
for ($i = $current; $i < $target; $i++) {
    $id = wp_insert_post([
        'post_type' => 'properties',
        'post_status' => 'publish',
        'post_title' => sprintf('CDC load fixture %04d', $i + 1),
        'post_content' => 'Synthetic fixture for the v1.7.1 capacity gate.',
    ], true);
    if (is_wp_error($id)) {
        fwrite(STDERR, $id->get_error_message() . "\n");
        exit(1);
    }
    $created++;
}
$after = (int) wp_count_posts('properties')->publish;
$commit = getenv('PK_COMMIT') ?: '';
if (!preg_match('/^[0-9a-f]{40}$/', $commit)) {
    fwrite(STDERR, "PK_COMMIT doit être un SHA de 40 caractères\n");
    exit(2);
}
$result = ['test_id' => 'LOAD-FIXTURE-001', 'candidate_version' => getenv('PK_VERSION') ?: '1.7.1', 'source_commit' => $commit, 'source_ref' => getenv('GITHUB_REF') ?: 'local', 'run_id' => getenv('PK_RUN_ID') ?: 'local', 'target' => $target, 'before' => $current, 'created' => $created, 'after' => $after, 'status' => ($after >= $target) ? 'PASS' : 'FAIL'];
echo wp_json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($result['status'] === 'PASS' ? 0 : 1);
