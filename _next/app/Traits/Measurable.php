<?php

namespace App\Traits;

trait Measurable
{
    public function geoDistance(float $latFrom, float $lonFrom, float $latTo, float $lonTo): float
    {
        $rad = M_PI / 180;
        $theta = $lonFrom - $lonTo;
        $dist = sin($latFrom * $rad) * sin($latTo * $rad)
            + cos($latFrom * $rad) * cos($latTo * $rad) * cos($theta * $rad);

        // Clamp against float-precision overshoot; acos of >1 returns NaN.
        $dist = max(-1.0, min(1.0, $dist));

        return acos($dist) / $rad * 60 * 1.852;
    }

    public function validateLatitude(float|int|string $lat): bool
    {
        return (bool) preg_match(
            '/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/',
            (string) $lat,
        );
    }

    public function validateLongitude(float|int|string $long): bool
    {
        return (bool) preg_match(
            '/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/',
            (string) $long,
        );
    }

    public function validateLatLong(float|int|string $lat, float|int|string $long): bool
    {
        return (bool) preg_match(
            '/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?),[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/',
            $lat . ',' . $long,
        );
    }

    /**
     * Numeric-range coordinate check. More robust than the regex validators
     * for native float inputs (avoids "0.0" -> "0" stringification artifacts).
     */
    public function isValidCoordinate(mixed $lat, mixed $lon): bool
    {
        if (! is_numeric($lat) || ! is_numeric($lon)) {
            return false;
        }

        return $lat >= -90.0 && $lat <= 90.0
            && $lon >= -180.0 && $lon <= 180.0;
    }
}
