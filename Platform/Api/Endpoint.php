<?php
namespace Platform\Api;

use Platform\Datarecord;
use Platform\Condition;
use Platform\Server\Instance;
use Platform\Security\Accesstoken;
use Platform\Utilities\Time;
use Platform\File;

class Endpoint {
    
    /**
     * Contained classes 
     * @var array
     */
    private $classes = array();
    
    /**
     * See if we should require an access token to access the API.
     * @var bool
     */
    protected $is_protected = true;
    
    /**
     * Used to preset the instance id
     * @var mixed
     */
    private $preset_instanceid = false;
    
    /**
     * Token code used for authentication
     * @var string
     */
    protected $token_code = null;
    
    /**
     * Construct an API endpoint
     * @param array $classnames Classes to include in this endpoint
     */
    public function __construct(array $classnames = []) {
        foreach ($classnames as $classname) {
            $shortname = $classname::getClassName();
            $this->classes[$shortname] = $classname;
        }
    }
    
    /**
     * Check if an valid accesstoken is provided either as a cookie or a GET parameter
     * @return boolean True if a valid token was supplied, otherwise the function halts.
     */
    protected function checkSecurity() : bool {
        $token_code = $_COOKIE['access_token'];
        if (! $token_code) $token_code = $_GET['access_token'];
        if (! $token_code) self::respondErrorAndDie (401, 'No access token provided');
        if (!Accesstoken::validateTokenCode($token_code)) self::respondErrorAndDie (401, 'Invalid or expired access token');
        $this->token_code = $token_code;
        return true;
    }

    /**
     * Override this to implement a custom handler after any security check
     * @param string $object_name Object name for custom handler
     * @param int $object_id Object ID for custom handler
     * @param string $method Method for custom handler. GET POST or DELETE
     * @param string $get Get input for custom handler
     * @param string $body Body input for custom handler
     */
    public function customHandlerAfterSecurity(string $object_name, int $object_id, string $method, array $get, string $body) {
        
    }
    
    /**
     * Override this to implement a custom handler before any security check
     * @param string $object_name Object name for custom handler
     * @param int $object_id Object ID for custom handler
     * @param string $method Method for custom handler. GET POST or DELETE
     * @param string $get Get input for custom handler
     * @param string $body Body input for custom handler
     */
    public function customHandlerBeforeSecurity(string $object_name, int $object_id, string $method, array $get, string $body) {
        
    }

    /**
     * Convert a Datarecord object to an API object
     * @param String $class A Datarecord class name
     * @param Datarecord $object An object of the given type
     * @param Boolean $retrieve_binary_data If true, then pass file and image fields as binary data
     * @return array An API object expressed as an array
     */
    protected static function getApiObject(string $class, Datarecord $object, bool $retrieve_binary_data = false) {
        $result = array();
        foreach ($class::getStructure() as $key => $definition) {
            if ($definition['invisible'] && $definition['fieldtype'] != Datarecord::FIELDTYPE_KEY) continue;
            switch ($definition['fieldtype']) {
                case Datarecord::FIELDTYPE_PASSWORD:
                    continue;
                case Datarecord::FIELDTYPE_KEY:
                    $result[$key] = (int)$object->getRawValue($key);
                    break;
                case Datarecord::FIELDTYPE_DATE:
                case Datarecord::FIELDTYPE_DATETIME:
                    $result[$key] = $object->getRawValue($key)->get();
                    break;
                case Datarecord::FIELDTYPE_REFERENCE_HYPER:
                    $remote_object = $object->getRawValue($key.'_foreign_class');
                    if (!class_exists($remote_object)) $result[$key] = null;
                    else {
                        $short_class = $remote_object::getClassName();
                        $result[$key] = array(
                            'object' => $short_class,
                            'id' => $object->getRawValue($key.'_reference')
                        );
                    }
                    break;
                case Datarecord::FIELDTYPE_IMAGE:
                case Datarecord::FIELDTYPE_FILE:
                    if ($retrieve_binary_data) {
                        $file = new File();
                        $id = $object->getRawValue($key);
                        if ($id) $file->loadForRead($id);
                        if ($file->isInDatabase() && $file->canAccess()) {
                            $result[$key] = array(
                                'filename' => $file->filename,
                                'mimetype' => $file->mimetype,
                                'binary' => base64_encode($file->getFileContent())
                            );
                        } else {
                            $result[$key] = null;
                        }
                    } else {
                        $result[$key] = $object->getRawValue($key);
                    }
                    break;
                default:
                    $result[$key] = $object->getRawValue($key);
            }
        }
        ksort($result);
        $result['__api_generated'] = Time::now()->get();
        return $result;
    }
    
    /**
     * Handles API requests
     */
    public function handle() {
        // Check for valid request and parse it
        $path = $_SERVER['PATH_INFO'];
        if ($this->preset_instanceid) {
            if (! preg_match('/^\\/([^\\/]+?)(\\/(\\d+))?$/i', $path, $m)) self::respondErrorAndDie (404, 'Invalid API path');
            $instance_id = $this->preset_instanceid;
            $object_name = $m[1];
            $object_id = (int)$m[3];
        } else {
            if (! preg_match('/^(\\/(\\d+))?\\/([^\\/]+?)(\\/(\\d+))?$/i', $path, $m)) self::respondErrorAndDie (404, 'Invalid API path');
            $instance_id = $m[2];
            if (! $instance_id) self::respondErrorAndDie (404, 'Invalid instance specified');
            $object_name = $m[3];
            $object_id = (int)$m[5];
        }
        
        // Check for valid instance and activate it
        if ($instance_id) {
            $instance = new Instance();
            $instance->loadForRead($instance_id);
            if (! $instance->isInDatabase()) self::respondErrorAndDie (404, 'No such instance: '.$instance_id);
            $instance->activate();
        }

        // Get input
        $input = file_get_contents("php://input");
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        
        $this->customHandlerBeforeSecurity($object_name, $object_id, $method, $_GET, $input);
        
        // Check for valid access
        if ($this->is_protected) {
            $this->checkSecurity();
        }
        
        $this->customHandlerAfterSecurity($object_name, $object_id, $method, $_GET, $input);
        
        // Check for valid object
        if (! isset($this->classes[$object_name])) self::respondErrorAndDie (404, 'No such object type: '.$object_name);
        $class = $this->classes[$object_name];
        
        switch ($method) {
            case 'POST':
                if (! $input) self::respondErrorAndDie(400, 'No post data received');
                $json = json_decode($input, true);
                if ($json === null) self::respondErrorAndDie(400, 'Data was received, but wasn\'t valid json');
                // Check if we are looking to update something specific
                $updated_object = new $class();
                if ($object_id) {
                    $updated_object->loadForWrite($object_id);
                    if (! $updated_object->isInDatabase()) self::respondErrorAndDie(404, 'No object of type: '.$object_name.' with id: '.$object_id);
                    if (! $updated_object->canAccess()) self::respondErrorAndDie(403, 'You don\'t have the permission to access this object.');
                    if (! $updated_object->canEdit()) self::respondErrorAndDie(403, 'You don\'t have the permission to edit this object.');
                } else {
                    if (! $class::canCreate()) self::respondErrorAndDie(403, 'You don\'t have the permission to create an object of this type.');
                }
                $is_new_object = ! $updated_object->isInDatabase();
                $result = self::updateObject($class, $updated_object, $json, $is_new_object);
                if ($result === true) {
                    $updated_object->save();
                    $response = self::getApiObject($class, $updated_object);
                    self::respondAndDie($is_new_object ? 201 : 200, json_encode($response));
                } else {
                    self::respondErrorAndDie(400, 'Post data wasn\'t valid: '.implode(', ',$result));
                }
                break;
            case 'GET':
                // Check if we are looking for something specific
                if ($object_id) {
                    $object = new $class();
                    $object->loadForRead($object_id);
                    if (! $object->isInDatabase()) self::respondErrorAndDie(404, 'No object of type: '.$object_name.' with id: '.$object_id);
                    if (! $object->canAccess()) self::respondErrorAndDie(403, 'You don\'t have the permission to access this object.');
                    $response = self::getApiObject($class, $object, $_GET['include_binary_data'] == 1);
                    self::respondAndDie(200, json_encode($response));
                } else {
                    $filter = $class::getDefaultFilter();
                    $filter->setPerformAccessCheck(true);
                    if ($_GET['query']) {
                        $query = json_decode($_GET['query'], true);
                        if ($query === null) self::respondAndDie (400, 'Invalid query JSON');
                        $additional_conditions = Condition::getConditionFromArray($query);
                        $filter->addCondition($additional_conditions);
                        if (! $filter->isValid()) {
                            self::respondAndDie(400, 'There was problems with your query: '.implode(', ', $filter->getErrors()));
                        }
                    }
                    $collection = $filter->execute();
                    $response = array();
                    foreach($collection->getAll() as $object) {
                        $response[] = self::getApiObject($class, $object, $_GET['include_binary_data'] == 1);
                    }
                    self::respondAndDie(200, json_encode($response));
                }
                break;
            case 'DELETE':
                if (! $object_id) self::respondErrorAndDie (404, 'No object id specified.');
                $object = new $class();
                $object->loadForWrite($object_id);
                if (! $object->canAccess()) self::respondErrorAndDie(403, 'You don\'t have the permission to access this object.');
                $result = $object->canDelete();
                if ($result !== true) self::respondErrorAndDie(403, 'You cannot delete object with id: '.$object_id.'. Reason: '.$result);
                $result = $object->delete();
                if ($result) self::respondAndDie (200, json_encode(array('file_deleted' => true)));
                else self::respondErrorAndDie (500, 'Could not delete file');
            default:
                self::respondErrorAndDie(405, 'Cannot handle request method: '.$method);
        }
        self::respondErrorAndDie(500, 'Unspecified error - Ran through!');
    }
    
    /**
     * Sends a HTTP response and halts execution
     * @param int $code HTTP response code
     * @param string $message Content to transmit
     */
    protected static function respondAndDie(int $code, string $message) {
        http_response_code($code);
        echo $message;
        exit;
    }

    /**
     * Sends a HTTP response assuming it to be an error. And halts execution
     * @param int $code HTTP response code
     * @param string $error_message Content to transmit
     */
    protected static function respondErrorAndDie(int $code, string $error_message) {
        http_response_code($code);
        echo json_encode(array('error' => true, 'http_response_code' => $code, 'message' => $error_message));
        exit;
    }
    
    /**
     * Preselects an instance id
     * @param int $instance_id
     */
    public function setInstanceID(int $instance_id) {
        $this->preset_instanceid = $instance_id;
    }
    
    /**
     * Set if this endpoint should be protected by an access token requirement
     * @param bool $is_protected True if protection should be enabled.
     */
    public function setProtection(bool $is_protected) {
        $this->is_protected = $is_protected;
    }
    
    /**
     * Update a Datarecord object from an API object
     * @param string $class The name of the Datarecord class we are operating on
     * @param Datarecord $object The object of this class
     * @param array $newdata An array containing the API object
     * @param bool $check_for_required_fields If true, then all required fields must be provided.
     * @return array|bool True or an array of error messages.
     */
    protected static function updateObject(string $class, Datarecord $object, array $newdata, bool $check_for_required_fields = false) {
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
                    if (! isset($structure[$key]['enumeration'][$value])) {
                        $errors[] = $value.' isn\'t a valid value for field '.$key;
                        continue;
                    }
                    $object->setValue($key, $value);
                    break;
                case Datarecord::FIELDTYPE_IMAGE:
                case Datarecord::FIELDTYPE_FILE:
                    if (is_array($value)) {
                        if ($value['action'] == 'remove') {
                            // Build compatible input array
                            $value = array(
                                'status' => 'removed'
                            );
                        } else {
                            // Check for all components
                            $required = array('filename', 'mimetype', 'binary', 'action');
                            foreach ($required as $r) {
                                if (! isset($value[$r])) {
                                    $errors[] = 'Required field '.$r.' missing when uploading file in field '.$key;
                                    continue 2;
                                }
                            }
                            
                            // Check for valid action
                            if ($value['action'] != 'add') {
                                $errors[] = 'action should be either "add" or "remove" when uploading file in field '.$key;
                                continue;
                            }
                            
                            // Check for safe base64 decode
                            $binary_data = base64_decode($value['binary'], true);
                            if ($binary_data === false) {
                                $errors[] = 'File content couldn\'t be BASE64-decoded in field '.$key;
                                continue;
                            }

                            // Check for valid mime-type
                            if ($structure[$key]['fieldtype'] == Datarecord::FIELDTYPE_IMAGE && strpos(strtolower($value['mimetype']),'image') === false) {
                                $errors[] = 'Cannot add non-image mimetype to an image field in field '.$key;
                                continue;
                            }

                            // Store binary content in temporary file
                            $temp_file_name = \Platform\File::getTempFilename();
                            $fh = fopen($temp_file_name, 'w');
                            if (! $fh) $this->respondErrorAndDie(500, 'Error writing temporary file handling field '.$key);
                            fwrite($fh, $binary_data);
                            fclose($fh);
                            // Build compatible input array
                            $value = array(
                                'original_file' => $value['filename'],
                                'mimetype' => $value['mimetype'],
                                'temp_file' => substr($temp_file_name,strrpos($temp_file_name,'/')+1),
                                'status' => 'changed'
                            );
                        }
                    } else {
                        $file = new File();
                        $file->loadForRead((int)$value);
                        if (! $file->isInDatabase()) {
                            $errors[] = $value.' isn\'t a valid reference in field '.$key;
                            continue;
                        }
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