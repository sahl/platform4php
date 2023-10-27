<?php
namespace Platform\Server;
/**
 * Datarecord class for registering servers.
 * 
 * Also responsible for building appropriate structures on server.
 * 
 * Also responsible for inter-server communication
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=server_class
 */

use Platform\ConditionMatch;
use Platform\Datarecord;
use Platform\Filter;
use Platform\Platform;
use Platform\Server\Instance;
use Platform\Server\Job;
use Platform\Utilities\Database;
use Platform\Utilities\Time;

class Server extends Datarecord {
    
    protected static $database_table = 'platform_servers';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $location = self::LOCATION_GLOBAL;
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;

    protected static $obsolete_global_tables = [];
    
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
     * Ensure that all global objects are built and obsolete objects are removed
     */
    public static function ensureGlobalObjects() {
        static::purgeUnsusedTables();
        Database::useGlobal();
        Server::ensureInDatabase();
        Instance::ensureInDatabase();
        Job::ensureInDatabase();
    }
    
    public function ensureJobs() {
        // Clean log files every day
        $job = Job::getServerJobForServer($this, '\\Platform\\Utilities\\Log', 'jobCleanPlatformLogFilesFromServer', Job::FREQUENCY_SETTIME);
        if (! $job->isInDatabase()) {
            $job->next_start = Time::today()->add(0,30);
            $job->save();
        }
    }
 
    /**
     * Ensure that the server table holds the current server.
     */
    public static function ensureThisServer() : int {
        if (! $_SERVER['HTTP_HOST']) return false;
        $filter = new Filter('Platform\\Server\\Server');
        $filter->addCondition(new ConditionMatch('hostname', $_SERVER['HTTP_HOST']));
        $server = $filter->executeAndGetFirst();
        if (! $server->isInDatabase()) {
            $server->setFromArray(array(
                'title' => $_SERVER['SERVER_NAME'],
                'hostname' => $_SERVER['HTTP_HOST'],
            ));
            $server->save();
        }
        $server->ensureJobs();
        return $server->server_id;
    }
    
    /**
     * Get the server with fewest instances.
     * @return Server
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
        return self::getThisServerID() == $this->server_id;
    }
    
    /**
     * Purge all unused tables from the global database
     */
    public static function purgeUnsusedTables() {
        if (! count(static::$obsolete_global_tables)) return;
        $result = Database::globalQuery("SHOW TABLES");
        while ($row = Database::getRow($result)) {
            $table = current($row);
            if (in_array($table, static::$obsolete_global_tables)) Database::globalQuery("DROP TABLE ".$table);
        }
    }
    
    /**
     * Send a message to this servers talk URL
     * @param array $message Key/value pair to send as message.
     * @return mixed Key/value pair as result
     */
    public function talk(array $message = array()) {
        $string = json_encode($message);

        $ch = curl_init();
        
        $url_servertalk = Platform::getConfiguration('url_server_talk') ?: '/Platform/Server/php/server_talk.php';
        
        curl_setopt($ch, CURLOPT_URL, "https://".$this->hostname.$url_servertalk);
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