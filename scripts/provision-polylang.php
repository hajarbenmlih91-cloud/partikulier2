<?php
/**
 * Provisioning senior de Polylang compatible WP 7.1 et Polylang 3.8.7.
 */
if (!defined('ABSPATH')) exit;

if (!function_exists('pll_languages_list')) {
    die("Polylang n'est pas actif.\n");
}

$model = PLL()->model;
$languages = $model->get_languages_list();

if (empty($languages)) {
    // Utilisation de l'API objet recommandée
    $model->add_language(array('name' => 'Français', 'slug' => 'fr', 'locale' => 'fr_FR', 'rtl' => 0, 'term_group' => 0));
    $model->add_language(array('name' => 'English', 'slug' => 'en', 'locale' => 'en_US', 'rtl' => 0, 'term_group' => 1));
    $model->add_language(array('name' => 'العربية', 'slug' => 'ar', 'locale' => 'ar', 'rtl' => 1, 'term_group' => 2));
    
    // Définir la langue par défaut via les options Polylang
    $options = get_option('polylang');
    $options['default_lang'] = 'fr';
    update_option('polylang', $options);
}

// Activer les types de post et taxonomies
$options = get_option('polylang');
$options['post_types'] = array_unique(array_merge((array)($options['post_types'] ?? array()), array('properties', 'page')));
$options['taxonomies'] = array_unique(array_merge((array)($options['taxonomies'] ?? array()), array('es_type', 'es_category', 'es_location')));
$options['force_lang'] = 1; // URL prefix
update_option('polylang', $options);

// Flush pour prendre en compte les nouveaux slugs
flush_rewrite_rules(true);
echo "PROVISIONING_DONE\n";
