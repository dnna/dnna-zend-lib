<?php
/**
 * Point object for spatial mapping
 * Modified from http://codeutopia.net/blog/2011/02/19/using-spatial-data-in-doctrine-2/
 */
class Dnna_Model_Point {
    private $latitude;
    private $longitude;
 
    public function __construct($latitude, $longitude) {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }
 
    public function setLatitude($x) {
        $this->latitude = $x;
    }
 
    public function getLatitude() {
        return $this->latitude;
    }
 
    public function setLongitude($y) {
        $this->longitude = $y;
    }
 
    public function getLongitude() {
        return $this->longitude;
    }
 
    public function __toString() {
        //Output from this is used with POINT_STR in DQL so must be in specific format
        return sprintf('POINT(%f %f)', $this->latitude, $this->longitude);
    }
}