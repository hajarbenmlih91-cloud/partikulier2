<?php
declare(strict_types=1);

namespace Partikulier\Core;

use WP_Error;

final class ListingService
{
    public function __construct(private ListingRepository $repository, private AuditLogger $audit)
    {
    }

    public function create(array $input, int $ownerId): int|WP_Error
    {
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $locale = sanitize_key((string) ($input['locale'] ?? 'fr'));
        $price = (float) ($input['price'] ?? 0);
        $area = (float) ($input['area'] ?? 0);
        if ($title === '' || $description === '') {
            return new WP_Error('invalid_listing', __('Titre et description obligatoires.', 'partikulier-core'), ['status' => 422]);
        }
        if (!in_array($locale, ['fr', 'en', 'ar'], true) || $price < 0 || $area <= 0) {
            return new WP_Error('invalid_listing', __('Données d’annonce invalides.', 'partikulier-core'), ['status' => 422]);
        }
        $id = $this->repository->insert([
            'owner_user_id' => $ownerId,
            'external_id' => wp_generate_uuid4(),
            'status' => 'draft',
            'locale' => $locale,
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'area' => $area,
        ]);
        if (is_wp_error($id)) {
            return $id;
        }
        $this->audit->record('listing_created', 'listing', $id, ['locale' => $locale, 'price' => $price, 'area' => $area]);
        return $id;
    }
}
