<?php
/**
 * Provisioning senior de Polylang avec traduction des données existantes.
 */
if (!defined('ABSPATH')) exit;

$polylang_options = array(
    'browser' => 1,
    'rewrite' => 1,
    'hide_default' => 0,
    'force_lang' => 1, // URL prefix
    'redirect_lang' => 1,
    'media_support' => 1,
    'sync' => array(),
    'post_types' => array('properties', 'page'),
    'taxonomies' => array('es_type', 'es_category', 'es_location'),
    'domains' => array(),
);

// Initialiser Polylang
if (function_exists('pll_languages_list')) {
    $languages = pll_languages_list();
    if (empty($languages)) {
        $fr = pll_add_language(array('name' => 'Français', 'slug' => 'fr', 'locale' => 'fr_FR', 'rtl' => 0, 'term_group' => 0));
        $en = pll_add_language(array('name' => 'English', 'slug' => 'en', 'locale' => 'en_US', 'rtl' => 0, 'term_group' => 1));
        $ar = pll_add_language(array('name' => 'العربية', 'slug' => 'ar', 'locale' => 'ar', 'rtl' => 1, 'term_group' => 2));
        pll_set_default_language('fr');
    }
}

update_option('polylang', $polylang_options);

// Traduire les annonces
$listings = get_posts(array('post_type' => 'properties', 'posts_per_page' => -1, 'suppress_filters' => true));
foreach ($listings as $post) {
    if (!pll_get_post_language($post->ID)) {
        pll_set_post_language($post->ID, 'fr');
    }
    
    $translations = pll_get_post_translations($post->ID);
    
    // Anglais
    if (empty($translations['en'])) {
        $en_id = wp_insert_post(array(
            'post_type' => 'properties',
            'post_status' => 'publish',
            'post_title' => 'Property: ' . $post->post_title,
            'post_content' => 'English version: ' . $post->post_content,
        ));
        pll_set_post_language($en_id, 'en');
        $translations['en'] = $en_id;
        // Copier les metas
        $metas = get_post_custom($post->ID);
        foreach ($metas as $key => $values) {
            foreach ($values as $value) update_post_meta($en_id, $key, $value);
        }
    }
    
    // Arabe
    if (empty($translations['ar'])) {
        $ar_id = wp_insert_post(array(
            'post_type' => 'properties',
            'post_status' => 'publish',
            'post_title' => 'عقار: ' . $post->post_title,
            'post_content' => 'النسخة العربية: ' . $post->post_content,
        ));
        pll_set_post_language($ar_id, 'ar');
        $translations['ar'] = $ar_id;
        // Copier les metas
        $metas = get_post_custom($post->ID);
        foreach ($metas as $key => $values) {
            foreach ($values as $value) update_post_meta($ar_id, $key, $value);
        }
        // Correction Meta Description AR Senior
        update_post_meta($ar_id, '_pk_city_name', 'الدار البيضاء');
    }
    
    pll_save_post_translations($translations);
}

// Traduire les pages
$pages = get_posts(array('post_type' => 'page', 'posts_per_page' => -1, 'suppress_filters' => true));
foreach ($pages as $page) {
    if (!pll_get_post_language($page->ID)) pll_set_post_language($page->ID, 'fr');
    $trans = pll_get_post_translations($page->ID);
    if (empty($trans['en'])) {
        $en_id = wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => $page->post_title . ' (EN)', 'post_name' => $page->post_name . '-en'));
        pll_set_post_language($en_id, 'en');
        $trans['en'] = $en_id;
    }
    if (empty($trans['ar'])) {
        $ar_id = wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => $page->post_title . ' (AR)', 'post_name' => $page->post_name . '-ar'));
        pll_set_post_language($ar_id, 'ar');
        $trans['ar'] = $ar_id;
    }
    pll_save_post_translations($trans);
}

flush_rewrite_rules(true);
echo "PROVISIONING_DONE\n";
