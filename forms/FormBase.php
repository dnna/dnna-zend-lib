<?php
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Form_FormBase extends Zend_Form {
    protected $_textareaRows;
    protected $_textareaCols;
    protected $_textareaMaxLength;

    public function __construct($view = null) {
        $textareainfo = self::getTextAreaInfo();
        $this->_textareaRows = $textareainfo['textareaRows'];
        $this->_textareaCols = $textareainfo['textareaCols'];
        $this->_textareaMaxLength = $textareainfo['textareaMaxLength'];
        $this->addElementPrefixPath('Application_Form_Decorator', APPLICATION_PATH.'/forms/Decorator', 'decorator');
        
        if($view instanceof Zend_View_Interface) {
            $this->setView($view);
        }
        
        parent::__construct();
        
        // Προσθήκη του elementprefix σε όλα τα display groups και subforms λόγω bug του Zend
        foreach($this->getDisplayGroups() as $curDg) {
            $curDg->addPrefixPath('Application_Form_Decorator', APPLICATION_PATH.'/forms/Decorator', 'decorator');
        }
        foreach($this->getSubForms() as $curSubform) {
            $curSubform->addPrefixPath('Application_Form_Decorator', APPLICATION_PATH.'/forms/Decorator', 'decorator');
        }
        
        $this->fixDecorators();
    }

    public static function getTextAreaInfo() {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $config = $bootstrap->getOptions();
        $info = array();
        $info['textareaRows'] = $config['form']['textareaRows'];
        $info['textareaCols'] = $config['form']['textareaCols'];
        $info['textareaMaxLength'] = $config['form']['textareaMaxLength'];
        
        return $info;
    }
    
    protected function initSubform() {
        $this->_isArray = true;
        $tempsubform = new Zend_Form_SubForm();
        $this->setDecorators($tempsubform->getDecorators());
        $this->removeElement('submit');
    }
    
    public function fixDecorators() {
        // Αφαίρεση των decorators από τα hidden πεδία αλλιώς προσθήκη του div decorator
        foreach ($this->getElements() as $element) {
            if ($element->getType() === "Zend_Form_Element_Hidden" || $element->getType() === "Application_Form_Element_Note") {
                $element->setDecorators(array('ViewHelper'));
            } else {
                $getIdWithDiv = create_function('$decorator',
                                         'return $decorator->getElement()->getId()
                                                 . "-div";');
                $element->addDecorators(array(
                    array(array('elementDiv' => 'HtmlTag'), array('tag' => 'div', 'id' => array('callback' => $getIdWithDiv))),
                ));
                // Αν είναι groupDiv τότε το ξαναπροσθέτουμε για να μπεί στην κορυφή του decorator stack και να καλύπτει σωστά τα στοιχεία
                if($element->getDecorator('groupDiv') != null) {
                    $decorator = $element->getDecorator('groupDiv');
                    $element->removeDecorator('groupDiv');
                    $element->addDecorator($decorator);
                }
            }
        }
    }
    
    public function addSubForm(Zend_Form $form, $name, $keepDecorators = null, $arrayCollectionFieldset = null) {
        parent::addSubForm($form, $name, null);
        $form->addPrefixPath('Application_Form_Decorator', APPLICATION_PATH.'/forms/Decorator', 'decorator');
        $form->fixDecorators();
        if($arrayCollectionFieldset != null) {
            $this->getSubForm($name)->removeDecorator('DtDdWrapper');
            $decorator = $this->getSubForm($name)->getDecorator('Fieldset');
            $decorator->setOption('id', 'fieldset-'.$arrayCollectionFieldset.'-'.$name);
            $this->getSubForm($name)->removeDecorator('Fieldset');
            $this->removeSubItemImg($name, $arrayCollectionFieldset);
            $this->getSubForm($name)->addDecorator($decorator);
            $form->addElement('hidden', 'isvisible', array(
                'value' => '0',
                )
            );
            $form->getElement('isvisible')->setDecorators(array('ViewHelper'));
        }
        if($keepDecorators === false) {
            $this->getSubForm($name)->setDecorators(array('FormElements'));
        }
    }
    
    protected function removeSubItemImg($baseName, $parent, $removeButtonName = null) {
        $baseNameUcFirst = ucfirst($baseName);
        
        // Ειδική περίπτωση όπου το όνομα του κουμπιού διαφέρει από το baseName
        if($removeButtonName != null) {
            $baseNameUcFirst = ucfirst($removeButtonName);
        }
        
        $view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
        $this->getSubForm($baseName)
            ->addDecorator('HtmlTag', array('tag' => 'img', 'src' => $view->baseUrl("images/delete_x.gif"),
            'alt' => 'Αφαίρεση', 'id' => 'remove-'.$parent.'-'.$baseNameUcFirst, 'placement' => 'prepend', 'class' => 'removeSubItem removeSubItemFieldset'));
    }
    
    protected function addExpandImg($name, $addDtDd = true, $id = 'toggleSupervisorDetails', $class = 'toggleSupervisorDetails') {
        $dtdddecorator = $this->getSubForm($name)->getDecorator('DtDdWrapper');
        $this->getSubForm($name)->removeDecorator('DtDdWrapper');
        $fieldsetdecorator = $this->getSubForm($name)->getDecorator('Fieldset');
        $fieldsetid = $fieldsetdecorator->getOption('id');
        $this->getSubForm($name)->removeDecorator('Fieldset');
        
        $this->getSubForm($name)
            ->addDecorator('HtmlTag', array('tag' => 'img', 'src' => $this->_view->baseUrl("images/toggle-details.png"),
            'alt' => 'Επιπλέον στοιχεία υπευθύνου', 'id' => $id, 'placement' => 'prepend', 'class' => $class));
        
        $this->getSubForm($name)->addDecorator('Fieldset');
        $this->getSubForm($name)->getDecorator('Fieldset')->setOption('id', $fieldsetid); // Id fix για το fieldset
        if($addDtDd == true) {
            $this->getSubForm($name)->addDecorator('DtDdWrapper');
        }
    }
    
    protected function addSubmitFields() {
        // Add the submit button
        $this->addElement('submit', 'submit', array(
            'ignore' => true,
            'label' => 'Υποβολή',
            'class' => 'submitbutton',
        ));
    }
    
    public function populate($object) {
        $defsubform = $this->getSubForm('default');
        if($object instanceof Dnna_Model_Object) {
            foreach($object->getOptions() as $curProperty => $curValue) {
                if(is_scalar($curValue) || method_exists($curValue, 'getId') || $curValue instanceof EDateTime || $curValue == null) {
                    // Api conversions για να βγαίνει valid xsd
                    if(Zend_Registry::isRegistered('performApiConversions')) {
                        if($this->getElement($curProperty) != null && $this->getElement($curProperty)->getValidator('Float') != false) {
                            // Float conversion για να κάνει validate το παραγόμενο xml με το xsd στα διάφορα engines
                            $curValue = Zend_Locale_Format::getNumber($curValue,
                                                        array('precision' => 2,
                                                              'locale' => Zend_Registry::get('Zend_Locale'))
                                                       );
                        }
                    }
                    if($defsubform != null) {
                        $defsubform->populate($object);
                    }
                    if(method_exists($curValue, 'getId')) {
                        $curValue = $curValue->getId();
                    }
                    if($this->getElement($curProperty) != null && $curValue !== "--NONAME--") {
                        $this->getElement($curProperty)->setValue($curValue);
                    }
                } else if($this->getSubForm($curProperty) != null) {
                    if($this->getSubForm($curProperty)->getElement('isvisible') != null) {
                        $this->getSubForm($curProperty)->getElement('isvisible')->setValue('1');
                    }
                    $this->getSubForm($curProperty)->populate($curValue);
                }
            }
        } else if($object instanceof Traversable || (is_array($object) && isset($object['1']))) {
            $i = 1;
            foreach($object as $curObject) {
                $subform = $this->getSubForm($i);
                if($subform != null) {
                    if($subform->getElement('isvisible') != null) {
                        $subform->getElement('isvisible')->setValue('1');
                    }
                    $subform->populate($curObject);
                }
                $i++;
            }
        } else {
            throw new Exception('Η φόρμα δεν μπόρεσε να γίνει populate.');
        }
        //return parent::populate($mapping); // Η populate της Zend_Form είναι recursive και δημιουργεί bugs
    }
    
    public function setRequired($required) {
        foreach($this->getElements() as $curElement) {
            $curElement->setRequired($required);
        }
        foreach($this->getSubForms() as $curSubForm) {
            if($curSubForm instanceof Dnna_Form_FormBase) {
                $curSubForm->setRequired($required);
            }
        }
    }
    
    public function setIgnore($ignore) {
        foreach($this->getElements() as $curElement) {
            if($curElement instanceof Zend_Form_Element_Submit || $curElement instanceof Zend_Form_Element_Button) {
                continue;
            }
            $curElement->setIgnore($ignore);
        }
        foreach($this->getSubForms() as $curSubForm) {
            if($curSubForm instanceof Dnna_Form_FormBase) {
                $curSubForm->setIgnore($ignore);
            }
        }
    }
    
    public function getElementsAsArray() {
        return $this->getElementsAsArray_r($this);
    }
    
    public function isValid($data) {
        if(isset($data['isvisible']) && $data['isvisible'] == 0) {
            $this->setRequired(false);
        }
        return parent::isValid($data);
    }
    
    public function addElement($element, $name = null, $options = null) {
        parent::addElement($element, $name, $options);
        if(Zend_Registry::isRegistered('performApiConversions')) {
            // Change the validator formats to correspond to what the xsd validators expect
            $element = $this->getElement($name);
            if($element != null) {
                foreach($element->getValidators() as $curValidator) {
                    if($curValidator instanceof Zend_Validate_Date) {
                        //$curValidator->setFormat(Zend_Date::ISO_8601); // Doesn't seem to work :(
                        $element->removeValidator('Date');
                    }/* else if($curValidator instanceof Zend_Validate_Float) {
                        $curValidator->setLocale(new Zend_Locale('en_US'));
                    }*/
                }
            }
        }
    }
    
    private function getElementsAsArray_r(Zend_Form $root) {
        $elements = array();
        foreach($root->getElements() as $curElement) {
            $elements[$curElement->getName()] = $curElement;
        }
        foreach($root->getSubForms() as $curSubForm) {
            $elements[$curSubForm->getName()] = $this->getElementsAsArray_r($curSubForm);
        }
        return $elements;
    }
    
    public function getValues($suppressArrayNotation = false) {
        $values = parent::getValues($suppressArrayNotation);
        foreach($this->getElements() as $curName => $curElement) {
            if($curElement instanceof Zend_Form_Element_File) {
                $curElement->receive();
                $filepath = $curElement->getFileName();
                if(is_scalar($filepath)) {
                    $values[$curName] = array(
                        'filename' => basename($filepath),
                        'contents' => file_get_contents($filepath),
                    );
                    if($filepath != '') {
                        unlink($filepath);
                    }
                } else {
                    $values[$curName] = array(
                        'filename' => "null",
                        'contents' => "",
                    );
                }
            }
        }
        return $values;
    }

    // Checks if the form should be considered empty or not (for ONE-TO-MANY and MANY-TO-MANY
    // loops). In Dnna_Form_FormBase this always returns false, it's there so you can override
    // it in your own forms.
    public function isEmpty() {
        $empty = true;
        foreach($this->getSubForms() as $curSubForm) {
            if($curSubForm instanceof Dnna_Form_FormBase && !$curSubForm->isEmpty()) {
                $empty = false;
                break;
            }
        }
        return $empty;
    }
    
    private function mergeDefault(array &$values) {
        if(isset($values['default'])) {
            $values = $values + $values['default'];
            unset($values['default']);
        }
        foreach($values as $curValue) {
            if(is_array($curValue)) {
                $this->mergeDefault($curValue);
            }
        }
    }
}
?>