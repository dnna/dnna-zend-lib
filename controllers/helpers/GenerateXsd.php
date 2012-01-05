<?php
require('SimpleDOM.php');

/**
 * Παίρνει ένα doc αρχείο και αντικαθιστά κάποια strings μέσα σε αυτό. Στη
 * συγκεκριμένη εφαρμογή χρησιμοποιείται για την παραγωγή των αιτήσεων μέσα από
 * τις φόρμες.
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Action_Helper_GenerateXsd extends Zend_Controller_Action_Helper_Abstract {
    /**
     * @var SimpleXMLElement
     */
    protected $_xmlobj;
    protected $_form;

    public function direct(Zend_Controller_Action $controller, Zend_Form $form, $root = 'item') {
        $xmlstr = 
        '<?xml version="1.0"?>
        <xsd:schema
            targetNamespace="'.htmlspecialchars($controller->view->serverUrl().$controller->view->url(array('id' => 'schema'))).'"
            elementFormDefault="qualified"
            xmlns:xsd="http://www.w3.org/2001/XMLSchema"
            xmlns="'.htmlspecialchars($controller->view->serverUrl().$controller->view->url(array('id' => 'schema'))).'"></xsd:schema>';
        $this->_xmlobj = simpledom_load_string($xmlstr);
        // Create the root type and the root element
        $rootelement = $this->_xmlobj->addChild('xsd:element');
        $rootelement->addAttribute('name', $root);
        $complexType = $rootelement->addChild('xsd:complexType');
        $sequence = $complexType->addChild('xsd:sequence');
        // Sometimes people change the form in isValid so lets make sure its executed
        @$form->isValid($form->getValues());
        // Add the rest of the elements
        $this->addElements($sequence, $form);
        return $this->_xmlobj->asXML();
    }

    protected function addElements(SimpleDOM &$xmlobj, Zend_Form $form) {
        foreach($form->getElements() as $curElement) {
            // Ignore submit/buttons
            if($curElement instanceof Zend_Form_Element_Submit || $curElement instanceof Zend_Form_Element_Button) {
                continue;
            }
            $elementxmlobj = $this->addElement($xmlobj, $curElement);
            $typexmlobj = $this->addSimpleType($elementxmlobj, $curElement);
        }
        foreach($form->getSubForms() as $curSubForm) {
            if($curSubForm->getName() === 'default') {
                // Merge the default subform
                $this->addElements($xmlobj, $curSubForm);
            } else if($curSubForm->getElement('1') != null || $curSubForm->getSubForm('1') != null) {
                // Looping elements or subforms
                $this->addComplexType($xmlobj, $curSubForm);
                // Create the wrapper element
                $rootelement = $xmlobj->addChild('xsd:element');
                $rootelement->addAttribute('name', $curSubForm->getName());
                $complexType = $rootelement->addChild('xsd:complexType');
                $sequence = $complexType->addChild('xsd:sequence');
                // Create the actual looping elements
                $elementxmlobj = $sequence->addChild('xsd:element');
                $elementxmlobj->addAttribute('name', 'item');
                $elementxmlobj->addAttribute('type', $curSubForm->getName().'_type');
                $elementxmlobj->addAttribute('minOccurs', 1);
                $elementxmlobj->addAttribute('maxOccurs', $this->calculateLoopingElementsNum($curSubForm));
            } else {
                $rootelement = $xmlobj->addChild('xsd:element');
                $rootelement->addAttribute('name', $curSubForm->getName());
                $complexType = $rootelement->addChild('xsd:complexType');
                $sequence = $complexType->addChild('xsd:sequence');
                $this->addElements($sequence, $curSubForm);
                // Add comment
                if(trim($curSubForm->getLegend()) != '') {
                    $rootelement->insertComment(str_replace(':', '', trim($curSubForm->getLegend())), 'before');
                }
            }
        }
        return $xmlobj;
    }

    protected function addSimpleType(SimpleDOM &$xmlobj, Zend_Form_Element $element) {
        $typexmlobj = $xmlobj->addChild('xsd:simpleType');
        $this->addRestrictions($typexmlobj, $element);
        return $typexmlobj;
    }
    
    protected function addComplexType(SimpleDOM &$xmlobj, Zend_Form $form) {
        $complexTypeXmlObj = $this->_xmlobj->addChild('xsd:complexType');
        $complexTypeXmlObj->addAttribute('name', $form->getName().'_type');
        $sequence = $complexTypeXmlObj->addChild('xsd:sequence');
        $this->addElements($sequence, $form->getSubForm('1'));
        return $complexTypeXmlObj;
    }

    protected function addElement(SimpleDOM &$xmlobj, Zend_Form_Element $element) {
        $elementxmlobj = $xmlobj->addChild('xsd:element');
        $elementxmlobj->addAttribute('name', $element->getName());
        // Add minOccurs based on whether its required or not
        if(!$element->isRequired()) {
            $elementxmlobj->addAttribute('minOccurs', '0');
            $elementxmlobj->addAttribute('nillable', 'true');
        }
        // Add annotation for ignored/readonly fields
        if($element->getIgnore() == true) {
            $annotation = $elementxmlobj->addChild('xsd:annotation');
            $appinfo = $annotation->addChild('xsd:appinfo');
            $readonly = $appinfo->addChild('readOnly', 'true', '');
        }
        // Add comment
        if(trim($element->getLabel()) != '') {
            $elementxmlobj->insertComment(str_replace(':', '', trim($element->getLabel())), 'before');
        }
        return $elementxmlobj;
    }

    protected function addRestrictions(SimpleDOM &$typexmlobj, Zend_Form_Element $element) {
        // 1. If the element type is a select then create a restriction and an enumeration of the available options
        if($element instanceof Zend_Form_Element_Select) {
            $restriction = $this->addSelectRestrictions($typexmlobj, $element);
        }
        // 2. If the element type is a checkbox then create have an enumeration of 0 or 1
        if($element instanceof Zend_Form_Element_Checkbox) {
            $restriction = $this->addCheckboxRestrictions($typexmlobj, $element);
        }
        // 3. If the element type is file then make it a base64Binary
        if($element instanceof Zend_Form_Element_File) {
            $restriction = $typexmlobj->addChild('xsd:restriction');
            $restriction->addAttribute('base', 'xsd:base64Binary');
        }
        // 4. Finally create restrictions based on the validators
        if(isset($restriction)) {
            $this->addValidatorRestrictions($restriction, $element);
        } else {
            $this->addValidatorRestrictions($typexmlobj->addChild('xsd:restriction'), $element);
        }
    }

    protected function addSelectRestrictions(SimpleDOM &$typexmlobj, Zend_Form_Element_Select $element) {
        $restriction = $typexmlobj->addChild('xsd:restriction');
        $restriction->addAttribute('base', 'xsd:string');
        foreach($element->getMultiOptions() as $curOption => $curValue) {
            //$comment = $restriction->addChild('!-- Σχόλιο --');
            $enum = $restriction->addChild('xsd:enumeration');
            $enum->addAttribute('value', $curOption);
            if($curValue === '-') {
                $curValue = ' - ';
            } else {
                $curValue = trim($curValue);
            }
            $enum->insertComment($curValue, 'before');
        }
        return $restriction;
    }

    protected function addCheckboxRestrictions(SimpleDOM &$typexmlobj, Zend_Form_Element_Checkbox $element) {
        $restriction = $typexmlobj->addChild('xsd:restriction');
        $restriction->addAttribute('base', 'xsd:integer');
        for($i = 0; $i <= 1; $i++) {
            $enum = $restriction->addChild('xsd:enumeration');
            $enum->addAttribute('value', $i);
        }
        return $restriction;
    }

    protected function addValidatorRestrictions(SimpleDOM &$typexmlobj, Zend_Form_Element $element) {
        $base = 'xsd:string';
        foreach($element->getValidators() as $curValidator) {
            //var_dump(get_class($curValidator));
            // Zend_Validate_StringLength
            if($curValidator instanceof Zend_Validate_StringLength) {
                $min = $typexmlobj->addChild('xsd:minLength');
                $min->addAttribute('value', $curValidator->getMin());
                $max = $typexmlobj->addChild('xsd:maxLength');
                $max->addAttribute('value', $curValidator->getMax());
            }
            // Zend_Validate_Float
            if($curValidator instanceof Zend_Validate_Float) {
                $base = 'xsd:float';
                //$pattern = $typexmlobj->addChild('xsd:pattern');
                //$pattern->addAttribute('value', '([\$]?)([0-9,\s]*\.?[0-9]{0,2})');
            }
            // Zend_Validate_Date - Chose dateTime isntead of date for easy ISO8601 formatting with PHP
            if($curValidator instanceof Zend_Validate_Date) {
                $base = 'xsd:dateTime';
            }
        }
        
        $attributes = $typexmlobj->attributes();
        if(!isset($attributes['base'])) {
            $typexmlobj->addAttribute('base', $base);
        }
        return $typexmlobj;
    }

    protected function calculateLoopingElementsNum(Zend_Form $form) {
        $i = 1;
        while($form->getElement($i) != null || $form->getSubForm($i) != null) {
            $i++;
        }
        return ($i - 1);
    }
}

?>