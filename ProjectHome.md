
---


This library is a compilation of classes for the Zend Framework and Doctrine 2 created for my projects, which I consider generic enough to be of use to others.


---


**Key components include:**
  * **AutoForm** - A Zend\_Form that is directly tied to a model object and can be controlled via annotations. You can use it to very quickly implement CRUD operations in your application.
  * **ApiRoute and ApiController** - These classes allow you to easily build fully-fledged and feature-rich RESTful APIs for your application. To use it you simply add ApiRoute to your module, extend ApiController. The library takes care of everything else!
  * **GenerateXsd** - An action helper that creates a fully commented XML Schema (XSD) based on a Zend\_Form's fields and validators. This helper can be a powerful tool for generating or enriching your API documentation.
  * **DoctrineStorage** - An implementation of Zend\_Auth\_Storage that reduces the size of the session by storing only the user id and transparently retrieving the user object from the database only when its actually needed.


---


To use it you can add the following to your Bootstrap.php:
```
/**
 * http://code.google.com/p/dnna-zend-lib/
 */
protected function _initDnnaLib() {
    $loader = new Zend_Application_Module_Autoloader(array(
                'basePath' => APPLICATION_PATH.'/../library/Dnna',
                'namespace' => 'Dnna',
            ));
    $loader->addResourceType('Controller', 'controllers/', 'Controller');
    if(class_exists('Doctrine\ORM\EntityManager')) {
        include_once(APPLICATION_PATH . '/../library/Dnna/plugins/PointType.php'); // Load the Point type
        //Assuming the entity manager is in Zend_Registry as entityManager
        $config = Zend_Registry::get('entityManager')->getConfiguration();
        $config->addCustomNumericFunction('DISTANCE', 'Dnna\Doctrine\Types\Distance');
        $config->addCustomNumericFunction('POINT_STR', 'Dnna\Doctrine\Types\PointStr');
    }
    Zend_Controller_Action_HelperBroker::addPath(APPLICATION_PATH.'/../library/Dnna/controllers/helpers',
                                                  'Dnna_Action_Helper');
    Zend_Controller_Action_HelperBroker::addPath(APPLICATION_PATH.'/../library/Dnna/controllers/helpers/Rest',
                                                  'Dnna_Action_Helper_Rest');
    $this->bootstrap('view');
    $this->getResource('view')->addHelperPath(APPLICATION_PATH.'/../library/Dnna/views/helpers', 'Dnna_View_Helper');
}
```
**Note:** If you wish to use the Point datatype, the Doctrine autoloaders need to be initialized in your bootstrap **before** dnna-zend-lib.


---


**Note:** This library is a personal project that I maintain only when my projects require additional functionality. I will make an effort to fix bugs and keep it working with the stable versions of ZF and Doctrine but ultimately you use at your own risk.