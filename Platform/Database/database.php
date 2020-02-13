<?php
namespace Platform;

class Database {
    
    /**
     * Used to carry the global database connection.
     * @var resource
     */
    private static $global_connection = false;
    
    /**
     * Used to carry the instance database connection.
     * @var resource
     */
    private static $instance_connection = false;
    
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
        self::$query_cache[] = array(
            'query' => $query,
            'time' => date('H:i:s', time()),
            'referer' => implode('<br>', Errorhandler::getFullCallStack())
        );
    }

    /**
     * Connect to the global database
     * @global array $platform_configuration Global configuration
     */
    public static function connectGlobal() {
        global $platform_configuration;
        if (self::$global_connection === false) {
            self::$global_connection = mysqli_connect($platform_configuration['global_database_server'], $platform_configuration['global_database_username'], $platform_configuration['global_database_password'], $platform_configuration['global_database_name']);
            if (! self::$global_connection) {
                trigger_error('Failed to connect to database '.mysqli_error(self::$global_connection), E_USER_ERROR);
            }
            mysqli_set_charset(self::$global_connection,"utf8mb4");        
        }
    }
    
    /**
     * Connect to the global database
     * @global array $platform_configuration Global configuration
     */
    public static function connectInstance() {
        global $platform_configuration;
        $instance = Instance::getActiveInstanceID();
        if (! $instance) trigger_error('Tried to connect to instance database without having an active instance.', E_USER_ERROR);
        if (self::$instance_connection === false) {
            self::$instance_connection = mysqli_connect($platform_configuration['local_database_server'], $platform_configuration['local_database_username'], $platform_configuration['local_database_password'], Instance::getActiveDatabaseName());
            if (! self::$instance_connection) {
                trigger_error('Failed to connect to database '.mysqli_error(self::$instance_connection), E_USER_ERROR);
            }
            self::$connected_instance = $instance;
            mysqli_set_charset(self::$instance_connection,"utf8mb4");        
        }
        if ($instance != self::$connected_instance) mysqli_select_db (self::$instance_connection, Instance::getActiveDatabaseName ());
    }

    /**
     * Enable the query cache to collect all queries executed.
     */
    public static function enableQueryCache() {
        self::$query_cache_enabled = true;
    }

    /**
     * Escapes a string, so it is safe for MySQL
     * @param string $string The string to escape.
     * @return string
     */
    public static function escape($string) {
        return mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x25\x27\x5C]', '\\\0', $string);
    }
    
    /**
     * Get a row from a result set
     * @param resource $result_set Result set
     * @return array Result row
     */
    public static function getRow($result_set) {
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
        if (self::$global_connection === false) {
            self::connectGlobal();
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
        return mysqli_affected_rows(self::$instance_connection);
    }
    
    /**
     * Get the last inserted key on the instance connection
     * @return int Inserted key
     */
    public static function instanceGetInsertedKey() {
        return mysqli_insert_id(self::$instance_connection);
    }
    
    /**
     * Do a query and return the first result set.
     * @param string $query The SQL query
     * @return boolean|array False if no results for query otherwise result row
     */
    public static function instanceFastQuery($query) {
        $result_set = self::instanceQuery($query);
        if ($result_set === false || $result_set === true) return false;
        $row = self::getRow($result_set);
        return $row ?: false;
    }
    
    /**
     * Query the global database
     * @param string $query SQL query to carry out
     * @param boolean $failonerror Set to true if a SQL error should trigger a php error.
     * @return boolean|resource Result set or false if an error occured.
     */
    public static function instanceQuery($query, $failonerror = true) {
        if (self::$instance_connection === false) {
            self::connectInstance();
        }
        $result_set = mysqli_query(self::$instance_connection, $query);
        if (self::$query_cache_enabled) self::cacheQuery($query);
        if ($result_set === false && $failonerror) trigger_error('Database error: '.mysqli_error(self::$instance_connection).' when executing '.$query, E_USER_ERROR);
        self::$instance_queries++;
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
    
}