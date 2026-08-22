<?php
/**
 * Lot 4bis — pages structurelles trilingues.
 * Usage: wp --path=wp eval-file scripts/test-pages-4bis.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! function_exists( 'pll_get_post_translations' ) ) { fwrite( STDERR, "Polylang unavailable\n" ); exit( 1 ); }
$slugs = array( 'faq', 'contact', 'confidentialite', 'mentions-legales', 'politique-de-confidentialite', 'conditions-utilisation' );
$report = array( 'passed' => true, 'pages' => array(), 'failures' => array() );
foreach ( $slugs as $slug ) {
    $source = get_page_by_path( $slug, OBJECT, 'page' );
    if ( ! $source || 'publish' !== $source->post_status ) {
        $report['passed'] = false;
        $report['failures'][] = $slug . ':missing-fr';
        continue;
    }
    $translations = pll_get_post_translations( $source->ID );
    $entry = array( 'slug' => $slug, 'fr' => get_permalink( $source->ID ), 'translations' => array() );
    foreach ( array( 'en', 'ar' ) as $language ) {
        $id = isset( $translations[ $language ] ) ? (int) $translations[ $language ] : 0;
        if ( ! $id || 'publish' !== get_post_status( $id ) ) {
            $report['passed'] = false;
            $report['failures'][] = $slug . ':missing-' . $language;
            continue;
        }
        $url = get_permalink( $id );
        $entry['translations'][ $language ] = $url;
        if ( false === strpos( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' . $language . '/' ) ) {
            $report['passed'] = false;
            $report['failures'][] = $slug . ':bad-url-' . $language;
        }
    }
    $report['pages'][] = $entry;
}
echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
if ( ! $report['passed'] ) { exit( 1 ); }
