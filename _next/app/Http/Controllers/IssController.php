<?php

namespace App\Http\Controllers;

use App\Repositories\ISSContract;
use App\Traits\Measurable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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

    public function calculateDistance(Request $request): JsonResponse
    {
        $latFrom = $request->input('lat');
        $lonFrom = $request->input('lon');

        return $this->distanceResponse($latFrom, $lonFrom);
    }

    public function getDistance(float $lat, float $lon): JsonResponse
    {
        return $this->distanceResponse($lat, $lon);
    }

    private function currentSatellite(?int $id): array
    {
        return $this->iss->getSatelliteId($id ?? self::ISS_NORAD_ID);
    }

    private function distanceResponse(mixed $lat, mixed $lon): JsonResponse
    {
        if (! $lat || ! $lon || ! $this->validateLatLong($lat, $lon)) {
            return response()->json(['result' => 0]);
        }

        $issInfo = $this->currentSatellite(null);

        if (($issInfo['result'] ?? 0) !== 1) {
            return response()->json(['result' => 0]);
        }

        $distance = $this->geoDistance(
            (float) $lat,
            (float) $lon,
            (float) $issInfo['data']['latitude'],
            (float) $issInfo['data']['longitude'],
        );

        return response()->json([
            'result' => 1,
            'data' => ['distance' => $distance],
        ]);
    }
}
