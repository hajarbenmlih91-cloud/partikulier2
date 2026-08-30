<?php
declare(strict_types=1);

namespace Partikulier\Core;

use WP_Error;

final class ListingRepository
{
    public function find(int $id): array|WP_Error
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT id, owner_user_id, external_id, status, locale, title, description, price, area, created_at, updated_at FROM ' . $wpdb->prefix . 'pk_listings WHERE id = %d',
            $id
        ), ARRAY_A);
        return $row ?: new WP_Error('listing_not_found', __('Annonce introuvable.', 'partikulier-core'), ['status' => 404]);
    }

    /** @return array<int, array<string, mixed>> */
    public function search(string $locale = 'fr', string $order = 'newest', int $page = 1, int $perPage = 24): array
    {
        global $wpdb;
        $orders = [
            'newest' => 'created_at DESC, id DESC',
            'price_asc' => 'price ASC, id DESC',
            'price_desc' => 'price DESC, id DESC',
            'area_asc' => 'area ASC, id DESC',
            'area_desc' => 'area DESC, id DESC',
        ];
        $orderBy = $orders[$order] ?? $orders['newest'];
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $offset = ($page - 1) * $perPage;

        // APCu est strictement optionnel : en présence d’un cache partagé par
        // worker, il évite de refaire la même lecture publique pendant une
        // courte fenêtre. Sans l’extension, le chemin SQL reste inchangé.
        $cacheKey = $this->searchCacheKey($locale, $order, $page, $perPage);
        if (self::apcuAvailable()) {
            $found = false;
            $cached = apcu_fetch($cacheKey, $found);
            if ($found && is_array($cached)) {
                return $cached;
            }
        }

        $sql = 'SELECT id, owner_user_id, external_id, status, locale, title, description, price, area, created_at, updated_at FROM ' . $wpdb->prefix . 'pk_listings WHERE status = %s AND locale = %s ORDER BY ' . $orderBy . ' LIMIT %d OFFSET %d';
        $rows = $wpdb->get_results($wpdb->prepare($sql, 'published', sanitize_key($locale), $perPage, $offset), ARRAY_A) ?: [];
        if (self::apcuAvailable()) {
            apcu_store($cacheKey, $rows, 2);
        }
        return $rows;
    }

    private function searchCacheKey(string $locale, string $order, int $page, int $perPage): string
    {
        $version = self::apcuAvailable() ? (int) (apcu_fetch('pk_listing_search_version') ?: 1) : 1;
        return 'pk_listing_search_' . $version . '_' . md5(sanitize_key($locale) . '|' . $order . '|' . $page . '|' . $perPage);
    }

    private static function apcuAvailable(): bool
    {
        return function_exists('apcu_enabled') && apcu_enabled() && function_exists('apcu_fetch') && function_exists('apcu_store');
    }

    private function invalidateSearchCache(): void
    {
        if (!self::apcuAvailable() || !function_exists('apcu_inc')) {
            return;
        }
        $nextVersion = apcu_inc('pk_listing_search_version');
        if (!is_int($nextVersion)) {
            apcu_store('pk_listing_search_version', 2, 0);
        }
    }

    public function syncEstatikProperties(): int
    {
        if (!function_exists('get_posts')) {
            return 0;
        }
        global $wpdb;
        $synced = 0;
        $posts = get_posts(['post_type' => 'properties', 'post_status' => 'publish', 'numberposts' => -1]);
        foreach ($posts as $post) {
            $external = 'estatik:' . (int) $post->ID;
            $exists = $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . $wpdb->prefix . 'pk_listings WHERE external_id = %s', $external));
            if ($exists) {
                continue;
            }
            $locale = function_exists('pll_get_post_language') ? (string) (pll_get_post_language((int) $post->ID, 'slug') ?: 'fr') : 'fr';
            $this->insert([
                'owner_user_id' => (int) $post->post_author,
                'external_id' => $external,
                'status' => 'published',
                'locale' => in_array($locale, ['fr', 'en', 'ar'], true) ? $locale : 'fr',
                'title' => (string) $post->post_title,
                'description' => wp_strip_all_tags((string) $post->post_content),
                'price' => (float) get_post_meta($post->ID, 'es_property_price', true),
                'area' => (float) get_post_meta($post->ID, 'es_property_area', true),
            ]);
            $synced++;
        }
        return $synced;
    }

    public function insert(array $data): int|WP_Error
    {
        global $wpdb;
        $now = gmdate('Y-m-d H:i:s');
        $ok = $wpdb->insert($wpdb->prefix . 'pk_listings', [
            'owner_user_id' => (int) $data['owner_user_id'],
            'external_id' => sanitize_text_field((string) $data['external_id']),
            'status' => sanitize_key((string) $data['status']),
            'locale' => sanitize_key((string) $data['locale']),
            'title' => sanitize_textarea_field((string) $data['title']),
            'description' => sanitize_textarea_field((string) $data['description']),
            'price' => (float) $data['price'],
            'area' => (float) $data['area'],
            'created_at' => $now,
            'updated_at' => $now,
        ], ['%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s']);
        if (!$ok) {
            return new WP_Error('listing_insert_failed', __('Création impossible.', 'partikulier-core'), ['status' => 500]);
        }
        $this->invalidateSearchCache();
        return (int) $wpdb->insert_id;
    }
}
