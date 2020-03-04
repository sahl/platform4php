<?php
namespace Platform;

class Utility {

    /**
     * Removes an element from an array 
     * @param array $array Array to remove element from
     * @param mixed $element Element to remove
     */
    public static function arrayRemove(&$array, $element) {
        \Platform\Errorhandler::checkParams($array, 'array');
        $array = array_diff($array, array($element));
    }
}