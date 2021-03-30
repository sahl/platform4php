<?php
namespace Platform;

interface DatarecordReferable {
    
    public function getRawValue(string $field);
    
    public static function getKeyField();
    
    public function loadForRead(int $id);

    public function isInDatabase();
    
    public function getTitle();
}