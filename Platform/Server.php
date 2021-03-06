<?php
namespace Platform;

use Platform\Utilities\Database;

class Server extends Datarecord {
    
    protected static $database_table = 'platform_servers';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $location = self::LOCATION_GLOBAL;
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;

    
    protected static $referring_classes = array(
        'Platform\\Server\\Instance',
        'Platform\\Server\\Job'
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
     * Ensure that the server table holds the current server.
     */
    public static function ensureThisServer() : int {
        if (! $_SERVER['HTTP_HOST']) return false;
        $filter = new Filter('Platform\\Server');
        $filter->addCondition(new ConditionMatch('hostname', $_SERVER['HTTP_HOST']));
        $server = $filter->executeAndGetFirst();
        if (! $server->isInDatabase()) {
            $server->setFromArray(array(
                'title' => $_SERVER['SERVER_NAME'],
                'hostname' => $_SERVER['HTTP_HOST'],
            ));
            $server->save();
        }
        return $server->server_id;
    }
    
    /**
     * Get the server with fewest instances.
     * @return \Platform\Server
     */
    public static function getLeastLoaded() : Server {
        $qr = Database::globalFastQuery("SELECT server_id, COUNT(*) AS antal FROM platform_servers LEFT JOIN platform_instances ON server_ref = server_id GROUP BY server_id ORDER BY antal");
        if (! $qr) trigger_error('No servers!', E_USER_ERROR);
        $server = new Server();
        $server->loadForRead($qr['server_id']);
        return $server;
    }
    
    /**
     * Get the ID of this server
     * @return int
     */
    public static function getThisServerID() : int {
        return Platform::getConfiguration('server_id');
    }
    
    public function isThisServer() : bool {
        return self::getThisServerID() == \Platform\Platform::getConfiguration('server_id');
    }
    /**
     * Send a message to this servers talk URL
     * @param array $message Key/value pair to send as message.
     * @return mixed Key/value pair as result
     */
    public function talk(array $message = array()) {
        $string = json_encode($message);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://".$this->hostname.Platform::getConfiguration('url_server_talk'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-length: '.mb_strlen($string)));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $string);

        $output = curl_exec($ch);
        
        curl_close($ch);
        
        $result = json_decode($output, true);
        if ($result === null) return false;
        return $result;
    }
    
}