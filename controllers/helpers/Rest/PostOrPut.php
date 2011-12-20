<?php
/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Action_Helper_Rest_PostOrPut extends Zend_Controller_Action_Helper_ContextSwitch
{
    public function direct($controller, $classname, Dnna_Form_FormBase $form, $id = null) {
        $controller->getHelper('viewRenderer')->setNoRender(TRUE);
        if($form->isValid($controller->getRequest()->getUserParams())) {
            $created = false;
            if(isset($id)) {
                $em = Zend_Registry::get('entityManager');
                $object = $em->getRepository($classname)->find($id);
            }
            if(!isset($object)) {
                $created = true;
                $object = new $classname();
                $object->save(); // Για να πάρει id
            }
            $object->setOptions($form->getValues());
            $object->save();
            $newurl = htmlspecialchars($controller->view->serverUrl().$controller->view->url(array('id' => $object->get_id())));
            if($created == true) {
                $this->getResponse()->setRedirect($newurl, 201); // Created
            } else {
                $this->getResponse()->setHttpResponseCode(204);
                //$this->getResponse()->setRedirect($newurl, 204); // OK (No Content)
            }
        } else {
            throw new Exception('Κάποια στοιχεία δεν συμπληρώθηκαν ή δεν είναι έγκυρα.');
            //return array('error' => true, 'errorRow' => $i, 'formElements' => $form->getElementsAsArray());
        }
    }
}
?>