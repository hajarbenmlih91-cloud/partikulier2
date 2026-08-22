<?php
/**
 * Provisioning Polylang Senior v6.17.9
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'PLL' ) ) {
    echo "ERREUR : Polylang non actif\n";
    exit(1);
}

$model = PLL()->model;

// 1. Langues
$languages = array(
    'fr' => array( 'name' => 'Français', 'locale' => 'fr_FR', 'rtl' => 0 ),
    'en' => array( 'name' => 'English', 'locale' => 'en_US', 'rtl' => 0 ),
    'ar' => array( 'name' => 'العربية', 'locale' => 'ar', 'rtl' => 1 )
);

foreach ( $languages as $slug => $args ) {
    if ( ! $model->get_language( $slug ) ) {
        $model->add_language( array(
            'name'       => $args['name'],
            'slug'       => $slug,
            'locale'     => $args['locale'],
            'rtl'        => $args['rtl'],
            'term_group' => 0,
        ) );
    }
}

// 2. Contrat de pages
$page_contract = array(
    'deposer'      => array( 'fr' => 'deposer',       'en' => 'deposer-en',       'ar' => 'deposer-ar' ),
    'mes-annonces' => array( 'fr' => 'mes-annonces',  'en' => 'mes-annonces-en',  'ar' => 'mes-annonces-ar' ),
    'favoris'      => array( 'fr' => 'favoris',       'en' => 'favoris-en',       'ar' => 'favoris-ar' ),
    'annonces'     => array( 'fr' => 'annonces',      'en' => 'annonces',         'ar' => 'annonces' )
);

// 3. Création et Liaison
foreach ( $page_contract as $base => $slugs ) {
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
        // Utiliser la fonction globale pll_set_post_language si disponible
        if ( function_exists( 'pll_set_post_language' ) ) {
            pll_set_post_language( $post->ID, $lang );
        } else {
            $model->post->set_language( $post->ID, $lang );
        }
        $translations[$lang] = $post->ID;
    }
    if ( function_exists( 'pll_save_post_translations' ) ) {
        pll_save_post_translations( $translations );
    }
}

// 4. Configuration
$options = get_option( 'polylang' );
$options['browser'] = 0; // Désactivé pour les tests E2E déterministes
$options['rewrite'] = 1;
$options['hide_default'] = 1;
update_option( 'polylang', $options );

flush_rewrite_rules( true );
echo "PROVISIONING_DONE\n";
