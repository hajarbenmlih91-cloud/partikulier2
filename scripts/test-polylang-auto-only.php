<?php
/** Vérifie qu'une auto-traduction seule n'est jamais masquée ni brouillonnée. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$source_id = wp_insert_post( array( 'post_type' => 'properties', 'post_status' => 'publish', 'post_title' => 'AUTO ONLY SOURCE FR', 'post_content' => 'Auto only source test', 'post_author' => 1 ), true );
if ( is_wp_error( $source_id ) ) { fwrite( STDERR, $source_id->get_error_message() . "\n" ); exit( 1 ); }
pll_set_post_language( $source_id, 'fr' );
$id = wp_insert_post( array( 'post_type' => 'properties', 'post_status' => 'publish', 'post_title' => 'AUTO ONLY TEST EN ' . $source_id, 'post_content' => 'Auto only test', 'post_author' => 1 ), true );
if ( is_wp_error( $id ) ) { fwrite( STDERR, $id->get_error_message() . "\n" ); exit( 1 ); }
pll_set_post_language( $id, 'en' );
update_post_meta( $id, '_pk_auto_translation', '1' );
update_post_meta( $id, '_pk_translation_source', (string) $source_id );
pll_save_post_translations( array( 'fr' => (int) $source_id, 'en' => (int) $id ) );
$before = get_post_status( $id );
$rows = Partikulier_Listing_Translations::reconcile_orphans( true );
$after = get_post_status( $id );
echo wp_json_encode( array( 'source_id' => $source_id, 'auto_only_id' => (int) $id, 'before' => $before, 'after' => $after, 'was_reconciled' => (bool) array_filter( $rows, static function ( $row ) use ( $id ) { return (int) $row['auto_id'] === (int) $id; } ), 'pass' => 'publish' === $after ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
