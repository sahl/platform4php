<?php
namespace Platform;

class Platform {
    
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
        return array_key_exists($key, $platform_configuration) ? $platform_configuration[$key] : null;
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
            fwrite($fh, "\t'$key' => '".str_replace("'", "\\'", $value)."',\n");
        }
        fwrite($fh, ");\n");
        fflush($fh);
        fclose($fh);
        return true;
    }
    
}
