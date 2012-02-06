<?php
/**
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
class Dnna_Action_Helper_EmailBase extends Zend_Controller_Action_Helper_Abstract {
    protected $_bootstrap;

    protected $_emailfromname;
    protected $_emailfromaddress;
    protected $_emailtoname;
    protected $_emailtoaddress;
    protected $_smtpHost;
    protected $_smtpPort;
    protected $_smtpSsl;
    protected $_smtpUser;
    protected $_smtpPass;
    protected $_livedocxMimeType;

    protected $_smtpconfig;
    protected $_tr;

    public function __construct($bootstrap = null) {
        if($bootstrap == null) {
            $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        }
        $this->_bootstrap = $bootstrap;
        $config = $bootstrap->getOptions();
        if(isset($config['email'])) {
            $this->_smtpHost = $config['email']['smtp']['host'];
            $this->_smtpPort = $config['email']['smtp']['port'];
            $this->_smtpSsl = $config['email']['smtp']['ssl'];
            $this->_smtpUser = $config['email']['smtp']['user'];
            $this->_smtpPass = $config['email']['smtp']['pass'];
            $this->_emailfromname = $config['email']['fromname'];
            $this->_emailfromaddress = $config['email']['fromaddress'];
        } else {
            throw new Exception('You need to define the email parameters in application.ini before attempting to send an email.');
        }
        if(isset($config['admin'])) {
            $this->_emailtoname = $config['admin']['email']['toname'];
            $this->_emailtoaddress = $config['admin']['email']['toaddress'];
        }
        if(isset($config['livedocx'])) {
        $this->_livedocxMimeType = $config['livedocx']['mimeType'];
        }

        $this->_smtpconfig = array('auth' => 'login',
                'port' => $this->_smtpPort,
                'username' => $this->_smtpUser,
                'password' => $this->_smtpPass);
        if($this->_smtpSsl !== "none" && $this->_smtpSsl !== "null" && $this->_smtpSsl != null) {
            $this->_smtpconfig['ssl'] = $this->_smtpSsl;
        }
        $this->_tr = new Zend_Mail_Transport_Smtp($this->_smtpHost, $this->_smtpconfig);
    }
}
?>