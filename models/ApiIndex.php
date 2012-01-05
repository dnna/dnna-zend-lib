<?php

class Dnna_Model_ApiIndex {
    protected $_id;
    protected $_name;

    public function __construct($id, $name) {
        $this->_id = $id;
        $this->_name = $name;
    }

    public function get_id() {
        return $this->_id;
    }

    public function set_id($_id) {
        $this->_id = $_id;
    }

    public function get_aitisiid() {
        return $this->get_id();
    }

    public function get_name() {
        return $this->_name;
    }

    public function set_name($_name) {
        $this->_name = $_name;
    }

    public function __toString() {
        return $this->get_name();
    }

    public static function getApiIndex($modulename = 'api') {
        $front = Zend_Controller_Front::getInstance();
        $apimoduledir = $front->getModuleDirectory($modulename);
        $apicontrollerdir = ($apimoduledir . '/controllers');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($apicontrollerdir), RecursiveIteratorIterator::CHILD_FIRST);
        //$iterator = new IteratorIterator(new DirectoryIterator($apicontrollerdir));
        $apiIndex = array();
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                if (substr($item->getFilename(), -14) === 'Controller.php') {
                    $curApiIndex = array();
                    include_once($item->getPathname());
                    $controllerclass = reset(self::get_php_classes(file_get_contents($item->getPathname())));
                    $controllerclassnoctrl = substr($controllerclass, 0, -10); // Αφαιρούμε το "controller" από το όνομα της κλάσης
                    if($controllerclassnoctrl == '' || $controllerclassnoctrl == false || (defined($controllerclass.'::noindex') && $controllerclass::noindex == true)) {
                        continue;
                    }
                    $controllerurl = Dnna_Plugin_ApiRoute::getControllerPath($controllerclassnoctrl);
                    $curApiIndex['id'] = str_replace($modulename.'/', '', $controllerurl);
                    $curApiIndex['url'] = $front->getBaseUrl().$controllerurl;
                    $curApiIndex['name'] = $controllerclass::name;
                    array_push($apiIndex, $curApiIndex);
                }
            }
        }
        return $apiIndex;
    }

    protected static function get_php_classes($php_code) {
        $classes = array();
        $tokens = token_get_all($php_code);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS
                    && $tokens[$i - 1][0] == T_WHITESPACE
                    && $tokens[$i][0] == T_STRING) {

                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }
        return $classes;
    }
}

?>