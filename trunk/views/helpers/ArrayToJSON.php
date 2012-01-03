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
    
    protected function addElements(&$data, Zend_Form $form) {
        foreach($form->getElements() as $curName => $curElement) {
            // Ignore submit/buttons
            if($curElement instanceof Zend_Form_Element_Submit || $curElement instanceof Zend_Form_Element_Button) {
                continue;
            }
            if($curElement->getValue() === null || $curElement->getValue() === '') {
                if(!$curElement->isRequired()) {
                    continue;
                }
            }
            $data[$curName] = $curElement->getValue();
        }
        foreach($form->getSubForms() as $curName => $curSubForm) {
            if($curSubForm->getName() === 'default') {
                // Merge the default subform
                $this->addElements($data, $curSubForm);
            } else if($curSubForm->getElement('1') != null || $curSubForm->getSubForm('1') != null) {
                $data[$curName] = array();
                $i = 1;
                while($curSubForm->getElement($i) != null) {
                    // Ignore submit/buttons
                    if($curSubForm->getElement($i) instanceof Zend_Form_Element_Submit || $curSubForm->getElement($i) instanceof Zend_Form_Element_Button) {
                        continue;
                    }
                    $data[$curName][$i][$curSubForm->getElement($i)->getName()] = $curSubForm->getElement($i)->getValue();
                }
                $i = 1;
                while($curSubForm->getSubForm($i) != null) {
                    if($curSubForm->getSubForm($i) instanceof Dnna_Form_FormBase && !$curSubForm->getSubForm($i)->isEmpty()) {
                        $this->addElements($data[$curName][$i], $curSubForm->getSubForm($i));
                    }
                    $i++;
                }
            } else {
                $this->addElements($data[$curName], $curSubForm);
            }
        }
    }

    public function arrayToJSON($data, $root = 'object') {
        $result = array();
        if($data instanceof Zend_Form) {
            $this->addElements($result, $data);
        } else {
            $result = $data;
        }
        $callback = Zend_Controller_Front::getInstance()->getRequest()->getParam('callback');
        if($callback != null) {
            // strip all non alphanumeric elements from callback
            $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $callback);
            return $callback.'('.json_encode($result).');';
        } else {
            return json_encode($result);
        }
    }
}
?>