<?php
namespace Platform\UI;
/**
 * Interface for making complex objects available as Component properties
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=serializable_interface
 */

interface Serializable {
    
    public function getSerialized() : array;
    
    public static function constructFromSerialized(array $data) : Serializable;
    
}