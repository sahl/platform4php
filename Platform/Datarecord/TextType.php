<?php
namespace Platform\Datarecord;
/**
 * Type class for limited text
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class TextType extends Type {
    
    protected $string_length = 255;
    
    protected $default_value = '';
    
    /**
     * Format a value for the database in accordance to this type
     * @param mixed $value
     * @return string
     */
    public function getFieldForDatabase($value) : string {
        if (mb_strlen($value) > $this->string_length) $value = mb_substr($value,0,$this->string_length);
        return parent::getFieldForDatabase($value);
    }    
    
    public function getTextValue($value, Collection &$collection = null): string {
        return (string)$value;
    }
    
    public function getFullValue($value, Collection &$collection = null): string {
        return htmlentities((string)$value);
    }

    public function getSQLFieldType() : string {
        return 'VARCHAR(255) NOT NULL';
    }
    
}

