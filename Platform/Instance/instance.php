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
                'foreignclass' => '\\Platform\\Server'
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
    }
    
    /**
     * Create a database for this instance if not already initiated
     * @return boolean True if we initiated a new instance
     */
    private function createDatabase() {
        if ($this->is_initiated || ! $this->isInDatabase()) return false;
        $result = Database::globalQuery("CREATE DATABASE ".$this->getDatabaseName()." CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        if (! $result) return false;
        $this->is_initiated = true;
        return true;
    }
    
    protected static function createInitialUser($username, $password) {
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
        $databasename = $this->getDatabaseName();
        $result = parent::delete($force_remove);
        if ($result && $this->is_initiated) {
            Database::globalQuery("DROP DATABASE ".$this->getDatabaseName(), false);
        }
        return $result;
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
        $filter = new Filter('\\App\\Instance');
        $filter->addCondition(new FilterConditionMatch('title', $title));
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
        if (! \Platform\Semaphore::wait('instance_initialize', 30, 20)) return false;
        // Check if name is valid
        if (self::getByTitle($title) !== false) {
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
    
    public function login($username, $password, $continue_url = '') {
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
        $result = static::tryLogin($username, $password);
        if ($result === false) return false;
        // Ensure database structures
        $this->initializeDatabase();
        Accesstoken::resumeLocation();
        header('location: '.$continue_url);
        exit;
    }
    
    public function loginToInstance($title, $username, $password, $continue_url = '') {
        $instance = Instance::getByTitle($title);
        if ($instance === false) return false;
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
        parent::save($force_save, $keep_open_for_write);
        if (! $this->is_initiated) {
            if ($this->createDatabase()) parent::save($forcesave);
        }
    }

    /**
     * Try a login
     * @param string $username
     * @param string $password
     * @return mixed Accesstoken on success, otherwise false.
     */
    public function tryLogin($username, $password) {
        return User::tryLogin($username, $password);
    }
}