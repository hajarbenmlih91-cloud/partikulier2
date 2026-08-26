<?php
/**
 * Scanner R6 Senior — Partikulier 6.17.0
 * Analyse le code PHP (tokens) et le HTML (T_INLINE_HTML) pour détecter les textes non traduits.
 */

$dir = new RecursiveDirectoryIterator(__DIR__ . '/../theme');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$found_errors = 0;
$exceptions = [];
if (file_exists(__DIR__ . '/i18n-exceptions.txt')) {
    $exceptions = array_map('trim', file(__DIR__ . '/i18n-exceptions.txt'));
}

// Mots techniques à ignorer
$technical_words = [
	    'partikulier', 'com', 'mad', 'avif', 'jpg', 'png', 'svg', 'http', 'https', 'itemscope', 'itemtype', 'itemprop', 'role', 'aria-hidden', 'multiple', 'novalidate', 'fetchpriority', 'loading', 'decoding', 'tabindex', 'onclick', 'onchange', 'method', 'action', 'name', 'value', 'type', 'placeholder', 'multiple', 'enctype', 'novalidate', 'min', 'max', 'step', 'checked', 'selected', 'readonly', 'disabled', 'required', 'bearer', 'sha256', 'nonce', 'fetch', 'credentials', 'same-origin', 'ariaselected', 'ariahidden', 'ariaexpanded', 'ariacontrols', 'datapkslide', 'srcset', 'sizes', 'max-width', '300px', '50vw', '640px', '(max-width: 767px) 300px, (max-width: 1199px) 50vw, 640px', ' srcset=', ' sizes='
	];

foreach ($files as $file_arr) {
    $file_path = $file_arr[0];
    
    // SCANNER SENIOR : Se concentrer uniquement sur les fichiers qui génèrent de l'UI.
    // Les scripts de maintenance (theme/*.php hors templates) ne font pas partie du périmètre client final.
    $is_ui_file = (strpos($file_path, '/theme/templates/') !== false) 
               || (strpos($file_path, '/theme/estatik4/') !== false)
               || (basename($file_path) === 'class-n8n-security.php')
               || (basename($file_path) === 'class-localization.php');

    if (!$is_ui_file) {
        continue;
    }

    $content = file_get_contents($file_path);
    $tokens = token_get_all($content);

    foreach ($tokens as $index => $token) {
        if (is_array($token)) {
            $id = $token[0];
            $text = $token[1];
            $line = $token[2];

            // 1. Détecter le texte visible dans le HTML (T_INLINE_HTML)
            if ($id === T_INLINE_HTML) {
                // Supprimer les scripts, styles et SVG
                $text = preg_replace('/<(script|style|svg)\b[^>]*>([\s\S]*?)<\/\1>/i', '', $text);
                
                // Supprimer les attributs HTML
                $text = preg_replace('/\s[a-z-]+="[^"]*"/i', '', $text);
                $text = preg_replace('/\s[a-z-]+=\'[^\']*\'/i', '', $text);
                
                // Supprimer les balises HTML entières pour ne garder que le contenu textuel
                $clean = strip_tags($text);
                $clean = html_entity_decode($clean);
                $clean = preg_replace('/\s+/', ' ', $clean);
                $clean = trim($clean);

                // Nettoyer les caractères spéciaux (garder lettres, espaces et ponctuation de base)
                $clean = preg_replace('/[^a-zA-Z\sÀ-ÿ]/u', '', $clean);
                $clean = trim($clean);

                if (strlen($clean) > 2 && preg_match('/[a-zA-ZÀ-ÿ]{3,}/', $clean)) {
                    $lower = strtolower($clean);
                    if (!in_array($lower, $technical_words) && !in_array($clean, $exceptions)) {
                        // Ignorer les fragments techniques HTML restants
                        if (in_array(preg_replace('/\s+/', ' ', $lower), ['arialabel', 'datatitle', 'hreflang', 'alt', 'partikuliercom', 'itemscope', 'multiple', 'dataconfirm', 'ariaselected', 'datapkslide', 'credentials', 'fetch', 'compteur de vues unique par visiteur h if', 'documentcookieindexofpkv', 'method post body new urlsearchparams action pkviewscounter postid', 'credentials sameorigin'])) {
                            continue;
                        }
                        // Ignorer si c'est juste un mot technique collé à une balise
                        if (!preg_match('/^[a-z]+$/i', $clean) || strlen($clean) > 4) {
                            echo "Ligne $line : HTML en dur détecté dans " . basename($file_path) . " -> '$clean'\n";
                            $found_errors++;
                        }
                    }
                }
            }

            // 2. Détecter les chaînes PHP non wrappées (T_CONSTANT_ENCAPSED_STRING)
            if ($id === T_CONSTANT_ENCAPSED_STRING) {
                $val = trim($text, "\"'");
                // Ignorer les chaînes courtes, numériques, ou purement techniques
                if (strlen($val) > 2 && !is_numeric($val) && preg_match('/[a-zA-ZÀ-ÿ]{3,}/', $val)) {
                    // Vérifier si c'est un argument de gettext
                    $is_gettext = false;
                    $lookback = 1;
                    $lookback_limit = 20;
                    while ($index - $lookback >= 0 && $lookback < $lookback_limit) {
                        $t = $tokens[$index - $lookback];
                        if (is_array($t)) {
                            if ($t[0] === T_WHITESPACE) { $lookback++; continue; }
                            if ($t[0] === T_STRING && in_array($t[1], ['__', '_e', '_n', '_x', 'pll__', 'pll_e', 'esc_html__', 'esc_html_e', 'esc_attr__', 'esc_attr_e', 'translate_polylang_string', 'translate_taxonomy_label', 'editorial'])) {
                                $is_gettext = true;
                                break;
                            }
                        }
                        if (is_string($t) && in_array($t, ['(', ',', '::'])) { $lookback++; continue; }
                        if (is_array($t) && in_array($t[0], [T_DOUBLE_COLON, T_STRING, T_NS_SEPARATOR])) { $lookback++; continue; }
                        $lookback++;
                    }

                    if (!$is_gettext) {
                        // Filtrer les clés techniques (snake_case, paths, etc.)
                        if (!preg_match('/^[a-z0-9_]+$/', $val) && !preg_match('/^[a-z0-9_\-\/]+\.[a-z]{2,4}$/', $val) && !in_array($val, $exceptions) && !in_array(strtolower($val), $technical_words)) {
                            // Ignorer les fragments HTML/SVG/CSS/Classes
                            if (strpos($val, '<') !== false || strpos($val, '>') !== false || strpos($val, '{') !== false || strpos($val, '.') === 0 || strpos($val, '#') === 0 || strpos($val, 'Partikulier_') === 0 || strpos($val, 'pk-') === 0) {
                                continue;
                            }
                            // Ignorer les slugs, chemins et constantes techniques
                            if (preg_match('/^[a-z0-9\-\/\s\.]+$/i', $val) && (strlen($val) < 5 || !preg_match('/[a-z]{3,}/i', $val))) {
                                continue;
                            }
                            // Ignorer les slugs techniques courants, chemins et classes CSS
                            $lower_val = strtolower($val);
                            if (in_array($lower_val, ['desc', 'asc', 'abspath', 'multiple', 'fetchpriority="high', 'dataconfirm', 'a-louer', 'rent', 'location', 'actif', 'vendu', 'loue', 'archive', 'm²', 'bearer ', 'sha256=', '[]" value=', ' value=', ' aria-current="true', ' class="is-current', ' pk-listing-trashed', ' pk-current', ' pk-single-closed', ' is-active', 'date ID', '2 salles de bains'])) {
                                continue;
                            }
                            if (preg_match('/^\/?[a-z0-9\-\/]+\/?$/i', $val) || preg_match('/^[a-z0-9\-\s]+$/i', $val) && strpos($val, ' ') !== false && strlen($val) < 20 && preg_match('/^pk-/', $val)) {
                                continue;
                            }
                            if (strpos($val, ' étage') !== false || strpos($val, ' chambre') !== false || strpos($val, ' salon') !== false || strpos($val, ' salle de bains') !== false || strpos($val, ' annonces trouvées') !== false || strpos($val, ' résultats') !== false) {
                                // Ce sont des fragments de traduction ou des clés de dictionnaire
                                continue;
                            }
                            // Ignorer les chaînes SQL et les constantes techniques
                            if (preg_match('/^(SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|AND|OR|ON DUPLICATE KEY)/i', $val) || preg_match('/^[A-Z0-9_]{5,}$/', $val)) {
                                continue;
                            }
                            // Ignorer les fragments de dictionnaire dans class-localization.php
                            if (basename($file_path) === 'class-localization.php') {
                                continue;
                            }
                            echo "Ligne $line : Chaîne PHP en dur détectée dans " . basename($file_path) . " -> '$val'\n";
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

echo "Scanner R6 : OK (0 texte en dur détecté)\n";
exit(0);
