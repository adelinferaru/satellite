<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IssEndpointsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_satellites_endpoint_returns_upstream_list(): void
    {
        Http::fake([
            'api.wheretheiss.at/v1/satellites' => Http::response([
                ['name' => 'iss', 'id' => 25544],
            ], 200),
        ]);

        $this->getJson('/api/satellites')
            ->assertOk()
            ->assertJson([
                'result' => 1,
                'data' => [['name' => 'iss', 'id' => 25544]],
            ]);
    }

    public function test_satellite_defaults_to_iss_norad_id(): void
    {
        Http::fake([
            'api.wheretheiss.at/v1/satellites/25544' => Http::response([
                'name' => 'iss',
                'id' => 25544,
                'latitude' => 12.34,
                'longitude' => -56.78,
                'altitude' => 408.0,
            ], 200),
        ]);

        $this->getJson('/api/satellite')
            ->assertOk()
            ->assertJsonPath('result', 1)
            ->assertJsonPath('data.id', 25544);
    }

    public function test_satellite_accepts_explicit_id(): void
    {
        Http::fake([
            'api.wheretheiss.at/v1/satellites/99999' => Http::response([
                'name' => 'other',
                'id' => 99999,
            ], 200),
        ]);

        $this->getJson('/api/satellite/99999')
            ->assertOk()
            ->assertJsonPath('data.id', 99999);
    }

    public function test_satellite_returns_envelope_failure_when_upstream_fails(): void
    {
        Http::fake([
            'api.wheretheiss.at/v1/satellites/25544' => Http::response(null, 503),
        ]);

        $this->getJson('/api/satellite')
            ->assertOk()
            ->assertJsonPath('result', 0);
    }

    public function test_coordinates_endpoint_passes_through(): void
    {
        Http::fake([
            'api.wheretheiss.at/v1/coordinates/40.7128,-74.006' => Http::response([
                'timezone_id' => 'America/New_York',
            ], 200),
        ]);

        $this->getJson('/api/coordinates/40.7128,-74.006')
            ->assertOk()
            ->assertJsonPath('data.timezone_id', 'America/New_York');
    }

    public function test_distance_computes_slant_range_using_iss_altitude(): void
    {
        Http::fake([
            'api.wheretheiss.at/v1/satellites/25544' => Http::response([
                'latitude' => 0.0,
                'longitude' => 0.0,
                'altitude' => 408.5,
            ], 200),
        ]);

        $response = $this->getJson('/api/distance/0.0,0.0');

        $response->assertOk()
            ->assertJsonPath('result', 1)
            ->assertJsonPath('data.unit', 'km')
            ->assertJsonPath('data.measurement', 'slant_range')
            ->assertJsonPath('data.iss.altitude', 408.5);

        // Ground point directly under the ISS: slant range == altitude.
        $this->assertEqualsWithDelta(408.5, $response->json('data.distance'), 0.001);
    }

    public function test_distance_rejects_invalid_coordinates_with_422(): void
    {
        Http::fake();

        $this->getJson('/api/distance/999,999')
            ->assertStatus(422)
            ->assertJsonPath('result', 0)
            ->assertJsonStructure(['errors' => ['lat', 'lon']]);
    }

    public function test_distance_rejects_non_numeric_coordinates(): void
    {
        Http::fake();

        $this->getJson('/api/distance/abc,xyz')
            ->assertStatus(422)
            ->assertJsonPath('result', 0);
    }

    public function test_distance_returns_502_when_upstream_unreachable(): void
    {
        Http::fake([
            'api.wheretheiss.at/v1/satellites/25544' => Http::response(null, 500),
        ]);

        $this->getJson('/api/distance/40.7128,-74.006')
            ->assertStatus(502)
            ->assertJsonPath('result', 0);
    }

    public function test_satellite_position_is_cached_for_one_second(): void
    {
        Http::fake([
            'api.wheretheiss.at/v1/satellites/25544' => Http::response([
                'name' => 'iss',
                'id' => 25544,
                'latitude' => 1.0,
                'longitude' => 2.0,
                'altitude' => 408.0,
            ], 200),
        ]);

        $this->getJson('/api/satellite')->assertOk();
        $this->getJson('/api/satellite')->assertOk();
        $this->getJson('/api/satellite')->assertOk();

        // 3 client calls, 1 upstream call (cached).
        Http::assertSentCount(1);
    }
}
