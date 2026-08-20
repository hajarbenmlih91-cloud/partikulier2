<?php
/**
 * Migre les anciennes auto-traductions dont _pk_translation_source contenait
 * une langue (ex. "fr") au lieu de l'ID numérique de l'annonce source.
 *
 * Usage :
 *   wp --path=wp eval-file scripts/migrate-polylang-source-meta.php
 *   PK_APPLIQUER=1 wp --path=wp eval-file scripts/migrate-polylang-source-meta.php
 *
 * Le mode par défaut est strictement dry-run. Une ligne n'est réparée que si
 * Polylang permet de retrouver un groupe et une source uniques. Les cas
 * ambigus ou historiques sont conservés et listés, jamais supprimés.
 */
if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "WordPress n'est pas chargé. Utilisez wp eval-file.\n" );
	exit( 1 );
}

if ( ! class_exists( 'Partikulier_Listing_Translations' ) ) {
	fwrite( STDERR, "Le module Partikulier de traductions n'est pas chargé.\n" );
	exit( 1 );
}

if ( ! function_exists( 'pll_get_post_translations' ) || ! function_exists( 'pll_get_post_language' ) ) {
	fwrite( STDERR, "Polylang est requis pour cette migration.\n" );
	exit( 1 );
}

$apply = '1' === (string) getenv( 'PK_APPLIQUER' );
$rows  = array();
$autos = get_posts(
	array(
		'post_type'      => PARTIKULIER_ESTATIK_POST_TYPE,
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => Partikulier_Listing_Translations::META_GENERATED,
		'meta_value'     => '1',
	)
);

foreach ( $autos as $auto_id ) {
	$auto_id   = (int) $auto_id;
	$raw       = get_post_meta( $auto_id, Partikulier_Listing_Translations::META_SOURCE, true );
	$raw_value = (string) $raw;

	if ( ctype_digit( $raw_value ) && (int) $raw_value > 0 ) {
		$rows[] = array(
			'auto_id' => $auto_id,
			'action'  => 'already_valid',
			'source_id' => (int) $raw,
		);
		continue;
	}

	$group = (array) pll_get_post_translations( $auto_id );
	$lang  = get_post_meta( $auto_id, Partikulier_Listing_Translations::META_SOURCE_LANG, true );
	if ( ! is_string( $lang ) || ! preg_match( '/^[a-z]{2,3}$/', $lang ) ) {
		$lang = 'fr';
	}

	$candidates = array();
	if ( ! empty( $group[ $lang ] ) && (int) $group[ $lang ] !== $auto_id ) {
		$candidates[] = (int) $group[ $lang ];
	}
	if ( empty( $candidates ) && ! empty( $group['fr'] ) && (int) $group['fr'] !== $auto_id ) {
		$candidates[] = (int) $group['fr'];
	}
	$candidates = array_values( array_unique( array_filter( $candidates ) ) );

	if ( 1 !== count( $candidates ) ) {
		$rows[] = array(
			'auto_id'    => $auto_id,
			'action'     => 'irreparable_or_ambiguous',
			'legacy_value' => $raw_value,
			'group'      => $group,
			'candidates' => $candidates,
		);
		continue;
	}

	$source_id = $candidates[0];
	$row       = array(
		'auto_id'      => $auto_id,
		'action'       => 'repair_source_meta',
		'legacy_value' => $raw_value,
		'source_id'    => $source_id,
		'source_lang'  => $lang,
		'applied'      => false,
	);

	if ( $apply ) {
		update_post_meta( $auto_id, '_pk_translation_source_legacy', $raw_value );
		update_post_meta( $auto_id, Partikulier_Listing_Translations::META_SOURCE, (string) $source_id );
		update_post_meta( $auto_id, Partikulier_Listing_Translations::META_SOURCE_LANG, $lang );
		$row['applied'] = true;
	}
	$rows[] = $row;
}

$summary = array(
	'mode'       => $apply ? 'apply' : 'dry-run',
	'total_auto' => count( $autos ),
	'repaired'   => count( array_filter( $rows, static function ( $row ) { return 'repair_source_meta' === $row['action']; } ) ),
	'valid'      => count( array_filter( $rows, static function ( $row ) { return 'already_valid' === $row['action']; } ) ),
	'irreparable_or_ambiguous' => count( array_filter( $rows, static function ( $row ) { return 'irreparable_or_ambiguous' === $row['action']; } ) ),
	'rows'       => $rows,
	'destructive' => false,
);

echo wp_json_encode( $summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
