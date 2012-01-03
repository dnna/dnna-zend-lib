<?php
/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Action_Helper_Rest_Index extends Zend_Controller_Action_Helper_ContextSwitch
{
    public function direct(Zend_Controller_Action $controller, $objects, $root = 'items', $idmethod = array('id' => 'get_id')) {
        $controller->view->objects = array();
        if($controller->getRequest()->getParam('format') === 'xml') {
            foreach($objects as $curObject) {
                $controller->view->objects[] = $curObject;
            }
            echo $controller->view->indexXML($controller->view->objects, $root, $idmethod);
        } else if($controller->getRequest()->getParam('format') === 'json') {
            foreach($objects as $curObject) {
                $controller->view->objects[] = $curObject;
            }
            echo $controller->view->indexJSON($controller->view->objects, $root, $idmethod);
        }
    }
}
?>