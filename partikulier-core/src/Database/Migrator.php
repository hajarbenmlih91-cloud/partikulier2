<?php
declare(strict_types=1);

namespace Partikulier\Core\Database;

final class Migrator
{
    private const OPTION = 'partikulier_core_schema_version';

    public function migrate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach (Schema::statements($wpdb->prefix) as $statement) {
            dbDelta($statement);
        }
        update_option(self::OPTION, Schema::VERSION, false);
    }

    public function currentVersion(): string
    {
        return (string) get_option(self::OPTION, '0.0.0');
    }
}
