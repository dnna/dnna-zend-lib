<?php
/**
 * Point object for spatial mapping
 * Modified from http://codeutopia.net/blog/2011/02/19/using-spatial-data-in-doctrine-2/
 */
class Dnna_Model_Point {
    /**
     * @FormFieldType Hidden
     */
    protected $_latitude;
    /**
     * @FormFieldType Hidden
     */
    protected $_longitude;

    public function __construct($latitude = null, $longitude = null) {
        $this->_latitude = $latitude;
        $this->_longitude = $longitude;
    }

    public function get_latitude() {
        return $this->_latitude;
    }

    public function set_latitude($_latitude) {
        $this->_latitude = $_latitude;
    }

    public function get_longitude() {
        return $this->_longitude;
    }

    public function set_longitude($_longitude) {
        $this->_longitude = $_longitude;
    }

    public function setOptions($options) {
        foreach($options as $curOption => $curValue) {
            $method = 'set_'.$curOption;
            if(method_exists($this, $method)) {
                $this->$method($curValue);
            }
        }
        return $this;
    }

    public function __toString() {
        //Output from this is used with POINT_STR in DQL so must be in specific format
        return sprintf('POINT(%f %f)', $this->_latitude, $this->_longitude);
    }
}