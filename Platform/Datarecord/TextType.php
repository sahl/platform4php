<?php
namespace Platform\Datarecord;
/**
 * Type class for limited text
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class TextType extends Type {
    
    public function getTextValue($value, Collection &$collection = null): string {
        return htmlentities($value);
    }
    
    public function getFullValue($value, Collection &$collection = null): string {
        return htmlentities($value);
    }

    public function getSQLFieldType() : string {
        return 'VARCHAR(1000) NOT NULL';
    }
    
}

