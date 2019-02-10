<?php

namespace App\Http\Controllers;

use App\Repositories\ISSGateway;
use App\Traits\Measurable;
use Illuminate\Http\Request;

class IssController extends Controller
{
    use Measurable;

    protected $issRepository;

    public $issId = 25544;

    /**
     * IssController constructor.
     * @param $issRepository ISSGateway
     */
    public function __construct(ISSGateway $issRepository)
    {
        $this->issRepository = $issRepository;
    }


    public function satellites() {
        $satellites = $this->issRepository->getSatellites();

        return response()->json($satellites);
    }

    public function satelliteId($id = null, Request $request = null) {

        if($id == null) $id = $this->issId;

        $satellite = $this->issRepository->getSatelliteId($id);

        if($request && $request->wantsJson()) {
            return response()->json($satellite);
        }

        return $satellite;
    }

    public function coordinates($lat, $lon) {
        $coordinates = $this->issRepository->getCoordinates($lat, $lon);

        return response()->json($coordinates);
    }

    public function calculateDistance(Request $request) {
        $input = $request->all();
        $error = ['result' => 0];

        $latFrom = isset($input['lat']) ? $input['lat'] : null;
        $lonFrom = isset($input['lon']) ? $input['lon'] : null;

        if($latFrom && $lonFrom && $this->validateLatLong($latFrom, $lonFrom)) {

            // Get the current coordinates of the ISS
            $issInfo = $this->satelliteId();

            if($issInfo['result'] == 1) {
                $latTo = $issInfo['data']['latitude'];
                $lonTo = $issInfo['data']['longitude'];
                $distance = $this->geoDistance($latFrom, $lonFrom, $latTo, $lonTo);
                return response()->json(['result' => 1, 'data' => ['distance' => $distance]]);
            }
        }

        return response()->json($error);

    }

    public function getDistance($lat, $lon) {
        $error = ['result' => 0];

        if($lat && $lon && $this->validateLatLong($lat, $lon)) {

            // Get the current coordinates of the ISS
            $issInfo = $this->satelliteId();

            //dd($issInfo);

            if($issInfo['result'] == 1) {
                $latTo = $issInfo['data']['latitude'];
                $lonTo = $issInfo['data']['longitude'];

                //dd($lat, $lon, $latTo, $lonTo);

                $distance = $this->geoDistance($lat, $lon, $latTo, $lonTo);
                return response()->json(['result' => 1, 'data' => ['distance' => $distance]]);
            }
        }

        return response()->json($error);

    }

}
