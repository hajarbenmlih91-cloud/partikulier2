<?php
declare(strict_types=1);

namespace Partikulier\Core;

use Partikulier\Core\Database\Migrator;

final class HealthCheck
{
    public function __construct()
    {
        add_filter('site_status_tests', [$this, 'registerTests']);
    }

    public function get(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'pk_listings';
        $exists = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        return [
            'status' => $exists ? 'ok' : 'degraded',
            'core_version' => PARTIKULIER_CORE_VERSION,
            'schema_version' => (new Migrator())->currentVersion(),
            'database' => $exists ? 'ready' : 'missing',
            'locale' => determine_locale(),
        ];
    }

    public function registerTests(array $tests): array
    {
        $tests['direct']['partikulier_core'] = [
            'label' => __('Partikulier Core', 'partikulier-core'),
            'test' => static function (): array {
                $result = (new self())->get();
                return [
                    'label' => __('Schéma Partikulier', 'partikulier-core'),
                    'status' => $result['status'] === 'ok' ? 'good' : 'critical',
                    'badge' => ['label' => 'Partikulier', 'color' => $result['status'] === 'ok' ? 'green' : 'red'],
                    'description' => $result['status'] === 'ok' ? __('Le schéma core est disponible.', 'partikulier-core') : __('Le schéma core est absent.', 'partikulier-core'),
                    'test' => 'partikulier_core_schema',
                ];
            },
        ];
        return $tests;
    }
}
