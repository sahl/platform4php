<?php
namespace Platform\Datarecord;
/**
 * Type class for integer number
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class IntegerType extends Type {

    public function filterGreaterEqual($value, $other_value) {
        return $value >= $other_value;
    }
    
    public function filterGreaterEqualSQL($value) {
        return '`'.$this->name.'` >= '.\Platform\Utilities\Database::escape($value);
    }
    
    public function filterGreater($value, $other_value) {
        return $value > $other_value;
    }
    
    public function filterGreaterSQL($value) {
        return '`'.$this->name.'` > '.\Platform\Utilities\Database::escape($value);
    }
    
    public function filterIsSet($value) {
        return $value !== null ? true : false;
    }
    
    public function filterIsSetSQL() {
        return '`'.$this->name.'` IS NOT NULL';
    }
    
    public function filterLike($value, $other_value) {
        return $this->filterMatch($value, $other_value);
    }
    
    public function filterLikeSQL($value) {
        return $this->filterMatchSQL($value);
    }
    
    public function filterLesserEqual($value, $other_value) {
        return $value <= $other_value;
    }
    
    public function filterLesserEqualSQL($value) {
        return '`'.$this->name.'` <= '.\Platform\Utilities\Database::escape($value);
    }
    
    public function filterLesser($value, $other_value) {
        return $value < $other_value;
    }
    
    public function filterLesserSQL($value) {
        return '`'.$this->name.'` < '.\Platform\Utilities\Database::escape($value);
    }
    
    public function filterMatch($value, $other_value) {
        return $value == $other_value;
    }
    
    public function filterMatchSQL($value) {
        return '`'.$this->name.'` = '.\Platform\Utilities\Database::escape($value);
    }
    
    public function filterOneOf($value, array $other_values) {
        return in_array($value, $other_values);
    }
    
    public function filterOneOfSQL(array $values) {
        if (! count($values)) return 'FALSE';
        $array = [];
        foreach ($values as $value) {
            $array[] = (int)\Platform\Utilities\Database::escape($value);
        }
        return '`'.$this->name.'` IN ('.implode(',',$array).')';
    }    

    public function getFieldForDatabase($value) : string {
        if ($value === null) return 'NULL';
        return (int)$value;
    }
    
    public function getFormField() : ?\Platform\Form\Field {
        if ($this->isReadonly() || $this->isInvisible()) return null;
        return \Platform\Form\NumberField::Field($this->title, $this->name, $this->getFormFieldOptions());
        
    }

    public function getLogValue($value) : string {
        return $value;
    }
    
    public function getRawValue($value) {
        return $value;
    }
    
    /**
     * Get a sorter for the Table component
     * @return array
     */
    public function getTableSorter() : array {
        return ['sorter' => 'number'];
    }
    
    public function getSQLFieldType() : string {
        return 'INT';
    }
    
    public function integrityCheck() : array {
        return [];
    }
    
    public function parseDatabaseValue($value) {
        if ($value === null) return null;
        return (int)$value;
    }    
    
    public function validateValue($value) {
        if ($value !== null && ! is_int($value)) return false;
        return true;
    }
    
}

