<?php
namespace Platform;

interface DatarecordReferable {
    
    public function getRawValue($field);
    
    public static function getKeyField();
    
    public function loadForRead($id);

    public function isInDatabase();
    
    public function getTitle();
}