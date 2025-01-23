<?php
namespace Platform\Utilities;
/**
 * Misc collection of utility functions
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=database_class
 */

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
     * Condense a long text from "A VERY LONG LONG LONG TEXT" to "A VERY...EXT"
     * @param string $long_text Original text
     * @param int $lead Number of leading characters to keep
     * @param int $trail Number of trailing characters to keep
     * @return string
     */
    public static function condenseLongText(string $long_text, int $lead = 100, int $trail = 20) : string {
        if (mb_strlen($long_text) <= $lead+$trail) return $long_text;
        return mb_substr($long_text,0,$lead).'...'.mb_substr($long_text,-$trail);
    }

    /**
     * Surround all links in the text with a-tags making them clickable.
     * @param string $text Text with links
     * @return string HTML text with clickable links
     */
    public static function linksToHTML(string $text) : string {
        return preg_replace('/(https?:\/\/[^\s]+)/i', '<a href="$1" target="_blank">$1</a>', $text);        
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
    
    private static $colour_map = [
        'black'     => 'white',
        'white'     => 'black',
    ];
    
    /**
     * Get a contrast colour to the given colour, which will be either white or black
     * @param string $colour Colour value
     * @return string Contrast colour value
     */
    public static function getContrastColour(string $colour) : string {
        if (array_key_exists($colour, static::$colour_map)) return static::$colour_map[$colour];
        if (preg_match('/\\#?([0-9a-fA-F][0-9a-fA-F])([0-9a-fA-F][0-9a-fA-F])([0-9a-fA-F][0-9a-fA-F])/', $colour, $match)) {
            $r = hexdec($match[1]);
            $g = hexdec($match[2]);
            $b = hexdec($match[3]);
            return ($r*3 + $g*5 + $b*2)/10 < 130 ? 'white' : 'black';
        }
        return 'inherit';
    }
    
    /**
     * Get a uniform class name with class path by removing leading slashes or double slashes
     * So \\Platform\\Datarecord would become Platform\Datarecord
     * @param string $class_name
     * @return string
     */
    public static function getProperClassName(string $class_name) : string {
        $result = str_replace("\\\\", "\\", $class_name);
        return substr($result,0,1) == '\\' ? substr($result,1) : $result;
    }
    
    /**
     * Remove all HTML from a string, both tags and html entities
     * @param string $string
     * @return string
     */
    public static function unHTML(string $string) : string {
        return html_entity_decode(strip_tags($string));
    }
}