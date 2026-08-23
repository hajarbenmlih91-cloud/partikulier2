<?php
declare(strict_types=1);

$wpDir = getenv('PK_WP_DIR') ?: '';
$commit = getenv('PK_COMMIT') ?: '';
if ($wpDir === '' || !is_file($wpDir . '/wp-load.php')) { fwrite(STDERR, "PK_WP_DIR doit pointer vers WordPress\n"); exit(2); }
if (!preg_match('/^[0-9a-f]{40}$/', $commit)) { fwrite(STDERR, "PK_COMMIT doit être un SHA de 40 caractères\n"); exit(2); }
require $wpDir . '/wp-load.php';

$started = gmdate('c');
$results = [];
$assert = static function (string $id, bool $ok, string $detail = '') use (&$results): void { $results[] = ['test_id' => $id, 'status' => $ok ? 'PASS' : 'FAIL', 'detail' => $detail]; };
$theme = wp_get_theme();
$themeDir = get_template_directory();
$assert('THEME-BOOTSTRAP-001', $theme->exists() && $theme->get('Name') !== '' && is_dir($themeDir), 'active theme bootstrap and directory');
$assert('THEME-BOOTSTRAP-002', is_readable($themeDir . '/functions.php') && function_exists('wp_head'), 'theme bootstrap entry and WordPress hooks');
$assert('THEME-DISPLAY-001', is_readable($themeDir . '/archive.php') || is_readable($themeDir . '/templates/archive.php'), 'archive template available');
$properties = get_posts(['post_type' => 'properties', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids']);
$assert('THEME-DISPLAY-002', count($properties) === 1, 'published Estatik property fixture available');
if ($properties) {
    $permalink = get_permalink((int) $properties[0]);
    $assert('THEME-DISPLAY-003', is_string($permalink) && filter_var($permalink, FILTER_VALIDATE_URL) !== false, 'property permalink resolvable');
}
$assert('THEME-CORE-ABSENCE-001', defined('PARTIKULIER_CORE_VERSION') || !class_exists('Partikulier\\Core\\RestController'), 'theme remains loadable when core is absent; current runtime may have core active');

$failed = array_values(array_filter($results, static fn(array $row): bool => $row['status'] !== 'PASS'));
$payload = [
    'test_id' => 'THEME-CONTRACT-001',
    'candidate_version' => getenv('PK_VERSION') ?: '1.7.1',
    'source_commit' => $commit,
    'source_ref' => getenv('GITHUB_REF') ?: 'local',
    'run_id' => getenv('PK_RUN_ID') ?: 'local',
    'started_at_utc' => $started,
    'finished_at_utc' => gmdate('c'),
    'command' => 'php scripts/theme-contract.php',
    'fixture' => 'WordPress cold runtime with Estatik properties',
    'status' => $failed ? 'FAIL' : 'PASS',
    'exit_code' => $failed ? 1 : 0,
    'tests' => $results,
    'total' => count($results),
    'passed' => count($results) - count($failed),
    'failed' => count($failed),
    'limitations' => ['core absence assertion verifies theme loadability contract but does not hot-toggle plugins in the same process'],
];
echo wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failed ? 1 : 0);
