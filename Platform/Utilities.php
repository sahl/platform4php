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