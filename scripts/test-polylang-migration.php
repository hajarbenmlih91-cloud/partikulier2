<?php
/**
 * Test d'intégration de migrate-polylang-source-meta.php.
 * Il exécute le script réel en dry-run puis en apply sur une fixture legacy.
 */
if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "WordPress n'est pas chargé.\n" );
	exit( 1 );
}

$created = array();
$failures = array();
$source_id = wp_insert_post( array( 'post_type' => PARTIKULIER_ESTATIK_POST_TYPE, 'post_status' => 'publish', 'post_title' => 'MIGRATION SOURCE ' . wp_generate_uuid4(), 'post_content' => 'Migration source', 'post_author' => get_current_user_id() ?: 1 ), true );
if ( is_wp_error( $source_id ) ) {
	fwrite( STDERR, $source_id->get_error_message() . "\n" );
	exit( 1 );
}
$source_id = (int) $source_id;
$created[] = $source_id;
wp_set_object_terms( $source_id, 'Appartement', 'es_type' );
wp_set_object_terms( $source_id, 'A louer', 'es_category' );
wp_set_object_terms( $source_id, 'Rabat', 'es_location' );
$auto_id = wp_insert_post( array( 'post_type' => PARTIKULIER_ESTATIK_POST_TYPE, 'post_status' => 'publish', 'post_title' => 'MIGRATION AUTO EN ' . $source_id, 'post_content' => 'Legacy auto', 'post_author' => get_current_user_id() ?: 1 ), true );
if ( is_wp_error( $auto_id ) ) {
	fwrite( STDERR, $auto_id->get_error_message() . "\n" );
	exit( 1 );
}
$auto_id = (int) $auto_id;
$created[] = $auto_id;
pll_set_post_language( $source_id, 'fr' );
pll_set_post_language( $auto_id, 'en' );
update_post_meta( $auto_id, Partikulier_Listing_Translations::META_GENERATED, '1' );
update_post_meta( $auto_id, Partikulier_Listing_Translations::META_SOURCE, 'fr' );
pll_save_post_translations( array( 'fr' => $source_id, 'en' => $auto_id ) );

$run = static function ( $apply ) {
	putenv( $apply ? 'PK_APPLIQUER=1' : 'PK_APPLIQUER=0' );
	ob_start();
	include __DIR__ . '/migrate-polylang-source-meta.php';
	$output = ob_get_clean();
	return json_decode( $output, true );
};

$dry = $run( false );
$dry_rows = array_values( array_filter( (array) ( $dry['rows'] ?? array() ), static function ( $row ) use ( $auto_id ) {
	return (int) ( $row['auto_id'] ?? 0 ) === $auto_id && 'repair_source_meta' === ( $row['action'] ?? '' ) && empty( $row['applied'] );
} ) );
if ( empty( $dry_rows ) ) {
	$failures[] = 'Le dry-run ne planifie pas la réparation de la méta legacy.';
}
if ( 'fr' !== (string) get_post_meta( $auto_id, Partikulier_Listing_Translations::META_SOURCE, true ) ) {
	$failures[] = 'Le dry-run a muté la méta source.';
}

$apply = $run( true );
$source_after = (string) get_post_meta( $auto_id, Partikulier_Listing_Translations::META_SOURCE, true );
$legacy_after = (string) get_post_meta( $auto_id, '_pk_translation_source_legacy', true );
$apply_rows = array_values( array_filter( (array) ( $apply['rows'] ?? array() ), static function ( $row ) use ( $auto_id ) {
	return (int) ( $row['auto_id'] ?? 0 ) === $auto_id && 'repair_source_meta' === ( $row['action'] ?? '' ) && ! empty( $row['applied'] );
} ) );
if ( empty( $apply_rows ) || (string) $source_id !== $source_after || 'fr' !== $legacy_after ) {
	$failures[] = 'Le mode apply ne restaure pas l’ID source et l’archive legacy attendus.';
}

$result = array(
	'passed'      => empty( $failures ),
	'failures'    => $failures,
	'source_id'   => $source_id,
	'auto_id'     => $auto_id,
	'dry_run'     => $dry,
	'apply'       => $apply,
	'meta_after'  => array( 'source' => $source_after, 'legacy' => $legacy_after, 'source_lang' => get_post_meta( $auto_id, Partikulier_Listing_Translations::META_SOURCE_LANG, true ) ),
);
echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;

foreach ( array_unique( $created ) as $id ) {
	wp_delete_post( (int) $id, true );
}
if ( ! empty( $failures ) ) {
	exit( 1 );
}
