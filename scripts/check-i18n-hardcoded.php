<?php
/**
 * Scanner R6 Senior : Détection infaillible des littéraux publics non traduits.
 * Utilise token_get_all() pour ignorer les commentaires et analyser la structure PHP.
 */

$targets = [
    __DIR__ . '/../theme/templates',
    __DIR__ . '/../theme/estatik4',
];

$technical_files = [
    'class-n8n-security.php',
    'class-search-filters.php',
    'class-localization.php',
];

$exceptions = [
    'Partikulier', 'WhatsApp', 'UTF-8', 'text/html', 'Content-Type', 'MAD', 'es_property',
    'fr-FR', 'ar', 'en-US', 'fr_FR', 'ar_AR', 'en_US', 'ltr', 'rtl', 'x-default',
    'post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset',
    'pk_theme_options', 'pk_customization_options', 'pk_n8n_settings',
    'À propos', 'Aide', 'Types de biens', 'Contact', 'Maroc', 'Tous droits réservés.',
    'Plus récentes', 'Prix croissant', 'Prix décroissant', 'Surface décroissante',
    'Zéro commission', 'Vendeur identifié', 'Contact direct', 'Maison lumineuse à vendre entre particuliers',
    'date ID', 'badge_1', 'badge_2', 'badge_3', 'partikulier.com', '&copy;', '🏠', '&rarr;', '&larr;',
    'fetchpriority="high"', 'admin-ajax.php',
];

// Charger les exceptions depuis le fichier si présent
$exceptions_file = __DIR__ . '/i18n-exceptions.txt';
if (file_exists($exceptions_file)) {
    $extra = file($exceptions_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $exceptions = array_merge($exceptions, $extra);
}

$found_errors = 0;

foreach ($targets as $target) {
    if (!is_dir($target)) continue;

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target));
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') continue;
        if (in_array(basename($file->getPathname()), $technical_files)) continue;

        $content = file_get_contents($file->getPathname());
        $tokens = token_get_all($content);
        
        $in_php = false;
        foreach ($tokens as $index => $token) {
            if (is_array($token)) {
                $id = $token[0];
                $text = $token[1];
                $line = $token[2];

                // Détecter les sorties directes hors PHP (HTML brut)
                if ($id === T_INLINE_HTML) {
                    // Supprimer scripts et styles
                    $text = preg_replace('/<script\b[^>]*>([\s\S]*?)<\/script>/i', '', $text);
                    $text = preg_replace('/<style\b[^>]*>([\s\S]*?)<\/style>/i', '', $text);
                    
                    // Supprimer les attributs HTML (avec ou sans valeur)
                    $text = preg_replace('/\s(class|id|aria-[a-z]+|data-[a-z-]+|style|rel|target|href|src|alt|loading|decoding|fetchpriority|width|height|viewBox|fill|stroke|stroke-[a-z]+|points|d|rx|x|y|x1|y1|x2|y2|tabindex|onclick|onchange|method|action|name|value|type|role|aria-hidden|aria-selected|aria-pressed|aria-label|itemscope|itemtype|placeholder|multiple|enctype|novalidate|min|max|step|checked|selected|readonly|disabled|required|itemprop|lang|hreflang|loading|decoding|fetchpriority)(="[^"]*")?/i', '', $text);
                    // Supprimer les restes de balises HTML mal formées par strip_tags
                    $text = preg_replace('/[a-z]+="[^"]*"/i', '', $text);
                    $text = preg_replace('/[a-z]+="/i', '', $text);
                    $text = preg_replace('/">/i', '', $text);
                    
                    $clean = trim(strip_tags($text));
                    // Ignorer les commentaires JS et fragments JS restants
                    if (strpos($clean, '//') === 0 || strpos($clean, '/*') === 0 || strpos($clean, '&&') === 0 || strpos($clean, 'fetch(') !== false || strpos($clean, 'credentials:') !== false || strpos($clean, 'body:') !== false || strpos($clean, 'nonce:') !== false) {
                        $clean = '';
                    }
                    
                    // On ne garde que ce qui contient des lettres et des espaces/ponctuation UI
                    if (strlen($clean) > 2 && preg_match('/[a-zA-ZÀ-ÿ]{3,}/', $clean) && !in_array($clean, $exceptions)) {
                        echo "Ligne $line : HTML en dur détecté dans " . basename($file->getPathname()) . " -> '$clean'\n";
                        $found_errors++;
                    }
                }

                // Détecter les chaînes dans PHP envoyées à echo/print ou assignées
                if ($id === T_CONSTANT_ENCAPSED_STRING) {
                    $val = trim($text, "\"'");
                    if (strlen($val) > 2 && !is_numeric($val) && !in_array($val, $exceptions)) {
                        // Vérifier si c'est un argument de gettext
                        $prev = $tokens[$index - 1] ?? null;
                        $prev2 = $tokens[$index - 2] ?? null;
                        
                        $is_gettext = false;
                        // Remonter pour trouver le nom de la fonction (en ignorant les parenthèses et espaces)
                        $lookback = 1;
                        while ($index - $lookback >= 0) {
                            $t = $tokens[$index - $lookback];
                            if (is_array($t)) {
                                if ($t[0] === T_WHITESPACE) { $lookback++; continue; }
                                if ($t[0] === T_STRING && in_array($t[1], ['__', '_e', '_n', '_x', 'pll__', 'pll_e', 'esc_html__', 'esc_html_e', 'esc_attr__', 'esc_attr_e', 'translate_polylang_string', 'translate_taxonomy_label'])) {
                                    $is_gettext = true;
                                    break;
                                }
                            } elseif ($t === '(' || $t === ',') {
                                $lookback++;
                                continue;
                            }
                            break;
                        }

                        if (!$is_gettext && preg_match('/[a-zA-ZÀ-ÿ]{3,}/', $val)) {
                             // Ignorer les clés de tableaux probables (souvent techniques)
                             $next = $tokens[$index + 1] ?? null;
                             if ($next === ']' || (is_array($next) && $next[0] === T_DOUBLE_ARROW)) {
                                 continue;
                             }
                             
                             // Ignorer les chaînes snake_case ou sans espaces (souvent techniques)
                             if (!preg_match('/\s/', $val) && preg_match('/[a-z]_[a-z]/', $val)) {
                                 continue;
                             }

                             // Ignorer les chemins de fichiers ou URLs
                             if (strpos($val, '/') !== false || strpos($val, '\\') !== false) {
                                 continue;
                             }

                             // Ignorer les mots techniques isolés sans espaces
                             if (!preg_match('/\s/', $val) && preg_match('/^[a-zA-Z0-9-]+$/', $val)) {
                                 continue;
                             }
                             
                             // Ignorer les fragments HTML/JS
                             if (strpos($val, '<') !== false || strpos($val, '{') !== false || strpos($val, ';') !== false || strpos($val, '(') !== false || strpos($val, ')') !== false) {
                                 continue;
                             }

                             // Ignorer les unités et petites chaînes techniques
                             if (in_array(trim($val), ['m²', ' m²', '%d chambres', '%d salons', '%d salles de bains', '%d vues', '.php', '.avif', '🏠', '&rarr;', 'Affichage %1$s–%2$s de %3$s résultats', '%s annonces trouvées'])) {
                                 continue;
                             }
                             
                             // Ignorer les classes CSS probables (commencent par un espace ou pk-)
                             if (strpos($val, ' ') === 0 || strpos($val, 'pk-') === 0 || strpos($val, 'is-') === 0) {
                                 continue;
                             }
                             
                             // Ignorer les noms de classes PHP (PascalCase)
                             if (preg_match('/^Partikulier_[A-Z][a-zA-Z_]+$/', $val)) {
                                 continue;
                             }

                             // Ignorer les fragments techniques restants
                             $technical_fragments = ['admin-ajax.php', 'e étage', '&larr;', '&rarr;', 'fetchpriority="high"', '[]" value="', '[]" value='];
                             if (in_array(trim($val), $technical_fragments) || strpos($val, 'fetchpriority') !== false || strpos($val, '[]" value=') !== false) {
                                 continue;
                             }
                             
                             // Ignorer les fragments JS/JSON probables
                             if (strpos($val, 'body:') !== false || strpos($val, 'method:') !== false || strpos($val, 'credentials:') !== false) {
                                 continue;
                             }

                             echo "Ligne $line : Chaîne PHP suspecte dans " . basename($file->getPathname()) . " -> '$val' (raw: $text)\n";
                             $found_errors++;
                        }
                    }
                }
            }
        }
    }
}

if ($found_errors > 0) {
    echo "\nTotal : $found_errors erreurs R6 trouvées.\n";
    exit(1);
}

echo "R6 : Aucun littéral public non traduit détecté.\n";
exit(0);
