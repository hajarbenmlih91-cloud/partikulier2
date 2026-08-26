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
require_once __DIR__ . '/src/ListingRepository.php';
require_once __DIR__ . '/src/HealthCheck.php';
require_once __DIR__ . '/src/Services.php';

/**
 * Les classes REST et d’écriture ne sont pas nécessaires sur une page HTML
 * publique. Elles restent chargées pour REST, WP-CLI et l’administration afin
 * de préserver les contrats existants et les commandes de maintenance.
 */
function partikulier_core_should_load_rest(): bool
{
    if (PHP_SAPI === 'cli') {
        return true;
    }
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return true;
    }
    if (defined('WP_CLI') && WP_CLI) {
        return true;
    }
    if (is_admin()) {
        return true;
    }
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    return str_contains($request_uri, '/wp-json/') || isset($_GET['rest_route']);
}

function partikulier_core_load_rest_classes(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    require_once __DIR__ . '/src/AuditLogger.php';
    require_once __DIR__ . '/src/ListingPolicy.php';
    require_once __DIR__ . '/src/ListingService.php';
    require_once __DIR__ . '/src/SearchService.php';
    require_once __DIR__ . '/src/TranslationService.php';
    require_once __DIR__ . '/src/RateLimiter.php';
    require_once __DIR__ . '/src/RestController.php';
    $loaded = true;
}

if (partikulier_core_should_load_rest()) {
    partikulier_core_load_rest_classes();
}

use Partikulier\Core\Database\Migrator;
use Partikulier\Core\HealthCheck;
use Partikulier\Core\RestController;

add_action('plugins_loaded', static function (): void {
    if (!class_exists('\\wpdb')) {
        return;
    }
    $GLOBALS['partikulier_core_migrator'] = new Migrator();
    $GLOBALS['partikulier_core_health'] = new HealthCheck();
    $GLOBALS['partikulier_core_jobs'] = new \Partikulier\Core\JobRunner();
    $GLOBALS['partikulier_core_jobs']->register();
    if (partikulier_core_should_load_rest()) {
        partikulier_core_load_rest_classes();
        $GLOBALS['partikulier_core_rest'] = new RestController();
    }
});

// Supporte aussi les appels programmatiques à rest_do_request() depuis WP-CLI,
// les tests PHP et les tâches internes, qui n’ont pas d’URI REST entrante.
add_action('rest_api_init', static function (): void {
    partikulier_core_load_rest_classes();
    if (!isset($GLOBALS['partikulier_core_rest'])) {
        $GLOBALS['partikulier_core_rest'] = new RestController();
    }
}, 1);

register_activation_hook(__FILE__, static function (): void {
    (new Migrator())->migrate();
    // Synchronisation initiale bornée à l’activation, jamais exécutée sur chaque requête publique.
    (new \Partikulier\Core\ListingRepository())->syncEstatikProperties();
});

register_deactivation_hook(__FILE__, static function (): void {
    // La désactivation ne supprime jamais les données : les migrations sont conservées.
});
