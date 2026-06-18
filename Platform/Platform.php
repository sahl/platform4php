<?php
namespace Platform;

use Microbizz\Security\Property;
use Platform\Server\Instance;
/**
 * Class for reading and setting Platform configuration options
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=platform_class
 */

class Platform {
    
    private static $overwritable_configuration = ['dir_log', 'dir_temp', 'dir_store', 'mail_type', 'smtp_server', 'smtp_port', 'smtp_username', 'smtp_password'];
    
    public static function getConfigFileName() : string {
        $root = $_SERVER['DOCUMENT_ROOT'];
        // Strip trailing slash (if any)
        if (substr($root,-1) == '/') $root = substr($root,0,-1);
        // Go one dir up
        $parent_dir = substr($root, 0, strrpos($root,'/'));
        return $parent_dir.'/platform_config.php';
    }
    
    /**
     * Get a parameter from the global configuration
     * @global array $platform_configuration Global configuration storage
     * @param string $key Key to retrieve
     * @return mixed
     */
    public static function getConfiguration(string $key) {
        global $platform_configuration;
        // Check if the configuration is overridden for active instance
        if (Instance::getActiveInstanceID() !== false && in_array($key, static::$overwritable_configuration)) {
            $value = Property::getForAll('platform_configuration_override', $key);
            if ($value !== null) return $value;
        }
        return array_key_exists($key, $platform_configuration) ? $platform_configuration[$key] : null;
    }
    
    /**
     * Override a configuration key in the active instance.
     * @param string $key Configuration key to override
     * @param mixed $value Value to set (or null to return to default)
     */
    public static function overrideConfigurationForActiveInstance(string $key, mixed $value) {
        if (Instance::getActiveInstanceID() === false) trigger_error('You need an active instance to override configuration.', E_USER_ERROR);
        if (! in_array($key, static::$overwritable_configuration)) trigger_error('You are not allowed to override '.$key, E_USER_ERROR);
        Property::setForAll('platform_configuration_override', $key, $value);
    }
    
    /**
     * Get the server root
     * @return string
     */
    public static function getServerRoot() : string {
        return $_SERVER['DOCUMENT_ROOT'];
    }
    
    /**
     * Set a parameter in the global configuration (in memory)
     * @global array $platform_configuration Global configuration storage
     * @param string $key Key
     * @param mixed $value Value
     */
    public static function setConfiguration(string $key, $value) {
        global $platform_configuration;
        $platform_configuration[$key] = $value;
    }
    
    /**
     * Set several parameters in the global configuration (in memory) from an array
     * @global array $platform_configuration Global configuration storage
     * @param array $array An array with values hashed by their keys
     * @param bool $clear If true, then clear the array before setting values
     */
    public static function setConfigurationFromArray(array $array, bool $clear = false) {
        global $platform_configuration;
        if ($clear || ! is_array($platform_configuration)) $platform_configuration = array();
        foreach ($array as $key => $value) {
            self::setConfiguration($key, $value);
        }
    }
    
    /**
     * Normalize a class name removing leading \ and replacing \\ with \
     * @param string $class
     */
    public static function normalizeClass(string &$class) {
        if (substr($class,0,1) == "\\") $class = substr($class,1);
        $class = str_replace("\\\\", "\\", $class);
    }
    
    /**
     * Write the information in memory to the config file
     * @global array $platform_configuration Global configuration storage
     * @return bool True if success
     */
    public static function writeConfigurationFile() : bool {
        global $platform_configuration;
        // Ensure some configuration
        if (! is_array($platform_configuration)) $platform_configuration = array();

        // Try to write configuration to file
        $fh = fopen(self::getConfigFileName(), 'w');
        if (! $fh) return false;
        fwrite($fh, "<?php\n\$platform_configuration = array(\n");
        foreach ($platform_configuration as $key => $value) {
            if (is_array($value)) {
                $final_value = [];
                foreach ($value as $v) $final_value[] = "'".str_replace("'", "\\'", $v)."'";
                fwrite($fh, "\t'$key' => [".implode(',', $final_value)."],\n");
            } else {
                fwrite($fh, "\t'$key' => '".str_replace("'", "\\'", $value)."',\n");
            }
        }
        fwrite($fh, ");\n");
        fflush($fh);
        fclose($fh);
        return true;
    }
    
}
