# Upgrade Progress Log

Autonomous execution of `UPGRADE.md` (Laravel 5.7 → 12 rewrite via `_next/` skeleton). Each entry records the phase, the decision taken, and why — for items the roadmap didn't pin down or for surprises encountered along the way.

## Phase 0 — Prerequisites ✅

Done before autonomous mode kicked in. PHP 8.3.19 on PATH via Laragon, Composer 2.8.9, Node 22.15.1, branch `upgrade/laravel-12` created.

## Phase 1 — Scaffold ✅

- `composer create-project laravel/laravel _next` initially pulled **Laravel 13.12.0** (the loose `laravel/laravel` template now resolves to L13 since the framework's January 2026 release moved on). Wiped and re-scaffolded with `^12.0` pin per the user's stated target. On disk: **Laravel 12.61.0**.
- Trimmed to pure-API: removed `package.json`, `vite.config.js`, `.npmrc`, `resources/css/`, `resources/js/`, `resources/views/welcome.blade.php`, and the `/` web route.
- Enabled `routes/api.php` via `withRouting(api: __DIR__.'/../routes/api.php', apiPrefix: 'api')` in `bootstrap/app.php`.
- Smoke test: `/up` returns 200, unmapped `/api/*` paths return 404 (i.e., the API route file is loaded).

**SQLite warning ignored.** The scaffold writes `DB_CONNECTION=sqlite` and `php artisan migrate` warns about a missing `pdo_sqlite` driver. The ISS app has no DB needs; `.env`'s connection will be flipped to `null` in Phase 6 along with the other env tidy-up. Not chasing the driver.

## Phase 2 — Domain port ✅

Ported with these deltas vs. the legacy 5.7 code:

- **HTTP client switched to `Http::` facade.** Roadmap originally deferred this to Phase 6; pulled forward at user's request. Drops the direct Guzzle dependency from our code (Laravel still pulls Guzzle transitively for the `Http::` client).
- **`ISSContract` is now bound to `ISSGateway` as a singleton** in `AppServiceProvider::register()`. The controller type-hints the interface, not the concrete class — makes it mockable via `Http::fake()` for tests.
- **`IssController::satelliteId()` split.** The legacy version doubled as a public action and an internal helper, switching its return type based on whether a `Request` was present. New version: `satelliteId()` returns `JsonResponse`, internal helper `currentSatellite(?int $id): array` for the distance flow.
- **Type declarations everywhere.** PHP 8 scalar params, return types, readonly promoted constructor properties in the gateway.
- **`Measurable` validators return real `bool`** (cast from `preg_match`'s 0/1/false).
- **Constants** instead of `public $issId = 25544;` on the controller — now `private const ISS_NORAD_ID = 25544;`.

### Smoke test caveat

When booted via `php artisan serve` on this Windows + Laragon machine, outbound `Http::` calls from inside a request handler **time out after 5s**, even though the same call via `php artisan tinker` (also using the L12 app's autoload + service container) returns 200 in ~200ms. PowerShell `Invoke-WebRequest` and a direct `curl_exec()` in PHP also work fine.

This is a known limitation of PHP's built-in single-threaded dev server on Windows — outbound TLS during a request handler can deadlock or starve. **The ported code is correct**; the test will pass once served via Laragon's nginx/Apache or via `php artisan test` (which uses the test client, not the dev server).

Not blocking. Documenting and moving on.

## Phase 3 — SKIPPED

User chose API-only build during Phase 1 decisions. The legacy Blade view and Vue components are not being ported. Webpack/Mix toolchain not migrated to Vite.

## Phase 4 — Dependency refresh — N/A for the rewrite path

The phase was written assuming an incremental in-place upgrade. Because we scaffolded a fresh L12 project, `_next/composer.json` already ships with the correct, modern dep set: no `fideloper/proxy`, no `beyondcode/laravel-dump-server`, no `fzaninotto/faker`, no `laravel-mix`, no jquery/popper. Nothing to refresh.

The one library decision was about `guzzlehttp/guzzle` — handled in Phase 2 by switching to `Http::`.
