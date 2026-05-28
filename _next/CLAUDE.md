# CLAUDE.md — `_next/` (Laravel 12 skeleton)

Scoped guidance for working **inside `_next/`**. The repo-root `CLAUDE.md` has the cross-cutting picture; read that first.

## What this directory is

A fresh Laravel **12.61.0** scaffold being populated as a clean-room rewrite of the legacy 5.7 app at the repo root. Pure API — no Blade view, no Vite, no Vue, no `npm`. See `../UPGRADE.md` for phase status.

## PHP binary

System `PATH` here resolves to PHP 7.2.11. Use `C:\laragon\bin\php\php-8.3.19-nts-Win32-vs16-x64\php.exe` for everything in this directory, or fix PATH via the Laragon tray menu.

## Commands

- `php artisan serve --port=8765` — dev server. Port 8765 avoids the legacy app on 8000.
- `php artisan test` — PHPUnit 11 wrapper. Add `--filter=method_name` for a single test, or pass a path.
- `php artisan route:list` — quick sanity check after editing routes.

`/up` is the built-in health endpoint (registered via `bootstrap/app.php`).

## Layout differences vs. the legacy root

- **No `app/Http/Kernel.php`, no `app/Console/Kernel.php`.** Middleware, exception handling, scheduled commands all configure fluently in `bootstrap/app.php` via `withMiddleware()`, `withExceptions()`, `withSchedule()`.
- **`routes/api.php` is opt-in.** It is already enabled here via `withRouting(api: __DIR__.'/../routes/api.php', apiPrefix: 'api')`. Don't remove that line — without it, API routes silently 404.
- **No service providers other than `AppServiceProvider`.** If you bind the ISS contract, do it there.
- **`config/*` is mostly unchanged**, but several config keys were removed/renamed across versions. When porting config from the legacy root, copy values into the L12 config, don't bulk-replace the file.

## When porting code from the legacy root

Phase-2 ports (see `../UPGRADE.md`):

- `app/Http/Controllers/IssController.php` — extends `Illuminate\Routing\Controller` (the L12 base). Drop the `Request $request = null` dual-purpose pattern: split `satelliteId()` into the public action returning `JsonResponse` and a private/internal `currentIssPosition(): array` helper for the distance flow.
- `app/Repositories/ISSGateway.php` + `ISSContract.php` — straight port with PHP 8 types and constructor promotion. Keep Guzzle for now; the `Http::` facade switch is queued for Phase 6 to keep phases atomic.
- `app/Traits/Measurable.php` — add scalar/return types, keep the regex validators verbatim.
- `routes/api.php` — four routes copy 1:1; controller is referenced via FQCN import (no L8-style namespace prefixing here).
- **Bind `ISSContract` → `ISSGateway`** in `AppServiceProvider::register()` and type-hint the interface in the controller. The legacy code skipped this binding; the rewrite should fix it so the controller is mockable in tests.

Do **not** port:
- Any `Auth/*Controller`, `RedirectIfAuthenticated`, `App\User`, or auth views — the legacy app never used them.
- `users` / `password_resets` migrations.
- `resources/views/iss.blade.php` or the Vue components — frontend was intentionally dropped.

## Pre-existing behaviors to fix during the port (queued for Phase 6 of `../UPGRADE.md`)

Don't replicate these naively from the legacy code:

- **Distance ignores ISS altitude.** Either rename `geoDistance` → `groundTrackDistance` or add a `slantRangeDistance(...)` that uses the actual `altitude` field from the upstream API. Don't ship the misleading "distance to ISS" semantics again.
- **`{result: 0}` envelope on failure.** Replace with FormRequest validation and proper 4xx status codes.
- **Direct Guzzle.** Switch to `Http::` for free retries/timeouts/`Http::fake()` in tests.
- **No caching.** wheretheiss.at rate-limits at 1 req/s; cache the ISS position for ~1s.

## Tests

The skeleton ships with `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php`. Replace, don't append:

- Unit: `Measurable::geoDistance` against known great-circle pairs; lat/long validator boundaries (±90, ±180, malformed).
- Feature: `/api/satellite/{id?}` defaults to 25544; `/api/distance/...` with `Http::fake()` returning a canned wheretheiss.at payload.

## SQLite default

`composer create-project` left a `database/database.sqlite` file and `DB_CONNECTION=sqlite` in `.env`. `php artisan migrate` warns because Laragon's PHP doesn't load `pdo_sqlite`. This app has **no DB needs** — when convenient, switch `.env` to `DB_CONNECTION=null` (or delete the sqlite file). Don't enable `pdo_sqlite` just to silence the warning.
