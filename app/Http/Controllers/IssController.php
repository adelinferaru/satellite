<?php

namespace App\Http\Controllers;

use App\Repositories\ISSContract;
use App\Traits\Measurable;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class IssController extends Controller
{
    use Measurable;

    private const ISS_NORAD_ID = 25544;

    public function __construct(
        private readonly ISSContract $iss,
    ) {
    }

    public function satellites(): JsonResponse
    {
        return response()->json($this->iss->getSatellites());
    }

    public function satelliteId(?int $id = null): JsonResponse
    {
        return response()->json($this->currentSatellite($id));
    }

    public function coordinates(float $lat, float $lon): JsonResponse
    {
        return response()->json($this->iss->getCoordinates($lat, $lon));
    }

    public function getDistance(string $lat, string $lon): JsonResponse
    {
        $validator = Validator::make(['lat' => $lat, 'lon' => $lon], [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validator->errors(),
            ], 422);
        }

        $iss = $this->currentSatellite(null);

        if (($iss['result'] ?? 0) !== 1) {
            return response()->json([
                'result' => 0,
                'message' => $iss['message'] ?? 'Upstream ISS position unavailable.',
            ], 502);
        }

        $distance = $this->slantRangeDistance(
            (float) $lat,
            (float) $lon,
            (float) $iss['data']['latitude'],
            (float) $iss['data']['longitude'],
            (float) ($iss['data']['altitude'] ?? 408.0),
        );

        return response()->json([
            'result' => 1,
            'data' => [
                'distance' => $distance,
                'unit' => 'km',
                'measurement' => 'slant_range',
                'iss' => [
                    'latitude' => $iss['data']['latitude'],
                    'longitude' => $iss['data']['longitude'],
                    'altitude' => $iss['data']['altitude'] ?? null,
                ],
            ],
        ]);
    }

    private function currentSatellite(?int $id): array
    {
        return $this->iss->getSatelliteId($id ?? self::ISS_NORAD_ID);
    }
}
