<?php
/**
 * Plugin Name: Partikulier Core
 * Description: Cœur métier contractuel de Partikulier : données, politiques et REST.
 * Version: 1.0.0
 * Requires PHP: 8.1
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

const PARTIKULIER_CORE_VERSION = '1.0.0';
const PARTIKULIER_CORE_FILE = __FILE__;

require_once __DIR__ . '/src/Database/Schema.php';
require_once __DIR__ . '/src/Database/Migrator.php';
require_once __DIR__ . '/src/AuditLogger.php';
require_once __DIR__ . '/src/ListingPolicy.php';
require_once __DIR__ . '/src/ListingRepository.php';
require_once __DIR__ . '/src/ListingService.php';
require_once __DIR__ . '/src/SearchService.php';
require_once __DIR__ . '/src/TranslationService.php';
require_once __DIR__ . '/src/HealthCheck.php';
require_once __DIR__ . '/src/Services.php';
require_once __DIR__ . '/src/RateLimiter.php';
require_once __DIR__ . '/src/RestController.php';

use Partikulier\Core\Database\Migrator;
use Partikulier\Core\HealthCheck;
use Partikulier\Core\RestController;

add_action('plugins_loaded', static function (): void {
    if (!class_exists('\wpdb')) {
        return;
    }
    $GLOBALS['partikulier_core_migrator'] = new Migrator();
    $GLOBALS['partikulier_core_health'] = new HealthCheck();
    $GLOBALS['partikulier_core_jobs'] = new \Partikulier\Core\JobRunner();
    $GLOBALS['partikulier_core_jobs']->register();
    $GLOBALS['partikulier_core_rest'] = new RestController();
});

register_activation_hook(__FILE__, static function (): void {
    (new Migrator())->migrate();
    // Synchronisation initiale bornée à l’activation, jamais exécutée sur chaque requête publique.
    (new \Partikulier\Core\ListingRepository())->syncEstatikProperties();
});

register_deactivation_hook(__FILE__, static function (): void {
    // La désactivation ne supprime jamais les données : les migrations sont conservées.
});
