<?php
/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_View_Helper_IndexJSON extends Zend_View_Helper_Abstract
{
    public $view;
    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    public function indexJSON(array $array, $root = 'items', $idmethod = array('id' => 'get_id')) {
        $fullarray = array();
        $fullarray[$root] = array();
        foreach($array as $id => $name) {
            if(is_array($idmethod)) {
                foreach($idmethod as $idkey => $idmethodfunc) {
                    $idname = $idkey;
                    $id = $name->$idmethodfunc();
                }
            } else {
                throw new Exception('Idmethod not specified');
            }
            $objarray = array();
            $objarray[$idname] = $id;
            $objarray['url'] = htmlspecialchars($this->view->serverUrl().$this->view->url(array('id' => $id)));
            $objarray['name'] = $name->__toString();
            $fullarray[$root][] = $objarray;
        }
        return json_encode($fullarray);
    }
}
?>