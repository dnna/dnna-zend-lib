<?php
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Γενική κλάση την οποία κληρονομούν τα αντικείμενα του model.
 * @author Dimosthenis Nikoudis <dnna@dnna.gr>
 */
abstract class Dnna_Model_Object {
    protected $__classname;
    protected $__metadata;

    public function __construct(array $options = null) {
        $this->__classname = get_class($this); // Χρησιμοποιείται για την επιλογή του σωστού τύπου στην setOptionsFromStrings
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    public function __set($name, $value) {
        $method = 'set' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid property');
        }
        $this->$method($value);
    }

    public function __get($name) {
        $method = 'get' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid property '.$name);
        }
        return $this->$method();
    }

    public function get__classname() {
        if(!isset($this->__classname)) {
            $classname = get_class($this);
            // Strip namespaces & handle proxies
            $classname = explode('\\', $classname);
            $classname = $classname[count($classname) - 1];
            if(strpos($classname, 'Proxy') !== false) {
                $classname = substr($classname, 0, strpos($classname, 'Proxy'));
            }
            $this->__classname = $classname;
        }
        return $this->__classname;
    }

    public function set__classname($__classname) {
        $this->__classname = $__classname;
    }

    public function setOptions(array $options, $ignoreisvisible = false) {
        if(isset($options['default']) && count($options['default']) > 0) {
            $options = array_merge($options, $options['default']);
            unset($options['default']);
        }
        // Αρχικά περνάμε τα scalar πεδία και καθαρίζουμε τυχόν άδειους πίνακες
        $methods = get_class_methods($this);
        foreach($options as $key => $value) {
            $method = 'set_'.$key;
            $methodget = 'get_'.$key;
            if(is_scalar($value)) {
                // Αν το πεδίο είναι association τότε το αλλάζουμε από scalar σε object
                if(($classname = $this->guessPropertyClass($key)) != null) {
                    try {
                        $value = Zend_Registry::get('entityManager')->getRepository($classname)->find($value);
                    } catch(Exception $e) { /* Δεν είναι Entity του Doctrine */ }
                }
                if(in_array($method, $methods)) {
                    $this->$method($value);
                }
                unset($options[$key]);
            } else if($value != null) {
                if(isset($value['classname'])) {
                    $classname = $value['classname'];
                } else {
                    $classname = $this->guessPropertyClass($key);
                }
                if(is_numeric(key($value))) {
                    // ArrayCollection
                    $subcollection = new ArrayCollection();
                    if(isset($value[0])) {
                        $i = 0;
                    } else {
                        $i = 1;
                    }
                    while(isset($value[$i])) {
                        if($ignoreisvisible == true || (isset($value[$i]['isvisible']) && $value[$i]['isvisible'] === '1')) {
                            if(isset($value[$i]['classname'])) {
                                $classname = $value[$i]['classname'];
                            }
                            $newObject = new $classname();
                            $newObject->setOwner($this);
                            $newObject->setOptions($value[$i]);
                            $subcollection->add($newObject);
                        }
                        $i++;
                    }
                    if(in_array($method, $methods)) {
                        $this->$method($this->modifySubCollection($subcollection, $this->$methodget()));
                    }
                } else if(isset($value['filename'])) {
                    // Αρχείο
                    $methodfilename = $method.'name';
                    if(in_array($methodfilename, $methods)) {
                        if($value['filename'] !== 'null') {
                            $this->$methodfilename($value['filename']);
                        } else {
                            $this->$methodfilename(null);
                        }
                    }
                    if(in_array($method, $methods)) {
                        if($value['filename'] !== "null") {
                            $this->$method($value['contents']);
                        } else {
                            $this->$method(null);
                        }
                    }
                } else {
                    // Απλό υποαντικείμενο
                    if($classname != null) {
                        if(in_array($method, $methods)) {
                            $idfieldvalues = $this->getIdFieldValues($classname, $value);
                            // Έλεγχος ότι δεν είναι "null"
                            if(count($idfieldvalues) <= 0 || (count($idfieldvalues) == 1 && reset($idfieldvalues) === 'null')) {
                                $this->$method(null);
                            } else {
                                $newObject = Dnna_Model_Object::createObjectSafe($classname, $idfieldvalues);
                                $newObjectMethods = get_class_methods(get_class($newObject));
                                if(in_array('setOwner', $newObjectMethods)) { $newObject->setOwner($this); }
                                $this->$method($newObject->setOptions($value));
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Προβλέπει τον τύπο ενός property με βάση το annotation @var και τον
     * επιστρέφει.
     * @param object $object Αντικείμενο στο οποίο ανήκει το property.
     * @param string $property Το όνομα του property.
     * @return string
     */
    private function guessPropertyClass($property) {
        if(!isset($this->__metadata)) {
            $this->__metadata = Zend_Registry::get('entityManager')->getClassMetadata(get_class($this));
        }
        if($property[0] !== '_') {
            $property = '_'.$property;
        }
        $associations = $this->__metadata->associationMappings;
        if(isset($associations[$property])) {
            return $associations[$property]['targetEntity'];
        } else {
            // Try to find it using the @var annotation
            $reflection = new Zend_Reflection_Class(get_class($this));
            if($reflection->hasProperty($property)) {
                $property = $reflection->getProperty($property);
                $docblock = $property->getDocComment();
                if($docblock instanceof Zend_Reflection_Docblock && $docblock->hasTag('var')) {
                    return trim($docblock->getTag('var')->getDescription());
                }
            }
            return null;
        }
    }

    /**
     * Βρίσκει τα id fields μιας κλάσης σύμφωνα με τα annotations του Doctrine.
     * @param string $classname Το όνομα της κλάσης.
     * @return array 
     */
    private function getIdFieldValues($classname, $values) {
        $ids = Array();
        $reflection = new Zend_Reflection_Class($classname);
        foreach($reflection->getProperties() as $curProperty) {
            $docblock = $curProperty->getDocComment();
            if($docblock instanceof Zend_Reflection_Docblock && $docblock->hasTag('Id')) {
                $newKey = substr($curProperty->getName(), 1);
                if(@$values[$newKey] !== null) {
                    $ids[$curProperty->getName()] = $values[$newKey];
                } else if(@$values['default'][$newKey] !== null) {
                    $ids[$curProperty->getName()] = $values['default'][$newKey];
                }
            }
        }
        return $ids;
    }

    private function dateFormat($curValue, $options = array()) {
        if(isset($options['timestamps']) && $options['timestamps'] == true) {
            return $curValue->getTimestamp();
        } else if(isset($options['iso8601']) && $options['iso8601'] == true) {
            return $curValue->format('c');
        } else if(Zend_Registry::isRegistered('performApiConversions')) {
            // Format που θέλει το XSD
            return $curValue->format('c');
        } else {
            return $curValue->__toString();
        }
    }

    /**
     * Συνάρτηση factory που φέρνει ένα αντικείμενο από τη βάση δεδομένων, αν
     * υπάρχει, ή δημιουργεί νέο, αν αυτό δεν υπάρχει.
     * @param string $classname Όνομα της κλάσης.
     * @param string $id Το κλειδί με βάση το οποίο θα γίνει ο έλεγχος για το αν
     * υπάρχει στη βάση ή όχι.
     * @return Dnna_Model_Object
     */
    public static function createObjectSafe($classname, $criteria) {
        if($criteria == null || count($criteria) <= 0) {
            return new $classname;
            //throw new Exception('Η createObject πρέπει να έχει τουλάχιστον ένα κριτήριο.');
        }
        $em = Zend_Registry::get('entityManager');
        $object = $em->getRepository($classname)->findOneBy($criteria);
        if($object != null) {
            return $object;
        } else {
            return new $classname;
        }
    }

    public function getOptions($onlyDbFields = true, $poptions = Array()) {
        $methods = get_class_methods($this);
        $options = Array();
        $defaultvars = array_keys(get_class_vars(get_class($this)));
        foreach($this as $key => $value) {
            if(is_object($value) && @$poptions['ignoreobjects'] == true) { continue; }
            if(!in_array($key, $defaultvars) || $key === '_entityPersister' || $key === '_identifier' || $key === '__isInitialized__') { continue; }
            if(!$onlyDbFields || strpos($key, '__') === false) {
                $method = 'get'.$key;
                if (in_array($method, $methods)) {
                    $options[substr($key, 1)] = $this->$method();
                    if($options[substr($key, 1)] instanceof EDateTime) {
                        $options[substr($key, 1)] = $this->dateFormat($options[substr($key, 1)], $poptions);
                    }
                }
            }
        }
        return $options;
    }

    protected function getOptionsRecursive($object, $visited = Array(), $options = Array()) {
        $result = Array();
        if($object instanceof Dnna_Model_Object) {
            foreach($object->getOptions() as $curProperty => $curValue) {
                if(is_scalar($curValue) || $curValue == null) {
                    $result[$curProperty] = $curValue;
                } else if($curValue instanceof EDateTime) {
                    $result[$curProperty] = $this->dateFormat($curValue, $options);
                } else if(!in_array($curValue, $visited, true)) {
                    array_push($visited, $curValue);
                    $result[$curProperty] = $this->getOptionsRecursive($curValue, $visited, $options);
                }
            }
        } else if($object instanceof Traversable || (is_array($object) && (isset($object['0']) || isset($object['1'])))) {
            $i = 1;
            foreach($object as $curObject) {
                if(!in_array($curObject, $visited, true)) {
                    array_push($visited, $curObject);
                    $result[$i] = $this->getOptionsRecursive($curObject, $visited, $options);
                    $i++;
                }
            }
        } else {
            throw new Exception('Υπήρξε σφάλμα κατά την αναδρομική ανάκτηση των options του αντικειμένου.');
        }
        return $result;
    }

    public function toArray($onlyDbFields = true, $recursive = false, $options = Array()) {
        if($recursive == true) {
            return $this->getOptionsRecursive($this, array($this), $options);
        } else {
            return $this->getOptions($onlyDbFields, $options);
        }
    }

    public function save() {
        $em = Zend_Registry::get('entityManager');
        $em->persist($this);
        $em->flush();
    }

    public function remove() {
        $em = Zend_Registry::get('entityManager');
        $em->remove($this);
        $em->flush();
    }

    /**
     * Επιστρέφει τα πεδία που αρχίζουν με __
     */
    public function getMetaOptions() {
        $methods = get_class_methods($this);
        $options = Array();
        foreach($this->getOptions(false) as $key => $value) {
            if(strpos($key, '__') !== false) {
                $method = 'get'.$key;
                if (in_array($method, $methods)) {
                    $options[substr($key, 2)] = $this->$method();
                }
            }
        }
        return $options;
    }

    public static function exposeMetaOptions() {
        $vars = Array();
        foreach(get_class_vars(get_called_class()) as $curVar => $curValue) {
            if(strpos($curVar, '__') !== false) {
                $vars[substr($curVar, 2)] = $curValue;
            }
        }
        return $vars;
    }

    /**
     * Βρίσκει αναδρομικά όλες τις μεταβλητές ενός αντικειμένου και των
     * υποαντικειμένων του.
     * @param string $where Η διαδρομή του αντικειμένου (δηλαδή που βρίσκεται σε
     * σχέση με το αρχικό αντικείμενο.
     * @param mixed $curObject Το αντικείμενο.
     * @param mixed $variables Βοηθητική μεταβλητή για την αναδρομή.
     * @return Array Οι μεταβλητες που περιέχονται στο αντικείμενο και
     * υποαντικείμενα.
     */
    public function getOptionsAsStrings($where = 'object', $dbfieldsonly = false, $curObject = null, &$variables = Array(), $visited = Array()) {
        if(count($visited) <= 0) { array_push($visited, $this); }
        if($curObject == null) { $curObject = $this->getOptions($dbfieldsonly); }
        foreach($curObject as $key => $value) {
            // Αντικατάσταση των τυχόν αντικειμένων με αντίστοιχο array
            if(is_object($value) && is_subclass_of($value, 'Dnna_Model_Object')) {
                $value = $value->getOptions($dbfieldsonly);
            } else if(is_object($value) && $value instanceof Doctrine\ORM\PersistentCollection) {
                $value = $value->toArray();
            }

            // Κύριος αναδρομικός κώδικας
            if(!in_array($value, $visited, true)) {
                if(is_array($value) && !empty($value)) {
                    array_push($visited, $value);
                    $funcname = __FUNCTION__;
                    $this->$funcname($where.'_'.$key, $dbfieldsonly, $value, $variables, $visited);
                } else {
                    for($i = 0; $i < count($value); $i++) {
                        //echo $where.'_'.$key.' = '.$value."<BR>\n";
                        $variables[$where.'_'.$key] = $value;
                    }
                }
            }
            // Τέλος κύριου αναδρομικού κώδικα
        }
        return $variables;
    }

    public function modifySubCollection($newcollection, &$oldcollection) {
        if($oldcollection instanceof Doctrine\ORM\PersistentCollection || !isset($oldcollection)) {
            if(!isset($oldcollection)) { $oldcollection = new ArrayCollection(); } // Αν δεν υπάρχει παλιό collection τότε αρχικοποιούμε ένα άδειο
            $em = Zend_Registry::get('entityManager');
            if(($newcollection instanceof ArrayCollection || $newcollection instanceof Doctrine\ORM\PersistentCollection) && $newcollection->count() > 0 && $newcollection->first() instanceof Dnna_Model_SubObject) {
                // 1. Φτιάχνουμε έναν πίνακα με τα recordid που περιέχονται και στα 2 collections (δηλαδή τα κοινά στοιχεία)
                $common = array();
                foreach($newcollection as $newItem) {
                    foreach($oldcollection as $oldItem) {
                        if($newItem->get_recordid() == $oldItem->get_recordid()) {
                            array_push($common, $newItem->get_recordid());
                            // 2. Κάνουμε update το αντικείμενο του oldcollection αν δεν συμπίπτει με αυτό του newcollection
                            if($newItem !== $oldItem) {
                                // Περιορίζουμε κατά το δυνατό την αύξηση του id στη βάση δεδομένων κάνοντας merge όταν μπορούμε
                                $containsnonscalar = false;
                                foreach($oldItem as $curOldProperty) {
                                    if($curOldProperty instanceof ArrayCollection || $curOldProperty instanceof Doctrine\ORM\PersistentCollection) {
                                        $containsnonscalar = true;
                                        break;
                                    }
                                }
                                if($containsnonscalar) {
                                    $oldcollection->removeElement($oldItem);
                                    $oldcollection->add($newItem);
                                } else {
                                    $em->merge($newItem);
                                }
                            }
                            break;
                        }
                    }
                }
                // 3. Βρίσκουμε ποια στοιχεία του oldcollection δεν βρίσκονται στον πίνακα και τα διαγράφουμε από τη βάση
                foreach($oldcollection as &$oldItem) {
                    if(!in_array($oldItem->get_recordid(), $common)) {
                        $oldcollection->removeElement($oldItem);
                    }
                }
                // 4. Βρίσκουμε ποια στοιχεία του newcollection δεν βρίσκονται στον πίνακα και τα προσθέτουμε
                foreach($newcollection as $newItem) {
                    if(!in_array($newItem->get_recordid(), $common)) {
                        $em->persist($newItem);
                        $oldcollection->add($newItem);
                    }
                }
                //$em->flush();
                return $oldcollection;
            } else {
                foreach($oldcollection as &$curItem) {
                    $oldcollection->removeElement($curItem);
                }
                //$em->flush();
                return $newcollection;
            }
        }
    }
}
?>