<?php
class Dnna_Plugin_DoctrineStorage implements Zend_Auth_Storage_Interface
{
    /**
     * Default session namespace
     */
    const NAMESPACE_DEFAULT = 'Zend_Auth';
    /**
     * Session namespace
     *
     * @var mixed
     */
    protected $_namespace;
    
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $_em;

    protected $_entityclass;
    
    protected $_session;
    
    public function __construct($entityclass, $namespace = self::NAMESPACE_DEFAULT) {
        $this->_entityclass = $entityclass;
        $this->_em = Zend_Registry::get('entityManager');
        $this->_namespace = $namespace;
        $this->_session = new Zend_Session_Namespace($this->_namespace);
    }
    
    /**
     * Returns true if and only if storage is empty
     *
     * @throws Zend_Auth_Storage_Exception If it is impossible to
     *                                     determine whether storage
     *                                     is empty
     * @return boolean
     */
    public function isEmpty()
    {
        if(!isset($this->_session->id) || $this->_em->getRepository($this->_entityclass)->find($this->_session->id) == null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the contents of storage
     *
     * Behavior is undefined when storage is empty.
     *
     * @throws Zend_Auth_Storage_Exception If reading contents from
     *                                     storage is impossible
     * @return mixed
     */
    public function read()
    {
        if(isset($this->_session->id)) {
            return $this->_em->getRepository($this->_entityclass)->find($this->_session->id);
        } else {
            return null;
        }
    }

    /**
     * Writes $contents to storage
     *
     * @param  mixed $object
     * @throws Zend_Auth_Storage_Exception If writing $contents to
     *                                     storage is impossible
     * @return void
     */
    public function write($object)
    {
        if(!is_object($object) || !($object instanceof $this->_entityclass)) {
            throw new Exception('Input is not an object or its an object of the wrong class');
        }
        $metadata = $this->_em->getClassMetadata($this->_entityclass);
        $ids = array();
        foreach($metadata->identifier as $curId) {
            $method = 'get'.$curId;
            $ids[$curId] = $object->$method();
        }
        $this->_session->id = $ids;
    }

    /**
     * Clears contents from storage
     *
     * @throws Zend_Auth_Storage_Exception If clearing contents from
     *                                     storage is impossible
     * @return void
     */
    public function clear()
    {
        unset($this->_session->id);
    }
}
?>