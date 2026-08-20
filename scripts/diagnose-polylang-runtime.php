<?php
$out = array(
	'active' => function_exists( 'pll_languages_list' ),
	'global_polylang' => isset( $GLOBALS['polylang'] ) ? get_class( $GLOBALS['polylang'] ) : null,
	'global_keys' => isset( $GLOBALS['polylang'] ) && is_object( $GLOBALS['polylang'] ) ? array_keys( get_object_vars( $GLOBALS['polylang'] ) ) : array(),
	'model_class' => isset( $GLOBALS['polylang']->model ) && is_object( $GLOBALS['polylang']->model ) ? get_class( $GLOBALS['polylang']->model ) : null,
	'model_keys' => isset( $GLOBALS['polylang']->model ) && is_object( $GLOBALS['polylang']->model ) ? array_keys( get_object_vars( $GLOBALS['polylang']->model ) ) : array(),
	'functions' => array_filter( array( 'pll_languages_list', 'pll_set_post_language', 'pll_save_post_translations', 'pll_get_post_translations', 'pll_get_post_language' ), 'function_exists' ),
);
echo wp_json_encode( $out, JSON_PRETTY_PRINT ) . PHP_EOL;
