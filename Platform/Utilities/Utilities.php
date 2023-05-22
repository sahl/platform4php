<?php
namespace Platform;

class Utilities {

    /**
     * Removes an element from an array 
     * @param array $array Array to remove element from
     * @param mixed $element Element to remove
     */
    public static function arrayRemove(array &$array, $element) {
        $array = array_diff($array, array($element));
    }
    
    /**
     * Safely read keys from an array
     * @param array $array The array to read from
     * @param string $key The keys to read separated by comma. So "test,name" would access $array['test']['name']
     * @return mixed The value or null of no value
     */
    public static function arraySafeRead(array &$array, string $key) {
        $keys = explode(',', $key);
        $this_key = array_shift($keys);
        if (count($keys) < 1) return array_key_exists($this_key, $array) ? $array[$this_key] : null;
        else return array_key_exists($this_key, $array) ? self::arraySafeRead($array[$this_key], implode(',',$keys)) : null;
    }
    
    /**
     * Translates a physical path to an URL. Only works when script is called from
     * web server
     * @param string $directory Directory to transform
     * @return string Corresponding URL
     */
    public static function directoryToURL(string $directory) {
        $url = substr($directory, strlen($_SERVER['DOCUMENT_ROOT']));
        if (substr($url,0,1) != '/') $url = '/'.$url;
        if (substr($url,-1) != '/') $url .= '/';
        return $url;
    }
}