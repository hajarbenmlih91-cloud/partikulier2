<?php
/**
 * Scénario d'intégration D : auto EN éjectée puis remplacée par une EN manuelle.
 * Le script est idempotent pour une sandbox fraîche : il crée une famille dédiée
 * et retourne les états avant/après de la réconciliation.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'pll_set_post_language' ) || ! function_exists( 'pll_save_post_translations' ) ) {
    fwrite( STDERR, "Polylang indisponible\n" ); exit( 1 );
}
$source = get_posts( array( 'post_type' => 'properties', 'post_status' => 'publish', 'posts_per_page' => 1, 'lang' => 'fr', 'orderby' => 'ID', 'order' => 'ASC' ) );
if ( empty( $source ) ) { fwrite( STDERR, "Aucune source FR\n" ); exit( 1 ); }
$source_id = (int) $source[0]->ID;
$group_before = pll_get_post_translations( $source_id );
$old_en = ! empty( $group_before['en'] ) ? (int) $group_before['en'] : 0;
$auto_id = wp_insert_post( array( 'post_type' => 'properties', 'post_status' => 'publish', 'post_title' => 'AUTO TEST EN ' . $source_id, 'post_content' => 'Auto test', 'post_author' => 1 ), true );
if ( is_wp_error( $auto_id ) ) { fwrite( STDERR, $auto_id->get_error_message() . "\n" ); exit( 1 ); }
pll_set_post_language( $auto_id, 'en' );
update_post_meta( $auto_id, '_pk_auto_translation', '1' );
update_post_meta( $auto_id, '_pk_translation_source', (string) $source_id );
pll_save_post_translations( array( 'fr' => $source_id, 'en' => $auto_id, 'ar' => ! empty( $group_before['ar'] ) ? (int) $group_before['ar'] : 0 ) );
$after_auto = pll_get_post_translations( $source_id );
$manual_id = wp_insert_post( array( 'post_type' => 'properties', 'post_status' => 'publish', 'post_title' => 'MANUAL TEST EN ' . $source_id, 'post_content' => 'Manual test', 'post_author' => 1 ), true );
if ( is_wp_error( $manual_id ) ) { fwrite( STDERR, $manual_id->get_error_message() . "\n" ); exit( 1 ); }
pll_set_post_language( $manual_id, 'en' );
pll_save_post_translations( array( 'fr' => $source_id, 'en' => $manual_id, 'ar' => ! empty( $group_before['ar'] ) ? (int) $group_before['ar'] : 0 ) );
$after_manual = pll_get_post_translations( $source_id );
$orphan_before = Partikulier_Listing_Translations::orphan_report();
$apply = Partikulier_Listing_Translations::reconcile_orphans( true );
$result = array(
    'source_id' => $source_id,
    'old_en' => $old_en,
    'auto_id' => (int) $auto_id,
    'manual_id' => (int) $manual_id,
    'group_after_auto' => $after_auto,
    'group_after_manual' => $after_manual,
    'orphan_report_before_apply' => $orphan_before,
    'apply_result' => $apply,
    'auto_status_after' => get_post_status( $auto_id ),
    'manual_status_after' => get_post_status( $manual_id ),
);
echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
