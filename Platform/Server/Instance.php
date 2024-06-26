<?php
namespace Platform\Server;
/**
 * Datarecord class for managing instances.
 * 
 * Also responsible for building and destroying instances.
 * 
 * Also responsible for providing database and file store information to the instance.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=instance_class
 */

use Platform\Filter\Filter;
use Platform\Platform;
use Platform\Security\User;
use Platform\Server\Server;
use Platform\Utilities\Database;
use Platform\Utilities\Semaphore;
use Platform\Utilities\Translation;

class Instance extends \Platform\Datarecord\Datarecord {
    
    protected static $database_table = 'platform_instances';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $title_field = false;
    protected static $location = self::LOCATION_GLOBAL;
    
    protected static $obsolete_instance_tables = [];
    
    protected static $depending_classes = array(
        'Platform\\Server\\Job'
    );

    protected static function buildStructure() {
        static::addStructure([
            new \Platform\Datarecord\KeyType('instance_id'),
            new \Platform\Datarecord\TextType('title', Translation::translateForUser('Instance title'), ['is_required' => true, 'is_title' => true]),
            new \Platform\Datarecord\SingleReferenceType('server_ref', Translation::translateForUser('Server'), ['is_required' => true, 'is_readonly' => true, 'foreign_class' => 'Platform\Server\Server']),
            new \Platform\Datarecord\BoolType('is_initiated', Translation::translateForUser('Is initiated'), ['is_invisible' => true])
        ]);
        parent::buildStructure();
    }
    
    /**
     * Activate this instance as the current instance
     */
    public function activate() {
        // Check if initiated
        if (! $this->is_initiated) trigger_error('This instance is not initiated yet!'. E_USER_ERROR);
        // Check if we are on the right server
        if ($this->server_ref != Server::getThisServerID()) trigger_error('You cannot activate this instance on this server!', E_USER_ERROR);
        $_SESSION['platform']['activeinstance'] = $this->instance_id;
        $_SESSION['platform']['instancedatabase'] = $this->getDatabaseName();
        Database::useInstance();
    }
    
    /**
     * Create a database for this instance if not already initiated
     * @return bool True if we initiated a new instance
     */
    private function createDatabase() : bool {
        if ($this->is_initiated || ! $this->isInDatabase()) return false;
        $result = Database::localQuery("CREATE DATABASE ".$this->getDatabaseName()." CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", false);
        if (! $result) return false;
        $this->is_initiated = true;
        return true;
    }
    
    protected static function createInitialUser(string $username, string $password) {
        // Create initial user
        $user = new User();
        $user->username = $username;
        $user->password = $password;
        $user->save();
    }
    
    /**
     * Deactivate this instance as the current instance
     */
    public static function deactivate() {
        unset($_SESSION['platform']['activeinstance']);
        unset($_SESSION['platform']['instancedatabase']);
    }
    
    /**
     * Delete this instance
     * @return bool
     */
    public function delete(bool $force_remove = false) : bool {
        $server = new Server();
        $server->loadForRead($this->server_ref);
        
        if (! $server->isThisServer()) {
            // Request deletion on remote server
            $request = array(
                'event' => 'delete_instance',
                'instance_id' => $this->instance_id,
                'class' => get_called_class(),
            );
            $result = $server->talk($request);
            if ($result === false || ! $result['status']) return false;
            return true;
        }
        
        $databasename = $this->getDatabaseName();
        $filepath = $this->getFilePath();
        $result = parent::delete($force_remove);
        if ($result && $this->is_initiated) {
            Database::localQuery("DROP DATABASE ".$databasename, false);
            if (strlen($filepath) > 10) exec('rm -R '.$filepath);
        }
        return $result;
    }
    
    public function ensureJobs() {
        // Delete token every 6th hour
        $job = Job::getJob('Platform\Security\Accesstoken', 'deleteExpiredTokens', 6*60);
        $job->save();
        // Delete temp files every 6th hour
        $job = Job::getJob('Platform\File\File', 'deleteTempFiles', 6*60);
        $job->save();
        // Clean log files every day
        $job = Job::getJob('Platform\Utilities\Log', 'jobCleanPlatformLogFilesFromInstance', Job::FREQUENCY_SETTIME);
        if (! $job->isInDatabase()) {
            $job->next_start = \Platform\Utilities\Time::today()->add(0,30);
            $job->save();
        }
    }
    
    /**
     * Get the active instance ID
     * @return int|bool False if no active instance
     */
    public static function getActiveInstanceID() {
        return $_SESSION['platform']['activeinstance'] ?: false;
    }
    
    /**
     * Get the active instance database name
     * @return string|bool Database name or false if no active instance
     */
    public static function getActiveDatabaseName() {
        return $_SESSION['platform']['instancedatabase'] ?: false;
    }

    /**
     * Get an instance by its title.
     * @param string $title Instance title
     * @return Instance|bool Instance or false if no instance
     */
    public static function getByTitle(string $title) {
        $filter = new Filter(get_called_class());
        $filter->addCondition(new \Platform\Filter\ConditionMatch('title', $title));
        return $filter->executeAndGetFirst();
    }

    /**
     * Get database name of this instance
     * @return string
     */
    public function getDatabaseName() {
        return Platform::getConfiguration('instance_database_name').$this->instance_id;
    }
    
    /**
     * Get the file path to the storage of this instance
     * @return string
     */
    public function getFilePath() {
        $folder = Platform::getConfiguration('dir_store');
        if (! substr($folder,-1) == '/') $folder .= '/';
        $folder .= $this->instance_id.'/';
        return $folder;
    }
    
    /**
     * Get all instance ids on a specific server.
     * @param int $server_id Server ID
     * @return array Instance ids on that server
     */
    public static function getIdsByServerId(int $server_id) : array {
        $result = array();
        $qh = Database::globalQuery("SELECT instance_id FROM platform_instances WHERE server_ref = ".$server_id);
        while ($qr = Database::getRow($qh)) {
            $result[] = $qr['instance_id'];
        }
        return $result;
    }
    
    /**
     * Get all instance ids on this server
     * @return array Instance ids on this server
     */
    public static function getIdsOnThisServer() : array {
        $server_id = Server::getThisServerID();
        return self::getIdsByServerId($server_id);
    }
    
    /**
     * Get the server object this instance is present on.
     * @return Server
     */
    public function getServer() : Server {
        $server = new Server();
        $server->loadForRead((int)$this->server_ref, false);
        return $server;
    }
    
    /**
     * Get the title of this instance
     * @return string
     */
    public function getTitle() : string {
        return '<i>'.$this->title.'</i>';
    }
    
    /**
     * Initialize this instance.
     * @param string $title Instance title.
     * @param string $username User name of first user.
     * @param string $password Password of first user.
     * @param int $on_server ID of server to place instance on.
     * @return Instance|bool The created instance or false
     */
    public static function initialize(string $title, string $username, string $password, int $on_server = 0) {
        if (! Semaphore::wait('instance_initialize', 30, 20)) return false;
        // Check if name is valid
        if (self::getByTitle($title)->isInDatabase()) {
            Semaphore::release('instance_initialize');
            return false;
        }
        // Check if we know the server
        if (! $on_server) {
            $server = Server::getLeastLoaded();
        } else {
            $server = new Server();
            $server->loadForRead($on_server);
        }
        if (! $server->isThisServer()) {
            // Request creation on remote server
            $request = array(
                'event' => 'create_instance',
                'class' => get_called_class(),
                'title' => $title,
                'username' => $username,
                'password' => $password
            );
            $result = $server->talk($request);
            if ($result === false || ! $result['status']) return false;
            $cls = get_called_class();
            $instance = new $cls();
            $instance->loadForRead($result['instance_id']);
            return $instance;
        }
        // Create and initialize instance
        $cls = get_called_class();
        $instance = new $cls();
        $instance->title = $title;
        $instance->server_ref = $server->server_id;
        $result = $instance->createDatabase();
        if (! $result) return false;
        $instance->is_initiated = true;
        $instance->save(false, true);
        
        $instance->activate();
        $instance->initializeDatabase();
        $instance->ensureJobs();
        
        static::createInitialUser($username, $password);
        
        Semaphore::release('instance_initialize');
        return $instance;
    }    
    
    /**
     * Initialize the instance database and ensure obsolete objects are purged. Extend to initialize own objects
     */
    public function initializeDatabase() {
        static::purgeUnsusedTables();
        \Platform\Security\Accesstoken::ensureInDatabase();
        \Platform\File\File::ensureInDatabase();
        \Platform\Utilities\Mail::ensureInDatabase();
        \Platform\Datarecord\ExtensibleField::ensureInDatabase();
        \Platform\Security\User::ensureInDatabase();
        \Platform\Security\Property::ensureInDatabase();
        \Platform\Currency\Rate::ensureInDatabase();
    }
    
    /**
     * Check if this is the active instance
     * @return bool
     */
    public function isActive() : bool {
        return $this->instance_id == self::getActiveInstanceID();
    }
    
    /**
     * Login to this instance
     * @param string $username Username to try
     * @param string $password Password to try
     * @return bool|array array with keys server=server to go to, token_code=token code to use, instance_id=ID of instance
     */
    public function login(string $username, string $password) {
        $server = new Server();
        $server->loadForRead($this->server_ref);
        if (! $server->isThisServer()) {
            // Request login on remote server
            $request = array(
                'event' => 'login',
                'class' => get_called_class(),
                'instance_id' => $this->instance_id,
                'username' => $username,
                'password' => $password
            );
            $result = $server->talk($request);
            if ($result === false || ! $result['status']) return false;
            return array('hostname' => $server->hostname, 'token_code' => $result['token_code'], 'instance_id' => $this->instance_id);
        }
        $this->activate();
        $result = $this->userLogin($username, $password);
        if ($result === false) return false;
        return array('hostname' => $server->hostname, 'token_code' => $result->token_code, 'instance_id' => $this->instance_id);
    }
    
    /**
     * Login to this instance and continue
     * @param string $username User name to use for login
     * @param string $password Password to use for login
     * @param string $continue_url URL to continue to, if logged in
     * @return bool false if invalid login. Otherwise the user is redirected to the continue-URL.
     */
    public function loginAndContinue(string $username, string $password, string $continue_url = '') {
        if (mb_substr($continue_url,0,1) != '/') $continue_url = '/'.$continue_url;
        $result = $this->login($username, $password);
        if ($result === false) return false;
        $continue_url .= ((mb_strpos($continue_url,'?') !== false) ? '&' : '?').'token_code='.$result['token_code'].'&instance_id='.$this->instance_id;
        header('location: https://'.$result['hostname'].$continue_url);
        exit;
    }
    
    /**
     * Login to the given instance
     * @param string $title Instance title
     * @param string $username Username to try
     * @param string $password Password to try
     * @param string $continue_url URL to go to if success
     * @return bool
     */
    public function loginToInstanceAndContinue(string $title, string $username, string $password, string $continue_url = '') {
        $instance = Instance::getByTitle($title);
        if (! $instance->isInDatabase()) return false;
        $instance->activate();
        return $instance->loginAndContinue($username, $password, $continue_url);
    }
    
    /**
     * Purge all unused tables from the instance
     */
    public static function purgeUnsusedTables() {
        if (! count(static::$obsolete_instance_tables)) return;
        $result = Database::instanceQuery("SHOW TABLES");
        while ($row = Database::getRow($result)) {
            $table = current($row);
            if (in_array($table, static::$obsolete_instance_tables)) Database::instanceQuery("DROP TABLE ".$table);
        }
    }
    
    /**
     * Try login as a user
     * @param string $username User name
     * @param string $password Password
     * @return bool True if success
     */
    public function userLogin(string $username, string $password) {
        return User::tryLogin($username, $password);
    }
}