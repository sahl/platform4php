<?php
namespace Platform\Datarecord;
/**
 * Type class for float number
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class FloatType extends IntegerType {

    public function getFormField() : \Platform\Form\Field {
        return \Platform\Form\NumberField::Field($this->title, $this->name);
        
    }
    
    public function getFieldForDatabase($value) : string {
        if ($value === null) return 'NULL';
        return (string)$value;
    }

    public function getSQLFieldType() : string {
        return 'DOUBLE';
    }
    
    public function parseDatabaseValue($value) {
        if ($value === null) return null;
        return (string)$value;
    }    
    
    public function validateValue($value) {
        if ($value !== null && !is_numeric($value)) return false;
        return true;
    }
    
}

