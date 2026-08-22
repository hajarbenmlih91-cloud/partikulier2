<?php
/**
 * Provisioning senior de Polylang avec slugs déterministes.
 */
if (!defined('ABSPATH')) exit;

// Désactiver les filtres Polylang pendant le provisioning pour éviter les interférences
$polylang_options = array(
    'browser' => 1,
    'rewrite' => 1,
    'hide_default' => 0,
    'force_lang' => 1,
    'redirect_lang' => 1,
    'media_support' => 1,
    'sync' => array(),
    'post_types' => array('properties', 'page'),
    'taxonomies' => array('es_type', 'es_category', 'es_location'),
    'domains' => array(),
);
update_option('polylang', $polylang_options);

if (function_exists('pll_languages_list')) {
    $languages = pll_languages_list();
    if (empty($languages)) {
        pll_add_language(array('name' => 'Français', 'slug' => 'fr', 'locale' => 'fr_FR', 'rtl' => 0, 'term_group' => 0));
        pll_add_language(array('name' => 'English', 'slug' => 'en', 'locale' => 'en_US', 'rtl' => 0, 'term_group' => 1));
        pll_add_language(array('name' => 'العربية', 'slug' => 'ar', 'locale' => 'ar', 'rtl' => 1, 'term_group' => 2));
        pll_set_default_language('fr');
    }
}

// Mapper les pages critiques pour avoir des slugs propres
$slug_map = array(
    'deposer-une-annonce' => 'deposer',
    'mes-annonces' => 'mes-annonces',
    'favoris' => 'favoris',
    'annonces' => 'annonces'
);

foreach ($slug_map as $old => $new) {
    $p = get_page_by_path($old);
    if ($p) wp_update_post(array('ID' => $p->ID, 'post_name' => $new));
}

// Traduire les pages
$pages = get_posts(array('post_type' => 'page', 'posts_per_page' => -1, 'suppress_filters' => true));
foreach ($pages as $page) {
    if (!pll_get_post_language($page->ID)) pll_set_post_language($page->ID, 'fr');
    $trans = pll_get_post_translations($page->ID);
    
    foreach (array('en', 'ar') as $lang) {
        if (empty($trans[$lang])) {
            $new_id = wp_insert_post(array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => $page->post_title . ' (' . strtoupper($lang) . ')',
                'post_name' => $page->post_name . '-' . $lang
            ));
            pll_set_post_language($new_id, $lang);
            $trans[$lang] = $new_id;
        }
    }
    pll_save_post_translations($trans);
}

flush_rewrite_rules(true);
echo "PROVISIONING_DONE\n";
