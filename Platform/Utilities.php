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
}