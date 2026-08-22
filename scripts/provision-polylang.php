<?php
/**
 * Provisioning senior de Polylang autonome v6.17.6.
 * Gère les langues, les options, la liaison des pages et le rafraîchissement des permaliens.
 */
if (!defined('ABSPATH')) exit;

if (!function_exists('PLL')) {
    die("Polylang n'est pas actif.\n");
}

$model = PLL()->model;
$languages = $model->get_languages_list();

// 1. Création des langues si absentes
if (empty($languages)) {
    echo "Création des langues...\n";
    $model->add_language(array('name' => 'Français', 'slug' => 'fr', 'locale' => 'fr_FR', 'rtl' => 0, 'term_group' => 0));
    $model->add_language(array('name' => 'English', 'slug' => 'en', 'locale' => 'en_US', 'rtl' => 0, 'term_group' => 1));
    $model->add_language(array('name' => 'العربية', 'slug' => 'ar', 'locale' => 'ar', 'rtl' => 1, 'term_group' => 2));
    
    $options = get_option('polylang');
    $options['default_lang'] = 'fr';
    update_option('polylang', $options);
}

// 2. Configuration des types de post et taxonomies
$options = get_option('polylang');
$options['post_types'] = array_unique(array_merge((array)($options['post_types'] ?? array()), array('properties', 'page')));
$options['taxonomies'] = array_unique(array_merge((array)($options['taxonomies'] ?? array()), array('es_type', 'es_category', 'es_location')));
$options['force_lang'] = 1; // Ajouter le slug de langue à l'URL
$options['rewrite'] = 1;    // Supprimer /language/ de l'URL
$options['hide_default'] = 0; // Ne pas cacher la langue par défaut pour éviter les 404 asymétriques
update_option('polylang', $options);

// 3. Liaison des pages critiques
$pages_to_sync = array(
    'deposer' => array(
        'fr' => array('title' => 'Déposer une annonce', 'slug' => 'deposer'),
        'en' => array('title' => 'Submit a listing', 'slug' => 'deposer-en'),
        'ar' => array('title' => 'إضافة إعلان', 'slug' => 'deposer-ar')
    ),
    'mes-annonces' => array(
        'fr' => array('title' => 'Mes annonces', 'slug' => 'mes-annonces'),
        'en' => array('title' => 'My listings', 'slug' => 'mes-annonces-en'),
        'ar' => array('title' => 'إعلاناتي', 'slug' => 'mes-annonces-ar')
    ),
    'favoris' => array(
        'fr' => array('title' => 'Favoris', 'slug' => 'favoris'),
        'en' => array('title' => 'Favorites', 'slug' => 'favoris-en'),
        'ar' => array('title' => 'المفضلة', 'slug' => 'favoris-ar')
    )
);

foreach ($pages_to_sync as $key => $translations) {
    $trans_ids = array();
    
    foreach ($translations as $lang => $data) {
        $page = get_page_by_path($data['slug'], OBJECT, 'page');
        if (!$page) {
            $id = wp_insert_post(array(
                'post_title' => $data['title'],
                'post_name' => $data['slug'],
                'post_status' => 'publish',
                'post_type' => 'page'
            ));
        } else {
            $id = $page->ID;
        }
        pll_set_post_language($id, $lang);
        $trans_ids[$lang] = $id;
    }
    pll_save_post_translations($trans_ids);
}

// 4. Rafraîchissement des permaliens
flush_rewrite_rules(true);
echo "PROVISIONING_DONE\n";
