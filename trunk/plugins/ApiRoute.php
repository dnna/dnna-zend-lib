<?php
/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Plugin_ApiRoute extends Zend_Rest_Route {
    protected $params = array();
    protected $controllerpath = '';
    /**
     * @var Zend_Cache_Core
     */
    protected $cache;

    public function getCache() {
        return $this->cache;
    }

    public function setCache($cache) {
        $this->cache = $cache;
    }

    public function match($request, $partial = false) {
        $pathInfo = $request->getPathInfo();
        $request->setPathInfo($pathInfo);
        $params = array();
        if(strpos($pathInfo, '.') !== false) {
            // Αφαίρεση της κατάληξης ώστε να μην επιρεάσει την διαδικασία
            $params['format'] = substr(strrchr($pathInfo, '.'), 1);
            $pathInfo = substr($pathInfo, 0, -strlen(strrchr($pathInfo, '.'))); // Αφαίρεση του extension
        }
        $pathInfo = preg_replace("/(\/)\\1+/", "$1", $pathInfo); // Αφαίρεση επαναλαμβανόμενων /
        if(($parentparams = parent::match($request, $partial)) != false) {
            $params = $params + $parentparams;
            $pathInfo = preg_replace('/'.$params['module'].'/', '', $pathInfo, 1);
            if(!in_array($params['module'], $this->_restfulModules)) {
                return false;
            }
        } else {
            return false;
        }
        if(!isset($this->cache) || ($this->params = $this->cache->load('apictrl_'.md5($pathInfo))) == false) {
            $this->params = $params;
            $this->controllerpath = realpath(APPLICATION_PATH).DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$this->params['module'].DIRECTORY_SEPARATOR.'controllers';
            $pathInfoExploded = explode('/', $pathInfo);
            // Αφαιρούμε το πρώτο αν είναι κενό ώστε να μην εμφανιστούν διπλά / μετά το explode
            if($pathInfoExploded[0] == '') {
                array_shift($pathInfoExploded);
            }
            // Μετατρέπουμε τα path σε ucfirst
            foreach($pathInfoExploded as &$curPath) {
                $curPath = ucfirst($curPath);
            }

            $this->params['controller'] = $this->findController(count($pathInfoExploded) - 1, $pathInfoExploded);
            // Σε περίπτωση που σαν path έχουμε το κενό τότε μπαίνει το DIRECTORY_SEPARATOR στο classname του controller, οπότε και το αφαιρούμε
            $this->params['controller'] = str_replace(DIRECTORY_SEPARATOR, '', $this->params['controller']);
            while($this->params['controller'][0] === '_') {
                $this->params['controller'] = substr($this->params['controller'], 1);
            }
            $this->params = array_merge($this->params, $this->findActionAndId($pathInfo));

            // Cleanup
            if($this->params['action'] === 'index') {
                unset($this->params['id']);
            }
            unset($this->params['folderwide']);

            // Προσθήκη των παραμέτρων του query string
            parse_str(parse_url($request->getRequestUri(), PHP_URL_QUERY), $queryparams);
            $this->params = array_merge_recursive($this->params, $queryparams);

            // Αλλαγή του action σε schema αν υπάρχει η παράμετρος schema
            if((isset($this->params['id']) && $this->params['id'] === 'schema') || isset($this->params['schema'])) {
                $this->params['action'] = 'schema';
                $this->params['format'] = 'xml';
            }

            if(isset($this->cache)) {
                $this->cache->save($this->params, 'apictrl_'.md5($pathInfo));
            }
        }
        return $this->params;
    }

    public function assemble($data = array(), $reset = false, $encode = true) {
        if(!$reset) {
            $data = array_merge($this->_front->getRequest()->getParams(), $data);
        }
        $url = $this->_front->getBaseUrl().$this->_front->getRequest()->getModuleName().'/'.self::getControllerPath($data['controller']);
        if(isset($data['subtype'])) {
            $url .= '/'.$data['subtype'];
        }
        if(isset($data['id'])) {
            if($data['id'] !== $this->_front->getRequest()->getModuleName()) {
                $url .= '/'.$data['id'];
            }
        }
        $url = preg_replace("/(\/)\\1+/", "$1", $url); // Αφαίρεση επαναλαμβανόμενων /
        return $url;
    }

    protected function findController($curPart, $pathInfo) { 
        $implodedPath = implode(DIRECTORY_SEPARATOR, array_slice($pathInfo, 0, $curPart));
        $curControllerPath = str_replace(DIRECTORY_SEPARATOR, '_', $implodedPath);
        $fullPath = $this->controllerpath.$implodedPath;
        // 1. Αρχείο IndexController στον υποφάκελο με όνομα $firstPart
        if(file_exists($fullPath.DIRECTORY_SEPARATOR.$pathInfo[$curPart].DIRECTORY_SEPARATOR.'IndexController.php')) {
            $this->params['folderwide'] = true;
            if($pathInfo[$curPart] != '') { // Το if υπάρχει γιατί αν είναι άδειο βγάζει controller name με δύο _
                return $curControllerPath.'_'.$pathInfo[$curPart].'_Index';
            } else {
                return $curControllerPath.'_Index';
            }
        // 2. Controller name ίδιο με το $firstPart
        } else if(file_exists($fullPath.DIRECTORY_SEPARATOR.$pathInfo[$curPart].'Controller.php')) {
            return $curControllerPath.'_'.$pathInfo[$curPart];
        } else if($curPart > 0) {
            return $this->findController(($curPart - 1), $pathInfo);
        }
    }

    protected function findActionAndId($pathInfo) {
        $pathInfo = substr($pathInfo, 1);
        $controllerpath = self::getControllerPath($this->params['controller']);
        $restpath = str_replace($controllerpath, '', $pathInfo);
        $restpathExploded = self::trimArray(explode('/', $restpath));

        $return = array();
        if(isset($this->params['folderwide']) && $this->params['folderwide'] == true) {
            if(count($restpathExploded) <= 0) {
                // No subtype (index)
                $return['action'] = 'index';
            } else if(count($restpathExploded) == 1) {
                // Subtype but no id (index)
                $return['subtype'] = $restpathExploded[0];
                $return['action'] = 'index';
            } else {
                // subtype with id (get)
                $return['subtype'] = $restpathExploded[0];
                $return['action'] = 'get';
                $return['id'] = $restpathExploded[1];
            }
        } else {
            if(count($restpathExploded) <= 0) {
                // Index
                $return['action'] = 'index';
            } else if(count($restpathExploded) == 1) {
                // Id
                $return['action'] = 'get';
                $return['id'] = $restpathExploded[0];
            } else {
                // Id + άλλοι παράμετροι
                throw new Exception('Δεν έχει υλοποιηθεί η εύρεση παραμέτρων στο ApiRoute');
            }
        }
        return $return;
    }
    
    public static function getControllerPath($controllername) {
        $controllerpath = str_replace('_', '/', strtolower($controllername));
        if(substr($controllerpath, -6) === '/index') {
            // Αφαιρούμε το /index
            $controllerpath = substr($controllerpath, 0, -6);
        } else if(substr($controllerpath, -5) === 'index') {
            $controllerpath = substr($controllerpath, 0, -5);
        }
        return $controllerpath;
    }

    public static function trimArray($array, $character = '') {
        while(isset($array[0]) && $array[0] == $character) { // Αφαιρούμε τα κενά από την αρχή
            array_shift($array);
        }
        while(isset($array[count($array) - 1]) && $array[count($array) - 1] == $character) { // Αφαιρούμε τα κενά από το τέλος
            array_pop($array);
        }
        return $array;
    }
}
?>