<?php
namespace Platform;

class Api {
    
    /**
     * Contained classes 
     * @var array
     */
    private $classes = array();
    
    /**
     * Construct an API endpoint
     * @param array $classnames Classes to include in this endpoint
     */
    public function __construct($classnames = array()) {
        if (! is_array($classnames)) trigger_error('Classes must be an array', E_USER_ERROR);
        foreach ($classnames as $classname) {
            $shortname = $classname::getClassName();
            $this->classes[$shortname] = $classname;
        }
    }

    /**
     * Convert a Datarecord object to an API object
     * @param String $class A Datarecord class name
     * @param Datarecord $object An object of the given type
     * @return array An API object expressed as an array
     */
    private static function getApiObject($class, $object) {
        $result = array();
        foreach ($class::getStructure() as $key => $definition) {
            if ($definition['invisible'] &&  $definition['fieldtype'] != Datarecord::FIELDTYPE_KEY) continue;
            switch ($definition['fieldtype']) {
                case Datarecord::FIELDTYPE_PASSWORD:
                    continue;
                case Datarecord::FIELDTYPE_KEY:
                    $result[$key] = (int)$object->getRawValue($key);
                    break;
                case Datarecord::FIELDTYPE_DATE:
                case Datarecord::FIELDTYPE_DATETIME:
                    $result[$key] = $object->getRawValue($key)->getTime();
                    break;
                default:
                    $result[$key] = $object->getRawValue($key);
            }
        }
        ksort($result);
        $result['__api_generated'] = Time::now()->getTime();
        return $result;
    }
    
    /**
     * Handles API requests
     */
    public function handle() {
        // Check for valid request and parse it
        $path = $_SERVER['PATH_INFO'];
        if (! preg_match('/^\\/(\\d+)\\/([^\\/]+?)(\\/(\\d+))?$/i', $path, $m)) self::respondAndDie (404, 'Invalid API path');
        $instance_id = $m[1];
        $object_name = $m[2];
        $object_id = $m[4];
        
        // Check for valid instance and activate it
        $instance = new Instance();
        $instance->loadForRead($instance_id);
        if (! $instance->isInDatabase()) self::respondAndDie (404, 'No such instance: '.$instance_id);
        $instance->activate();
        
        // Check for valid access
        $token_code = $_COOKIE['access_token'];
        if (! $token_code) $token_code = $_GET['access_token'];
        if (! $token_code) self::respondAndDie (401, 'No access token provided');
        if (!Accesstoken::validateTokenCode($token_code)) self::respondAndDie (401, 'Invalid or expired access token');
        
        // Check for valid object
        if (! isset($this->classes[$object_name])) self::respondAndDie (404, 'No such object type: '.$object_name);
        $class = $this->classes[$object_name];
        
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $input = file_get_contents("php://input");
                if (! $input) self::respondAndDie(400, 'No post data received');
                $json = json_decode($input, true);
                if ($json === null) self::respondAndDie(400, 'Data was received, but wasn\'t valid json');
                // Check if we are looking to update something specific
                $updated_object = new $class();
                if ($object_id) {
                    $updated_object->loadForWrite($object_id);
                    if (! $updated_object->isInDatabase()) self::respondAndDie(404, 'No object of type: '.$object_name.' with id: '.$object_id);
                    if (! $updated_object->canAccess()) self::respondAndDie(403, 'You don\'t have the permission to access this object.');
                    if (! $updated_object->canEdit()) self::respondAndDie(403, 'You don\'t have the permission to edit this object.');
                } else {
                    if (! $class::canCreate()) self::respondAndDie(403, 'You don\'t have the permission to create an object of this type.');
                }
                $is_new_object = ! $updated_object->isInDatabase();
                $result = self::updateObject($class, $updated_object, $json, $is_new_object);
                if ($result === true) {
                    $updated_object->save();
                    $response = self::getApiObject($class, $updated_object);
                    self::respondAndDie($is_new_object ? 201 : 200, json_encode($response));
                } else {
                    self::respondAndDie(400, 'Post data wasn\'t valid: '.implode(', ',$result));
                }
                break;
            case 'GET':
                // Check if we are looking for something specific
                if ($object_id) {
                    $object = new $class();
                    $object->loadForRead($object_id);
                    if (! $object->isInDatabase()) self::respondAndDie(404, 'No object of type: '.$object_name.' with id: '.$object_id);
                    if (! $object->canAccess()) self::respondAndDie(403, 'You don\'t have the permission to access this object.');
                    $response = self::getApiObject($class, $object);
                    self::respondAndDie(200, json_encode($response));
                } else {
                    $filter = new Filter($class);
                    $collection = $filter->execute();
                    $response = array();
                    foreach($collection->getAll() as $object) {
                        $response[] = self::getApiObject($class, $object);
                    }
                    self::respondAndDie(200, json_encode($response));
                }
                
            default:
                self::respondAndDie(405, 'Cannot handle request method: '.$_SERVER['REQUEST_METHOD']);
        }
        self::respondAndDie(500, 'Unspecified error - Ran through!');
    }
    
    /**
     * Sends a HTTP response
     * @param int $code HTTP response code
     * @param string $message Content to transmit
     */
    private static function respondAndDie($code, $message) {
        http_response_code($code);
        echo $message;
        exit;
    }
    
    /**
     * Update a Datarecord object from an API object
     * @param string $class The name of the Datarecord class we are operating on
     * @param Datarecord $object The object of this class
     * @param array $newdata An array containing the API object
     * @param boolean $check_for_required_fields If true, then all required fields must be provided.
     * @return array|boolean True or an array of error messages.
     */
    private static function updateObject($class, $object, $newdata, $check_for_required_fields = false) {
        $structure = $class::getStructure();
        $errors = array();
        foreach ($newdata as $key => $value) {
            if (! isset($structure[$key]) || $structure[$key]['invisible'] && $structure[$key]['fieldtype'] != Datarecord::FIELDTYPE_KEY) {
                $errors[] = 'Tried to update non-existing field '.$key;
                continue;
            }
            if ($structure[$key]['readonly'] || $structure[$key]['fieldtype'] == Datarecord::FIELDTYPE_KEY) {
                $errors[] = 'Tried to update read-only field '.$key;
                continue;
            }
            switch ($structure[$key]['fieldtype']) {
                case Datarecord::FIELDTYPE_INTEGER:
                    if (! is_int($value)) {
                        $errors[] = 'Tried to set integer field '.$key.' to non-integer value '.$value;
                        continue;
                    }
                    $object->setValue($key, $value);
                    break;
                case Datarecord::FIELDTYPE_FLOAT:
                    if (!is_numeric($value)) {
                        $errors[] = 'Tried to set float field '.$key.' to non-float value '.$value;
                        continue;
                    }
                    $object->setValue($key, $value);
                    break;
                case Datarecord::FIELDTYPE_BOOLEAN:
                    if (!is_bool($value)) {
                        $errors[] = 'Tried to set boolean field '.$key.' to non-boolean value '.$value;
                        continue;
                    }
                    $object->setValue($key, $value);
                    break;
                case Datarecord::FIELDTYPE_DATETIME:
                    $stamp = new Time($value);
                    if ($stamp->getTimestamp() <= 0) {
                        $errors[] = 'Could not parse '.$value.' into field '.$key.' as a valid timestamp';
                        continue;
                    }
                    $object->setValue($key, $stamp);
                    break;
                case Datarecord::FIELDTYPE_ARRAY:
                    if (!is_array($value)) {
                        $errors[] = 'The value for array field '.$key.' wasn\'t an array';
                        continue;
                    }
                    $object->setValue($key, $value);
                    break;
                case Datarecord::FIELDTYPE_ENUMERATION:
                    if (isset($structure[$key]['enumeration'][$value])) {
                        $errors[] = $value.' isn\'t a valid value for field '.$key;
                        continue;
                    }
                    $object->setValue($key, $value);
                    break;
                case Datarecord::FIELDTYPE_FILE:
                    $file = new File();
                    $file->loadForRead($value);
                    if (! $file->isInDatabase()) {
                        $errors[] = $value.' isn\'t a valid reference in field '.$key;
                        continue;
                    }
                    $object->setValue($key, $value);
                    break;
                case Datarecord::FIELDTYPE_REFERENCE_SINGLE:
                    if ($value > 0) {
                        $foreign_class = $structure[$key]['foreign_class'];
                        $foreign_object = new $foreign_class();
                        $foreign_object->loadForRead($value);
                        if (! $foreign_object->isInDatabase()) {
                            $errors[] = $value.' isn\'t a valid reference in field '.$key;
                            continue;
                        }
                    }
                    $object->setValue($key, $value);
                    break;
                case Datarecord::FIELDTYPE_REFERENCE_MULTIPLE:
                    if (! is_array($value)) $value = array($value);
                    $foreign_class = $structure[$key]['foreign_class'];
                    $foreign_object = new $foreign_class();
                    $values = array();
                    foreach ($value as $v) {
                        if ($v == 0) continue;
                        $foreign_object->loadForRead($v);
                        if (! $foreign_object->isInDatabase()) {
                            $errors[] = $v.' isn\'t a valid reference in field '.$key;
                            continue;
                        }
                        $values[] = $v;
                    }
                    $object->setValue($key, $value);
                    break;
                case Datarecord::FIELDTYPE_REFERENCE_HYPER:
                    $errors[] = 'Field '.$key.' is not supported for writing in the API (yet!)';
                    continue;
                default:
                    $object->setValue($key, $value);
            }
        }
        if ($check_for_required_fields) {
            foreach ($structure as $key => $definition) {
                if ($definition['required'] && ! $definition['readonly'] && ! $definition['invisible'] && ! isset($newdata[$key])) $errors[] = 'Required field '.$key.' is missing.';
            }
        }
        // Do further validation
        $validation = $object->validateObject();
        if (is_array($validation)) {
            foreach ($validation as $error) $errors[] = $error;
        }
        
        if (count($errors)) return $errors;
        return true;
    }
}