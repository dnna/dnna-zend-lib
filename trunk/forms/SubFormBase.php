<?php
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Form_SubFormBase extends Dnna_Form_FormBase {
    /**
     * Whether or not form elements are members of an array
     * @var bool
     */
    protected $_isArray = true;
    
    public function __construct($view = null) {
        parent::__construct($view);
        $tempsubform = new Zend_Form_SubForm();
        $this->setDecorators($tempsubform->getDecorators());
    }
}
?>