<?php
use Doctrine\ORM\Mapping\ClassMetadata;

class Dnna_Form_Abstract_FormField {

    protected $_belongingClass;

    protected $_name;

    protected $_label;

    protected $_required = false;

    protected $_disabled = false;

    protected $_type = self::TYPE_TEXT;

    protected $_maxoccurs = 20; // For ONE-TO-MANY and MANY-TO-MANY recursive associations

    /**
     * @var ClassMetadata
     */
    protected $_metadata;

    protected $_var; // @var annotation

    const TYPE_TEXT = 0;

    const TYPE_PARENTSELECT = 1;

    const TYPE_HIDDEN = 2;

    const TYPE_PASSWORD = 3;

    const TYPE_RECURSIVE = 4;

    const TYPE_RECURSIVEID = 5;

    const TYPE_CHECKBOX = 6;

    const TYPE_TEXTAREA = 7;

    public function get_belongingClass() {
        return $this->_belongingClass;
    }

    public function set_belongingClass($_belongingClass) {
        $this->_belongingClass = $_belongingClass;
    }

    public function get_name() {
        return $this->_name;
    }

    public function set_name($_name) {
        if($_name[0] === '_') {
            $_name = substr($_name, 1);
        }
        $this->_name = $_name;
    }

    public function get_label() {
        return $this->_label;
    }

    public function set_label($_label) {
        $this->_label = $_label;
    }

    public function get_required() {
        return $this->_required;
    }

    public function set_required($_required) {
        $this->_required = $_required;
    }

    public function get_disabled() {
        return $this->_disabled;
    }

    public function set_disabled($_disabled) {
        $this->_disabled = $_disabled;
    }

    public function get_type() {
        return $this->_type;
    }

    public function set_type($_type) {
        if(isset($_type)) {
            $_type = trim($_type);
        }
        if(!is_numeric($_type)) {
            if(strtolower($_type) === 'text') {
                $this->_type = self::TYPE_TEXT;
            } else if(strtolower($_type) === 'parentselect') {
                $this->_type = self::TYPE_PARENTSELECT;
            } else if(strtolower($_type) === 'hidden') {
                $this->_type = self::TYPE_HIDDEN;
            } else if(strtolower($_type) === 'password') {
                $this->_type = self::TYPE_PASSWORD;
            } else if(strtolower($_type) === 'checkbox') {
                $this->_type = self::TYPE_CHECKBOX;
            } else if(strtolower($_type) === 'textarea') {
                $this->_type = self::TYPE_TEXTAREA;
            } else if(strtolower($_type) === 'recursive') {
                $this->_type = self::TYPE_RECURSIVE;
            } else if(strtolower($_type) === 'recursiveid') {
                $this->_type = self::TYPE_RECURSIVEID;
            } else {
                throw new Exception('Unknown field type in Dnna_Form_Abstract_FormField.');
            }
        } else {
            $this->_type = $_type;
        }
    }

    public function get_maxoccurs() {
        return $this->_maxoccurs;
    }

    public function set_maxoccurs($_maxoccurs) {
        $this->_maxoccurs = $_maxoccurs;
    }

    public function get_metadata() {
        return $this->_metadata;
    }

    public function set_metadata($_metadata) {
        $this->_metadata = $_metadata;
    }

    public function get_var() {
        return $this->_var;
    }

    public function set_var($_var) {
        $this->_var = $_var;
    }

    public function getTargetClassName() {
        $metadataresult = @$this->get_metadata()->associationMappings['_'.$this->get_name()]['targetEntity'];
        if(isset($metadataresult)) {
            return $metadataresult;
        } else {
            return $this->get_var();
        }
    }

    public function getAssociationType() {
        $metadataclass = 'Doctrine\ORM\Mapping\ClassMetadataInfo';
        $metadataassociation = @$this->get_metadata()->associationMappings['_'.$this->get_name()]['type'];
        if(isset($metadataassociation)) {
            return $metadataassociation;
        } else {
            return null;
        }
    }

    public function getDoctrineType() {
        $metadataresult = @$this->get_metadata()->fieldMappings['_'.$this->get_name()][type];
        if(isset($metadataresult)) {
            return $metadataresult;
        } else {
            return 'notype';
        }
    }
}
?>