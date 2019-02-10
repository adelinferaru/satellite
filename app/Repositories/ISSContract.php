<?php
/**
 * Created by PhpStorm.
 * User: Ioan
 * Date: 09.01.2019
 * Time: 02:05
 */

namespace App\Repositories;



interface ISSContract
{
    /**
     * Returns a list of satellites that this API has information about,
     * inluding a common name and NORAD catalog id. Currently, there is only one,
     * the International Space Station. But in the future, we plan to provide more.
     *
     * @return mixed
     */
    public function getSatellites();

    /**
     * Returns position, velocity, and other related information about a satellite
     * for a given point in time. [id] is required and should be the NORAD catalog id.
     * For the ISS, that id is 25544.
     *
     * @param $id int
     * @return mixed
     */
    public function getSatelliteId($id);


    /**
     * Returns a list in which each entry contains position, velocity,
     * and other related information about a satellite for a comma delimited list of timestamps (up to 10).
     * [id] is required and should be the NORAD catalog id. For the ISS, that id is 25544.
     *
     * @param $id int
     * @param array $timestamps
     * @return mixed
     */
    public function getSatelliteIdPositions($id, $timestamps = []);


    /**
     * Returns position, current time offset, country code, and timezone id for a given set of coordinates in the format of latitude, longitude
     *
     * @param $lat float
     * @param $lon float
     * @return mixed
     */
    public function getCoordinates($lat, $lon);
}
