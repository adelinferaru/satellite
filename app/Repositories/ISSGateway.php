<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class ISSGateway implements ISSContract
{
    private const DEFAULT_BASE = 'https://api.wheretheiss.at/v1/';

    public function __construct(
        private readonly string $baseUrl = self::DEFAULT_BASE,
        private readonly int $timeoutSeconds = 5,
    ) {
    }

    public function getSatellites(): array
    {
        return $this->call('satellites');
    }

    public function getSatelliteId(int $id = 25544): array
    {
        // 1-second cache. wheretheiss.at rate-limits at 350 req / 5 minutes;
        // a position fix is also only meaningful for ~1s anyway.
        //
        // Use a *relative* integer TTL — evaluated when the value is stored, after
        // the upstream call returns. An absolute now()->addSecond() expiry computed
        // up front is already in the past by write time whenever the call takes
        // longer than the TTL, so the entry is written pre-expired and never hits.
        // Cache only successful fixes, so a transient upstream failure isn't served
        // stale.
        $key = "iss.satellite.{$id}";

        if (($cached = Cache::get($key)) !== null) {
            return $cached;
        }

        $result = $this->call("satellites/{$id}");

        if (($result['result'] ?? 0) === 1) {
            Cache::put($key, $result, 1);
        }

        return $result;
    }

    public function getSatelliteIdPositions(int $id, array $timestamps = []): array
    {
        if ($timestamps === []) {
            return $this->failure('At least one timestamp is required.');
        }

        return $this->call("satellites/{$id}/positions", [
            'timestamps' => implode(',', $timestamps),
        ]);
    }

    public function getCoordinates(float $lat, float $lon): array
    {
        return $this->call("coordinates/{$lat},{$lon}");
    }

    private function call(string $path, array $query = []): array
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout($this->timeoutSeconds)
                ->acceptJson()
                ->get($path, $query)
                ->throw();

            return [
                'result' => 1,
                'data' => $response->json(),
            ];
        } catch (Throwable $e) {
            return $this->failure($e->getMessage());
        }
    }

    private function failure(string $message): array
    {
        return [
            'result' => 0,
            'data' => null,
            'message' => $message,
        ];
    }
}
