<?php
namespace Platform\Datarecord;
/**
 * Type class for boolean
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class BoolType extends IntegerType {
    
    /**
     * Get SQL to determine if a field of this type is greater or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterEqualSQL($value) {
        return '`'.$this->name.'` >= '.((int)$value);
    }
    
    /**
     * Filter if a value is greater than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterGreater($value, $other_value) {
        return $value > $other_value;
    }
    
    /**
     * Get SQL to determine if a field of this type is greater than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterSQL($value) {
        return '`'.$this->name.'` > '.((int)$value);
    }
    
    /**
     * Filter if a value is like another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLike($value, $other_value) {
        return $this->filterMatch($value, $other_value);
    }
    
    /**
     * Get SQL to determine if a field of this type is like another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLikeSQL($value) {
        return $this->filterMatchSQL($value);
    }
    
    /**
     * Get SQL to determine if a field of this type is lesser or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserEqualSQL($value) {
        return '`'.$this->name.'` <= '.((int)$value);
    }
    
    /**
     * Get SQL to determine if a field of this type is lesser than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserSQL($value) {
        return '`'.$this->name.'` < '.((int)$value);
    }
    
    /**
     * Filter if a value matches another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterMatch($value, $other_value) {
        return $value == $other_value;
    }
    
    /**
     * Get SQL to determine if a field of this type matches another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterMatchSQL($value) {
        return '`'.$this->name.'` = '.((int)$value);
    }
    
    /**
     * Filter if a value is one of an array of other values
     * @param mixed $value Value of this
     * @param array $other_values Other values
     * @return bool
     */
    public function filterOneOf($value, array $other_values) {
        return in_array($value, $other_values);
    }
    
    /**
     * Get SQL to determine if a field of this type is part of some values
     * @param mixed $values Other values
     * @return bool
     */
    public function filterOneOfSQL(array $values) {
        if (! count($values)) return 'FALSE';
        $array = [];
        foreach ($values as $value) {
            $array[] = '\''.\Platform\Utilities\Database::escape($value).'\'';
        }
        return '`'.$this->name.'` IN ('.implode(',',$array).')';
    }    
    

    public function filterIsSet($value) {
        return $value;
    }
    
    public function filterIsSetSQL() {
        return '`'.$this->name.'` = TRUE';
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
        return 'TINYINT(1) NOT NULL';
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
    
    /**
     * Validate if this is a valid value for fields of this type
     * @param mixed $value
     * @return mixed True if no problem or otherwise a string explaining the problem
     */
    public function validateValue($value) {
        return $value === 1 || $value === 0 || $value === true || $value === false;
    }
}

