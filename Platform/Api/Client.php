<?php
namespace Platform\Api;
/**
 * Provides an API Client against another REST API also exposed by Platform
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=client_class
 */

use Platform\Filter\Condition;

class Client {
    
    /**
     * The endpoint URL
     * @var boolean|string
     */
    protected $endpoint = false;
    
    /**
     * The token code to use for validation
     * @var string
     */
    protected $token_code = '';
    
    /**
     * Used for custom headers
     * @var array
     */
    protected $custom_headers = [];
    
    /**
     * Indicate if we should log communication
     * @var type
     */
    protected $log = false;
    
    /**
     * Constructs a new API Client
     * @param string $endpoint The endpoint to connect to
     */
    public function __construct(string $endpoint) {
        $this->endpoint = $endpoint;
    }
    
    /**
     * Add a custom header which will be appended to each API call
     * @param string $custom_header
     */
    public function addCustomHeader(string $custom_header) {
        $this->custom_headers[] = $custom_header;
    }
    
    /**
     * Enable logging of this client
     */
    public function enableLog() {
        $this->log = true;
    }
    
    /**
     * Perform a filtered GET on a given object
     * @param string $object The object to filter
     * @param \Platform\Data\Condition\Condition $condition The condition to use
     * @return array Hashed by code=http code  message=http message  headers=array of all headers
     * body=body output  json=json decoded body output
     */
    public function filter(string $object, Condition $condition) : array {
        return $this->query($object, 'GET', 0, array('query' => $condition->getAsJSON()));
    }
    
    /**
     * Log communication
     * @param string $event Communication event
     * @param string $endpoint Endpoint used
     * @param string $data Data
     */
    public function log($event, $endpoint, $data) {
        // Delve log name from endpoint
        if (preg_match('/http(s)?\\:\\/\\/([^\\/]*)/i', $endpoint, $match)) $log_file = $match[2];
        else $log_file = 'unknown';
        $log = new \Platform\Utilities\Log('api_client_'.$log_file, ['6r', '10r', '10']);
        $log->log(\Platform\Security\Accesstoken::getCurrentUserID(), $event, $endpoint, $data);
    }
    
    /**
     * Parse a RAW HTTP response
     * @param string $http_output HTTP response
     * @return array Hashed by code=http code  message=http message  headers=array of all headers
     * body=body output  json=json decoded body output
     */
    public static function parseResponse(string $http_output) : array {
        $lines = explode("\n", $http_output);
        $parsingheader = true; $canswitch = false;
        $result = ['body' => ''];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line == '') {
                if ($parsingheader && $canswitch) $parsingheader = false;
                continue;
            }
            if ($parsingheader) {
                if (preg_match('/^HTTP\\/\\S* (\\d+)(.*)$/', $line, $match)) {
                    $result['code'] = $match[1];
                    $result['message'] = trim($match[2]);
                    continue;
                }
                if (strpos($line, ':') !== false) {
                    $canswitch = true;
                    $keyword = substr($line,0,strpos($line,':'));
                    $value = substr($line, strpos($line,':')+1);
                    $result['headers'][$keyword] = $value;
                }
            } else {
                $result['body'] .= $line;
            }
        }
        $result['json'] = json_decode($result['body'], true);
        return $result;
    }
    
    /**
     * Make a request against a common REST API
     * @param string $path The path to request against
     * @param string $method The request method
     * @param array $parameters Parameters for the request
     * @return array Hashed by code=http code  message=http message  headers=array of all headers
     * body=body output  json=json decoded body output
     */
    public function request(string $path, string $method = '', array $parameters = []) : array {
        return $this->query($path, $method, 0, $parameters);
    }
    
    /**
     * Query a REST API from Platform
     * @param string $object The object to query
     * @param string $method The method to use
     * @param int $id The object ID to query (if any)
     * @param array $parameters Parameters for GET or POST as hashed array
     * @return array Hashed by code=http code  message=http message  headers=array of all headers
     * body=body output  json=json decoded body output
     */
    public function query(string $object, string $method = '', int $id = 0, array $parameters = []) : array {
        if ($method == '') $method = 'GET';
        $endpoint = $this->endpoint;
        if (substr($endpoint,-1,1) != '/') $endpoint .= '/';
        $endpoint .= $object;
        if ($id) $endpoint .= '/'.$id;
        
        $parameters_as_body = in_array(strtolower($method), ['post', 'put', 'getwithbody', 'patch']);
        
        if (strtolower($method) == 'getwithbody') $method = 'GET';
        
        if (! $parameters_as_body && count($parameters)) {
            // Build querystring
            $endpoint .= '?'.http_build_query($parameters);
        }
        
        // Prepare CURL
        $curl = curl_init($endpoint);
        $options = array(
            'Content-Type: application/json',
            'Accept: application/json'
        );
        // Add custom headers
        if (count($this->custom_headers)) $options = array_merge($options, $this->custom_headers);
        
        if ($this->token_code) {
            $options[] = 'Authorization: Bearer '.$this->token_code;
        }
        
        if ($this->log) $this->log('Request', strtoupper($method).' '.$endpoint, json_encode($parameters));

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $options);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_HEADER, 1);

        if ($parameters_as_body) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters));
        }

        $curlResponse = curl_exec($curl);
        if ($curlResponse === false) {
            if ($this->log) $this->log('CURL error', $endpoint, curl_error($curl));
            return ['code' => '000', 'error' => true, 'message' => curl_error($curl)];
        }
        curl_close($curl);
        if ($this->log) $this->log('Response', $endpoint, $curlResponse);
        
        return $this->parseResponse($curlResponse);
    }
    
    /**
     * Set the access token to use for logging in with the API
     * @param string $token_code
     */
    public function setAccessToken(string $token_code) {
        $this->token_code = $token_code;
    }
}