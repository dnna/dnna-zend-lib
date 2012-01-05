<?php
/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_View_Helper_ArrayToXML extends Zend_View_Helper_Abstract
{
    public $view;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    protected function array_to_xml(SimpleXMLElement &$node, $data) {
        foreach($data as $key => $value) {
            if(is_array($value)) {
                if(!is_numeric($key)){
                    $subnode = $node->addChild("$key");
                    $this->array_to_xml($subnode, $value);
                }
                else{
                    $this->array_to_xml($node, $value);
                }
            }
            else {
                    $subnode = $node->addChild("$key","$value");
                if($curElement->getValue() === null || $curElement->getValue() === '') {
                    $subnode->addAttribute('xsi:nil', 'true', 'http://www.w3.org/2001/XMLSchema-instance');
                }
            }
        }
    }
    
    protected function addSingleElement(SimpleXMLElement &$node, Zend_Form_Element $element) {
            if($element->getValue() === null || $element->getValue() === '') {
                if($element->isRequired()) {
                    $subnode = $node->addChild($element->getName(), $element->getValue());
                    $subnode->addAttribute('xsi:nil', 'true', 'http://www.w3.org/2001/XMLSchema-instance');
                } else {
                    return; // Don't create a node for empty optional fields
                }
            } else {
                $subnode = $node->addChild($element->getName(), $element->getValue());
            }
            return $subnode;
    }

    protected function addElements(SimpleXMLElement &$node, Zend_Form $form) {
        foreach($form->getElements() as $curElement) {
            // Ignore submit/buttons
            if($curElement instanceof Zend_Form_Element_Submit || $curElement instanceof Zend_Form_Element_Button) {
                continue;
            }
            $this->addSingleElement($node, $curElement);
        }
        foreach($form->getSubForms() as $curSubForm) {
            if($curSubForm->getName() === 'default') {
                // Merge the default subform
                $this->addElements($node, $curSubForm);
            } else if($curSubForm->getElement('1') != null || $curSubForm->getSubForm('1') != null) {
                $subnode = $node->addChild($curSubForm->getName());
                $i = 1;
                while($curSubForm->getElement($i) != null) {
                    $this->addSingleElement($subnode->addChild('item'), $curSubForm->getElement($i));
                }
                $i = 1;
                while($curSubForm->getSubForm($i) != null) {
                    if($curSubForm->getSubForm($i) instanceof Dnna_Form_FormBase && !$curSubForm->getSubForm($i)->isEmpty()) {
                        $this->addElements($subnode->addChild('item'), $curSubForm->getSubForm($i));
                    }
                    $i++;
                }
            } else {
                $this->addElements($node->addChild($curSubForm->getName()), $curSubForm);
            }
        }
    }

    public function arrayToXML($data, $root = 'object') {
        // creating object of SimpleXMLElement
        $schemaurl = preg_replace('/\?.*/', '', $this->view->url(array('id' => 'schema')));
        $xmlstr =
        '<?xml version="1.0"?>
        <'.$root.' xmlns="'.htmlspecialchars($this->view->serverUrl().$schemaurl).'"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="'.htmlspecialchars($this->view->serverUrl().$schemaurl).' schema.xsd"></'.$root.'>';
        $xml = new SimpleXMLElement($xmlstr);
        // function call to convert array to xml
        if($data instanceof Zend_Form) {
            $this->addElements($xml, $data);
        } else {
            $this->array_to_xml($xml, $data);
        }
        //saving generated xml file
        return $xml->asXML();
    }
}
?>