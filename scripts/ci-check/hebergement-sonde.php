<?php
/**
 * Partikulier — sonde hébergement (AVIF / exec / cache) — v1
 * Usage : poser ce fichier à la RACINE du WordPress (à côté de wp-load.php),
 *         ouvrir http://domaine/pk-sonde-hebergement.php?cle=VOUS_LA_DEFINISSEZ
 *         PUIS LE SUPPRIMER. Aucune donnée n'est envoyée ailleurs : tout est calculé ici.
 * Il ne modifie rien : lectures + une conversion dans uploads/pk-sonde/ seulement.
 */
$cle = isset($_GET['cle']) ? (string) $_GET['cle'] : '';
if (!hash_equals('REMPLACER_CLE', $cle)) { http_response_code(403); exit("403 — passe ?cle=<celle que tu as mise dans le fichier>, puis supprime-le.\n"); }
define('WP_USE_THEMES', false);
require __DIR__ . '/wp-load.php';
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL & ~E_DEPRECATED);

function row($k, $v) { printf("%-46s %s\n", $k, $v); }
function yn($b) { return $b ? 'OUI' : 'NON'; }
function bytes($n) { return is_numeric($n) ? number_format((int)$n) . ' o' : (string)$n; }
function try_run($cmd, $args = '') { // n'utilise PAS exec() : shell_exec -> then passthru absent -> then return ""
    $out = '';
    if (function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))), true)) {
        $out = (string)@shell_exec($cmd . ' ' . $args . ' 2>&1');
    }
    return trim($out);
}
$disabled = array_map('trim', array_filter(explode(',', (string)ini_get('disable_functions'))));
$binaires = ['/usr/bin/avifenc' => 'avifenc', '/usr/local/bin/avifenc' => 'avifenc', '/usr/bin/vips' => 'vips', '/usr/local/bin/vips' => 'vips', '/usr/bin/convert' => 'ImageMagick', '/usr/bin/magick' => 'ImageMagick', '/usr/bin/cwebp' => 'cwebp', '/usr/bin/ffmpeg' => 'ffmpeg'];

echo "== 1. identite serveur ==\n";
row('php', PHP_VERSION . ' (' . PHP_SAPI . ')');
row('serveur logiciel', isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'inconnu');
row('litespeed detecte', yn(stripos((string)($_SERVER['SERVER_SOFTWARE'] ?? ''), 'litespeed') !== false));
row('os', PHP_OS . ' ' . php_uname('m'));
row('memoire limite', ini_get('memory_limit'));
row('max_execution_time', ini_get('max_execution_time') . ' s');

echo "\n== 2. execution de commandes (le point crucial du theme) ==\n";
foreach (['exec', 'shell_exec', 'system', 'passthru', 'popen', 'proc_open'] as $f) {
    $bloquee = in_array($f, $disabled, true);
    $dispo = function_exists($f);
    row($f . '()', $dispo ? ($bloquee ? 'DESACTIVEE par disable_functions' : 'disponible') : 'absente de PHP');
}
row('disable_functions (extrait)', implode(', ', array_slice($disabled, 0, 12)) ?: '(aucun)');
row('fallback possible sans exec', function_exists('popen') || function_exists('proc_open') ? 'oui (a confirmer par appel reel)' : 'non');

echo "\n== 3. binaires attendus par le theme (chemins codés en dur) ==\n";
foreach ($binaires as $p => $nom) { row($p, (is_executable($p) ? 'EXECUTABLE' : (file_exists($p) ? 'present mais non executable' : 'ABSENT'))); }
row('which avifenc (si shell_exec marche)', try_run('command -v avifenc') ?: '(mute — pas de shell_exec)');
row('which vips', try_run('command -v vips') ?: '(mute)');

echo "\n== 4. ce que le THEME utilisera reellement (meme code que class-avif.php) ==\n";
$gd = function_exists('gd_info') ? gd_info() : [];
row('gd chargee', yn(extension_loaded('gd')));
row('gd AVIF (cle AVIF Support)', isset($gd['AVIF Support']) ? yn((bool)$gd['AVIF Support']) : 'cle absente du gd_info');
row('function imageavif()', yn(function_exists('imageavif')));
row('wp_image_editor_supports(image/avif)', yn(wp_image_editor_supports(['mime_type' => 'image/avif'])));
row('wp_image_editor_supports(image/webp)', yn(wp_image_editor_supports(['mime_type' => 'image/webp'])));
row('imagick charge', yn(extension_loaded('imagick')));
if (class_exists('Imagick')) {
    try {
        $im = new Imagick(); $im->newPseudoImage(8, 8, 'gradient:red-blue'); $im->setImageFormat('AVIF');
        $blob = $im->getImageBlob();
        row('imagick ENCODE AVIF (test reel)', strlen($blob) > 0 ? 'OK (' . strlen($blob) . " o)" : 'ECHEC (blob vide)');
    } catch (Throwable $e) { row('imagick ENCODE AVIF (test reel)', 'ECHEC: ' . substr($e->getMessage(), 0, 70)); }
    row('imagick queryFormats contient AVIF', yn(in_array('AVIF', (array)@Imagick::queryFormats(), true)));
} else { echo "     (imagick absent : le theme passera par GD — c'est le gd_info ci-dessus qui decidera)\n"; }
if (extension_loaded('vips') || class_exists('Vips\\Image')) { row('extension vips', 'presente'); }

echo "\n== 5. conversion d'UN media reel (ecrit uniquement dans uploads/pk-sonde/) ==\n";
$up = wp_get_upload_dir();
$dir = $up['basedir'] . '/pk-sonde';
if (!is_dir($dir)) { wp_mkdir_p($dir); @file_put_contents($dir . '/.htaccess', "Require all denied\n"); }
$id = (int) get_post_meta(get_option('page_on_front'), '_thumbnail_id', true);
if (!$id) { $q = get_posts(['post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'numberposts' => 1, 'fields' => 'ids']); $id = (int) ($q[0] ?? 0); }
if (!$id) { echo "     aucun media JPEG trouve — etape 5 ignoree\n"; }
else {
    $f = get_attached_file($id);
    if (!$f || !file_exists($f)) { echo "     fichier introuvable pour l'attachment $id\n"; }
    else {
        row('media teste', basename($f) . '  (' . bytes(filesize($f)) . ')');
        $editor = wp_get_image_editor($f);
        if (is_wp_error($editor)) { echo "     editeur: WP_Error " . $editor->get_error_message() . "\n"; }
        else {
            $out = "$dir/" . basename($f) . '.avif';
            $r = $editor->save($out, 'image/avif', ['quality' => 75]);
            if (is_wp_error($r)) { echo "     sauvegarde AVIF: ECHEC (" . $r->get_error_message() . ")\n"; }
            else {
                row('AVIF obtenu', bytes(filesize($out)) . '  soit ' . round(100 * filesize($out) / max(1, filesize($f))) . '% du JPEG');
                echo "     (le theme vise -50% a -70% de poids ; en dessous de 60% tu as gagne)\n";
            }
        }
        if (function_exists('imageavif')) {
            $im = @imagecreatefromjpeg($f);
            if ($im) { $o2 = "$dir/gd-" . basename($f) . '.avif'; $ok = @imageavif($im, $o2, 75); imagedestroy($im);
                row('imageavif() direct', ($ok && file_exists($o2)) ? 'OK ' . bytes(filesize($o2)) : 'ECHEC'); }
        }
    }
}

echo "\n== 6. cache : qui sert le HTML (LiteSpeed vs cache du theme) ==\n";
$pk = $up['basedir'] . '/partikulier-cache';
row('dossier cache du theme', is_dir($pk) ? 'present (' . @count(glob($pk . '/*.html')) . ' fichiers html)' : 'absent (le module na rien ecrit)');
row('constante WP_CACHE', defined('WP_CACHE') && WP_CACHE ? 'true' : 'false');
$ls = glob($up['basedir'] . '/../../litepress') ?: glob(WP_CONTENT_DIR . '/cache');
row('plugin LiteSpeed Cache installe', is_dir(WP_PLUGIN_DIR . '/litespeed') ? 'OUI' : 'non');
if (is_dir(WP_PLUGIN_DIR . '/litespeed')) {
    echo "     ATTENTION : LiteSpeed sert son propre HTML. Les en-tetes X-Partikulier-Cache du theme\n"
       . "     ne prouvent plus rien pour un visiteur, et le purge du theme ne purgera pas le cache\n"
       . "     LiteSpeed. A trancher (soit desactiver LSCache, soit brancher une purge litespeed_purge_all()).\n";
}
row('mod_rewrite dispo (pour .htaccess)', yn(function_exists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules()) : (bool)preg_match('/litespeed|apache/i', (string)($_SERVER['SERVER_SOFTWARE'] ?? ''))));

echo "\n== 7. divers utiles au dossier ==\n";
row('wordpress', get_bloginfo('version'));
row('estatik actif', yn(is_plugin_active('estatik/estatik_plugin.php')));
row('polylang actif', yn(defined('POLYLANG_VERSION') ? true : false));
row('wp_cron', (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) ? 'DESACTIVE (le cron de synchro ne tournera pas)' : 'actif');
row('open_basedir', ini_get('open_basedir') ?: '(aucune restriction)');
echo "\nfin. supprime ce fichier maintenant : rm " . basename(__FILE__) . "\n";
