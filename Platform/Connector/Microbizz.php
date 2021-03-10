<?php
namespace Platform;

class ConnectorMicrobizz {

    private $endpoint = '';
    private $contract = '';
    private $accesstoken = '';
    
    private $disable_callbacks = false;

    /**
     * Construct a new API connection
     * @param string $endpoint Endpoint to talk to
     * @param string $contract Contract number
     * @param string $accesstoken Access token
     */
    public function __construct($endpoint, $contract, $accesstoken) {
        $this->endpoint = $endpoint;
        $this->contract = $contract;
        $this->accesstoken = $accesstoken;
    }
    
    /**
     * Reports an error to a Microbizz update call and halts execution.
     * @param string $error_text
     */
    public static function answerFailure($error_text) {
        echo json_encode(array(
            'status' => 0,
            'error' => $error_text
        ));
        exit;
    }
    
    /**
     * Reports success to a Microbizz update call.
     */
    public static function answerSuccess() {
        echo json_encode(array(
            'status' => 1
        ));
    }
    
    /**
     * Build a permission request for Microbizz
     * @param string $modcode Microbizz module code
     * @param string $hook Microbizz hook to request
     * @param string $title Title to assign function in MB
     * @param string $url Url to request when using hook
     * @return array
     */
    public static function buildPermission($modcode, $hook, $title, $url) {
        Errorhandler::checkParams($modcode, 'string', $hook, 'string', $title, 'string', $url, 'string');
        $url .= (strpos($url,'?') !== false ? '&' : '?').'instance_id='.Instance::getActiveInstanceID();
        return array(
            'modcode' => $modcode,
            'hook' => $hook,
            'title' => $title,
            'url' => $url
        );
    }
    
    /**
     * Build a request for Microbizz
     * @param string $return_url The URL to return to.
     * @param array $permissions The permissions to request. Each of these can be build with buildPermission function
     * @return array The request
     */
    public static function buildRequest($return_url, $permissions = array()) {
        Errorhandler::checkParams($return_url, 'string', $permissions, 'array');
        global $platform_configuration;
        $request = array(
            'publicid' => $platform_configuration['microbizz_public_id'],
            'negotiateurl' => ($_SERVER['HTTPS'] ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].'/Platform/Connector/php/microbizz_negotiate.php?instance_id='.Instance::getActiveInstanceID().'&token='.Accesstoken::getSavedTokenCode().'&userid='.Accesstoken::getCurrentUserID(),
            'returnurl' => $return_url
        );
        foreach ($permissions as $permission) {
            $request['hooks'][] = $permission;
        }
        return $request;
    }
    
    /**
     * Set the disable callback option on subsequent API communication
     */
    public function disableCallbacks() {
        $this->disable_callbacks = true;
    }
    
    /**
     * Get a form for connecting with Microbizz (consisting only of a button)
     * @param array $request The connection request, which can be retrieved from buildRequest function
     * @param string $button_text The button text
     * @param boolean $connect_testserver Indicate if the Microbizz test environment should be used
     */
    public static function getConnectForm($request, $button_text = 'Link to Microbizz', $connect_testserver = false) {
        Errorhandler::checkParams($request, 'array', $button_text, 'string', $connect_testserver, 'boolean');
        $request_form = new \Platform\Form('microbizz_connect_form');
        $action = $connect_testserver ? 'https://dev2.microbizz.dk/appconnect/' : 'https://system15.microbizz.dk/appconnect/';
        $request_form->setAction($action);
        $request_form->addField(new \Platform\FieldHidden('', 'request', array('value' => json_encode($request))));
        if ($button_text) $request_form->addField(new \Platform\FieldSubmit($button_text, 'performlink'));
        return $request_form;
    }

    /**
     * Handle the return URL after connecting with Microbizz. Return an array consisting of endpoint, contract number
     * and accesstoken on success or false if an error occured.
     * @return array|boolean
     */
    public static function handleReturn() {
        $filename = \Platform\File::getFullFolderPath('temp').'microbizz_credentials_user_'.Accesstoken::getCurrentUserID();
        if (!file_exists($filename)) return false;
        $data = file($filename);
        if (count($data) <> 3) return false;
        return array(trim($data[0]), trim($data[1]), trim($data[2]));
    }
    

    /**
     * Activates an instance based on a call from Microbizz, and queues the
     * Microbizz CSS file
     */
    public static function prepareInstanceFromRequest() {
        // Switch to requested instance
        $instance = new \Platform\Instance();
        $instance->loadForRead($_GET['instance_id'] ?: $_SESSION['microbizz_stored_instance']);
        if (! $instance->isInDatabase()) die('Invalid instance.');
        $instance->activate();

        // Queue Microbizz design file
        Page::queueCSSFile('/Platform/Connector/css/microbizz.css');
    }
    
    /**
     * Query the Microbizz API
     * @param string $command Command to execute
     * @param array $parameters Parameters to the command
     * @return array Result array.
     */
    public function query($command, $parameters = array()) {
        Errorhandler::checkParams($command, 'string', $parameters, 'array');
        
        if (! $this->endpoint) return array('status' => false, 'error' => 'No endpoint defined');
        
        $commands = $parameters;
        $commands['command'] = $command;
        
        $request = array('contract' => $this->contract, 'accesstoken' => $this->accesstoken, 'commands' => array($commands));
        
        if ($this->disable_callbacks) $request['disable_callbacks'] = true;

        $ch = \curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);


        $data = array(
            'json' => json_encode($request)
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($output, true);
        if ($result === null) return array('status' => false, 'error' => 'Unexpected answer from API');
        
        if (! $result['status']) return array('status' => false, 'error' => $result['msg']);
        
        $result = $result['results'][0];
        if (! $result['status']) return array('status' => false, 'error' => $result['msg']);
        
        return array('status' => true, 'result' => $result);
    }
    
    /**
     * Solve a challenge from Microbizz.
     * @param string $challenge The challenge string
     * @return string The answer
     */
    public static function solveChallenge($challenge) {
        Errorhandler::checkParams($challenge, 'string');
        global $platform_configuration;
        return sha1($challenge.$platform_configuration['microbizz_secret_token']);
    }

    /**
     * Validates a session from Microbizz to ensure that it runs within a users space.
     * @return boolean Always returns true otherwise execution is halted.
     */
    public function validateSession() {
        if ($_GET['sessiontoken'] && $_SESSION['microbizz_validated_sessiontoken'] == $_GET['sessiontoken']) return true;
        $result = $this->query('ValidateSessionToken', array('sessiontoken' => $_GET['sessiontoken']));
        if (! $result['status'] || ! $result['result']) die('Could not validate session with Microbizz.');
        $_SESSION['microbizz_validated_sessiontoken'] = $_GET['sessiontoken'];
        $_SESSION['microbizz_stored_instance'] = $_GET['instance_id'];
        return true;
    }
    
}