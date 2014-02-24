<?php

/**
 * @package GeoDetails
 * @license GPL
 */

namespace samjoyce777\Location;

class Location {

    private $earthRadiusinKM = 6371.00; # in km
    private $earthRadiusinMiles = 3960.00; # in miles
    private $dLatitude = "";
    private $dLongitude = "";
    public $firstCoordinates = array();
    public $secondCoordinates = array();
    public $resultArray = array();

    public function __construct($metric = 'km') {
        if ($metric != 'km') {
            $this->earthRadius = $this->earthRadiusinMiles;
        } else {
            $this->earthRadius = $this->earthRadiusinKM;
        }
    }

    /**
     * @method Calculating distance between two address
     * @access public
     * @param $address1 is string passed to the Google Maps API Geocoding endpoint
     * @param $address2 is string passed to the Google Maps API Geocoding endpoint
     * @return string, contains the distance in km.
     */
    public function distance_two_point($address1, $address2) {
        $this->firstCoordinates = $this->getAddressCoordinates($address1);
        $this->firstLatitude = $this->firstCoordinates["lat"];
        $this->firstLongitude = $this->firstCoordinates["lng"];
        $this->secondCoordinates = $this->getAddressCoordinates($address2);
        $this->secondLatitude = $this->secondCoordinates["lat"];
        $this->secondLongitude = $this->secondCoordinates["lng"];
        $this->resultDistance = $this->distance_haversine($this->firstLatitude, $this->firstLongitude, $this->secondLatitude, $this->secondLongitude);
        return $this->resultDistance;
    }

    /**
     * @method Retriving geo coordinates for an address
     * @access public
     * @param $address is string passed to the Google Maps API Geocoding endpoint
     * @return array. First element [0] is longitude, the second element [1] is the latitude, third [2] is text description
     */
    public function getAddressCoordinates($address = 'uk') {
        if (!is_string($address)) {
            die("All Addresses must be passed as a string");
        }
        $address = urlencode($address);
        $request = file_get_contents("http://maps.google.com/maps/api/geocode/json?address=" . $address . "&sensor=false");
        $json = json_decode($request, true);

        $result = array("lng" => "null", "lat" => "null", "format" => "null");

        if (isset($json["results"][0]["formatted_address"])) {
            $result["lng"] = $json["results"][0]["geometry"]["location"]["lng"];
            $result["lat"] = $json["results"][0]["geometry"]["location"]["lat"];
            $result["format"] = $json["results"][0]["formatted_address"];
        }

        return $result;
    }

    /**
     *
     * @method Take a target address, check the distances with every address was defined in the $options array, then return result the closest - $sortorder = 'asc' - or the farest - $sortorder = 'desc'
     * @access public
     * @param string: $target, the first address
     * @param array: $options, multidimension array of addresses you want to get the distance (example: $options = array ('0' => array ('id' => 2, 'address' => '285-299 Havelock Street, Ashburton'))
     * @param string: $order, possibel values: 'asc' or 'desc'
     * @return array
     */
    public function getAddressesDistances($target, $options, $sortorder) {
        if (!is_string($target)) {
            die("All addresses must be passed as a string");
        }
        if (!is_array($options)) {
            die("All option addresses must be passed as an array");
        }
        $this->firstCoordinates = $this->getAddressCoordinates($target);
        $this->firstLatitude = $this->firstCoordinates[1];
        $this->firstLongitude = $this->firstCoordinates[0];
        foreach ($options as $key => $value) {
            $this->secondCoordinates = $this->getAddressCoordinates($value['address']);
            $this->secondLatitude = $this->secondCoordinates[1];
            $this->secondLongitude = $this->secondCoordinates[0];
            $this->resultDistance = $this->distance_haversine($this->firstLatitude, $this->firstLongitude, $this->secondLatitude, $this->secondLongitude);
            $this->resultArray[$key]['id'] = $value['id'];
            $this->resultArray[$key]['distance'] = $this->resultDistance;
        }
        $this->resultArray = $this->sortmulti($this->resultArray, 'distance', $sortorder);
        return $this->resultArray[0];
    }

    /**
     * @method Calculating distance between two geo coordinates defined point, using the Haversine Formula
     * @link http://en.wikipedia.org/wiki/Haversine_formula
     * @access protected
     * @param $latitude1: float, the latitude coordinate for the first point
     * @param $longitude1: float, the longitude coordinate for the first point
     * @param $latitude2: float, the latitude coordinate for the second point
     * @param $longitude2: float, the longitude coordinate for the second point
     * @return float, the distance in km
     */
    protected function distance_haversine($latitude1, $longitude1, $latitude2, $longitude2) {
        $this->dLatitude = $latitude2 - $latitude1;
        $this->dLongitude = $longitude2 - $longitude1;
        $alpha = $this->dLatitude / 2;
        $beta = $this->dLongitude / 2;
        $a = sin(deg2rad($alpha)) * sin(deg2rad($alpha)) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin(deg2rad($beta)) * sin(deg2rad($beta));
        $c = asin(min(1, sqrt($a)));
        $distance = 2 * $this->earthRadius * $c;
        $distance = round($distance, 3);
        return $distance;
    }

   
}
