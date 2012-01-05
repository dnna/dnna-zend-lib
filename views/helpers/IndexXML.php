<?php
/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_View_Helper_IndexXML extends Zend_View_Helper_Abstract
{
    public $view;
    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    public function indexXML(array $array, $root = 'items', $idmethod = array('id' => 'get_id')) {
        echo '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        echo '<'.$root.'>';
        foreach($array as $id => $name) {
            if(is_array($idmethod)) {
                foreach($idmethod as $idkey => $idmethodfunc) {
                    $idname = $idkey;
                    $id = $name->$idmethodfunc();
                }
            } else {
                throw new Exception('Idmethod not specified');
            }
            echo '
            <item>
                 <'.$idname.'>'.$id.'</'.$idname.'>
                 <name>'.htmlspecialchars($name).'</name>
                 <url>'.htmlspecialchars($this->view->serverUrl().$this->view->url(array('id' => $id))).'</url>
            </item>';
        }
        echo '</'.$root.'>';
    }
}
?>