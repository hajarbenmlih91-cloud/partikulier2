<?php
declare(strict_types=1);

$wpDir = getenv('PK_WP_DIR') ?: '';
if ($wpDir === '' || !is_file($wpDir . '/wp-load.php')) {
    fwrite(STDERR, "PK_WP_DIR doit pointer vers une installation WordPress\n");
    exit(2);
}
require $wpDir . '/wp-load.php';

$started = gmdate('c');
$version = getenv('PK_VERSION') ?: '1.7.1';
$commit = getenv('PK_COMMIT') ?: '';
$runId = getenv('PK_RUN_ID') ?: 'local-' . gmdate('Ymd\THis\Z');
if (!preg_match('/^[0-9a-f]{40}$/', $commit)) {
    fwrite(STDERR, "PK_COMMIT doit être un SHA Git de 40 caractères\n");
    exit(2);
}
$results = [];
$assert = static function (string $id, bool $ok, string $detail = '') use (&$results): void {
    $results[] = ['test_id' => $id, 'status' => $ok ? 'PASS' : 'FAIL', 'detail' => $detail];
};

$health = rest_do_request(new WP_REST_Request('GET', '/partikulier/v1/health'));
$assert('CORE-HEALTH-001', $health->get_status() === 200 && ($health->get_data()['status'] ?? '') === 'ok', 'health status');

$request = new WP_REST_Request('GET', '/partikulier/v1/listings');
$request->set_param('locale', 'fr');
$request->set_param('order', 'price_asc');
$request->set_param('per_page', 5);
$list = rest_do_request($request);
$data = $list->get_data()['data'] ?? [];
$prices = array_map(static fn(array $row): float => (float) $row['price'], $data);
$sorted = $prices;
sort($sorted, SORT_NUMERIC);
$assert('CORE-LIST-001', $list->get_status() === 200 && count($data) > 0, 'public collection');
$assert('CORE-LIST-002', $prices === $sorted, 'price whitelist ordering');

$write = new WP_REST_Request('POST', '/partikulier/v1/listings');
$write->set_header('Content-Type', 'application/json');
$write->set_body(wp_json_encode(['title' => 'anonymous', 'description' => 'blocked', 'locale' => 'fr', 'price' => 1, 'area' => 2]));
$writeResult = rest_do_request($write);
$assert('CORE-AUTH-001', in_array($writeResult->get_status(), [401, 403], true), 'anonymous write denied');

wp_set_current_user(1);
$ownerWrite = new WP_REST_Request('POST', '/partikulier/v1/listings');
$ownerWrite->set_header('Content-Type', 'application/json');
$ownerWrite->set_body(wp_json_encode(['title' => 'contract listing', 'description' => 'contract fixture', 'locale' => 'fr', 'price' => 100, 'area' => 10]));
$ownerResult = rest_do_request($ownerWrite);
$createdId = (int) (($ownerResult->get_data()['id'] ?? 0));
$assert('CORE-AUTH-002', $ownerResult->get_status() === 201 && $createdId > 0, 'authenticated write allowed');

$leadRequest = new WP_REST_Request('POST', '/partikulier/v1/leads');
$leadRequest->set_header('Content-Type', 'application/json');
$leadRequest->set_body(wp_json_encode(['email' => 'contract@example.test', 'message' => 'contract lead']));
$leadResult = rest_do_request($leadRequest);
$leadId = (int) (($leadResult->get_data()['data']['id'] ?? 0));
$assert('CORE-LEAD-001', $leadResult->get_status() === 201 && $leadId > 0, 'lead accepted');

$favoriteRequest = new WP_REST_Request('POST', '/partikulier/v1/favorites');
$favoriteRequest->set_param('listing_id', (int) ($data[0]['id'] ?? $createdId));
$favoriteResult = rest_do_request($favoriteRequest);
$assert('CORE-FAVORITE-001', $favoriteResult->get_status() === 200, 'authenticated favorite toggle');

if ($createdId > 0) {
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'pk_listings', ['id' => $createdId], ['%d']);
}
if ($leadId > 0) {
    wp_delete_comment($leadId, true);
}
delete_user_meta(1, 'partikulier_favorites');
wp_set_current_user(0);

$failed = array_values(array_filter($results, static fn(array $row): bool => $row['status'] !== 'PASS'));
$finished = gmdate('c');
$payload = [
    'test_id' => 'CORE-CONTRACT-001',
    'candidate_version' => $version,
    'source_commit' => $commit,
    'source_ref' => getenv('GITHUB_REF') ?: 'local',
    'run_id' => $runId,
    'started_at_utc' => $started,
    'finished_at_utc' => $finished,
    'command' => 'php partikulier-core/tests/core-contract.php',
    'fixture' => 'WordPress cold runtime with Estatik properties',
    'status' => $failed ? 'FAIL' : 'PASS',
    'exit_code' => $failed ? 1 : 0,
    'tests' => $results,
    'passed' => count($results) - count($failed),
    'failed' => count($failed),
    'artifacts' => ['partikulier-core/src', 'documentation/data-contract.json'],
    'limitations' => ['authenticated owner matrix and full load fixture are separate gates'],
];
printf("%s\n", wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
exit($failed ? 1 : 0);
