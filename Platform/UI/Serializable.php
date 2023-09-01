<?php
namespace Platform\UI;

interface Serializable {
    
    public function getSerialized() : array;
    
    public static function constructFromSerialized(array $data) : Serializable;
    
}