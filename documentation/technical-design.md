# Partikulier Core — technical design v1.7.1

## Scope

`partikulier-core` is the M0 business layer. It owns canonical listing records, search ordering, permission decisions, REST contracts, audit correlation and the health endpoint. The theme remains a presentation adapter and must not be the source of truth for business permissions or persistence.

## Runtime composition

The plugin bootstrap loads database migration, repository, policy, service, search, translation, health and REST components on `plugins_loaded`. Activation invokes the idempotent migrator. Deactivation preserves data and does not drop tables.

## Interfaces

| Service | Input | Output | Errors | Persistent effects |
|---|---|---|---|---|
| `ListingRepository::find` | integer ID | listing array or `WP_Error(404)` | not found | none |
| `ListingRepository::search` | locale, whitelisted order, page, per-page | listing array | invalid values normalize to safe defaults | none |
| `ListingRepository::insert` | validated listing data | integer ID or `WP_Error(500)` | database insert failure | creates one listing |
| `ListingService::create` | request array, owner ID | integer ID or `WP_Error(422)` | invalid title, description, locale, price or area | creates listing and audit record |
| `SearchService::search` | query array | listing array | invalid locale/order normalize safely | none |
| `HealthCheck::get` | none | non-secret status array | database unavailable is degraded | none |

All public REST routes define a permission callback and argument schema. Database values use prepared statements; sorting uses a closed server-side whitelist. User-visible values are sanitized by the service boundary and output encoding remains the responsibility of the response/template context.

## REST contract

The namespace is `partikulier/v1`. Public routes are `GET /listings`, `GET /listings/{id}` and `GET /health`. Creation is `POST /listings` and requires a logged-in user with `edit_posts` or `publish_posts`. Errors use WordPress structured `WP_Error` responses and status metadata.

## Security boundaries

The core does not accept a client-provided owner ID. It uses `get_current_user_id()`. Audit metadata removes keys that look like secrets, tokens, signatures, authorization headers, passwords or nonces before persistence. HMAC automation remains a separate integration boundary and must use its dedicated HTTP harness.

## Known limitations

The initial implementation does not yet include payments, pro commerce, the full listing media workflow, full migration rollback, native language attestations or independent UX review. REST rate limiting middleware is implemented with WordPress transients and a 429 contract, but shared object-cache behavior and production proxy identity handling remain operational validations. These capabilities remain `NOT_IMPLEMENTED` or `NOT_COVERED` in `documentation/scope-matrix.csv` and cannot contribute to the Ultra-Premium label until completed and tested.
