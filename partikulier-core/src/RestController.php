<?php
declare(strict_types=1);

namespace Partikulier\Core;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class RestController
{
    private ListingRepository $repository;
    private ListingPolicy $policy;
    private SearchService $search;
    private ListingService $service;
    private HealthCheck $health;
    private LeadService $leads;
    private FavoriteService $favorites;
    private RateLimiter $rateLimiter;

    public function __construct()
    {
        $audit = new AuditLogger();
        $this->repository = new ListingRepository();
        $this->policy = new ListingPolicy();
        $this->search = new SearchService($this->repository);
        $this->service = new ListingService($this->repository, $audit);
        $this->leads = new LeadService();
        $this->favorites = new FavoriteService();
        $this->rateLimiter = new RateLimiter();
        $this->health = new HealthCheck();
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route('partikulier/v1', '/listings', [
            'methods' => 'GET',
            'callback' => [$this, 'listings'],
            'permission_callback' => [$this, 'guardPublic'],
            'args' => $this->listArgs(),
        ]);
        register_rest_route('partikulier/v1', '/listings/(?P<id>[0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'listing'],
            'permission_callback' => [$this, 'guardPublic'],
            'args' => ['id' => ['required' => true, 'validate_callback' => static fn($value): bool => ctype_digit((string) $value)]],
        ]);
        register_rest_route('partikulier/v1', '/listings', [
            'methods' => 'POST',
            'callback' => [$this, 'createListing'],
            'permission_callback' => [$this, 'guardWrite'],
            'args' => [
                'title' => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'],
                'description' => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'],
                'locale' => ['required' => false, 'default' => 'fr', 'type' => 'string'],
                'price' => ['required' => true, 'type' => 'number', 'minimum' => 0],
                'area' => ['required' => true, 'type' => 'number', 'minimum' => 0.01],
            ],
        ]);
        register_rest_route('partikulier/v1', '/leads', [
            'methods' => 'POST',
            'callback' => [$this, 'createLead'],
            'permission_callback' => [$this, 'guardLead'],
            'args' => [
                'email' => ['required' => true, 'type' => 'string'],
                'message' => ['required' => true, 'type' => 'string'],
            ],
        ]);
        register_rest_route('partikulier/v1', '/favorites', [
            'methods' => 'POST',
            'callback' => [$this, 'toggleFavorite'],
            'permission_callback' => [$this, 'guardPrivate'],
            'args' => ['listing_id' => ['required' => true, 'type' => 'integer', 'minimum' => 1]],
        ]);
        register_rest_route('partikulier/v1', '/health', [
            'methods' => 'GET',
            'callback' => fn(): WP_REST_Response => new WP_REST_Response($this->health->get(), 200),
            'permission_callback' => [$this, 'guardPublic'],
        ]);
    }

    public function guardPublic(WP_REST_Request $request): bool|WP_Error
    {
        return $this->rateLimiter->guard($request, 'public', $this->policy->canReadPublic(), 120, 60);
    }

    public function guardWrite(WP_REST_Request $request): bool|WP_Error
    {
        return $this->rateLimiter->guard($request, 'write', $this->policy->canCreate(), 30, 60);
    }

    public function guardLead(WP_REST_Request $request): bool|WP_Error
    {
        return $this->rateLimiter->guard($request, 'lead', $this->policy->canReadPublic(), 10, 60);
    }

    public function guardPrivate(WP_REST_Request $request): bool|WP_Error
    {
        return $this->rateLimiter->guard($request, 'private', $this->policy->canReadPrivate(), 60, 60);
    }

    private function listArgs(): array
    {
        return [
            'locale' => ['required' => false, 'default' => 'fr', 'type' => 'string'],
            'order' => ['required' => false, 'default' => 'newest', 'enum' => SearchService::ALLOWED_ORDERS, 'type' => 'string'],
            'page' => ['required' => false, 'default' => 1, 'type' => 'integer', 'minimum' => 1],
            'per_page' => ['required' => false, 'default' => 24, 'type' => 'integer', 'minimum' => 1, 'maximum' => 100],
        ];
    }

    public function listings(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response(['data' => $this->search->search($request->get_params()), 'page' => (int) $request['page']], 200);
    }

    public function listing(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $result = $this->repository->find((int) $request['id']);
        return is_wp_error($result) ? $result : new WP_REST_Response(['data' => $result], 200);
    }

    public function createListing(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = $this->service->create($request->get_json_params() ?: $request->get_params(), get_current_user_id());
        return is_wp_error($id) ? $id : new WP_REST_Response(['id' => $id, 'status' => 'draft'], 201);
    }

    public function createLead(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $result = $this->leads->create($request->get_json_params() ?: $request->get_params());
        return is_wp_error($result) ? $result : new WP_REST_Response(['data' => $result], 201);
    }

    public function toggleFavorite(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $result = $this->favorites->toggle((int) $request['listing_id'], get_current_user_id());
        return is_wp_error($result) ? $result : new WP_REST_Response(['data' => $result], 200);
    }
}
