<?php
/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_View_Helper_ArrayToJSON extends Zend_View_Helper_Abstract
{
    public $view;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }
    
    protected function addElements($data, Zend_Form $form) {
        foreach($form->getElements() as $curName => $curElement) {
            // Ignore submit/buttons
            if($curElement instanceof Zend_Form_Element_Submit || $curElement instanceof Zend_Form_Element_Button) {
                continue;
            }
            $data[$curName] = $curElement->getValue();
        }
        foreach($form->getSubForms() as $curName => $curSubForm) {
            $data[$curName] = $this->addElements($data[$curName], $curSubForm);
        }
        return $data;
    }

    public function arrayToJSON($data, $root = 'object') {
        if($data instanceof Zend_Form) {
            $result = $this->addElements(array(), $data);
        } else {
            $result = $data;
        }
        return json_encode($result);
    }
}
?>