<?php
namespace Platform\Api;
/**
 * Exposes a REST API and makes it easy to interact with Datarecord objects using REST.
 * Can also be extended to provide other rest services
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=endpoint_class
 */

use Platform\Filter\Condition;
use Platform\Datarecord\Datarecord;
use Platform\File;
use Platform\Security\Accesstoken;
use Platform\Server\Instance;
use Platform\Utilities\Errorhandler;
use Platform\Utilities\Time;

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
            if (!class_exists($classname)) trigger_error('No such class '.$classname, E_USER_ERROR);
            $shortname = $classname::getBaseClassName();
            $this->classes[$shortname] = $classname;
        }
    }
    
    /**
     * Check if an valid accesstoken is provided either as a bearer token, a cookie or a GET parameter
     * @return boolean True if a valid token was supplied, otherwise the function halts.
     */
    protected function checkSecurity() : bool {
        $authentication_header = static::getHeader('Authorization');
        $token_code = false;
        if ($authentication_header !== false && substr($authentication_header,0,7) == 'Bearer ') $token_code = trim(substr($authentication_header,7));
        if (! $token_code) $token_code = $_COOKIE['access_token'];
        if (! $token_code) $token_code = $_GET['access_token'];
        if (! $token_code) static::respondErrorAndDie (401, 'No access token provided');
        if (!Accesstoken::validateTokenCode($token_code)) static::respondErrorAndDie (401, 'Invalid or expired access token');
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
     * @param string $command The command to execute on the object
     */
    public function customHandlerAfterSecurity(string $object_name, int $object_id, string $method, array $get, string $body, string $command) {
        
    }
    
    /**
     * Override this to implement a custom handler before any security check
     * @param string $object_name Object name for custom handler
     * @param int $object_id Object ID for custom handler
     * @param string $method Method for custom handler. GET POST or DELETE
     * @param string $get Get input for custom handler
     * @param string $body Body input for custom handler
     * @param string $command The command to execute on the object
     */
    public function customHandlerBeforeSecurity(string $object_name, int $object_id, string $method, array $get, string $body, string $command) {
        
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
        foreach ($class::getStructure() as $name => $type) {
            if ($type->isInvisible()) continue;
            $result[$name] = $type->getJSONValue($object->getRawValue($name), $retrieve_binary_data);
        }
        ksort($result);
        $result['__api_generated'] = Time::now()->get();
        return $result;
    }
    
    /**
     * Retrieve the given HTTP header from the incoming request
     * @param string $header Header to retrieve
     * @param bool $case_insensitive Indicate if the match should be case-insensitive for increased compatibility. (Default=false)
     * @return mixed
     */
    public static function getHeader($header, $case_insensitive = true) {
        foreach (apache_request_headers() as $header_title => $header_value) {
            if (!$case_insensitive && $header == $header_title || $case_insensitive && strtolower($header_title) == strtolower($header)) return $header_value;
        }
        return false;
    }
    
    /**
     * Handles API requests
     */
    public function handle() {
        header('Content-Type: application/json');
        // Check for valid request and parse it
        $path = $_SERVER['PATH_INFO'];
        if ($this->preset_instanceid) {
            if (! preg_match('/^\\/([^\\/]+?)(\\/(\\d+))?(\\/([^\\/]+?))?$/i', $path, $m)) static::respondErrorAndDie (404, 'Invalid API path');
            $instance_id = $this->preset_instanceid;
            $object_name = $m[1];
            $object_id = (int)$m[3];
            $command = (string)$m[5];
        } else {
            if (! preg_match('/^(\\/(\\d+))?\\/([^\\/]+?)(\\/(\\d+))?(\\/([^\\/]+?))?$/i', $path, $m)) static::respondErrorAndDie (404, 'Invalid API path');
            $instance_id = $m[2];
            if (! $instance_id) static::respondErrorAndDie (404, 'Invalid instance specified');
            $object_name = $m[3];
            $object_id = (int)$m[5];
            $command = (string)$m[7];
        }
        
        // Check for valid instance and activate it
        if ($instance_id) {
            $instance = new Instance();
            $instance->loadForRead($instance_id);
            if (! $instance->isInDatabase()) static::respondErrorAndDie (404, 'No such instance: '.$instance_id);
            $instance->activate();
        }

        // Get input
        $input = file_get_contents("php://input");
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        
        $this->customHandlerBeforeSecurity($object_name, $object_id, $method, $_GET, $input, $command);
        
        // Check for valid access
        if ($this->is_protected) {
            $this->checkSecurity();
        }
        
        $this->customHandlerAfterSecurity($object_name, $object_id, $method, $_GET, $input, $command);
        
        // Check for valid object
        if (! isset($this->classes[$object_name])) static::respondErrorAndDie (404, 'No such object type: '.$object_name);
        $class = $this->classes[$object_name];
        
        switch ($method) {
            case 'POST':
                if (! $input) static::respondErrorAndDie(400, 'No post data received');
                $json = json_decode($input, true);
                if ($json === null) static::respondErrorAndDie(400, 'Data was received, but wasn\'t valid json');
                // Check if we are looking to update something specific
                $updated_object = new $class();
                if ($object_id) {
                    $updated_object->loadForWrite($object_id);
                    if (! $updated_object->isInDatabase()) static::respondErrorAndDie(404, 'No object of type: '.$object_name.' with id: '.$object_id);
                    if (! $updated_object->canAccess()) static::respondErrorAndDie(403, 'You don\'t have the permission to access this object.');
                    if (! $updated_object->canEdit()) static::respondErrorAndDie(403, 'You don\'t have the permission to edit this object.');
                } else {
                    if (! $class::canCreate()) static::respondErrorAndDie(403, 'You don\'t have the permission to create an object of this type.');
                }
                $is_new_object = ! $updated_object->isInDatabase();
                $result = self::updateObject($class, $updated_object, $json, $is_new_object);
                if ($result === true) {
                    $updated_object->save();
                    $response = self::getApiObject($class, $updated_object);
                    static::respondAndDie($is_new_object ? 201 : 200, json_encode($response));
                } else {
                    static::respondErrorAndDie(400, 'Post data wasn\'t valid: '.implode(', ',$result));
                }
                break;
            case 'GET':
                // Check if we are looking for something specific
                if ($object_id) {
                    $object = new $class();
                    $object->loadForRead($object_id, false);
                    if (! $object->isInDatabase()) static::respondErrorAndDie(404, 'No object of type '.$object_name.' with id: '.$object_id);
                    if (! $object->canAccess()) static::respondErrorAndDie(403, 'You don\'t have the permission to access this object.');
                    $response = self::getApiObject($class, $object, $_GET['include_binary_data'] == 1);
                    static::respondAndDie(200, json_encode($response));
                } else {
                    $filter = $class::getDefaultFilter();
                    $filter->setPerformAccessCheck(true);
                    if ($_GET['query']) {
                        $query = json_decode($_GET['query'], true);
                        if ($query === null) static::respondAndDie (400, 'Invalid query JSON');
                        $additional_conditions = Condition::getConditionFromArray($query);
                        $filter->addCondition($additional_conditions);
                        if (! $filter->isValid()) {
                            static::respondAndDie(400, 'There was problems with your query: '.implode(', ', $filter->getErrors()));
                        }
                    }
                    $collection = $filter->execute();
                    $response = array();
                    foreach($collection->getAll() as $object) {
                        self::memoryCheck('Gathering elements');
                        $response[] = self::getApiObject($class, $object, $_GET['include_binary_data'] == 1);
                    }
                    static::respondAndDie(200, json_encode($response));
                }
                break;
            case 'DELETE':
                if (! $object_id) static::respondErrorAndDie (404, 'No object id specified.');
                $object = new $class();
                $object->loadForWrite($object_id, false);
                if (! $object->isInDatabase()) static::respondErrorAndDie(404, 'No object of type '.$object_name.' with id: '.$object_id);
                if (! $object->canAccess()) static::respondErrorAndDie(403, 'You don\'t have the permission to access this object.');
                $result = $object->canDelete();
                if ($result !== true) static::respondErrorAndDie(403, 'You cannot delete object with id: '.$object_id.'. Reason: '.$result);
                $result = $object->delete();
                if ($result) static::respondAndDie (200, json_encode(array('file_deleted' => true)));
                else static::respondErrorAndDie (500, 'Could not delete object');
            default:
                static::respondErrorAndDie(405, 'Cannot handle request method: '.$method);
        }
        static::respondErrorAndDie(500, 'Unspecified error - Ran through!');
    }
    
    /**
     * Send an API error if we doesn't have the specified amount of memory left.
     * @param string $additional_info Additional text to error message
     * @param int $stop_level Memory required in bytes
     */
    public static function memoryCheck($additional_info = '', $stop_level = 1024*1024*10) {
        if ($additional_info) $additional_info = ' ('.$additional_info.')';
        if (! Errorhandler::checkMemory($stop_level/(1024.0*1024.0), false)) static::respondErrorAndDie (500, 'Out of memory trying to execute your request'.$additional_info.' '.memory_get_usage().'/'.Errorhandler::getMemoryLimitInBytes());
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
     * @param array $api_data An array containing the API object
     * @param bool $check_for_required_fields If true, then all required fields must be provided.
     * @return array|bool True or an array of error messages.
     */
    protected static function updateObject(string $class, Datarecord $object, array $api_data, bool $check_for_required_fields = false) {
        $errors = array();
        foreach ($api_data as $field_name => $value) {
            $type = $class::getFieldDefinition($field_name);
            if ($type === null || $type->isInvisible() ) {
                $errors[] = \Platform\Utilities\Translation::translateForUser('Tried to update non-existing field %1', $field_name);
                continue;
            }
            if ($type->isReadonly() || $type->isPrimaryKey()) {
                $errors[] = \Platform\Utilities\Translation::translateForUser('Tried to update read-only field %1', $field_name);
                continue;
            }
            $validation = $type->validateValue($value);
            if ($validation !== true) {
                $errors[] = \Platform\Utilities\Translation::translateForUser('Error updating field %1: %2', $field_name, $validation);
                continue;
            }
            $object->setValue($field_name, $value);
        }
        if ($check_for_required_fields) {
            foreach ($class::getStructure() as $field_name => $type) {
                if ($type->isRequired() && ! $type->isReadonly() && ! $type->isInvisible() && ! isset($api_data[$field_name])) $errors[] = \Platform\Utilities\Translation::translateForUser('Required field %1 is missing', $field_name);
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