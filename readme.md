# ISS — Satellite

A small Laravel API that wraps [api.wheretheiss.at](https://wheretheiss.at) to
expose the current International Space Station position and compute the
distance from any latitude/longitude to the ISS.

## Tasks (original brief)

1. Return the current position of the ISS (latitude/longitude).
2. Compute an estimated distance between the current ISS location and a
   given latitude/longitude, in km.
3. Expose JSON API endpoints for the above.

## Stack

- **Laravel 12** on **PHP 8.2+**.
- Pure API — no frontend. Anything that wants a dashboard can build one
  against the JSON endpoints.
- Outbound HTTP via Laravel's `Http::` facade with a 1-second response
  cache so repeated polls don't blow through wheretheiss.at's rate limit.

## Requirements

- PHP 8.3 recommended (8.2 minimum).
- Composer 2.7+.

No Node/npm required.

## Install & run

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

The dev server runs at `http://127.0.0.1:8000`. Health check at `/up`.

## API

| Method | Path | Returns |
|---|---|---|
| `GET` | `/api/satellites` | List of satellites the upstream knows about (just the ISS today). |
| `GET` | `/api/satellite/{id?}` | Position, velocity, altitude, timestamp for the satellite. Defaults to NORAD id 25544 (ISS). |
| `GET` | `/api/coordinates/{lat},{lon}` | Timezone/country info for arbitrary coordinates (passthrough from the upstream). |
| `GET` | `/api/distance/{lat},{lon}` | Slant-range distance from the supplied ground point to the ISS, in km. Uses the upstream's current altitude. |

Successful responses wrap data as `{"result": 1, "data": {...}}`. Invalid input
returns **422** with an `errors` map; upstream failures return **502** with a
`message`.

## Tests

```sh
php artisan test
```

Unit tests cover the great-circle and slant-range math plus the coordinate
validators. Feature tests exercise every route against `Http::fake()` so the
suite never touches wheretheiss.at.

## History

Originally written on Laravel 5.7 with a Vue 2 + Bootstrap 4 frontend, then
rewritten onto Laravel 12 as a pure API. See `UPGRADE.md` for the staged plan
and `PROGRESS.md` for the decision log from the rewrite.

## Author

Feraru Ioan Adelin · `adelin.feraru@gmail.com`
