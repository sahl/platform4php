<?php
namespace Platform;

class Database {
    
    /**
     * Used to carry the global database connection.
     * @var resource
     */
    private static $global_connection = false;
    
    /**
     * Used to carry the local database connection.
     * @var resource
     */
    private static $local_connection = false;
    
    /**
     * Id of connected instance.
     * @var int 
     */
    private static $connected_instance = false;
    /**
     * Performance variables.
     * @var int
     */
    private static $total_queries = 0, $global_queries = 0, $instance_queries = 0;
    
    /**
     * Query cache if enabled.
     * @var array 
     */
    private static $query_cache = array();
    
    /**
     * Indicate if query cache is enabled.
     * @var boolean 
     */
    private static $query_cache_enabled = false;
    
    /**
     * Caches a query
     * @param string $query Query to cache
     */
    private static function cacheQuery($query) {
        Errorhandler::checkParams($query, 'string');
        self::$query_cache[] = array(
            'query' => $query,
            'time' => date('H:i:s', time()),
            'referer' => implode('<br>', Errorhandler::getFullCallStack())
        );
    }

    /**
     * Connect to the global database
     * @global array $platform_configuration Global configuration
     * @return boolean True if connected
     */
    public static function connectGlobal() {
        global $platform_configuration;
        if (self::$global_connection === false) {
            self::$global_connection = @mysqli_connect($platform_configuration['global_database_server'], $platform_configuration['global_database_username'], $platform_configuration['global_database_password']);
            if (! self::$global_connection) return false;
            mysqli_set_charset(self::$global_connection,"utf8mb4");        
        }
        return true;
    }
    
    /**
     * Connect to the global database
     * @global array $platform_configuration Global configuration
     */
    public static function connectLocal() {
        global $platform_configuration;
        if (self::$local_connection === false) {
            self::$local_connection = @mysqli_connect($platform_configuration['local_database_server'], $platform_configuration['local_database_username'], $platform_configuration['local_database_password']);
            if (! self::$local_connection) return false;
        }
        return true;
    }

    /**
     * Enable the query cache to collect all queries executed.
     */
    public static function enableQueryCache() {
        self::$query_cache_enabled = true;
    }
    
    /**
     * Ensure that the global database exists and is created.
     * @return boolean True if database exists or was created.
     */
    public static function ensureGlobalDatabase() {
        global $platform_configuration;
        return self::globalQuery("CREATE DATABASE IF NOT EXISTS ".$platform_configuration['global_database_name'], false) !== false;
    }

    /**
     * Escapes a string, so it is safe for MySQL
     * @param string $string The string to escape.
     * @return string
     */
    public static function escape($string) {
        Errorhandler::checkParams($string, 'string');
        return mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x25\x27\x5C]', '\\\0', $string);
    }
    
    /**
     * Get the last error message on the global connection
     * @return string
     */
    public static function getLastGlobalError() {
        return mysqli_error(self::$global_connection);
    }
    
    /**
     * Get the last error message on the local connection
     * @return string
     */
    public static function getLastLocalError() {
        return mysqli_error(self::$local_connection);
    }
    
    /**
     * Get a row from a result set
     * @param resource $result_set Result set
     * @return array Result row
     */
    public static function getRow($result_set) {
        Errorhandler::checkParams($result_set, array('mysqli_result', 'boolean'));
        return mysqli_fetch_assoc($result_set);
    }
    
    /**
     * Get the number of rows affected by the last global query
     * @return int Number of rows affected
     */
    public static function globalAffected() {
        return mysqli_affected_rows(self::$global_connection);
    }
    
    /**
     * Get the last inserted key on the global connection
     * @return int Inserted key
     */
    public static function globalGetInsertedKey() {
        return mysqli_insert_id(self::$global_connection);
    }
    
    /**
     * Do a query and return the first result set.
     * @param string $query The SQL query
     * @return boolean|array False if no results for query otherwise result row
     */
    public static function globalFastQuery($query) {
        Errorhandler::checkParams($query, 'string');
        $result_set = self::globalQuery($query);
        if ($result_set === false || $result_set === true) return false;
        $row = self::getRow($result_set);
        return $row ?: false;
    }
    
    /**
     * Query the global database
     * @param string $query SQL query to carry out
     * @param boolean $fail_on_error Set to true if a SQL error should trigger a php error.
     * @return boolean|resource Result set or false if an error occured.
     */
    public static function globalQuery($query, $fail_on_error = true) {
        Errorhandler::checkParams($query, 'string', $fail_on_error, 'boolean');
        if (self::$global_connection === false) {
            $result = self::connectGlobal();
            if (! $result) trigger_error('Could not connect to global database. Error: '.mysqli_error (self::$global_connection), E_USER_ERROR);
            self::useGlobal();
        }
        $resultset = mysqli_query(self::$global_connection, $query);
        if (self::$query_cache_enabled) self::cacheQuery($query);
        if ($resultset === false && $fail_on_error) trigger_error('Database error: '.mysqli_error(self::$global_connection).' when executing '.$query, E_USER_ERROR);
        self::$global_queries++;
        self::$total_queries++;
        return $resultset;
    }
    
    /**
     * Get the number of rows affected by the last instance query
     * @return int Number of rows affected
     */
    public static function instanceAffected() {
        return mysqli_affected_rows(self::$local_connection);
    }
    
    /**
     * Get the last inserted key on the instance connection
     * @return int Inserted key
     */
    public static function instanceGetInsertedKey() {
        return mysqli_insert_id(self::$local_connection);
    }
    
    /**
     * Do a query and return the first result set.
     * @param string $query The SQL query
     * @return boolean|array False if no results for query otherwise result row
     */
    public static function instanceFastQuery($query) {
        Errorhandler::checkParams($query, 'string');
        $result_set = self::instanceQuery($query);
        if ($result_set === false || $result_set === true) return false;
        $row = self::getRow($result_set);
        return $row ?: false;
    }
    
    /**
     * Query the instance database
     * @param string $query SQL query to carry out
     * @param boolean $fail_on_error Set to true if a SQL error should trigger a php error.
     * @return boolean|resource Result set or false if an error occured.
     */
    public static function instanceQuery($query, $fail_on_error = true) {
        Errorhandler::checkParams($query, 'string', $fail_on_error, 'boolean');
        if (self::$local_connection === false) {
            $result = self::connectLocal();
            if (! $result) trigger_error('Could not connect to local database. Error: '.mysqli_error (self::$global_connection), E_USER_ERROR);
            self::useInstance();
        }
        $result_set = mysqli_query(self::$local_connection, $query);
        if (self::$query_cache_enabled) self::cacheQuery($query);
        if ($result_set === false && $fail_on_error) trigger_error('Database error: '.mysqli_error(self::$local_connection).' when executing '.$query, E_USER_ERROR);
        self::$instance_queries++;
        self::$total_queries++;
        return $result_set;
    }
    
    /**
     * Query the local database
     * @param string $query SQL query to carry out
     * @param boolean $fail_on_error Set to true if a SQL error should trigger a php error.
     * @return boolean|resource Result set or false if an error occured.
     */
    public static function localQuery($query, $fail_on_error = true) {
        Errorhandler::checkParams($query, 'string', $fail_on_error, 'boolean');
        if (self::$local_connection === false) {
            $result = self::connectLocal();
            if (! $result) trigger_error('Could not connect to local database. Error: '.mysqli_error (self::$global_connection), E_USER_ERROR);
        }
        $result_set = mysqli_query(self::$local_connection, $query);
        if (self::$query_cache_enabled) self::cacheQuery($query);
        if ($result_set === false && $fail_on_error) trigger_error('Database error: '.mysqli_error(self::$local_connection).' when executing '.$query, E_USER_ERROR);
        self::$total_queries++;
        return $result_set;
    }

    /**
     * Render the query cache to screen.
     */
    public static function renderQueryCache() {
        echo '<table>';
        foreach (self::$query_cache as $query) {
            echo '<tr><td>'.$query['time'].'</td><td style="font-size: 0.7em; border-bottom: 1px solid black;">'.$query['referer'].'</td><td>'.$query['query'].'</td></tr>';
        }
        echo '</table>';
        echo '<p><b>Total queries: </b>'.count(self::$query_cache);
    }
    
    /**
     * Render query cache stats
     */
    public static function renderStats() {
        echo '<div><b>Total:</b> '.self::$total_queries.'</div>';
    }

    /**
     * Use the global database on the global connection.
     * @global array $platform_configuration Platform configuration
     */
    public static function useGlobal() {
        global $platform_configuration;
        if (self::$global_connection === false) {
            $result = self::connectGlobal();
            if (! $result) trigger_error('Failed to connect to global database '.mysqli_error(self::$global_connection), E_USER_ERROR);
        }
        $result = mysqli_select_db(self::$global_connection, $platform_configuration['global_database_name']);
        if (! $result) trigger_error('Failed to use to global database '.mysqli_error(self::$global_connection), E_USER_ERROR);
    }

    /**
     * Use the active instance database on the local connection. This will fail
     * if an instance isn't activated yet.
     */
    public static function useInstance() {
        $instance = Instance::getActiveInstanceID();
        if (! $instance) trigger_error('Tried to use instance database without having an active instance.', E_USER_ERROR);
        if ($instance == self::$connected_instance) return;
        if (self::$local_connection === false) self::connectLocal();
        $result = mysqli_select_db(self::$local_connection, Instance::getActiveDatabaseName());
        if (! $result) trigger_error('Failed to use to instance database '.mysqli_error(self::$local_connection), E_USER_ERROR);
        self::$connected_instance = $instance;
        return true;
    }
    
}