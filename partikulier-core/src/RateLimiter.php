<?php
declare(strict_types=1);

namespace Partikulier\Core;

use WP_Error;
use WP_REST_Request;

final class RateLimiter
{
    public function guard(WP_REST_Request $request, string $bucket, bool $authorized, int $limit, int $window = 60): bool|WP_Error
    {
        if (!$authorized) {
            return false;
        }
        $identity = is_user_logged_in() ? 'user:' . (string) get_current_user_id() : 'ip:' . $this->clientIp();
        $route = preg_replace('/[^a-z0-9_-]/i', '_', $request->get_route()) ?: 'route';
        $key = 'pk_rl_' . md5($bucket . '|' . $route . '|' . $identity);
        $state = get_transient($key);
        $now = time();
        if (!is_array($state) || !isset($state['started'], $state['count']) || ($now - (int) $state['started']) >= $window) {
            $state = ['started' => $now, 'count' => 0];
        }
        $state['count']++;
        set_transient($key, $state, $window);
        if ((int) $state['count'] > $limit) {
            $retryAfter = max(1, $window - ($now - (int) $state['started']));
            return new WP_Error(
                'pk_rate_limited',
                'Trop de requêtes; réessayez plus tard.',
                ['status' => 429, 'retry_after' => $retryAfter, 'bucket' => $bucket]
            );
        }
        return true;
    }

    private function clientIp(): string
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
    }
}
