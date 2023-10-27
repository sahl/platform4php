<?php
namespace Platform;
/**
 * An interface to make other objects referable like Datarecord objects.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=datarecordreferable_interface
 */

interface DatarecordReferable {
    
    public function getRawValue(string $field);
    
    public function getTextValue(string $field);
    
    public function getFullValue(string $field);
    
    public static function getKeyField();
    
    public function getKeyValue();
    
    public function loadForRead(int $id);
    
    public function loadForWrite(int $id);
    
    public function reloadForWrite();
    
    public function delete(bool $force);

    public function isInDatabase();
    
    public function getTitle();
}