<?php
namespace Platform;

interface DatarecordReferable {
    
    public function getRawValue($field);
    
    public static function getKeyField();
    

}