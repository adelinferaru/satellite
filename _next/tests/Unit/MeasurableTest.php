<?php

namespace Tests\Unit;

use App\Traits\Measurable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MeasurableTest extends TestCase
{
    private object $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new class {
            use Measurable;
        };
    }

    public function test_geo_distance_returns_near_zero_for_identical_points(): void
    {
        // Spherical law of cosines loses precision near 0 from float roundoff;
        // sub-meter is the practical floor (clamp keeps it non-NaN).
        $this->assertEqualsWithDelta(
            0.0,
            $this->subject->geoDistance(40.0, -74.0, 40.0, -74.0),
            0.001,
        );
    }

    public function test_geo_distance_new_york_to_london(): void
    {
        // JFK (40.6413, -73.7781) -> LHR (51.4700, -0.4543)
        // Expected great-circle ~5538 km. Loose tolerance because the trait
        // uses spherical-law-of-cosines, not haversine.
        $km = $this->subject->geoDistance(40.6413, -73.7781, 51.4700, -0.4543);
        $this->assertEqualsWithDelta(5538.0, $km, 30.0);
    }

    public function test_geo_distance_is_symmetric(): void
    {
        $a = $this->subject->geoDistance(40.6413, -73.7781, 51.4700, -0.4543);
        $b = $this->subject->geoDistance(51.4700, -0.4543, 40.6413, -73.7781);
        $this->assertEqualsWithDelta($a, $b, 1e-6);
    }

    #[DataProvider('validLatitudes')]
    public function test_validate_latitude_accepts_valid(string|float $lat): void
    {
        $this->assertTrue($this->subject->validateLatitude($lat));
    }

    public static function validLatitudes(): array
    {
        return [
            'zero' => [0],
            'positive int' => [45],
            'negative decimal' => [-45.5],
            'max positive' => [90],
            'max negative' => [-90],
        ];
    }

    #[DataProvider('invalidLatitudes')]
    public function test_validate_latitude_rejects_invalid(mixed $lat): void
    {
        $this->assertFalse($this->subject->validateLatitude($lat));
    }

    public static function invalidLatitudes(): array
    {
        return [
            'over 90' => [90.5],
            'under -90' => [-90.5],
            'far over' => [180],
            'non-numeric' => ['abc'],
        ];
    }

    #[DataProvider('validLongitudes')]
    public function test_validate_longitude_accepts_valid(string|float $lon): void
    {
        $this->assertTrue($this->subject->validateLongitude($lon));
    }

    public static function validLongitudes(): array
    {
        return [
            'zero' => [0],
            'positive int' => [120],
            'negative decimal' => [-73.99],
            'max positive' => [180],
            'max negative' => [-180],
        ];
    }

    #[DataProvider('invalidLongitudes')]
    public function test_validate_longitude_rejects_invalid(mixed $lon): void
    {
        $this->assertFalse($this->subject->validateLongitude($lon));
    }

    public static function invalidLongitudes(): array
    {
        return [
            'over 180' => [180.5],
            'under -180' => [-180.5],
            'way over' => [360],
            'non-numeric' => ['xyz'],
        ];
    }
}
