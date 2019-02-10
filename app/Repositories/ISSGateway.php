<?php
/**
 * Created by PhpStorm.
 * User: Ioan
 * Date: 09.01.2019
 * Time: 02:18
 */

namespace App\Repositories;


use GuzzleHttp\Client;

class ISSGateway implements ISSContract
{
    private $issApiScheme = 'https';
    private $issApiHost = 'api.wheretheiss.at';
    private $issApiVersion = 'v1';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $issClient;

    /**
     * @var string
     */
    protected $issApiBase;

    /**
     * ISSGateway constructor.
     * @param $apiVersion
     */
    public function __construct($issApiVersion = null, $issApiScheme = null, $issApiHost = null)
    {
        if( $issApiVersion !== null) $this->issApiVersion = $issApiVersion;
        if( $issApiScheme !== null) $this->issApiScheme = $issApiScheme;
        if( $issApiHost !== null) $this->issApiHost = $issApiHost;

        $this->issApiBase = $this->issApiScheme . '://' . $this->issApiHost . '/' . $this->issApiVersion . '/';

        $this->issClient = new Client();
    }


    /**
     * Returns a list of satellites that this API has information about,
     * inluding a common name and NORAD catalog id. Currently, there is only one,
     * the International Space Station. But in the future, we plan to provide more.
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSatellites()
    {

        try {
            $response = $this->issClient->request('GET', $this->issApiBase . 'satellites');

            return [
                'result' => 1,
                'data' => json_decode($response->getBody()->getContents(), true)
            ];

        }
        catch (\Exception $e) {
            return [
                'result' => 0,
                'data' => null,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Returns position, velocity, and other related information about a satellite
     * for a given point in time. [id] is required and should be the NORAD catalog id.
     * For the ISS, that id is 25544.
     *
     * @param $id int
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSatelliteId($id = 25544)
    {
        try {
            $response = $this->issClient->request('GET', $this->issApiBase . 'satellites/' . $id);

            return [
                'result' => 1,
                'data' => json_decode($response->getBody()->getContents(), true)
            ];

        }
        catch (\Exception $e) {
            return [
                'result' => 0,
                'data' => null,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Returns a list in which each entry contains position, velocity,
     * and other related information about a satellite for a comma delimited list of timestamps (up to 10).
     * [id] is required and should be the NORAD catalog id. For the ISS, that id is 25544.
     *
     * @param $id int
     * @param array $timestamps
     * @return mixed
     */
    public function getSatelliteIdPositions($id, $timestamps = [])
    {
        // TODO: Implement getSatelliteIdPositions() method.
    }

    /**
     * Returns position, current time offset, country code, and timezone id for a given set of coordinates in the format of latitude, longitude
     *
     * @param $lat float
     * @param $lon float
     * @return mixed
     */
    public function getCoordinates($lat, $lon)
    {
        try {
            $response = $this->issClient->request('GET', $this->issApiBase . "coordinates/$lat,$lon");

            return [
                'result' => 1,
                'data' => json_decode($response->getBody()->getContents(), true)
            ];

        }
        catch (\Exception $e) {
            return [
                'result' => 0,
                'data' => null,
                'message' => $e->getMessage()
            ];
        }
    }
}
