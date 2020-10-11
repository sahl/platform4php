<?php
namespace Platform;

class Instance extends Datarecord {
    
    protected static $database_table = 'platform_instances';
    protected static $structure = false;
    protected static $key_field = false;
    protected static $location = self::LOCATION_GLOBAL;
    
    protected static $depending_classes = array(
        'Platform\Job'
    );

    protected static function buildStructure() {
        static::addStructure(array(
            'instance_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'title' => array(
                'label' => 'Instance title',
                'store_in_metadata' => false,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'server_ref' => array(
                'label' => 'Server',
                'fieldtype' => self::FIELDTYPE_REFERENCE_SINGLE,
                'foreign_class' => '\\Platform\\Server'
            ),
            'is_initiated' => array(
                'label' => 'Is initiated',
                'fieldtype' => self::FIELDTYPE_BOOLEAN
            )
        ));
        parent::buildStructure();
    }
    
    /**
     * Activate this instance as the current instance
     */
    public function activate() {
        $_SESSION['platform']['activeinstance'] = $this->instance_id;
        $_SESSION['platform']['instancedatabase'] = $this->getDatabaseName();
        Database::useInstance();
    }
    
    /**
     * Create a database for this instance if not already initiated
     * @return boolean True if we initiated a new instance
     */
    private function createDatabase() {
        if ($this->is_initiated || ! $this->isInDatabase()) return false;
        $result = Database::localQuery("CREATE DATABASE ".$this->getDatabaseName()." CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        if (! $result) return false;
        $this->is_initiated = true;
        return true;
    }
    
    protected static function createInitialUser($username, $password) {
        Errorhandler::checkParams($username, 'string', $password, 'string');
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
     * @return boolean
     */
    public function delete($force_remove = false) {
        Errorhandler::checkParams($force_remove, 'boolean');
        $databasename = $this->getDatabaseName();
        $result = parent::delete($force_remove);
        if ($result && $this->is_initiated) {
            Database::localQuery("DROP DATABASE ".$databasename, false);
        }
        return $result;
    }
    
    public function ensureJobs() {
        // Delete token every 6th hour
        $job = Job::getJob('\\Platform\\Accesstoken', 'deleteExpiredTokens', 6*60);
        $job->save();
        // Delete temp files every 6th hour
        $job = Job::getJob('\\Platform\\File', 'deleteTempFiles', 6*60);
        $job->save();
    }
    
    /**
     * Get the active instance ID
     * @return int|boolean False if no active instance
     */
    public static function getActiveInstanceID() {
        return $_SESSION['platform']['activeinstance'] ?: false;
    }
    
    /**
     * Get the active instance database name
     * @return string|boolean Database name or false if no active instance
     */
    public static function getActiveDatabaseName() {
        return $_SESSION['platform']['instancedatabase'] ?: false;
    }

    /**
     * Get an instance by its title.
     * @param string $title Instance title
     * @return Instance|boolean Instance or false if no instance
     */
    public static function getByTitle($title) {
        Errorhandler::checkParams($title, 'string');
        $filter = new Filter(get_called_class());
        $filter->addCondition(new ConditionMatch('title', $title));
        return $filter->executeAndGetFirst();
    }

    /**
     * Get database name of this instance
     * @global array $platform_configuration
     * @return string
     */
    public function getDatabaseName() {
        global $platform_configuration;
        return $platform_configuration['instance_database_name'].$this->instance_id;
    }
    
    /**
     * Get all instance ids on a specific server.
     * @param int $server_id Server ID
     * @return array Instance ids on that server
     */
    public static function getIdsByServerId($server_id) {
        Errorhandler::checkParams($server_id, 'int');
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
    public static function getIdsOnThisServer() {
        $server = Server::getThisServer();
        if (! $server->isInDatabase()) trigger_error('Could not determine this server.', E_USER_ERROR);
        return self::getIdsByServerId($server->server_id);
    }
    
    /**
     * Get the title of this instance
     * @return string
     */
    public function getTitle() {
        return '<i>'.$this->title.'</i>';
    }
    
    /**
     * Initialize this instance.
     * @param string $title Instance title.
     * @param string $username User name of first user.
     * @param string $password Password of first user.
     * @param int $on_server ID of server to place instance on.
     * @return Instance|boolean The created instance or false
     */
    public static function initialize($title, $username, $password, $on_server = 0) {
        Errorhandler::checkParams($title, 'string', $username, 'string', $password, 'string', $on_server, 'int');
        if (! \Platform\Semaphore::wait('instance_initialize', 30, 20)) return false;
        // Check if name is valid
        if (self::getByTitle($title)->isInDatabase()) {
            \Platform\Semaphore::release('instance_initialize');
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
        
        static::createInitialUser($username, $password);
        
        \Platform\Semaphore::release('instance_initialize');
        return $instance;
    }    
    
    /**
     * Initialize the instance database. Override to initialize own objects
     */
    public function initializeDatabase() {
        \Platform\DatarecordExtensiblefield::ensureInDatabase();
        \Platform\Accesstoken::ensureInDatabase();
        \Platform\File::ensureInDatabase();
        \Platform\User::ensureInDatabase();
        \Platform\UserProperty::ensureInDatabase();
    }
    
    /**
     * Check if this is the active instance
     * @return boolean
     */
    public function isActive() {
        return $this->instance_id = self::getActiveInstanceID();
    }
    
    /**
     * Login to this instance
     * @param string $username Username to try
     * @param string $password Password to try
     * @param string $continue_url URL to go to if success
     * @return boolean
     */
    public function login($username, $password, $continue_url = '') {
        Errorhandler::checkParams($username, 'string', $password, 'string', $continue_url, 'string');
        if (mb_substr($continue_url,0,1) != '/') $continue_url = '/'.$continue_url;
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
            $continue_url .= ((mb_strpos($continue_url,'?') !== false) ? '&' : '?').'token_code='.$result['token_code'].'&instance_id='.$this->instance_id;
            header('location: https://'.$server->hostname.$continue_url);
            exit;
        }
        $this->activate();
        $result = static::tryLogin($username, $password);
        if ($result === false) return false;
        // Ensure database structures
        $this->initializeDatabase();
        // Ensure jobs
        $this->ensureJobs();
        Accesstoken::resumeLocation();
        header('location: '.$continue_url);
        exit;
    }
    
    /**
     * Login to the given instance
     * @param string $title Instance title
     * @param string $username Username to try
     * @param string $password Password to try
     * @param string $continue_url URL to go to if success
     * @return boolean
     */
    public function loginToInstance($title, $username, $password, $continue_url = '') {
        Errorhandler::checkParams($title, 'string', $username, 'string', $password, 'string', $continue_url, 'string');
        $instance = Instance::getByTitle($title);
        if (! $instance->isInDatabase()) return false;
        $instance->activate();
        return $instance->login($username, $password, $continue_url);
    }
    
    /**
     * Save the instance initializing it if not already initialized
     * @param boolean $force_save Set true to always save object
     * @param boolean $keep_open_for_write Set to true to keep object open for write after saving
     * @return boolean True if we actually saved the object
     */
    public function save($force_save = false, $keep_open_for_write = false) {
        Errorhandler::checkParams($force_save, 'boolean', $keep_open_for_write, 'boolean');
        parent::save($force_save, $keep_open_for_write);
        if (! $this->is_initiated) {
            if ($this->createDatabase()) parent::save($force_save);
        }
    }

    /**
     * Try a login
     * @param string $username
     * @param string $password
     * @return mixed Accesstoken on success, otherwise false.
     */
    public function tryLogin($username, $password) {
        Errorhandler::checkParams($username, 'string', $password, 'string');
        return User::tryLogin($username, $password);
    }
}