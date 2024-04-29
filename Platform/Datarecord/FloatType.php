<?php
namespace Platform\Datarecord;
/**
 * Type class for float number
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class FloatType extends IntegerType {

    protected function getBaseFormField() : ?\Platform\Form\Field {
        return \Platform\Form\NumberField::Field($this->title, $this->name, $this->getFormFieldOptions());
        
    }
    
    public function getFieldForDatabase($value) : string {
        if ($value === null || $value === '') return 'NULL';
        return (string)$value;
    }
    
    /**
     * Get a sorter for the Table component
     * @return array
     */
    public function getTableSorter() : array {
        return ['sorter' => 'number'];
    }

    public function getSQLFieldType() : string {
        return 'DOUBLE';
    }
    
    public function parseDatabaseValue($value) {
        if ($value === null) return null;
        return (float)$value;
    }    
    
    public function parseValue($value, $existing_value = null) {
        if ($value === null) return null;
        return (float)$value;
    }    

    public function validateValue($value) {
        if ($value !== null && !is_numeric($value)) return false;
        return true;
    }
    
}

