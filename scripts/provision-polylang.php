<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'PLL' ) ) {
    echo "Polylang non actif\n";
    exit;
}

$model = PLL()->model;
$options = get_option( 'polylang' );

// Configuration des langues si nécessaire
$languages = array(
    'fr' => array( 'name' => 'Français', 'locale' => 'fr_FR', 'rtl' => 0 ),
    'en' => array( 'name' => 'English', 'locale' => 'en_US', 'rtl' => 0 ),
    'ar' => array( 'name' => 'العربية', 'locale' => 'ar', 'rtl' => 1 )
);

foreach ( $languages as $slug => $args ) {
    if ( ! $model->get_language( $slug ) ) {
        $model->add_language( array(
            'name'     => $args['name'],
            'slug'     => $slug,
            'locale'   => $args['locale'],
            'rtl'      => $args['rtl'],
            'term_group' => 0,
        ) );
    }
}

// Nettoyage et Liaison des pages
$pages = array(
    'deposer' => array( 'fr' => 'deposer', 'en' => 'deposer-en', 'ar' => 'deposer-ar' ),
    'mes-annonces' => array( 'fr' => 'mes-annonces', 'en' => 'mes-annonces-en', 'ar' => 'mes-annonces-ar' ),
    'favoris' => array( 'fr' => 'favoris', 'en' => 'favoris-en', 'ar' => 'favoris-ar' ),
    'annonces' => array( 'fr' => 'annonces', 'en' => 'annonces', 'ar' => 'annonces' )
);

foreach ( $pages as $base => $slugs ) {
    $translations = array();
    foreach ( $slugs as $lang => $slug ) {
        $post = get_page_by_path( $slug, OBJECT, 'page' );
        if ( ! $post ) {
            $post_id = wp_insert_post( array(
                'post_title'  => ucfirst( $base ) . ' ' . strtoupper( $lang ),
                'post_name'   => $slug,
                'post_status' => 'publish',
                'post_type'   => 'page',
            ) );
            $post = get_post( $post_id );
        }
        if ( function_exists( 'pll_set_post_language' ) ) {
            pll_set_post_language( $post->ID, $lang );
        } elseif ( method_exists( $model, 'set_post_language' ) ) {
            $model->set_post_language( $post->ID, $lang );
        } elseif ( isset( $model->post ) && method_exists( $model->post, 'set_language' ) ) {
            $model->post->set_language( $post->ID, $lang );
        }
        $translations[$lang] = $post->ID;
    }
    pll_save_post_translations( $translations );
}

flush_rewrite_rules( true );
echo "PROVISIONING_DONE\n";
