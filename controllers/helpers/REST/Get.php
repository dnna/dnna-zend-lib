<?php
/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Action_Helper_Rest_Get extends Zend_Controller_Action_Helper_ContextSwitch
{
    public function direct(Zend_Controller_Action $controller, $object, Zend_Form $form, $root = 'item') {
        if($form instanceof Dnna_Form_FormBase) {
            $form->setIgnore(false);
        }
        $form->populate($object);
        if($this->getRequest()->getParam('format') === 'xml') {
            echo $controller->view->arrayToXML($form, $root);
        } else if($this->getRequest()->getParam('format') === 'json') {
            echo $controller->view->arrayToJSON($form, $root);
        }
    }
}
?>