<?php
/** Provisioning reproductible Polylang 3.8.7 — langues, routes et famille traduite. */
if (!defined('ABSPATH')) exit;
if (!function_exists('PLL')) die("Polylang n'est pas actif.\n");

$model = PLL()->model;
$languages = $model->get_languages_list();
if (empty($languages)) {
    $model->add_language(array('name' => 'Français', 'slug' => 'fr', 'locale' => 'fr_FR', 'rtl' => 0, 'term_group' => 0));
    $model->add_language(array('name' => 'English', 'slug' => 'en', 'locale' => 'en_US', 'rtl' => 0, 'term_group' => 1));
    $model->add_language(array('name' => 'العربية', 'slug' => 'ar', 'locale' => 'ar', 'rtl' => 1, 'term_group' => 2));
}

$options = get_option('polylang', array());
$options['default_lang'] = 'fr';
$options['force_lang'] = 1;
$options['rewrite'] = 1;
$options['hide_default'] = 0;
$options['browser'] = 1; // Polylang négocie AR/EN; le mu-plugin bloque uniquement une cible identique à la requête.
$options['post_types'] = array_values(array_unique(array_merge((array)($options['post_types'] ?? array()), array('page', 'properties'))));
$options['taxonomies'] = array_values(array_unique(array_merge((array)($options['taxonomies'] ?? array()), array('es_type', 'es_category', 'es_location'))));
update_option('polylang', $options, false);

$pages = array(
    'deposer' => array(
        'fr' => array('title' => 'Déposer une annonce', 'slug' => 'deposer', 'template' => 'templates/page-deposer-annonce.php'),
        'en' => array('title' => 'Submit a listing', 'slug' => 'deposer-en', 'template' => 'templates/page-deposer-annonce.php'),
        'ar' => array('title' => 'إضافة إعلان', 'slug' => 'deposer-ar', 'template' => 'templates/page-deposer-annonce.php'),
    ),
    'mes-annonces' => array(
        'fr' => array('title' => 'Mes annonces', 'slug' => 'mes-annonces', 'template' => 'templates/page-mes-annonces.php'),
        'en' => array('title' => 'My listings', 'slug' => 'mes-annonces-en', 'template' => 'templates/page-mes-annonces.php'),
        'ar' => array('title' => 'إعلاناتي', 'slug' => 'mes-annonces-ar', 'template' => 'templates/page-mes-annonces.php'),
    ),
    'favoris' => array(
        'fr' => array('title' => 'Favoris', 'slug' => 'favoris', 'template' => 'templates/page-favoris.php'),
        'en' => array('title' => 'Favorites', 'slug' => 'favoris-en', 'template' => 'templates/page-favoris.php'),
        'ar' => array('title' => 'المفضلة', 'slug' => 'favoris-ar', 'template' => 'templates/page-favoris.php'),
    ),
);
foreach ($pages as $translations) {
    $ids = array();
    foreach ($translations as $lang => $data) {
        $page = get_page_by_path($data['slug'], OBJECT, 'page');
        $id = $page ? $page->ID : wp_insert_post(array('post_title' => $data['title'], 'post_name' => $data['slug'], 'post_status' => 'publish', 'post_type' => 'page'), true);
        if (is_wp_error($id)) continue;
        update_post_meta($id, '_wp_page_template', $data['template']);
        pll_set_post_language($id, $lang);
        $ids[$lang] = (int) $id;
    }
    if (count($ids) === 3) pll_save_post_translations($ids);
}

// Famille de contenu officielle pour I18N-01, sans dépasser les 30 annonces de la fixture.
$family = array();
$titles = array('fr' => 'Maison témoin Casablanca', 'en' => 'Casablanca sample home', 'ar' => 'منزل نموذجي في الدار البيضاء');
$fixture_posts = get_posts(array('post_type' => 'properties', 'post_status' => 'publish', 'posts_per_page' => 3, 'orderby' => 'ID', 'order' => 'ASC'));
foreach (array('fr', 'en', 'ar') as $index => $lang) {
    if (empty($fixture_posts[$index])) continue;
    $id = (int) $fixture_posts[$index]->ID;
    wp_update_post(array('ID' => $id, 'post_title' => $titles[$lang]));
    pll_set_post_language($id, $lang);
    $family[$lang] = $id;
}
if (count($family) === 3) pll_save_post_translations($family);

// Les appels pll_save_post_translations peuvent réécrire l’option avec le cache initial de Polylang.
// Réappliquer le contrat après toutes les liaisons garantit le même état en recette froide.
$options = get_option('polylang', array());
$options['default_lang'] = 'fr';
$options['force_lang'] = 1;
$options['rewrite'] = 1;
$options['hide_default'] = 0;
$options['browser'] = 1;
$options['post_types'] = array_values(array_unique(array_merge((array)($options['post_types'] ?? array()), array('page', 'properties'))));
$options['taxonomies'] = array_values(array_unique(array_merge((array)($options['taxonomies'] ?? array()), array('es_type', 'es_category', 'es_location'))));
update_option('polylang', $options, false);

flush_rewrite_rules(true);
echo "PROVISIONING_DONE\n";
