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
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"
            targetNamespace="'.htmlspecialchars($controller->view->serverUrl().$controller->view->url()).'/schema"
            xmlns:tns="'.htmlspecialchars($controller->view->serverUrl().$controller->view->url()).'/schema"
            elementFormDefault="qualified"></xs:schema>';
        $this->_xmlobj = simpledom_load_string($xmlstr);
        // Create the root type and the root element
        $roottype = $this->_xmlobj->addChild('xs:complexType');
        $roottype->addAttribute('name', $root.'_type');
        $sequence = $roottype->addChild('xs:sequence');
        $rootelement = $this->_xmlobj->addChild('xs:element');
        $rootelement->addAttribute('name', $root);
        $rootelement->addAttribute('type', 'tns:'.$root.'_type');
        // Add the rest of the elements
        $this->addElements($sequence, $form);
        return $this->_xmlobj->asXML();
    }

    protected function addElements(SimpleDOM &$xmlobj, Zend_Form $form) {
        // Merge the default subform
        if($form->getSubForm('default') != null) {
            $this->addElements($xmlobj, $form->getSubForm('default'));
        }
        foreach($form->getElements() as $curElement) {
            // Ignore submit/buttons
            if($curElement instanceof Zend_Form_Element_Submit || $curElement instanceof Zend_Form_Element_Button) {
                continue;
            }
            $this->addSimpleType($this->_xmlobj, $curElement);
            $this->addElement($xmlobj, $curElement);
        }
        foreach($form->getSubForms() as $curSubForm) {
            $newxmlobj = $this->_xmlobj->addChild('xs:complexType');
            $newxmlobj->addAttribute('name', $curSubForm->getName());
            $sequence = $newxmlobj->addChild('xs:sequence');
            $this->addElements($sequence, $curSubForm);
        }
    }

    protected function addSimpleType(SimpleDOM &$xmlobj, Zend_Form_Element $element) {
        $typexmlobj = $xmlobj->addChild('xs:simpleType');
        $typexmlobj->addAttribute('name', $element->getName().'_type');
        if(trim($element->getLabel()) != '') {
            $typexmlobj->insertComment(str_replace(':', '', trim($element->getLabel())), 'before');
        }
        $this->addRestrictions($typexmlobj, $element);
        
    }

    protected function addElement(SimpleDOM &$xmlobj, Zend_Form_Element $element) {
        $elementxmlobj = $xmlobj->addChild('xs:element');
        $elementxmlobj->addAttribute('name', $element->getName());
        $elementxmlobj->addAttribute('type', 'tns:'.$element->getName().'_type');
        // Add annotation for ignored/readonly fields
        if($element->getIgnore() == true) {
            $annotation = $elementxmlobj->addChild('xs:annotation');
            $appinfo = $annotation->addChild('xs:appinfo');
            $readonly = $appinfo->addChild('readOnly', 'true', '');
        }
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
            $restriction = $typexmlobj->addChild('xs:restriction');
            $restriction->addAttribute('base', 'xs:base64Binary');
        }
        // 4. Finally create restrictions based on the validators
        if(isset($restriction)) {
            $this->addValidatorRestrictions($restriction, $element);
        } else {
            $this->addValidatorRestrictions($typexmlobj->addChild('xs:restriction'), $element);
        }
    }
    
    protected function addSelectRestrictions(SimpleDOM &$typexmlobj, Zend_Form_Element_Select $element) {
        $restriction = $typexmlobj->addChild('xs:restriction');
        $restriction->addAttribute('base', 'xs:string');
        foreach($element->getMultiOptions() as $curOption => $curValue) {
            //$comment = $restriction->addChild('!-- Σχόλιο --');
            $enum = $restriction->addChild('xs:enumeration');
            $enum->addAttribute('value', $curOption);
            if($curValue === '-') {
                $curValue = ' - ';
            }
            $enum->insertComment($curValue, 'before');
        }
        return $restriction;
    }
    
    protected function addCheckboxRestrictions(SimpleDOM &$typexmlobj, Zend_Form_Element_Checkbox $element) {
        $restriction = $typexmlobj->addChild('xs:restriction');
        $restriction->addAttribute('base', 'xs:integer');
        for($i = 0; $i <= 1; $i++) {
            $enum = $restriction->addChild('xs:enumeration');
            $enum->addAttribute('value', $i);
        }
        return $restriction;
    }
    
    protected function addValidatorRestrictions(SimpleDOM &$typexmlobj, Zend_Form_Element $element) {
        $base = 'xs:string';
        foreach($element->getValidators() as $curValidator) {
            //var_dump(get_class($curValidator));
            // Zend_Validate_StringLength
            if($curValidator instanceof Zend_Validate_StringLength) {
                $min = $typexmlobj->addChild('xs:minInclusive');
                $min->addAttribute('value', $curValidator->getMin());
                $max = $typexmlobj->addChild('xs:maxInclusive');
                $max->addAttribute('value', $curValidator->getMax());
            }
            // Zend_Validate_Float
            if($curValidator instanceof Zend_Validate_Float) {
                $pattern = $typexmlobj->addChild('xs:pattern');
                $pattern->addAttribute('value', '^([\$]?)([0-9,\s]*\.?[0-9]{0,2})$');
            }
            // Zend_Validate_Date
            if($curValidator instanceof Zend_Validate_Date) {
                $base = 'xs:date';
            }
        }
        
        $attributes = $typexmlobj->attributes();
        if(!isset($attributes['base'])) {
            $typexmlobj->addAttribute('base', $base);
        }
        return $typexmlobj;
    }
}

?>