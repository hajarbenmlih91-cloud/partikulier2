<?php
/**
 * Inventaire/réconciliation des auto-traductions Polylang orphelines.
 *
 * Usage depuis la racine du dépôt :
 *   wp --path=wp eval-file scripts/reconcile-polylang-orphans.php
 *   PK_APPLIQUER=1 wp --path=wp eval-file scripts/reconcile-polylang-orphans.php
 *
 * Le mode par défaut est un dry-run. PK_APPLIQUER=1 passe uniquement les autos
 * remplacées en brouillon ; aucune annonce n'est supprimée.
 */
if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "WordPress n'est pas chargé. Utilisez wp eval-file.\n" );
    exit( 1 );
}
if ( ! class_exists( 'Partikulier_Listing_Translations' ) ) {
    fwrite( STDERR, "Le module de traductions Partikulier n'est pas chargé.\n" );
    exit( 1 );
}
$apply = '1' === (string) getenv( 'PK_APPLIQUER' );
$rows  = Partikulier_Listing_Translations::reconcile_orphans( $apply );
echo wp_json_encode(
    array(
        'mode'       => $apply ? 'apply' : 'dry-run',
        'count'      => count( $rows ),
        'orphaned'   => $rows,
        'destructive' => false,
    ),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
) . PHP_EOL;
