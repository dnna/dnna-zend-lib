<?php
/**
 * Creates a form based on model annotations
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Form_AutoForm extends Dnna_Form_FormBase {
    protected $_class = 'Dnna_Model_Object';
    protected $_idfieldsonly;
    protected $_classesadded; // Prevent recursion loops
    protected $_issubform;
    protected $_elementorder = 100;

    public function __construct($class, $view = null, $idfieldsonly = false, $classesadded = array(), $issubform = false) {
        $this->_class = $class;
        if(!isset($idfieldsonly)) {
            $idfieldsonly = false;
        }
        $this->_idfieldsonly = $idfieldsonly;

        if(count($classesadded) <= 0) {
            array_push($classesadded, $this->_class);
        }
        $this->_classesadded = $classesadded;
        $this->_issubform = $issubform;
        parent::__construct($view);
        if($this->_issubform) {
            $this->initSubform();
        }
    }

    public function get_class() {
        return $this->_class;
    }

    public function set_class($_class) {
        $this->_class = $_class;
    }

    protected function inFormFields($id, $fields) {
        foreach($fields as $curField) {
            if($id === $curField->get_name()) {
                return true;
            }
        }
        return false;
    }

    public function createFieldsFromType() {
        $ids = $this->getIdFields();
        $fields = $this->getFormFields();
        foreach($ids as $curId) {
            if(!$this->inFormFields($curId, $fields)) {
                $this->addElement('hidden', $curId, array());
            }
        }

        foreach($fields as $curField) {
            if($curField->get_type() == Dnna_Form_Abstract_FormField::TYPE_TEXT) {
                $this->addElement('text', $curField->get_name(), array(
                    'label' => $curField->get_label(),
                    'required' => $curField->get_required(),
                ));
            } else if($curField->get_type() == Dnna_Form_Abstract_FormField::TYPE_PASSWORD) {
                $this->addElement('password', $curField->get_name(), array(
                    'label' => $curField->get_label(),
                    'required' => $curField->get_required(),
                ));
            } else if($curField->get_type() == Dnna_Form_Abstract_FormField::TYPE_CHECKBOX) {
                $this->addElement('checkbox', $curField->get_name(), array(
                    'label' => $curField->get_label(),
                    'required' => $curField->get_required(),
                ));
            } else if($curField->get_type() == Dnna_Form_Abstract_FormField::TYPE_TEXTAREA) {
                $this->addElement('textarea', $curField->get_name(), array(
                    'label' => $curField->get_label(),
                    'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(0, $this->_textareaMaxLength))
                    ),
                    'rows' => $this->_textareaRows,
                    'cols' => $this->_textareaCols,
                    'required' => $curField->get_required(),
                ));
            } else if($curField->get_type() == Dnna_Form_Abstract_FormField::TYPE_PARENTSELECT) {
                $this->createParentSelectField($curField);
            } else if($curField->get_type() == Dnna_Form_Abstract_FormField::TYPE_RECURSIVE) {
                $this->createRecursiveField($curField, false);
            } else if($curField->get_type() == Dnna_Form_Abstract_FormField::TYPE_RECURSIVEID) {
                $this->createRecursiveField($curField, true);
            } else if($curField->get_type() == Dnna_Form_Abstract_FormField::TYPE_HIDDEN) {
                if($this->getElement($curField->get_name()) == null) {
                    $this->addElement('hidden', $curField->get_name(), array(
                        'required' => $curField->get_required(),
                    ));
                }
            } else {
                throw new Exception('Άγνωστος τύπος πεδίου.');
            }

            // Add a high enough order so that other elements can be prepended
            if(($element = $this->getElement($curField->get_name())) != null) {
                $element->setOrder($this->_elementorder);
            } else if(($subform = $this->getSubForm($curField->get_name())) != null) {
                $subform->setOrder($this->_elementorder);
            }
            $this->_elementorder = $this->_elementorder + 10;

            // Check if field should be disabled
            if($curField->get_disabled() == true) {
                $this->getElement($curField->get_name())->setIgnore($curField->get_disabled());
                $this->getElement($curField->get_name())->setAttrib('readonly', $curField->get_disabled());
            } else {
                // Lets add some validators based on the metadata
                if($curField->getDoctrineType() === 'integer') {
                    $element->addValidator(new Zend_Validate_Int());
                } else if($curField->getDoctrineType() === 'date') {
                    $element->addValidator(new Zend_Validate_Date());
                    $element->setAttrib('class', 'usedatepicker hasDatepicker');
                }
            }
        }
    }

    protected function createParentSelectField($curField) {
        $targetClassname = $curField->getTargetClassName();
        $targetForm = new Dnna_Form_AutoForm($targetClassname, $this->_view, null, $this->_classesadded, false);
        $targetKey = $targetForm->getIdFields();
        $subform = new Dnna_Form_SubFormBase($this->_view);
        $subform->addElement('select', $targetKey[0], array(
            'label' => $curField->get_label(),
            'required' => $curField->get_required(),
            'multiOptions' => Application_Model_Repositories_Lists::getListAsArray($targetClassname),
        ));
        $this->addSubForm($subform, $curField->get_name(), false);
    }

    protected function createRecursiveField($curField, $idonly = false) {
        $targetClassname = $curField->getTargetClassName();
        $metadataclass = 'Doctrine\ORM\Mapping\ClassMetadataInfo';
        if(in_array($targetClassname, $this->_classesadded)) {
            return;
        }
        $newclassesadded = $this->_classesadded;
        array_push($newclassesadded, $targetClassname);
        if($curField->getAssociationType() == $metadataclass::ONE_TO_MANY || $curField->getAssociationType() == $metadataclass::MANY_TO_MANY) {
            $targetForm = new Dnna_Form_SubFormBase($this->_view);
            for($i = 1; $i < $curField->get_maxoccurs(); $i++) {
                $targetForm->addSubForm(new Dnna_Form_AutoForm($targetClassname, $this->_view, $idonly, $newclassesadded, true), $i);
            }
        } else {
            $targetForm = new Dnna_Form_AutoForm($targetClassname, $this->_view, $idonly, $newclassesadded, true);
        }
        $targetForm->setLegend($curField->get_label());
        $this->addSubForm($targetForm, $curField->get_name(), true);
    }

    /**
     * Δημιουργεί δυναμικά τα πεδία της φόρμας μέσα από την αντίστοιχη κλάση,
     * χρησιμοποιώντας annotations για το label.
     * @return Dnna_Form_Abstract_FormField
     */
    public function getFormFields() {
        $fields = Array();
        $reflection = new Zend_Reflection_Class($this->_class);
        $idfields = $this->getIdFields();
        foreach($reflection->getProperties() as $curProperty) {
            $docblock = $curProperty->getDocComment();
            if($docblock instanceof Zend_Reflection_Docblock) {
                if($this->_idfieldsonly == false || in_array($curProperty->getName(), $idfields)) {
                    $curField = new Dnna_Form_Abstract_FormField();
                    if($docblock->hasTag('FormFieldLabel') || $docblock->hasTag('FormFieldType')) {
                        $curField->set_belongingClass($this->_class);
                        $curField->set_name($curProperty->getName());
                        try {
                            $curField->set_metadata(Zend_Registry::get('entityManager')->getMetadataFactory()->getMetadataFor($this->_class));
                        } catch(Exception $e) { /* Object isn't an entity */ }
                        if($docblock->hasTag('FormFieldLabel')) {
                            $curField->set_label($docblock->getTag('FormFieldLabel')->getDescription());
                        }
                        if($docblock->hasTag('FormFieldRequired')) {
                            $curField->set_required(true);
                        }
                        if($docblock->hasTag('FormFieldType')) {
                            $curField->set_type($docblock->getTag('FormFieldType')->getDescription());
                            if($curField->get_type() == Dnna_Form_Abstract_FormField::TYPE_RECURSIVE ||
                                    $curField->get_type() == Dnna_Form_Abstract_FormField::TYPE_RECURSIVEID) {
                                if($docblock->hasTag('FormFieldMaxOccurs')) {
                                    $curField->set_maxoccurs($docblock->getTag('FormFieldMaxOccurs')->getDescription());
                                }
                            }
                        }
                        if($docblock->hasTag('FormFieldDisabled')) {
                            $curField->set_disabled($docblock->getTag('FormFieldDisabled')->getDescription());
                        }
                        if($docblock->hasTag('var')) {
                            $curField->set_var($docblock->getTag('var')->getDescription());
                        }
                    array_push($fields, $curField);
                    }
                }
            }
        }
        return $fields;
    }

    protected function getIdFields() {
        $ids = Array();
        $reflection = new Zend_Reflection_Class($this->_class);
        foreach($reflection->getProperties() as $curProperty) {
            $docblock = $curProperty->getDocComment();
            if($docblock instanceof Zend_Reflection_Docblock && $docblock->hasTag('Id')) {
                array_push($ids, substr($curProperty->getName(), 1));
            }
        }
        return $ids;
    }

    public function isEmpty() {
        $empty = true;
        $ids = $this->getIdFields();
        foreach($ids as $curId) {
            if($this->getElement($curId) != null && $this->getElement($curId)->getValue() != '') {
                $empty = false;
                break;
            }
        }
        return $empty;
    }

    public function init() {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAction($this->getView()->url());

        $this->createFieldsFromType();

        if($this->_issubform == false) {
            $this->addSubmitFields();
        }
    }
}
?>