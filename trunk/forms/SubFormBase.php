<?php
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Form_SubFormBase extends Dnna_Form_FormBase {
    public function __construct($view = null) {
        parent::__construct($view);
        $this->initSubform();
    }
}
?>