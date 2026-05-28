# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this project is

A small Laravel app that wraps the public `api.wheretheiss.at` service to expose the current International Space Station position and compute the distance between any latitude/longitude and the ISS. Originally a homework project ("Avanti Homework") — the entire domain footprint is ~150 lines of PHP plus two Vue components.

The project is mid-migration from Laravel 5.7 to Laravel 12. See `UPGRADE.md` for the staged plan and current decisions (API-only build, fresh scaffold in `_next/` rather than incremental upgrade).

## Two trees, one repo

- **Repo root** (`master` branch): the original Laravel 5.7 app — PHP 7.1+, Vue 2 + Bootstrap 4 + Laravel Mix.
- **`_next/`** (`upgrade/laravel-12` branch): a fresh Laravel 12 skeleton being filled in. PHP 8.2+, no frontend (pure API).

When working on the migration, code goes into `_next/`. When working on the legacy app, code goes at repo root. Don't cross-port files without reading `UPGRADE.md` first — there are deliberate behavior fixes queued for Phase 6 (e.g., ISS-altitude-aware distance, FormRequest validation, switch to `Http::` facade).

## Toolchain

The system `PATH` on this machine resolves to PHP 7.2.11; PHP 8.3 is installed at `C:\laragon\bin\php\php-8.3.19-nts-Win32-vs16-x64\php.exe`. For anything in `_next/`, invoke that binary explicitly or fix `PATH` via Laragon (PHP → Version → 8.3.19). For the legacy 5.7 root, either PHP works for serving but Composer requires 8.3+.

Other tools: Composer 2.8.9, Node 22.15.1, npm 11.12.1.

## Commands

### Legacy root (Laravel 5.7)
- `composer install` — installs vendor deps.
- `php artisan serve` — boots dev server at `http://127.0.0.1:8000`.
- `npm install && npm run dev` — Laravel Mix asset build (webpack). `npm run prod` for production, `npm run watch` for incremental rebuilds.
- `./vendor/bin/phpunit` — runs the test suite (currently only Laravel-default example tests).
- `./vendor/bin/phpunit tests/Unit/SomeTest.php` — single file.
- `./vendor/bin/phpunit --filter test_method_name` — single test.

### L12 skeleton (`_next/`)
- `php artisan serve --port=8765` — boots the skeleton. Use port 8765 to avoid colliding with the legacy app on 8000.
- `/up` is the built-in health endpoint, registered via `bootstrap/app.php`'s `withRouting(health: '/up')`.
- `php artisan test` — preferred entrypoint in L12 (wraps PHPUnit 11).
- No `npm` needed — frontend has been intentionally stripped.

## Domain architecture (legacy root)

The whole feature lives in five files. Read them in this order:

1. **`app/Repositories/ISSContract.php`** — interface describing the upstream API surface. Defines `getSatellites()`, `getSatelliteId($id)`, `getSatelliteIdPositions($id, $timestamps)` (stubbed, unimplemented), `getCoordinates($lat, $lon)`.
2. **`app/Repositories/ISSGateway.php`** — Guzzle 6 implementation. Hardcoded to `https://api.wheretheiss.at/v1/`. Every method wraps the response in `['result' => 1|0, 'data' => ..., 'message' => ...]` — controllers rely on this envelope.
3. **`app/Traits/Measurable.php`** — `geoDistance()` (spherical law of cosines, returns km) plus three regex-based lat/long validators. Used by the controller via trait composition.
4. **`app/Http/Controllers/IssController.php`** — single controller for all four API actions. ISS NORAD id `25544` is hardcoded as `$issId`. `satelliteId()` is special: it doubles as the public action and as an internal helper for the distance calculation, returning either a `JsonResponse` or a raw array depending on whether the caller passes a `Request`.
5. **`routes/api.php`** — the four endpoints: `GET /api/satellites`, `GET /api/satellite/{id?}`, `GET /api/coordinates/{lat},{lon}`, `GET /api/distance/{lat},{lon}`.

The `ISSGateway` is type-hinted as a concrete class in `IssController` — there is **no service binding** of `ISSContract` → `ISSGateway`. If you add tests that need to mock the upstream, either add the binding in `AppServiceProvider::register()` and switch the controller to type-hint `ISSContract`, or use `$this->app->instance(ISSGateway::class, $mock)` directly.

The two Vue components (`resources/js/components/IssPosition.vue`, `IssDistance.vue`) are mounted from `resources/views/iss.blade.php` via the global `axios` set up in `resources/js/bootstrap.js`. The view is the only web route (`routes/web.php` → `view('iss')`).

## Auth scaffolding is unused

`app/Http/Controllers/Auth/*`, `RedirectIfAuthenticated`, the `users`/`password_resets` migrations, and `App\User` are all leftovers from `make:auth`. Nothing in the ISS feature touches them. The `UPGRADE.md` Phase 2 plan deletes them; do not extend this scaffolding in legacy code unless an actual auth requirement appears.

## Known-broken or quirky bits

These are real characteristics of the current code worth knowing before changing behavior:

- **`geoDistance` ignores ISS altitude.** It computes great-circle distance on Earth's surface (~6371 km radius), but the ISS orbits at ~400 km altitude. The number returned is really "ground-track distance," not slant range. `UPGRADE.md` Phase 6 plans to fix this; until then, don't add tests asserting "real" distance to the ISS.
- **`getSatelliteIdPositions` is declared in the contract but unimplemented** (literally a `// TODO` body). Calling it via the gateway returns `null`.
- **`calculateDistance` returns `{result: 0}` on any failure** — no specific error message, no 4xx status. Validation is regex-based in the `Measurable` trait, not via Laravel's validator.
- **`.env` was committed in the initial commit.** The values were Laravel/Homestead defaults (no real secrets), but `.gitignore` has been corrected on `upgrade/laravel-12` to ignore `.env` and track `.env.example` instead. Don't commit a new `.env` to either branch.

## Upgrade work in progress

Active branch is `upgrade/laravel-12`. Phases 0 and 1 of `UPGRADE.md` are complete: PHP 8.3 active, Composer 2.8.9, Node 22, L12.61.0 scaffolded in `_next/`, frontend stripped, API routing enabled, `/up` smoke-tested. Phase 2 (porting the controller/gateway/contract/trait/routes) has not started.

When picking up the upgrade, read `UPGRADE.md` end-to-end first — the choices about API-only and `_next/` location materially change which steps still apply (Phase 3 is dropped entirely).
