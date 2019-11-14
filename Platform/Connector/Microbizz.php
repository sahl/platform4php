<?php
namespace Platform;

class ConnectorMicrobizz {
    
    /**
     * Build a permission request for Microbizz
     * @param string $modcode Microbizz module code
     * @param string $hook Microbizz hook to request
     * @param string $title Title to assign function in MB
     * @param string $url Url to request when using hook
     * @return array
     */
    public static function buildPermission($modcode, $hook, $title, $url) {
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
        global $platform_configuration;
        $request = array(
            'publicid' => $platform_configuration['microbizz_public_id'],
            'negotiateurl' => ($_SERVER['HTTPS'] ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].'/Platform/Connector/php/microbizz_negotiate.php?instance_id='.Instance::getActiveInstanceID().'&token='.Accesstoken::getSavedTokenCode(),
            'returnurl' => $return_url
        );
        foreach ($permissions as $permission) {
            $request['hooks'][] = $permission;
        }
        return $request;
    }
    
    /**
     * Handles a request from Microbizz, by selecting the right instance and validating the session token.
     * @return boolean Always returns true otherwise execution is halted.
     */
    public static function handleRequest() {
        // Switch to requested instance
        $instance = new \App\Instance();
        $instance->loadForRead($_GET['instance_id'] ?: $_SESSION['microbizz_stored_instance']);
        if (! $instance->isInDatabase()) die('Invalid instance.');
        $instance->activate();

        Design::queueCSSFile('/Platform/Connector/css/microbizz.css');
        
        if ($_SESSION['microbizz_validated_sessiontoken'] == $_GET['sessiontoken']) return true;
        $result = self::query('ValidateSessionToken', array('sessiontoken' => $_GET['sessiontoken']));
        if (! $result['status'] || ! $result['result']) die('Could not validate session with Microbizz.');
        $_SESSION['microbizz_validated_sessiontoken'] = $_GET['sessiontoken'];
        $_SESSION['microbizz_stored_instance'] = $_GET['instance_id'];
        return true;
    }
    
    /**
     * Get the currently stored access token for Microbizz
     * @return string|boolean The token or false if no token present
     */
    public static function getAccessToken() {
        return \Platform\UserProperty::getPropertyForUser(0, 'ConnectorMicrobizz', 'access_token') ?: false;
    }
    
    /**
     * Get the currently stored contract number for MB
     * @return string|boolean The contract number or false if no number present
     */
    public static function getContract() {
        return \Platform\UserProperty::getPropertyForUser(0, 'ConnectorMicrobizz', 'contract') ?: false;
    }
    
    /**
     * Get the currently stored endpoint for MB
     * @return string|boolean The endpoint URL or false if no number present
     */
    public static function getEndpoint() {
        return \Platform\UserProperty::getPropertyForUser(0, 'ConnectorMicrobizz', 'endpoint') ?: false;
    }

    /**
     * Set a new accesstoken for Microbizz
     * @param string $token The token or null to remove existing token.
     */
    public static function setAccessToken($token = null) {
        \Platform\UserProperty::setPropertyForUser(0, 'ConnectorMicrobizz', 'access_token', $token);
    }
    
    /**
     * Set a new contract for Microbizz
     * @param string $contract The contract ID or null to remove existing contract.
     */
    public static function setContract($contract = null) {
        \Platform\UserProperty::setPropertyForUser(0, 'ConnectorMicrobizz', 'contract', $contract);
    }

    /**
     * Set a new endpoint for Microbizz
     * @param string $endpoint The endpoint URL or null to remove existing URL.
     */
    public static function setEndpoint($endpoint = null) {
        \Platform\UserProperty::setPropertyForUser(0, 'ConnectorMicrobizz', 'endpoint', $endpoint);
    }
    
    /**
     * Solve a challenge from Microbizz.
     * @param string $challenge The challenge string
     * @return string The answer
     */
    public static function solveChallenge($challenge) {
        global $platform_configuration;
        return sha1($challenge.$platform_configuration['microbizz_secret_token']);
    }
    
    /**
     * Render a button for connecting to Microbizz
     * @param array $request The connection request, which can be retrieved from buildRequest function
     * @param string $button_text The button text
     * @param boolean $connect_testserver Indicate if the Microbizz test environment should be used
     */
    public static function renderConnectInterface($request, $button_text = 'Link to Microbizz', $connect_testserver = false) {
        $request_form = new \Platform\Form('microbizz_connect_form');
        $action = $connect_testserver ? 'https://test2.microbizz.dk/appconnect/' : 'https://system.microbizz.dk/appconnect/';
        $request_form->setAction($action);
        $request_form->addField(new \Platform\FieldHidden('', 'request', array('value' => json_encode($request))));
        $request_form->addField(new \Platform\FieldSubmit($button_text, 'performlink'));
        $request_form->render();
    }

    /**
     * Query the Microbizz API
     * @param string $command Command to execute
     * @param array $parameters Parameters to the command
     * @return array Result array.
     */
    public static function query($command, $parameters = array()) {
        $contract = self::getContract();
        $endpoint = self::getEndpoint();
        $access_token = self::getAccessToken();
        
        if (! $endpoint) return array('status' => false, 'error' => 'No endpoint defined');
        
        $commands = $parameters;
        $commands['command'] = $command;
        
        $request = array('contract' => $contract, 'accesstoken' => $access_token, 'commands' => array($commands));

        $ch = \curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);

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
    
}