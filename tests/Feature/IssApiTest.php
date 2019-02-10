<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IssApiTest extends TestCase
{
    public function testGetSatellitesStructure()
    {
        $this->json('GET', 'api/satellites')
            ->assertStatus(200)
            ->assertJsonStructure([
                'result',
                'data' => [
                    [
                        'name',
                        'id'
                    ]
                ]
            ]);

    }

    public function testGetSatelliteIdStructure()
    {
        $this->json('GET', 'api/satellite')
            ->assertStatus(200)
            ->assertJsonStructure([
                'result',
                'data' => [
                    "name",
                    "id",
                    "latitude",
                    "longitude",
                    "altitude",
                    "velocity",
                    "visibility",
                    "footprint",
                    "timestamp",
                    "daynum",
                    "solar_lat",
                    "solar_lon",
                    "units"
                ]
            ]);

    }
}
