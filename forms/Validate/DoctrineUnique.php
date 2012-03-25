<?php
/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Form_Validate_DoctrineUnique extends Zend_Validate_Abstract {
    const NOT_UNIQUE = 'entityNotUnique';

    protected $_entityName;

    protected $_fieldName;

    protected $_messageTemplates;
    
    protected $_ignoreValues;

    /**
     * Constructor of this validator
     *
     * The argument to this constructor is the third argument to the elements' addValidator
     * method.
     *
     * @param array|string $fieldsToCheck
     */
    public function __construct($entityName, $options = array()) {
        $this->_entityName = $entityName;
        // field name
        if(isset($options['fieldName'])) {
            $this->_fieldName = $options['fieldName'];
        }
        // except
        if(isset($options['ignoreValues'])) {
            $this->_ignoreValues = $options['ignoreValues'];
        } else {
            $this->_ignoreValues = array();
        }
        // error message
        if(isset($options['errorMsg'])) {
            $this->_messageTemplates = array(self::NOT_UNIQUE => $options['errorMsg']);
        } else {
            $this->_messageTemplates = array(self::NOT_UNIQUE => 'Entity not found');
        }
    }

    /**
     * Check if the element using this validator is valid
     *
     * This method will compare the $value of the element to the other elements
     * it needs to match. If they all match, the method returns true.
     *
     * @param $value string
     * @param $context array All other elements from the form
     * @return boolean Returns true if the element is valid
     */
    public function isValid($value, $context = null) {
        if(in_array($value, $this->_ignoreValues)) {
            return true;
        }
        if(isset($this->_fieldName)) {
            $object = Zend_Registry::get('entityManager')->getRepository($this->_entityName)->findOneBy(array($this->_fieldName => $value));
        } else {
            $object = Zend_Registry::get('entityManager')->getRepository($this->_entityName)->find($value);
        }
        if(isset($object)) {
            $this->_error(self::NOT_UNIQUE);
            return false;
        }
        return true;
    }
}
?>