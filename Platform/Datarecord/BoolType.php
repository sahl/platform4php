<?php
namespace Platform\Datarecord;
/**
 * Type class for boolean
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class BoolType extends IntegerType {

    public function filterIsSet($value) {
        return $value;
    }
    
    public function filterIsSetSQL() {
        return $this->name.' = TRUE';
    }
    
    public function getFieldForDatabase($value) : string {
        return $value ? 'true' : 'false';
    }
    
    public function getFormField() : ?\Platform\Form\Field {
        if ($this->isReadonly() || $this->isInvisible()) return null;
        return \Platform\Form\CheckboxField::Field($this->title, $this->name, $this->getFormFieldOptions());
        
    }
    
    public function getFullValue($value, Collection &$collection = null) : string {
        return static::getTextValue($value);
    }
    
    public function getTextValue($value, Collection &$collection = null): string {
        return $value ? \Platform\Utilities\Translation::translateForUser('Yes') : \Platform\Utilities\Translation::translateForUser('No');
    }

    public function getLogValue($value) : string {
        return static::getTextValue($value);
    }
    
    public function getRawValue($value) {
        return $value ? true : false;
    }
    
    public function getSQLFieldType() : string {
        return 'BOOL';
    }
    
    public function integrityCheck() : array {
        return [];
    }
    
    public function parseDatabaseValue($value) {
        return (bool)$value;
    }
    
    public function parseValue($value, $existing_value = null) {
        return (bool)$value;
    }
    
    public function validateValue($value) {
        return true;
    }
    
}

