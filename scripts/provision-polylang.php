<?php
/**
 * Provisioning senior de Polylang autonome v6.17.5.
 * Gère les langues, les options et la liaison des pages.
 */
if (!defined('ABSPATH')) exit;

if (!function_exists('PLL')) {
    die("Polylang n'est pas actif.\n");
}

$model = PLL()->model;
$languages = $model->get_languages_list();

if (empty($languages)) {
    echo "Création des langues...\n";
    $model->add_language(array('name' => 'Français', 'slug' => 'fr', 'locale' => 'fr_FR', 'rtl' => 0, 'term_group' => 0));
    $model->add_language(array('name' => 'English', 'slug' => 'en', 'locale' => 'en_US', 'rtl' => 0, 'term_group' => 1));
    $model->add_language(array('name' => 'العربية', 'slug' => 'ar', 'locale' => 'ar', 'rtl' => 1, 'term_group' => 2));
    
    $options = get_option('polylang');
    $options['default_lang'] = 'fr';
    update_option('polylang', $options);
}

// Configuration des types de post et taxonomies
$options = get_option('polylang');
$options['post_types'] = array_unique(array_merge((array)($options['post_types'] ?? array()), array('properties', 'page')));
$options['taxonomies'] = array_unique(array_merge((array)($options['taxonomies'] ?? array()), array('es_type', 'es_category', 'es_location')));
$options['force_lang'] = 1; 
$options['rewrite'] = 1;    
update_option('polylang', $options);

// Liaison des pages critiques
$pages_to_sync = array(
    'deposer' => array('en' => 'deposer-en', 'ar' => 'deposer-ar', 'title_en' => 'Submit', 'title_ar' => 'إضافة إعلان'),
    'mes-annonces' => array('en' => 'mes-annonces-en', 'ar' => 'mes-annonces-ar', 'title_en' => 'My listings', 'title_ar' => 'إعلاناتي'),
    'favoris' => array('en' => 'favoris-en', 'ar' => 'favoris-ar', 'title_en' => 'Favorites', 'title_ar' => 'المفضلة')
);

foreach ($pages_to_sync as $fr_slug => $translations) {
    $fr_page = get_page_by_path($fr_slug, OBJECT, 'page');
    if (!$fr_page) continue;
    
    pll_set_post_language($fr_page->ID, 'fr');
    $trans_ids = array('fr' => $fr_page->ID);
    
    foreach (array('en', 'ar') as $lang) {
        $slug = $translations[$lang];
        $title = $translations['title_' . $lang];
        
        $existing = get_page_by_path($slug, OBJECT, 'page');
        if (!$existing) {
            $id = wp_insert_post(array(
                'post_title' => $title,
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_type' => 'page'
            ));
        } else {
            $id = $existing->ID;
        }
        
        pll_set_post_language($id, $lang);
        $trans_ids[$lang] = $id;
    }
    
    pll_save_post_translations($trans_ids);
}

flush_rewrite_rules(true);
echo "PROVISIONING_DONE\n";
