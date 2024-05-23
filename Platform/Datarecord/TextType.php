<?php
namespace Platform\Datarecord;
/**
 * Type class for limited text
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class TextType extends Type {
    
    protected $default_value = '';
    
    public function getTextValue($value, Collection &$collection = null): string {
        return htmlentities((string)$value);
    }
    
    public function getFullValue($value, Collection &$collection = null): string {
        return htmlentities((string)$value);
    }

    public function getSQLFieldType() : string {
        return 'VARCHAR(255) NOT NULL';
    }
    
}

