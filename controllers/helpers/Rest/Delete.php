<?php
/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Action_Helper_Rest_Delete extends Zend_Controller_Action_Helper_ContextSwitch
{
    public function direct($controller, $classname, $id = null) {
        $controller->getHelper('viewRenderer')->setNoRender(TRUE);
        $em = Zend_Registry::get('entityManager');
        $object = $em->getRepository($classname)->find($id);
        if(isset($object)) {
            $em->remove($object);
        } else {
            throw new Exception('Το αντικείμενο που προσπαθήσατε να διαγράψετε δεν υπάρχει.');
        }
        $this->getResponse()->setHttpResponseCode(204); // OK (No Content)
    }
}
?>