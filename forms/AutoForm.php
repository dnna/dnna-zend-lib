<?php
/**
 * Creates a form based on model annotations
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Form_AutoForm extends Dnna_Form_FormBase {
    protected $_class = 'Dnna_Model_Object';

    public function __construct($class, $view = null) {
        $this->_class = $class;
        parent::__construct($view);
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
            if($curField->get_type() == Application_Form_Abstract_FormField::TYPE_TEXT) {
                $this->addElement('text', $curField->get_name(), array(
                    'label' => $curField->get_label(),
                    'required' => $curField->get_required(),
                ));
            } else if($curField->get_type() == Application_Form_Abstract_FormField::TYPE_PARENTSELECT) {
                $targetClassname = $curField->get_metadata()->associationMappings['_'.$curField->get_name()]['targetEntity'];
                $targetForm = new Dnna_Form_AutoForm($targetClassname, $this->_view);
                $targetKey = $targetForm->getIdFields();
                $subform = new Dnna_Form_SubFormBase($this->_view);
                $subform->addElement('select', $targetKey[0], array(
                    'label' => $curField->get_label(),
                    'required' => $curField->get_required(),
                    'multiOptions' => Application_Model_Repositories_Lists::getListAsArray($targetClassname),
                ));
                $this->addSubForm($subform, $curField->get_name(), false);
            } else if($curField->get_type() == Application_Form_Abstract_FormField::TYPE_HIDDEN) {
                if($this->getElement($curField->get_name()) == null) {
                    $this->addElement('hidden', $curField->get_name(), array(
                        'required' => $curField->get_required(),
                    ));
                }
            } else {
                throw new Exception('Άγνωστος τύπος πεδίου.');
            }
            if($curField->get_disabled() == true) {
                $this->getElement($curField->get_name())->setIgnore($curField->get_disabled());
                $this->getElement($curField->get_name())->setAttrib('readonly', $curField->get_disabled());
            }
        }
    }

    /**
     * Δημιουργεί δυναμικά τα πεδία της φόρμας μέσα από την αντίστοιχη κλάση,
     * χρησιμοποιώντας annotations για το label.
     * @return Application_Form_Abstract_FormField
     */
    public function getFormFields() {
        $fields = Array();
        $reflection = new Zend_Reflection_Class($this->_class);
        foreach($reflection->getProperties() as $curProperty) {
            $docblock = $curProperty->getDocComment();
            if($docblock instanceof Zend_Reflection_Docblock) {
                $curField = new Application_Form_Abstract_FormField();
                if($docblock->hasTag('FormFieldLabel') || $docblock->hasTag('FormFieldType')) {
                    $curField->set_belongingClass($this->_class);
                    $curField->set_name(substr($curProperty->getName(), 1));
                    $curField->set_metadata(Zend_Registry::get('entityManager')->getMetadataFactory()->getMetadataFor($this->_class));
                    if($docblock->hasTag('FormFieldLabel')) {
                        $curField->set_label($docblock->getTag('FormFieldLabel')->getDescription());
                    }
                    if($docblock->hasTag('FormFieldRequired')) {
                        $curField->set_required(true);
                    }
                    if($docblock->hasTag('FormFieldType')) {
                        $curField->set_type($docblock->getTag('FormFieldType')->getDescription());
                    }
                    if($docblock->hasTag('FormFieldDisabled')) {
                        $curField->set_disabled($docblock->getTag('FormFieldDisabled')->getDescription());
                    }
                array_push($fields, $curField);
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

    protected function addSubmitFields(&$dg = Array()) {
        $id = Zend_Controller_Front::getInstance()->getRequest()->getParam('id');
        // Add the submit button
        $this->addElement('submit', 'submit', array(
            'ignore' => true,
            'label' => 'Υποβολή',
        ));
    }

    public function init() {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAction($this->getView()->url());

        $this->createFieldsFromType();

        $this->addSubmitFields();
    }
}
?>