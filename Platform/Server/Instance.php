<?php
namespace Platform\Server;

use Platform\Filter;
use Platform\Platform;
use Platform\Server;
use Platform\User;
use Platform\Utilities\Semaphore;
use Platform\Utilities\Database;

class Instance extends \Platform\Datarecord {
    
    protected static $database_table = 'platform_instances';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $title_field = false;
    protected static $location = self::LOCATION_GLOBAL;
    
    protected static $depending_classes = array(
        'Platform\\Server\\Job'
    );

    protected static function buildStructure() {
        static::addStructure(array(
            'instance_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'title' => array(
                'label' => 'Instance title',
                'required' => true,
                'is_title' => true,
                'store_in_metadata' => false,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'server_ref' => array(
                'label' => 'Server',
                'required' => true,
                'readonly' => true,
                'fieldtype' => self::FIELDTYPE_REFERENCE_SINGLE,
                'foreign_class' => '\\Platform\\Server'
            ),
            'is_initiated' => array(
                'label' => 'Is initiated',
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_BOOLEAN
            )
        ));
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
        $job = Job::getJob('\\Platform\\Security\\Accesstoken', 'deleteExpiredTokens', 6*60);
        $job->save();
        // Delete temp files every 6th hour
        $job = Job::getJob('\\Platform\\File', 'deleteTempFiles', 6*60);
        $job->save();
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
        $filter->addCondition(new \Platform\ConditionMatch('title', $title));
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
        $instance->save(false, true);
        
        $instance->activate();
        $instance->initializeDatabase();
        $instance->ensureJobs();
        
        static::createInitialUser($username, $password);
        
        Semaphore::release('instance_initialize');
        return $instance;
    }    
    
    /**
     * Initialize the instance database. Override to initialize own objects
     */
    public function initializeDatabase() {
        \Platform\Security\Accesstoken::ensureInDatabase();
        \Platform\File::ensureInDatabase();
        \Platform\Utilities\Mail::ensureInDatabase();
        \Platform\ExtensibleField::ensureInDatabase();
        \Platform\User::ensureInDatabase();
        \Platform\Property::ensureInDatabase();
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
     * Save the instance initializing it if not already initialized
     * @param bool $force_save Set true to always save object
     * @param bool $keep_open_for_write Set to true to keep object open for write after saving
     * @return bool True if we actually saved the object
     */
    public function save(bool $force_save = false, bool $keep_open_for_write = false) : bool {
        $result = parent::save($force_save, $keep_open_for_write);
        if (! $this->is_initiated) {
            if ($this->createDatabase()) parent::save($force_save);
        }
        return $result;
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