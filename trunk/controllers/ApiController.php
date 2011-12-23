<?php
/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Controller_ApiController extends Zend_Rest_Controller
{
    protected $_allowAnonymous = false;
    protected $_returnhtml = false;

    protected $_classname;
    protected $_idfieldname;
    protected $_rootfieldname;
    protected $_rootfieldnameplural;

    public function init()
    {
        $this->view->request = $this->_request;
        $this->view->response = $this->_response;
        $this->view->format = $this->_request->getParam('format');

        // We remove ALL contexts before adding our own. This makes sure we ONLY use the contexts we supply here.
        $this->_helper->restContextSwitch()
            ->clearContexts();
        if($this->_returnhtml != true) {
            $this->_helper->restContextSwitch()
                ->clearContexts()
                ->addContext(
                    'xml',
                    array('suffix' => 'xml', 'headers' => array('Content-Type' => 'text/xml')))
                ->addContext(
                    'json',
                    array('suffix' => 'json', 'headers' => array('Content-Type' => 'application/json')))
                ->clearActionContexts()
                ->addGlobalContext(array('xml', 'json'))
                ->setDefaultContext('xml')
                ->initContext();
        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    public function preDispatch() {
        // ACL
    }

    public function indexAction() {
        $posts = Zend_Registry::get('entityManager')->getRepository($this->_classname)->findAll();
        $this->_helper->Index($this, $posts, $this->_rootfieldnameplural, array($this->_idfieldname => 'get_'.$this->_idfieldname));
    }

    public function getAction() {
        $object = Zend_Registry::get('entityManager')->getRepository($this->_classname)->find($this->_request->getParam('id'));
        if(!isset($object)) {
            throw new Exception('PostNotFound', 404);
        }
        $this->_helper->Get($this, $object, new Dnna_Form_AutoForm(get_class($object), $this->view), $this->_rootfieldname);
    }

    public function postAction() {
        $form = new Dnna_Form_AutoForm($this->_classname, $this->view);
        $this->_helper->PostOrPut($this, $this->_classname, $form);
    }

    public function putAction() {
        $form = new Dnna_Form_AutoForm($this->_classname, $this->view);
        $form->setRequired(false);
        $this->_helper->PostOrPut($this, $this->_classname, $form, $this->_request->getParam('id'));
    }

    public function deleteAction() {
        $this->_helper->Delete($this, $this->_classname, $this->_request->getParam('id'));
    }

    public function schemaAction() {
        echo $this->_helper->generateXsd($this, new Dnna_Form_AutoForm($this->_classname, $this->view), $this->_rootfieldname);
    }

    protected function utf8_urldecode($str) {
        $str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));
        return html_entity_decode($str,null,'UTF-8');
    }
}
?>