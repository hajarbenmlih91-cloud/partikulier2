<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! function_exists( 'pll_languages_list' ) || ! isset( $GLOBALS['polylang']->model ) ) { fwrite( STDERR, "Polylang model unavailable\n" ); exit( 1 ); }
$language_model = $GLOBALS['polylang']->model;
$add_language = null;
if ( method_exists( $language_model, 'add_language' ) ) {
    $add_language = static function ( $args ) use ( $language_model ) { return $language_model->add_language( $args ); };
} elseif ( isset( $language_model->languages ) && is_object( $language_model->languages ) && method_exists( $language_model->languages, 'add' ) ) {
    $add_language = static function ( $args ) use ( $language_model ) { return $language_model->languages->add( $args ); };
}
if ( ! $add_language ) { fwrite( STDERR, "Polylang initialized language service unavailable\n" ); exit( 1 ); }
$languages = array(
    array( 'locale' => 'fr_FR', 'name' => 'Français', 'slug' => 'fr', 'flag' => 'fr', 'term_group' => 0, 'rtl' => false ),
    array( 'locale' => 'en_US', 'name' => 'English', 'slug' => 'en', 'flag' => 'us', 'term_group' => 1, 'rtl' => false ),
    array( 'locale' => 'ar', 'name' => 'العربية', 'slug' => 'ar', 'flag' => 'ma', 'term_group' => 2, 'rtl' => true ),
);
$ids = array();
foreach ( $languages as $args ) {
    $existing = array();
    foreach ( (array) pll_languages_list() as $language_slug ) {
        if ( is_string( $language_slug ) && $language_slug !== '' ) {
            $existing[] = $language_slug;
        }
    }
    if ( ! in_array( $args['slug'], $existing, true ) ) {
        $result = $add_language( $args );
        if ( is_wp_error( $result ) ) {
            fwrite( STDERR, "Could not add language {$args['slug']}: {$result->get_error_message()}\n" );
            exit( 1 );
        }
    }
    $lang = get_term_by( 'slug', $args['slug'], 'language' );
    $ids[ $args['slug'] ] = $lang ? (int) $lang->term_id : 0;
}
$option = get_option( 'polylang', array() );
$option['default_lang'] = 'fr';
$option['hide_default'] = true;
$option['force_lang'] = 1;
$option['browser'] = 1;
$option['rewrite'] = true;
$option['post_types'] = array_values( array_unique( array_merge( (array) ( $option['post_types'] ?? array() ), array( 'properties', 'page' ) ) ) );
$option['taxonomies'] = array_values( array_unique( array_merge( (array) ( $option['taxonomies'] ?? array() ), array( 'es_type', 'es_category', 'es_location' ) ) ) );
update_option( 'polylang', $option );
// Normalize every existing published listing to FR before querying language-filtered sources.
$all_listings = get_posts( array( 'post_type' => 'properties', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
foreach ( $all_listings as $listing ) {
    if ( ! pll_get_post_language( $listing->ID ) ) {
        pll_set_post_language( $listing->ID, 'fr' );
    }
}
// Create one linked FR/EN/AR family for each published Estatik listing.
$listings = get_posts( array( 'post_type' => 'properties', 'post_status' => 'publish', 'numberposts' => -1, 'lang' => 'fr', 'orderby' => 'ID', 'order' => 'ASC' ) );
foreach ( $listings as $source ) {
    pll_set_post_language( $source->ID, 'fr' );
    $translations = array( 'fr' => $source->ID );
    foreach ( array( 'en', 'ar' ) as $slug ) {
        $existing_translation = pll_get_post_translations( $source->ID );
        if ( ! empty( $existing_translation[ $slug ] ) ) { $translations[ $slug ] = (int) $existing_translation[ $slug ]; continue; }
        $title = 'en' === $slug ? 'Translated listing: ' . $source->post_title : 'إعلان مترجم: ' . $source->post_title;
        $new_id = wp_insert_post( array( 'post_type' => 'properties', 'post_status' => 'publish', 'post_title' => $title, 'post_content' => $source->post_content, 'post_excerpt' => $source->post_excerpt, 'post_author' => $source->post_author ), true );
        if ( is_wp_error( $new_id ) ) { fwrite( STDERR, $new_id->get_error_message() . "\n" ); exit( 1 ); }
        pll_set_post_language( $new_id, $slug );
        foreach ( get_post_meta( $source->ID ) as $key => $values ) { foreach ( $values as $value ) { add_post_meta( $new_id, $key, maybe_unserialize( $value ) ); } }
        foreach ( array( 'es_type', 'es_category', 'es_location' ) as $taxonomy ) {
            $term_ids = wp_get_object_terms( $source->ID, $taxonomy, array( 'fields' => 'ids' ) );
            if ( ! is_wp_error( $term_ids ) && ! empty( $term_ids ) ) { wp_set_object_terms( $new_id, $term_ids, $taxonomy, false ); }
        }
        $translations[ $slug ] = $new_id;
    }
    pll_save_post_translations( $translations );
}
// Structure pages Lot 4bis : chaque page existe dans les trois langues et reste liee.
$structure_pages = array(
    'deposer-une-annonce' => array( 'fr' => 'Déposer une annonce', 'en' => 'Submit a listing', 'ar' => 'إضافة إعلان' ),
    'favoris' => array( 'fr' => 'Favoris', 'en' => 'Favorites', 'ar' => 'المفضلة' ),
    'mes-annonces' => array( 'fr' => 'Mes annonces', 'en' => 'My listings', 'ar' => 'إعلاناتي' ),
    'politique-de-confidentialite' => array( 'fr' => 'Politique de confidentialité — contenu en attente de validation juridique', 'en' => 'Privacy policy — pending legal validation', 'ar' => 'سياسة الخصوصية — في انتظار المصادقة القانونية' ),
    'confidentialite' => array( 'fr' => 'Confidentialité — contenu en attente de validation juridique', 'en' => 'Privacy — pending legal validation', 'ar' => 'الخصوصية — في انتظار المصادقة القانونية' ),
    'conditions-utilisation' => array( 'fr' => 'Conditions générales — contenu en attente de validation juridique', 'en' => 'Terms of use — pending legal validation', 'ar' => 'شروط الاستخدام — في انتظار المصادقة القانونية' ),
    'faq' => array( 'fr' => 'Questions fréquentes', 'en' => 'Frequently asked questions', 'ar' => 'الأسئلة الشائعة' ),
    'contact' => array( 'fr' => 'Contact', 'en' => 'Contact', 'ar' => 'اتصل بنا' ),
    'mentions-legales' => array( 'fr' => 'Mentions légales — contenu en attente de validation juridique', 'en' => 'Legal notice — pending legal validation', 'ar' => 'الإشعار القانوني — في انتظار المصادقة القانونية' ),
);
foreach ( $structure_pages as $page_slug => $titles ) {
    $source = get_page_by_path( $page_slug, OBJECT, 'page' );
    if ( ! $source ) {
        $source_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $page_slug, 'post_title' => $titles['fr'], 'post_content' => '' ), true );
        if ( is_wp_error( $source_id ) ) { fwrite( STDERR, $source_id->get_error_message() . "\\n" ); exit( 1 ); }
        $source = get_post( $source_id );
    } else {
        // Une page WordPress préexistante peut être en brouillon ou porter un slug historique.
        // Le provisioning doit la rendre publiable et canonique de façon idempotente.
        wp_update_post( array( 'ID' => $source->ID, 'post_status' => 'publish', 'post_name' => $page_slug, 'post_title' => $titles['fr'] ) );
        $source = get_post( $source->ID );
    }
    pll_set_post_language( $source->ID, 'fr' );
    $template_map = array(
        'deposer-une-annonce' => 'templates/page-deposer-annonce.php',
        'favoris' => 'templates/page-favoris.php',
        'mes-annonces' => 'templates/page-mes-annonces.php',
    );
    if ( isset( $template_map[ $page_slug ] ) ) {
        update_post_meta( $source->ID, '_wp_page_template', $template_map[ $page_slug ] );
    }
    $translations = array( 'fr' => $source->ID );
    foreach ( array( 'en', 'ar' ) as $slug_lang ) {
        $existing = pll_get_post_translations( $source->ID );
        $translated_id = ! empty( $existing[ $slug_lang ] ) ? (int) $existing[ $slug_lang ] : 0;
        if ( ! $translated_id ) {
            $translated_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $page_slug . '-' . $slug_lang, 'post_title' => $titles[ $slug_lang ], 'post_content' => '' ), true );
            if ( is_wp_error( $translated_id ) ) { fwrite( STDERR, $translated_id->get_error_message() . "\\n" ); exit( 1 ); }
            pll_set_post_language( $translated_id, $slug_lang );
        } else {
            wp_update_post( array( 'ID' => $translated_id, 'post_name' => $page_slug . '-' . $slug_lang, 'post_title' => $titles[ $slug_lang ], 'post_status' => 'publish' ) );
        }
        if ( isset( $template_map[ $page_slug ] ) ) {
            update_post_meta( $translated_id, '_wp_page_template', $template_map[ $page_slug ] );
        }
        $translations[ $slug_lang ] = $translated_id;
    }
    pll_save_post_translations( $translations );
}
$taxonomy_script = '';
foreach ( array(
    __DIR__ . '/provision-polylang-taxonomies.php',
    ( function_exists( 'getcwd' ) ? getcwd() . '/scripts/provision-polylang-taxonomies.php' : '' ),
    ( defined( 'ABSPATH' ) ? dirname( ABSPATH ) . '/scripts/provision-polylang-taxonomies.php' : '' ),
    ( defined( 'ABSPATH' ) ? ABSPATH . 'scripts/provision-polylang-taxonomies.php' : '' ),
) as $candidate ) {
    if ( $candidate && is_file( $candidate ) ) { $taxonomy_script = $candidate; break; }
}
if ( ! $taxonomy_script ) { fwrite( STDERR, "Polylang taxonomy provisioning script unavailable\\n" ); exit( 1 ); }
require_once $taxonomy_script;
// Polylang peut réécrire ses options pendant le provisioning : la détection
// navigateur est donc forcée une dernière fois, puis vérifiée avant le rapport.
$final_option = get_option( 'polylang', array() );
$final_option['browser'] = 1;
update_option( 'polylang', $final_option );
wp_cache_delete( 'polylang', 'options' );
register_shutdown_function(
    static function() {
        $shutdown_option = get_option( 'polylang', array() );
        if ( empty( $shutdown_option['browser'] ) ) {
            $shutdown_option['browser'] = 1;
            update_option( 'polylang', $shutdown_option );
        }
    }
);
if ( empty( get_option( 'polylang', array() )['browser'] ) ) {
    fwrite( STDERR, "Polylang browser detection was not persisted\\n" );
    exit( 1 );
}
// Estatik, Polylang, les traductions et les taxonomies sont maintenant en place.
// Le flush final doit donc refleter l’etat complet, et non un etat intermediaire.
flush_rewrite_rules( false );
// Invalider explicitement les caches persistants : sur un premier provisioning,
// get_option('rewrite_rules') peut encore exposer la table antérieure au flush.
wp_cache_delete( 'rewrite_rules', 'options' );
wp_cache_delete( 'polylang', 'options' );
// Rejouer le flush via WP-CLI après la mutation complète : cela force une
// reconstruction des règles dans un cycle WP-CLI final et évite une table
// partielle lorsque Polylang vient d'être provisionné dans le même processus.
if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) && method_exists( 'WP_CLI', 'runcommand' ) ) {
    $rewrite_flush = WP_CLI::runcommand( 'rewrite flush --hard', array( 'return' => 'all', 'parse' => 'json' ) );
    if ( is_array( $rewrite_flush ) && isset( $rewrite_flush['return_code'] ) && 0 !== (int) $rewrite_flush['return_code'] ) {
        fwrite( STDERR, "Final WP-CLI rewrite flush failed\\n" );
        exit( 1 );
    }
}
wp_cache_delete( 'rewrite_rules', 'options' );
if ( function_exists( 'wp_cache_flush' ) ) { wp_cache_flush(); }
$rewrite_rules = (array) get_option( 'rewrite_rules', array() );
$rewrite_checks = array(
    'polylang_language_rules' => false,
    'polylang_lang_query' => false,
);
foreach ( array_keys( $rewrite_rules ) as $rule ) {
    $rule = (string) $rule;
    if ( false !== strpos( $rule, '(en|ar)' ) ) { $rewrite_checks['polylang_language_rules'] = true; }
    if ( false !== strpos( $rule, 'lang=' ) || false !== strpos( (string) ( $rewrite_rules[ $rule ] ?? '' ), 'lang=' ) ) { $rewrite_checks['polylang_lang_query'] = true; }
}
foreach ( $rewrite_checks as $check => $passed ) {
    if ( ! $passed ) {
        fwrite( STDERR, "Required rewrite check missing: {$check}\\n" );
        exit( 1 );
    }
}
$language_report = array();
foreach ( (array) $GLOBALS['polylang']->model->languages->get_list() as $language ) {
    $language_report[] = array( 'slug' => (string) $language->slug, 'locale' => (string) $language->locale, 'name' => (string) $language->name );
}
echo wp_json_encode( array( 'languages' => $language_report, 'post_types' => $option['post_types'], 'taxonomies' => $option['taxonomies'], 'published_properties' => count( $listings ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
