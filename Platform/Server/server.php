<?php
namespace Platform;

class Server extends Datarecord {
    
    protected static $database_table = 'platform_servers';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $location = self::LOCATION_GLOBAL;
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;

    
    protected static $referring_classes = array(
        'Platform\\Instance'
    );
    

    protected static function buildStructure() {
        static::addStructure(array(
            'server_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'title' => array(
                'label' => 'Server name',
                'is_title' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'hostname' => array(
                'label' => 'Hostname',
                'fieldtype' => self::FIELDTYPE_TEXT
            )
        ));
        parent::buildStructure();
    }
 
    /**
     * Ensure that the server table holds at least one server, and tries to
     * make it this server.
     */
    public static function ensureThisServer() {
        if (! count(static::getTitleAsArray())) {
            static::purge();
            $server = new Server(array(
                'title' => $_SERVER['SERVER_NAME'],
                'hostname' => $_SERVER['HTTP_HOST'],
            ));
            $server->save();
        }
    }
    
    /**
     * Get the server with fewest instances.
     * @return \Platform\Server
     */
    public static function getLeastLoaded() {
        $qr = Database::globalFastQuery("SELECT server_id, COUNT(*) AS antal FROM platform_servers LEFT JOIN platform_instances ON server_ref = server_id GROUP BY server_id ORDER BY antal");
        if (! $qr) trigger_error('No servers!', E_USER_ERROR);
        $server = new Server();
        $server->loadForRead($qr['server_id']);
        return $server;
    }
    
    /**
     * Get this server (based on host)
     * @return \Platform\Server
     */
    public static function getThisServer() {
        global $platform_configuration;
        $server = new Server();
        $server->loadForRead($platform_configuration['server_id']);
        return $server;
    }
    
    /**
     * Check if this is the current server
     * @return boolean
     */
    public function isThisServer() {
        global $platform_configuration;
        return $this->server_id = $platform_configuration['server_id'];
    }

    /**
     * Send a message to this servers talk URL
     * @param array $message Key/value pair to send as message.
     * @return mixed Key/value pair as result
     */
    public function talk($message = array()) {
        Errorhandler::checkParams($message, 'array');
        
        global $platform_configuration;
        
        $string = json_encode($message);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://".$this->hostname.$platform_configuration['url_server_talk']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-length: '.mb_strlen($string)));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $string);

        $output = curl_exec($ch);
        
        curl_close($ch);
        
        var_dump($output);
        
        $result = json_decode($output, true);
        if ($result === null) return false;
        return $result;
    }
    
}